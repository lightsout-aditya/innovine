<?php

namespace App\Services;

use App\Entity\EmailTemplate;
use App\Entity\Setting;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MessageService
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly MailerInterface $mailer,
        private readonly HttpClientInterface $client
    ){}

    public function sendMailTemplatePreview($templateName, $params = []): ?string
    {
            $em = $this->doctrine->getManager();
            $template = $em->getRepository(EmailTemplate::class)->findOneBy(['slug' => $templateName]);
            return $template->getFormattedBody($params);
    }

    public function sendMailTemplate($templateName, $params = [], $user = null, $emailTo = null, $options = []): void
    {
        try {
            $em = $this->doctrine->getManager();
            $setting = $em->getRepository(Setting::class)->findOneBy([]);
            /** @var EmailTemplate $template */
            $template = $em->getRepository(EmailTemplate::class)->findOneBy(['slug' => $templateName]);

            if($template){
                $count = 0;
                foreach ($template->getTo() as $t) {
                    if(!$count) {
                        if(filter_var($t, FILTER_VALIDATE_EMAIL)){
                            $emailTo = $t;
                        }elseif($t === 'USER' and $user instanceof User){
                            $emailTo = $user->getEmail();
                        }
                    }elseif(filter_var($t, FILTER_VALIDATE_EMAIL)){
                        $options['to'][] = $t;
                    }elseif ($t === 'USER' and $user instanceof User){
                        $options['to'][] = $user->getEmail();
                    }
                    $count++;
                }

                if($emailTo){
                    $subject = $template->getFormattedSubject($params);
                    $content = $template->getFormattedBody($params);
                    $email = (new TemplatedEmail())
                        ->from(new Address($_ENV['MAILER_DEFAULT_SENDER_ADDRESS'], $_ENV['MAILER_DEFAULT_SENDER_NAME']))
                        ->to($emailTo)
                        ->subject($subject)
                        ->htmlTemplate('mailer/layout.html.twig')
                        ->context(['content' => $content, 'setting' => $setting]);

                    if (isset($options['replyTo']) && $options['replyTo']) {
                        $email->addReplyTo($options['replyTo']);
                    }

                    if (isset($options['to']) && count((array)$options['to'])) {
                        foreach ((array) $options['to'] as $to) {
                            $email->addTo($to);
                        }
                    }

                    if($cc = $template->getCc()) {
                        foreach ($cc as $t) {
                            if (filter_var($t, FILTER_VALIDATE_EMAIL)) {
                                $options['cc'][] = $t;
                            } elseif ($t === 'USER' and $user instanceof User) {
                                $options['cc'][] = $user->getEmail();
                            }
                        }
                    }

                    if (isset($options['cc']) && count((array)$options['cc'])) {
                        foreach ((array) $options['cc'] as $cc) {
                            $email->addCc($cc);
                        }
                    }

                    if($bcc = $template->getBcc()) {
                        foreach ($bcc as $t) {
                            if (filter_var($t, FILTER_VALIDATE_EMAIL)) {
                                $options['bcc'][] = $t;
                            } elseif ($t === 'USER' and $user instanceof User) {
                                $options['bcc'][] = $user->getEmail();
                            }
                        }
                    }

                    if (isset($options['bcc']) && count((array)$options['bcc'])) {
                        foreach ((array) $options['bcc'] as $bcc) {
                            $email->addBcc($bcc);
                        }
                    }

                    if (isset($options['attachments']) && count($options['attachments'])) {
                        foreach ($options['attachments'] as $path) {
                            if(is_array($path) && isset($path['url'])){
                                $email->attach(fopen($path['url'], 'r'), $path['filename'], $path['filetype']);
                            }else{
                                $email->attachFromPath($path);
                            }
                        }
                    }

                    $this->mailer->send($email);
                }
            }
        }catch (TransportExceptionInterface $e){
        }
    }

    public function sendMail($to, $subject, $content, $options = []): void
    {
        try {
            $em = $this->doctrine->getManager();
            $setting = $em->getRepository(Setting::class)->findOneBy([]);
            $email = (new TemplatedEmail())
                ->from(new Address($_ENV['MAILER_DEFAULT_SENDER_ADDRESS'], $_ENV['MAILER_DEFAULT_SENDER_NAME']))
                ->to($to)
                ->subject($subject)
                ->htmlTemplate('mailer/layout.html.twig')
                ->context(['content' => $content, 'setting' => $setting]);

            if (isset($options['replyTo']) && $options['replyTo']) {
                $email->addReplyTo($options['replyTo']);
            }

            if (isset($options['to']) && count((array)$options['to'])) {
                foreach ((array) $options['to'] as $to) {
                    $email->addTo($to);
                }
            }

            if (isset($options['cc']) && count((array)$options['cc'])) {
                foreach ((array) $options['cc'] as $cc) {
                    $email->addCc($cc);
                }
            }

            if (isset($options['bcc']) && count((array)$options['bcc'])) {
                foreach ((array) $options['bcc'] as $bcc) {
                    $email->addBcc($bcc);
                }
            }

            if (isset($options['attachments']) && count($options['attachments'])) {
                foreach ($options['attachments'] as $path) {
                    if(is_array($path) && isset($path['url'])){
                        $email->attach(fopen($path['url'], 'r'), $path['filename'], $path['filetype']);
                    }else{
                        $email->attachFromPath($path);
                    }
                }
            }

            $this->mailer->send($email);
        }catch (TransportExceptionInterface $e){

        }
    }

    public function sendSMS($mobile, $content, $header = 'INNOVI'): array
    {
        $success = false;
        $message = null;
        try {
            $text = $content;
            $url = "https://sms.cell24x7.com/otpReceiver/sendSMS";
            $response = $this->client->request('POST', $url, [
                'body' => [
                    'user' => $_ENV['SMS_UID'],
                    'pwd' => $_ENV['SMS_PASS'],
                    'sender' => $header,
                    'mobile' => $mobile,
                    'msg' => $text,
                    'mt' => 0,
                ],
            ]);
            $message = $response->getContent();
            if(stripos($message, $mobile) !== false){
                $success = true;
            }
        }catch (\Exception $exception){
            //var_dump($exception);
        }

        return ['success' => $success, 'message' => $message];
    }
}