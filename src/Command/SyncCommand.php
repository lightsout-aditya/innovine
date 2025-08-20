<?php

namespace App\Command;

use App\Entity\Transaction;
use App\Services\OrderService;
use Doctrine\Persistence\ManagerRegistry;
use Razorpay\Api\Api;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

//php bin/console IS:Sync transaction
//php bin/console IS:Sync transaction --date 2025-02-25

//0 * * * * cd /web/innovine && php bin/console IS:Sync transaction --env=prod >> var/log/transaction_`date '+\%m-\%d-\%Y'`.log

#[AsCommand(
    name: 'IS:Sync',
    description: 'Sync Transaction',
)]
class SyncCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly OrderService $orderService
    ){parent::__construct();}

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Action: subscription | transaction')
            ->addOption('date', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        set_time_limit(0);
        $action = $input->getArgument('action');
        $io = new SymfonyStyle($input, $output);
        switch ($action) {
            case 'transaction':
                $date = $input->getOption('date');
                $this->transaction($io, $date);
                break;
            default:
                $io->error("Invalid Action '{$action}'");
                break;
        }

        return Command::SUCCESS;
    }

    protected function transaction($io, $date = null): void
    {
        $io->write("Date: " . date('Y-m-d H:i:s'));
        $io->newLine();
        $timestamp = date_create($date ?? date('Y-m-d H:i:s', strtotime('-2 hour')));
        $em = $this->doctrine->getManager();
        $transactions = $em->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->where('t.createdAt >= :timestamp')
            ->andWhere("t.offline = false AND IFNULL(t.status, '') != 'captured' AND t.order IS NULL")
            ->setParameter('timestamp', $timestamp)
            ->getQuery()
            ->getResult();
        $io->write(count($transactions) . " transactions found\n\n");

        $api = new Api($_ENV['RAZORPAY_KEY_ID'], $_ENV['RAZORPAY_KEY_SECRET']);
        /** @var Transaction $tran */
        foreach ($transactions as $tran) {
            if ($tran->getRazorpayOrderId()) {
                $io->write("OrderId: {$tran->getRazorpayOrderId()}\n");
                $transaction = null;
                try {
                    $payments = $api->order->fetch($tran->getRazorpayOrderId())->payments();
                    //dd($payments);
                    if ($payments and is_array($payments['items'])) {
                        //$transaction = $payments['items'][array_key_last($payments['items'])];
                        foreach ($payments['items'] as $transaction) {
                            if($transaction->status === 'captured') {
                                break;
                            }
                        }
                    }
                } catch (\Exception $exception) {
                    $io->error($exception->getMessage());
                }

                if ($transaction) {
                    $io->write("{$tran->getId()}: {$transaction->id}: {$tran->getStatus()} : {$transaction->status}\n\n");
                    $tran->setRazorpayPaymentId($transaction->id);
                    $tran->setRazorpaySignature('admin-sync');
                    $tran->setPaymentMode($transaction->method ?? null);
                    $tran->setFee((isset($transaction->fee) and $transaction->fee) ? ($transaction->fee/100) : 0);
                    $tran->setTax((isset($transaction->tax) and $transaction->tax) ? ($transaction->tax/100) : 0);
                    $tran->setTransactionDate(date_create("@{$transaction->created_at}")->setTimezone(new \DateTimeZone(date_default_timezone_get())));
                    $tran->setCurrency($transaction->currency);
                    $tran->setStatus($transaction->status);
                    $em->persist($tran);
                    $em->flush();

                    if($transaction->status === 'captured'){
                        try {
                            $order = $this->orderService->createOrder($tran);
                            $em->refresh($order);
                            $this->orderService->generateSalesOrder($order);
                        } catch (\Exception $exception) {
                            $io->error($exception->getMessage());
                        }
                    }
                }else{
                    $io->write("No Payments Found\n\n");
                }
            }
        }
    }
}