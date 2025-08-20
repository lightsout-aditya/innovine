<?php

namespace App\EventListener;

use App\Entity\Address;
use App\Entity\Enquiry;
use App\Entity\Invoice;
use App\Entity\Package;
use App\Entity\SalesOrder;
use App\Entity\User;
use App\Services\MessageService;
use App\Services\ZohoService;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class EventListener
{
    public function __construct(
        private ParameterBagInterface $params,
        private MessageService $messageService,
        private UrlGeneratorInterface $urlGenerator,
        private ZohoService $zoho
    ){}

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $uploadDir = $this->params->get('upload_dir');
        $invoiceDir = $this->params->get('invoice_dir');
        $methods = ['getImage', 'getPdf'];
        foreach ($methods as $method) {
            if (is_callable([$entity, $method])) {
                if($entity->$method()) {
                    if ($entity instanceof Invoice) {
                        @unlink($invoiceDir . $entity->$method());
                    }elseif (is_callable([$entity, 'getPhotoBaseDir'])) {
                        @unlink($entity->getPhotoBaseDir($uploadDir) . DIRECTORY_SEPARATOR . $entity->$method());
                    }else{
                        @unlink($uploadDir . $entity->$method());
                    }
                }
            }
        }

        if ($entity instanceof Address) {
            if ($_ENV['ZOHO_SYNC'] and $entity->getZohoId() and $entity->isLifecycleCallback()){
                $response = $this->zoho->deleteAddress($entity->getUser()->getZohoId(), $entity->getZohoId());
                if (isset($response['code']) && !$response['code']) {
                    //throw new BadRequestHttpException($response['message']);
                }
            }
        }
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        //$entity = $args->getObject();
        //$em = $args->getObjectManager();
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        $em = $args->getObjectManager();
        if ($entity instanceof User and $entity->isLifecycleCallback()) {
            if ($_ENV['ZOHO_SYNC']){
                $this->syncZohoCustomer($args);
            }
            if (($_ENV['SIGNUP_EMAIL'] or $entity->isSendEmail()) and !$entity->isEmailVerified() and !$entity->isMobileVerified()) {
                $token = sha1(md5(time()) . uniqid() . $entity->getEmail());
                $entity->setToken($token);
                $notifyAdmin = false;
                if ($entity->isEnabled()) {
                    $entity->setTokenDate(date_create());
                    $resetLink = $this->urlGenerator->generate('resetPassword', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                    $templateName = 'on-new-account-setup';
                    $params = [
                        'NAME' => ucwords(strtolower($entity->getName())),
                        'EMAIL' => strtolower($entity->getEmail()),
                        'RESET_URL' => $resetLink,
                    ];
                    $this->messageService->sendMailTemplate($templateName, $params, $entity);
                } else {
                    $notifyAdmin = true;
                    $confirmationLink = $this->urlGenerator->generate('verifyEmail', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                    $templateName = 'on-signup';
                    $params = [
                        'NAME' => ucwords(strtolower($entity->getName())),
                        'EMAIL' => strtolower($entity->getEmail()),
                        'VERIFY_URL' => $confirmationLink,
                    ];
                }
                $em->persist($entity);
                $em->flush();
                $this->messageService->sendMailTemplate($templateName, $params, $entity);
                if ($notifyAdmin) {
                    $templateName = 'on-signup-admin-alert';
                    $params = [
                        'NAME' => ucwords(strtolower($entity->getName())),
                        'EMAIL' => strtolower($entity->getEmail()),
                        'MOBILE' => $entity->getMobile(),
                    ];
                    $this->messageService->sendMailTemplate($templateName, $params);
                }
            }
        }elseif ($entity instanceof Address and $entity->isLifecycleCallback()) {
            if ($_ENV['ZOHO_SYNC']){
                $this->syncZohoAddress($args);
            }
        }elseif ($entity instanceof Enquiry){
            $this->sendNewEnquiry($args);
        }elseif ($entity instanceof SalesOrder){
            $templateName = 'on-new-sales-order-admin-alert';
            $params = [
                'ORDER_NUMBER' => $entity->getOrder()->getOrderNumber(),
                'SALES_ORDER_NUMBER' => $entity->getSoNumber(),
                'CUSTOMER_NAME' => $entity->getOrder()->getCustomer(),
                'SHIPPING_ADDRESS' => $entity->getOrder()->getShipAddress(),
            ];
            $this->messageService->sendMailTemplate($templateName, $params);
        }
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof User and $entity->isLifecycleCallback()) {
            if ($_ENV['ZOHO_SYNC']){
                $this->syncZohoCustomer($args);
            }
        }elseif ($entity instanceof Address and $entity->isLifecycleCallback()) {
            if ($_ENV['ZOHO_SYNC']){
                $this->syncZohoAddress($args);
            }
        }
    }

    private function syncZohoCustomer($args): void
    {
        /** @var User $user */
        $user = $args->getObject();
        $em = $args->getObjectManager();
        $params = [
            "contact_type" => "customer",
            "contact_persons" => [
                [
                    "first_name" => $user->getFirstName(),
                    "last_name" => $user->getLastName(),
                    "email" => $user->getEmail(),
                    "mobile" => $user->getMobile(),
                    "is_primary_contact" => true
                ]
            ],
            "contact_name" => $user->getName(),
            "company_name" => $user->getCompany()??"",
            "pan_no" => $user->getPan()??"",
            "gst_no" => $user->getGstNumber()??"",
            "gst_treatment" => $user->getGstNumber() ? 'business_gst' : 'consumer',
        ];

        $response = $user->getZohoId() ? $this->zoho->updateCustomer($user->getZohoId(), $params) : $this->zoho->createCustomer($params);
        //dd($params, $response);
        if($customer = $response['contact']??null) {
            $user->setZohoId($customer['contact_id']);
            $user->setGstTreatment($customer['gst_treatment']);
            $user->setImported(true);
            $em->persist($user);
            $em->flush();
        }
    }

    private function syncZohoAddress($args): void
    {
        $address = $args->getObject();
        $em = $args->getObjectManager();
        $params = [
            "attention" => $address->getName(),
            "address" => $address->getAddress(),
            "street2" => $address->getStreet(),
            "city" => $address->getCity(),
            "state" => $address->getState(),
            "state_code" => $address->getStateCodeGST(),
            "country" => "India",
            "country_code" => "IN",
            "zip" => $address->getPincode(),
            "phone" => $address->getPhone(),
        ];
        $response = $address->getZohoId() ? $this->zoho->updateAddress($address->getUser()->getZohoId(), $address->getZohoId(), $params) : $this->zoho->createAddress($address->getUser()->getZohoId(), $params);
        //dd($address->getUser()->getZohoId(), $address->getZohoId(), $params, $response);
        if($contactAddress = $response['address_info']??null) {
            $address->setZohoId($contactAddress['address_id']);
            $em->persist($address);
            $em->flush();
        }
    }

    private function sendNewEnquiry($args): void
    {
        $entity = $args->getObject();;
        $templateName = 'on-new-enquiry';
        $params = [
            'NAME' => $entity->getName(),
            'EMAIL' => $entity->getEmail(),
            'MOBILE' => $entity->getMobile(),
            'SUBJECT' => $entity->getSubject(),
            'MESSAGE' => $entity->getMessage(),
        ];
        $this->messageService->sendMailTemplate($templateName, $params);
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
        foreach ([$uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates()] as $entities) {
            foreach ($entities as $entity) {
                /*if ($_ENV['ZOHO_SYNC'] and $entity instanceof Item and $entity->getZohoId()) {
                    $changeSet = $uow->getEntityChangeSet($entity);
                    if(array_key_exists('group', $changeSet)){
                        if($itemGroup = $changeSet['group'][1]){
                            $params = [
                                "group_name" => $itemGroup->getName(),
                                "items" => [
                                    [
                                        "item_id" => $entity->getZohoId(),
                                        "attribute_name1" => $entity->getSize() ? 'Size' : null,
                                        "attribute_option_name1" => $entity->getSize(),
                                    ]
                                ] ,
                            ];
                            $this->zoho->addToItemGroup($itemGroup->getZohoId(), $params);
                        }elseif($changeSet['group'][0]){
                            $this->zoho->unlinkItemGroup($entity->getZohoId());
                        }
                    }
                }*/

                if ($entity instanceof Package) {
                    $changeSet = $uow->getEntityChangeSet($entity);
                    if(array_key_exists('trackingNumber', $changeSet)) {
                        if ($changeSet['trackingNumber'][1]) {
                            $entity->setStatus(Package::SHIPPED);
                            $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(get_class($entity)), $entity);
                            $em->persist($entity);
                        }
                    }
                }
            }
        }
    }
}