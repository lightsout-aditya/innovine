<?php

namespace App\Services;

use App\Entity\Setting;
use Doctrine\Persistence\ManagerRegistry;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[WithMonologChannel('zoho')]
final class ZohoService
{
    const OAUTH_URL = 'https://accounts.zoho.in/oauth/v2';
    const INVENTORY_URL = 'https://www.zohoapis.in/inventory/v1';
    private ?string $token = null;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
    ){}

    private function getToken(): void
    {
        $em = $this->doctrine->getManager();
        $setting = $em->getRepository(Setting::class)->findOneBy([]);
        if(!$setting->getZohoToken() or !$setting->getZohoTokenCreatedAt() or $setting->getZohoTokenCreatedAt() < date_create('57 minutes ago')){
            $setting->setZohoToken($this->refreshToken());
            $setting->setZohoTokenCreatedAt(date_create());
            $em->persist($setting);
            $em->flush();
        }
        $this->token = $setting->getZohoToken();
    }

    public function callZoho_($endpoint, $query = [], $json = null, $method = 'GET', $raw = false): array|string
    {
        try {
            $this->getToken();
            $response = $this->client->request($method, self::INVENTORY_URL . $endpoint,
                array_filter([
                    'headers' => [
                        "Authorization: Zoho-oauthtoken {$this->token}",
                    ],
                    'query' => array_merge([
                        'organization_id' => $_ENV['ZOHO_ORG_ID'],
                    ], $query),
                    'json' => $json,
                ])
            );
            return $raw ? $response->getContent() : $response->toArray();
        }catch (\Exception $exception){
            if($_ENV['APP_ENV'] === 'dev'){
                dd($exception);
                //$this->requestStack->getSession()->getFlashBag()->add('danger', 'Something went wrong');
            }
            return [];
        }
    }

    public function callZoho($endpoint, $query = [], $json = null, $method = 'GET', $raw = false)
    {
        usleep(500000); // 500,000 microseconds = 0.5 seconds
        $queryParams = array_merge([
            'organization_id' => $_ENV['ZOHO_ORG_ID'],
        ], $query);
        $url = self::INVENTORY_URL . $endpoint . '?' . http_build_query($queryParams);

        try {
            $this->getToken();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                "Authorization: Zoho-oauthtoken {$this->token}"
            ]);
            if($json) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
            }
            $response = curl_exec($ch);
            if ($response === false) {
                if($_ENV['APP_ENV'] === 'dev') {
                    dd(curl_error($ch));
                }
                return ['code' => -1, 'message' => 'CURL Error'];
            }
            curl_close($ch);
            if(!$raw){
                $response = json_decode($response, true);
                $this->logger->info(json_encode(['url' => $url, 'params' => $json, 'response' => $response]));
            }
            return $response;
        }catch (\Exception $exception){
            if($_ENV['APP_ENV'] === 'dev'){
                dd($exception);
                //$this->requestStack->getSession()->getFlashBag()->add('danger', 'Something went wrong');
            }
            $this->logger->info(json_encode(['url' => $url, 'params' => $json, 'exception' => $exception->getMessage()]));
            return ['code' => -1, 'message' => 'Something went wrong'];
        }
    }

    public function refreshToken(): ?string
    {
        $response = $this->client->request('POST', self::OAUTH_URL."/token", [
            'query' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $_ENV['ZOHO_REFRESH_TOKEN'],
                'client_id' => $_ENV['ZOHO_CLIENT_ID'],
                'client_secret' => $_ENV['ZOHO_CLIENT_SECRET'],
            ],
        ]);
        $decodedPayload = $response->toArray();
        return $decodedPayload['access_token']??null;
    }

    public function getItemGroup($page = 1)
    {
        $endpoint = "/itemgroups";
        $query = [
            'page' => $page,
            'per_page' => 200,
        ];
        return $this->callZoho($endpoint, $query);
    }

    public function getItems($page = 1)
    {
        $endpoint = "/items";
        $query = [
            'page' => $page,
            'per_page' => 200,
        ];
        return $this->callZoho($endpoint, $query);
    }

    public function getWarehouses($page = 1)
    {
        $endpoint = "/settings/warehouses";
        $query = [
            'page' => $page,
            'per_page' => 200,
        ];
        return $this->callZoho($endpoint, $query);
    }

    public function getItemDetails($ids)
    {
        $endpoint = "/itemdetails";
        $query = [
            'item_ids' => $ids,
            'per_page' => 200,
        ];
        return $this->callZoho($endpoint, $query);
    }

    public function getContacts($page = 1)
    {
        $endpoint = "/contacts";
        $query = [
            'page' => $page,
            'per_page' => 200,
        ];
        return $this->callZoho($endpoint, $query);
    }

    public function getContactAddress($contact)
    {
        $endpoint = "/contacts/{$contact}/address";
        $query = [
            'page' => 1,
            'per_page' => 200,
        ];
        return $this->callZoho($endpoint, $query);
    }

    public function getTaxes($page = 1)
    {
        $endpoint = "/settings/taxes";
        $query = [
            'page' => $page,
            'per_page' => 200,
        ];
        return $this->callZoho($endpoint, $query);
    }

    public function createItem($params)
    {
        $endpoint = "/items";
        return $this->callZoho($endpoint, [], $params, 'POST');
    }

    public function updateItem($id, $params)
    {
        $endpoint = "/items/{$id}";
        return $this->callZoho($endpoint, [], $params, 'PUT');
    }

    public function addToItemGroup($id, $params)
    {
        $endpoint = "/items/grouping/{$id}";
        return $this->callZoho($endpoint, [], $params, 'PUT');
    }

    public function unlinkItemGroup($ids)
    {
        $endpoint = "/items/ungroup";
        $query = [
            'item_ids' => $ids,
        ];
        return $this->callZoho($endpoint, $query, null, 'POST');
    }

    public function createSalesOrder($params)
    {
        $endpoint = "/salesorders";
        return $this->callZoho($endpoint, [], $params, 'POST');
    }

    public function updateSalesOrderStatus($ids, $status = 'confirmed')
    {
        $endpoint = "/salesorders/status/{$status}";
        $query = [
            'salesorder_ids' => $ids,
        ];
        return $this->callZoho($endpoint, $query, null, 'POST');
    }

    public function voidSalesOrder($id)
    {
        $endpoint = "/salesorders/{$id}/status/void";
        return $this->callZoho($endpoint, [], null, 'POST');
    }

    public function getSalesOrder($id)
    {
        $endpoint = "/salesorders/{$id}";
        return $this->callZoho($endpoint);
    }

    public function createInvoiceFromSalesOrder($id)
    {
        $endpoint = "/invoices/fromsalesorder";
        $query = [
            'salesorder_id' => $id,
        ];
        return $this->callZoho($endpoint, $query, null, 'POST');
    }

    public function createPayment($params)
    {
        $endpoint = "/customerpayments";
        return $this->callZoho($endpoint, [], $params, 'POST');
    }

    public function updateInvoice($id, $params)
    {
        $endpoint = "/invoices/{$id}";
        return $this->callZoho($endpoint, [], $params, 'PUT');
    }

    public function sendInvoices($ids)
    {
        $endpoint = "/invoices/email";
        $query = [
            'invoice_ids' => $ids,
        ];
        return $this->callZoho($endpoint, $query, null, 'POST');
    }

    public function getInvoice($id)
    {
        $endpoint = "/invoices/{$id}";
        return $this->callZoho($endpoint);
    }

    public function emailSalesOrder($id, $email, $subject, $body, $cc = [])
    {
        $endpoint = "/salesorders/{$id}/email";
        $params = array_filter([
            "subject" => $subject,
            "body" => $body,
            "send_from_org_email_id" => true,
            "to_mail_ids" => (array) $email,
            //"cc_mail_ids" => $_ENV['APP_ENV'] === 'dev' ? [$_ENV['MAILER_RECIPIENT_DEV']] : $cc,
        ]);
        return $this->callZoho($endpoint, [], $params, 'POST');
    }

    public function emailInvoice($id, $email, $cc = [])
    {
        $endpoint = "/invoices/{$id}/email";
        $params = array_filter([
            "send_from_org_email_id" => true,
            "to_mail_ids" => (array) $email,
            "cc_mail_ids" => $_ENV['APP_ENV'] === 'dev' ? [$_ENV['MAILER_RECIPIENT_DEV']] : $cc,
        ]);
        return $this->callZoho($endpoint, [], $params, 'POST');
    }

    public function pushInvoice($id)
    {
        $endpoint = "/invoices/{$id}/einvoice/push";
        return $this->callZoho($endpoint, [], null, 'POST');
    }

    public function invoicesPdf($ids)
    {
        $endpoint = "/invoices/print";
        $query = [
            'invoice_ids' => $ids,
        ];
        return $this->callZoho($endpoint, $query, null, 'GET', true);
    }

    public function invoicePdf($id)
    {
        $endpoint = "/invoices/{$id}";
        $query = [
            'accept' => 'pdf',
        ];
        return $this->callZoho($endpoint, $query, null, 'GET', true);
    }

    public function createCustomer($params)
    {
        $endpoint = "/contacts";
        return $this->callZoho($endpoint, [], $params, 'POST');
    }

    public function updateCustomer($id, $params)
    {
        $endpoint = "/contacts/{$id}";
        return $this->callZoho($endpoint, [], $params, 'PUT');
    }

    public function createAddress($customer, $params)
    {
        $endpoint = "/contacts/{$customer}/address";
        return $this->callZoho($endpoint, [], $params, 'POST');
    }

    public function updateAddress($customer, $id, $params)
    {
        $endpoint = "/contacts/{$customer}/address/{$id}";
        return $this->callZoho($endpoint, [], $params, 'PUT');
    }

    public function deleteAddress($customer, $id)
    {
        $endpoint = "/contacts/{$customer}/address/{$id}";
        return $this->callZoho($endpoint, [], null, 'DELETE');
    }
}