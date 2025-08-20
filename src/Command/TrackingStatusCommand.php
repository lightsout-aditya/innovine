<?php

namespace App\Command;

use App\Entity\Order;
use App\Entity\Package;
use App\Services\BlueDartService;
use App\Services\SafexService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

//php bin/console IS:tracking updateStatus
#[AsCommand(
    name: 'IS:tracking',
    description: 'SaFex and BlueDart tracking status update Command',
)]
class TrackingStatusCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly SafexService $saFex,
        private readonly BlueDartService $blueDartService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Action: updateStatus');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        set_time_limit(0);
        $action = $input->getArgument('action');
        $io = new SymfonyStyle($input, $output);
        switch ($action) {
            case 'updateStatus':
                $this->updateStatus($io);
                break;
            default:
                $io->error("Invalid Action '{$action}'");
                break;
        }

        return Command::SUCCESS;
    }

    protected function updateStatus($io): void
    {
        $io->write("Date: " . date('Y-m-d H:i:s'));
        $io->newLine();
        $em = $this->doctrine->getManager();
        $connection = $this->doctrine->getConnection();
        $sql = "SELECT tracking_number FROM `package` WHERE `tracking_number` IS NOT NULL AND `status` >= 2 AND delivered = 0";
        $trackingNumbers = $connection->fetchFirstColumn($sql);
        $io->write(count($trackingNumbers)." Shipment Found\n");
        foreach ($trackingNumbers as $trackingNumber){
            $package = $em->getRepository(Package::class)->findOneBy(['trackingNumber' => $trackingNumber]);
            if ($package and $package->getCarrier() === 'SaFex'){
                $response = $this->saFex->getTrackingStatus($trackingNumber);
                if ($response['shipment']['result']??null === 'success'){
                    $status = $response['shipment']['status']??null;
                    if (str_contains(strtolower($status), 'delivered')){
                        $package->setStatus(Package::DELIVERED);
                        $package->setDelivered(true);
                        if ($deliveryDate = $response['shipment']['deliveryDate']??null) {
                            $formattedDeliveryDate = \DateTime::createFromFormat('d-M-Y', $deliveryDate);
                            $package->setDeliveryDate($formattedDeliveryDate);
                        }
                        $allDelivered = true;
                        $order = $package->getSalesOrder()->getOrder();
                        if(count($order->getSalesOrders()) > 1){
                            foreach ($order->getSalesOrders() as $so){
                                foreach ($so->getPackages() as $pac) {
                                    if ($pac->getStatus() !== Package::DELIVERED) {
                                        $allDelivered = false;
                                        break;
                                    }
                                }
                            }
                        }
                        if($allDelivered){
                            $order->setStatus(Order::COMPLETED);
                        }
                    }elseif (str_contains(strtolower($status), 'out for delivery')){
                        $package->setStatus(Package::OUT_FOR_DELIVERY);
                    }
                    $em->persist($package);
                    $em->flush();
                    $io->write("{$package->getSalesOrder()->getOrder()} : {$package->getPackageNumber()} : {$trackingNumber} - {$status}\n");
                }
            }elseif ($package->getCarrier() === 'BlueDart'){
                $response = $this->blueDartService->getTrackingStatus($trackingNumber);
                $shipment = $response['ShipmentData']['Shipment'][0];
                if ($shipment){
                    $status = $shipment['Status'];
                    if (str_contains(strtolower($status), 'delivered')){
                        $package->setStatus(Package::DELIVERED);
                        $package->setDelivered(true);
                        if ($deliveryDate = $shipment['StatusDate'].' '.$shipment['StatusTime']??null) {
                            $formattedDeliveryDate = \DateTime::createFromFormat('d F Y H:i', $deliveryDate);
                            $package->setDeliveryDate($formattedDeliveryDate);
                        }
                        $allDelivered = true;
                        $order = $package->getSalesOrder()->getOrder();
                        if(count($order->getSalesOrders()) > 1){
                            foreach ($order->getSalesOrders() as $so){
                                foreach ($so->getPackages() as $pac) {
                                    if ($pac->getStatus() !== Package::DELIVERED) {
                                        $allDelivered = false;
                                        break;
                                    }
                                }
                            }
                        }
                        if($allDelivered){
                            $order->setStatus(Order::COMPLETED);
                        }
                    }elseif (str_contains(strtolower($status), 'out for delivery')){
                        $package->setStatus(Package::OUT_FOR_DELIVERY);
                    }
                    $em->persist($package);
                    $em->flush();
                    $io->write("{$package->getSalesOrder()->getOrder()} : {$package->getPackageNumber()} : {$trackingNumber} - {$status}\n");
                }
            }
            //echo "<pre> {$trackingNumber}"; print_r($response);
        }
    }
}
