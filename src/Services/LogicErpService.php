<?php

namespace App\Services;

use App\Entity\Address;
use App\Entity\Item;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[WithMonologChannel('logic')]
final class LogicErpService
{
    private const BASE_URL = 'https://logicapi.logicerp.in/InnovineUniversalPvtLtd/';

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
    ){}

    /**
     * WORKAROUND: Create or Update the Customer in Logic ERP first.
     * We use the Mobile Number as the unique Party Code.
     */
    public function createCustomer(User $customer, ?Address $billingAddress): void
    {
        $url = $this->getApiUrl() . '/SaveParty';
        
        // Use Mobile Number as the unique Party Code
        $partyCode = $customer->getMobile(); 

        $payload = [
            'PartyCode' => $partyCode, // This will be our Link
            'PartyName' => $customer->getName(),
            'MobileNo' => $customer->getMobile(),
            'Email' => $customer->getEmail(),
            'AccountGroup' => 'Sundry Debtors', // Standard Group
            'Branch_Code' => $_ENV['LOGIC_BRANCH_CODE'] ?? '1',
            
            // Address Details
            'Address1' => $billingAddress ? $billingAddress->getAddress() : '',
            'City' => $billingAddress ? $billingAddress->getCity() : '',
            'State' => $billingAddress ? $billingAddress->getState() : '',
            'PinCode' => $billingAddress ? $billingAddress->getPincode() : '',
            'GSTNo' => $customer->getGstNumber() ?? '',
            'PANNo' => '', 
        ];

        try {
            $this->logger->info('LogicERP CreateCustomer Request: ' . json_encode($payload));
            
            $response = $this->client->request('POST', $url, [
                'json' => $payload,
                'headers' => $this->getHeaders(),
                'timeout' => 10,
            ]);

            // We don't strictly check for success here because if the customer
            // already exists, Logic might return an error or success.
            // We just want to ensure we TRIED to create them.
            $this->logger->info('LogicERP CreateCustomer Response: ' . $response->getContent(false));

        } catch (\Throwable $e) {
            // Log error but don't stop the order process; 
            // the customer might already exist.
            $this->logger->error('LogicERP CreateCustomer Error: ' . $e->getMessage());
        }
    }

    public function createSalesOrder(array $params): array
    {
        $em = $this->doctrine->getManager();

        $customer = $em->getRepository(User::class)->findOneBy(['zohoId' => $params['customer_id']]);
        $shippingAddress = $em->getRepository(Address::class)->findOneBy(['zohoId' => $params['shipping_address_id']]);
        $billingAddress = $em->getRepository(Address::class)->findOneBy(['zohoId' => $params['billing_address_id']]);

        $orderDate = $params['date'] ?? date('Y-m-d');
        $logicDate = date('d/m/Y', strtotime($orderDate));

        // --- DEFAULTS ---
        $partyUserCode = '1351'; 
        $orderPrefix = 'SOT';
        // ----------------

        $payload = [
            'Branch_Short_Name' => $_ENV['LOGIC_BRANCH_SHORT'] ?? '',
            'Branch_Code' => $_ENV['LOGIC_BRANCH_CODE'] ?? '',
            'Order_Prefix' => $orderPrefix,
            'Order_Date' => $logicDate,
            'Party_User_code' => $partyUserCode,
            'Party_Order_No' => $params['reference_number'] ?? '',
            'External_Order_ID' => $params['reference_number'] ?? '',
            'Remarks' => $params['notes'] ?? '',
            'Tax_Region' => $params['place_of_supply'] ?? '',
            
            // --- EMPTY RCU FIELDS (Safe Mode) ---
            'RCU_Mem_Prefix' => '',
            'RCU_Mem_Number' => '',
            'RCU_Mobile_No' => '', 
            'RCU_Email' => '',     
            'RCU_First_Name' => '',
            'RCU_Last_Name' => '',
            'RCU_State' => '',
            'RCU_PinCode' => '',
            'RCU_Country' => '',
            'RCU_City' => '',
            'RCU_GST_No' => '',
            // ------------------------------------

            // Billing Address
            'Billing_FirstName' => $customer ? $customer->getFirstName() : '',
            'Billing_MiddleName' => '',
            'Billing_LastName' => $customer ? $customer->getLastName() : '',
            'Billing_MobileNo' => $customer ? $customer->getMobile() : '',
            'Billing_EmailID' => $customer ? $customer->getEmail() : '',
            'Billing_Address1' => $billingAddress ? $billingAddress->getAddress() : '',
            'Billing_Address2' => '',
            'Billing_Country' => 'India',
            'Billing_State' => $billingAddress ? $billingAddress->getState() : '',
            'Billing_City' => $billingAddress ? $billingAddress->getCity() : '',
            'Billing_PinCode' => $billingAddress ? $billingAddress->getPincode() : '',
            'Billing_ContactPerson' => '',
            'Billing_Location' => '',

            // Shipping Address
            'Shipping_FirstName' => $customer ? $customer->getFirstName() : '',
            'Shipping_MiddleName' => '',
            'Shipping_LastName' => $customer ? $customer->getLastName() : '',
            'Shipping_MobileNo' => $customer ? $customer->getMobile() : '',
            'Shipping_EmailID' => $customer ? $customer->getEmail() : '',
            'Shipping_Address1' => $shippingAddress ? $shippingAddress->getAddress() : '',
            'Shipping_Address2' => '',
            'Shipping_Country' => 'India',
            'Shipping_State' => $shippingAddress ? $shippingAddress->getState() : '',
            'Shipping_City' => $shippingAddress ? $shippingAddress->getCity() : '',
            'Shipping_PinCode' => $shippingAddress ? $shippingAddress->getPincode() : '',
            'Shipping_ContactPerson' => '',
            'Shipping_Location' => '',

            'Payment_Method' => '', 
            'Exfactory_Date' => '',
            'Delivery_Date' => '',
            'Delivery_Time' => '',
            'Total_Other_Val_1' => (float) ($params['shipping_charge'] ?? 0.0),
            'Total_Other_Val_2' => 0.0,
            'Total_Other_Val_3' => 0.0,
            'Total_Other_Val_4' => 0.0,
            'Total_Other_Val_5' => 0.0,
            'Total_Other_Val_6' => 0.0,
            'Total_Other_Val_7' => 0.0,
            'Indirect_Customer_User_Code' => '',
            'Agent_Name' => '',
            'Company_Name' => '',
            'Transport_Name' => '',
            'Indirect_Order_No' => '',
            'Indirect_Order_Date' => '',
            'Order_Type' => '',
            'Party_Order_Date' => '',
            'Doc_Through' => '',
            'Branch_Order_Date' => '',
            'Delv_Vessel_Fight_No' => '',
            'AdvanceEntry' => [
                'CashAmount' => '',
                'CreditCardAmount' => 0.0,
                'ChequeAmount' => 0.0,
                'CreditNoteAmount' => 0.0,
            ],
            'ListItems' => [],
        ];

        // Map line items
        foreach (($params['line_items'] ?? []) as $li) {
            $item = $em->getRepository(Item::class)->findOneBy(['zohoId' => $li['item_id']]);
            
            // --- UPDATED MAPPING ---
            // 1. Get Dynamic SKU from DB
            $itemSku = $item ? $item->getSku() : '';
            
            // 2. Hardcode LogicUser_Code to 'Q5610'
            $logicItemCode = 'Q5610';

            $payload['ListItems'][] = [
                'LogicUser_Code' => $logicItemCode, // Always 'Q5610'
                'AddlItemCode' => $itemSku,         // Stores the actual SKU
                'Order_Qty' => (float) ($li['quantity'] ?? 0),
                'Rate' => (float) ($li['rate'] ?? 0),
                'CD_P' => 0.0,
                'TD_P' => 0.0,
                'SPCD_P' => 0.0,
                'CD_Rs' => 0.0,
                'Scheme_Item' => 0.0,
                'Adjust_Item' => 0.0,
                'Tax_Amount' => (float) ($li['tax_amount'] ?? 0.0),
                'Remarks' => $li['description'] ?? '',
                'User_Column_1' => $li['hsn_or_sac'] ?? '',
                'User_Column_2' => '',
                'User_Column_3' => '',
                'User_Column_4' => '',
                'User_Column_5' => '',
                'Item_Order_ID' => '',
                'Exfactory_Date' => '',
                'Delivery_Date' => '',
                'ItemDetCode' => $li['warehouse_id'] ?? '',
                'Lot_Number' => '',
            ];
        }

        // Use ENV variable for URL or fallback to constant
        $url = ($_ENV['LOGIC_API_URL'] ?? '') . '/SaveSaleOrder';
        if (empty($_ENV['LOGIC_API_URL'])) {
             $url = self::SAVE_SALE_ORDER; 
        }

        try {
            $this->logger->info('LogicERP request: ' . json_encode($payload));

            $headers = ['Content-Type' => 'application/json'];
            if (!empty($_ENV['LOGIC_API_KEY'])) {
                $headers['Authorization'] = 'Bearer ' . $_ENV['LOGIC_API_KEY'];
            } elseif (!empty($_ENV['LOGIC_USERNAME']) && !empty($_ENV['LOGIC_PASSWORD'])) {
                $headers['Authorization'] = 'Basic ' . base64_encode($_ENV['LOGIC_USERNAME'] . ':' . $_ENV['LOGIC_PASSWORD']);
            }

            $response = $this->client->request('POST', $url, [
                'json' => $payload,
                'headers' => $headers,
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            if ($statusCode !== 200 && $statusCode !== 201) {
                 return [
                     'code' => -1, 
                     'message' => "HTTP Error $statusCode: " . $content . " (URL: $url)"
                 ];
            }

            $decoded = $response->toArray(false);
            $this->logger->info('LogicERP response: ' . $content);

            // Logic Status check (1 = Success)
            $status = (int) ($decoded['Status'] ?? 0);
            
            if ($status !== 1) {
                return [
                    'code' => -1,
                    'message' => $decoded['Message'] ?? 'Unknown Error from Logic ERP'
                ];
            }

            // --- SUCCESS ---
            $orderId = $decoded['LastSavedCode'] ?? $decoded['OrderID'] ?? uniqid('logic_');
            $orderNumber = $decoded['Order_No'] ?? $decoded['LastSavedDocNo'] ?? $payload['Party_Order_No'];

            $subTotal = array_reduce($payload['ListItems'], fn($carry, $item) => $carry + ($item['Rate'] * $item['Order_Qty']), 0.0);
            $taxTotal = array_reduce($payload['ListItems'], fn($carry, $item) => $carry + $item['Tax_Amount'], 0.0);

            $returnedLineItems = $params['line_items'] ?? [];

            return [
                'code' => 0,
                'message' => $decoded['Message'] ?? 'Sales order processed',
                'salesorder' => [
                    'salesorder_id' => $orderId,
                    'salesorder_number' => $orderNumber,
                    'sub_total' => $subTotal,
                    'taxes' => [],
                    'tax_total' => $taxTotal,
                    'shipping_charge' => $payload['Total_Other_Val_1'],
                    'total' => $subTotal + $taxTotal + $payload['Total_Other_Val_1'],
                    'line_items' => $returnedLineItems, 
                ],
            ];
            
        } catch (\Throwable $e) {
            $this->logger->error('LogicERP error: ' . $e->getMessage());
            return ['code' => -1, 'message' => $e->getMessage()];
        }
    }

    public function cancelSalesOrder(string $orderId, string $orderNumber, $salesOrderItems, string $reason = 'Cancelled By Customer'): array
    {
         // (Keep your existing cancelSalesOrder code here)
         // ...
         return ['code' => 0, 'message' => 'Implementation hidden for brevity']; 
    }

    // Helper to get Base URL
    private function getApiUrl(): string
    {
        $url = $_ENV['LOGIC_API_URL'] ?? self::BASE_URL;
        return rtrim($url, '/');
    }

    // Helper to get Headers
    private function getHeaders(): array
    {
        $headers = ['Content-Type' => 'application/json'];
        if (!empty($_ENV['LOGIC_API_KEY'])) {
            $headers['Authorization'] = 'Bearer ' . $_ENV['LOGIC_API_KEY'];
        } elseif (!empty($_ENV['LOGIC_USERNAME'])) {
            $headers['Authorization'] = 'Basic ' . base64_encode($_ENV['LOGIC_USERNAME'] . ':' . $_ENV['LOGIC_PASSWORD']);
        }
        return $headers;
    }
}