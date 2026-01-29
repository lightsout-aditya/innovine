<?php

namespace App\Services;

use App\Entity\Cart;
use App\Entity\Invoice;
use App\Entity\Item;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\SalesOrder;
use App\Entity\SalesOrderItem;
use App\Entity\Setting;
use App\Entity\Tax;
use App\Entity\Transaction;
use App\Entity\Warehouse;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final readonly class OrderService
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private ParameterBagInterface $parameterBag,
        private ZohoService $zoho,
        private LogicErpService $logicErp, // <--- Add this line
        private MessageService $messageService
    ){}

    private function calcShipping($order, $items): float|int
    {
        $total = 0;
        foreach($items as $item){
            $total += $item['rate'] * $item['quantity'];
        }
        
        // Fix: Check if subtotal is greater than 0 to avoid DivisionByZeroError
        if ($order->getSubtotal() > 0) {
            return round(($total * 100 / $order->getSubtotal()) * ($order->getShipping()) / 100, 2);
        }
        
        return 0;
    }

    public function createOrder(Transaction $transaction): Order
    {
        $em = $this->doctrine->getManager();
        $user = $transaction->getCreatedBy();
        $transactionCart = $transaction->getCart();
        $order = new Order();
        $order->setCustomer($user);
        $order->setEmail($transaction->getEmail());
        $order->setShipAddress($transaction->getShipAddress());
        $order->setShippingName($transaction->getName());
        $order->setShippingAddress($transaction->getAddress());
        $order->setShippingStreet($transaction->getAddress1());
        $order->setShippingState($transaction->getState());
        $order->setShippingCity($transaction->getCity());
        $order->setShippingPincode($transaction->getPincode());
        $order->setShippingPhone($transaction->getMobile());
        if(!$transaction->isBillingSame()) {
            $order->setBillAddress($transaction->getBillAddress());
            $order->setBillingSame($transaction->isBillingSame());
            $order->setBillingName($transaction->getBillingName());
            $order->setBillingPhone($transaction->getBillingPhone());
            $order->setBillingAddress($transaction->getBillingAddress());
            $order->setBillingStreet($transaction->getBillingStreet());
            $order->setBillingState($transaction->getBillingState());
            $order->setBillingCity($transaction->getBillingCity());
            $order->setBillingPincode($transaction->getBillingPincode());
        }
        $order->setSubtotal($transactionCart['subtotal']??0);
        $order->setTax($transactionCart['taxes']??null);
        $order->setShipping($transactionCart['shipping']??0);
        $order->setDiscount($transactionCart['discount']??0);
        $order->setTotal($transactionCart['total']??0);
        $order->setStatus(Order::ORDERED);
        if($cartItems = $transactionCart['items']??null) {
            foreach ($cartItems as $cartItem) {
                $item = $em->getRepository(Item::class)->find($cartItem['id']);
                $orderItem = new OrderItem();
                $orderItem->setItem($item);
                $orderItem->setQuantity($cartItem['qty']);
                $orderItem->setPrice($item->getPrice());
                $order->addItem($orderItem);
                $cart = $em->getRepository(Cart::class)->findOneBy(['item' => $item, 'createdBy' => $user]);
                if($cart) {
                    $em->remove($cart);
                }
            }
        }
        $em->persist($order);
        $transaction->setOrder($order);
        $em->persist($transaction);
        $em->flush();
        return $order;
    }

    public function generateSalesOrder(Order $order): array
    {
        $success = false;
        $message = 'Error while creating Sales Order';
        if ($_ENV['ZOHO_SYNC'] and $order->getStatus() === Order::ORDERED) {
            $em = $this->doctrine->getManager();
            $customer = $order->getCustomer();
            $transaction = $order->getTransaction();
            $lineItems = [];
            foreach ($order->getItems() as $i) {
                $item = $i->getItem();
                $isBundle = $item->isComboProduct();
                if ($isBundle) {
                    foreach ($item->getCompositeItems() as $ci) {
                        $item = $ci->getItem();
                        $qty = $i->getQuantity() * $ci->getQuantity();
                        $warehouse = $item->searchWarehouse($qty)?:$em->getRepository(Warehouse::class)->findOneBy(['primary' => true]);
                        if ($warehouse) {
                            $lineItems[$warehouse->getId()][] = [
                                "item_id" => $item->getZohoId(),
                                "name" => $item->getName(),
                                "description" => $item->getDescription(),
                                "hsn_or_sac" => $item->getHsnCode(),
                                "rate" => $item->getRate(),
                                "quantity" => $qty,
                                "unit" => $item->getUnit(),
                                "warehouse_id" => $warehouse->getZohoId(),
                            ];
                        }
                    }
                } else {
                    $qty = $i->getQuantity();
                    $warehouse = $item->searchWarehouse($qty)?:$em->getRepository(Warehouse::class)->findOneBy(['primary' => true]);
                    if ($warehouse) {
                        $lineItems[$warehouse->getId()][] = [
                            "item_id" => $item->getZohoId(),
                            "name" => $item->getName(),
                            "description" => $item->getDescription(),
                            "hsn_or_sac" => $item->getHsnCode(),
                            "rate" => $item->getRate(),
                            "quantity" => $qty,
                            "unit" => $item->getUnit(),
                            "warehouse_id" => $warehouse->getZohoId(),
                            //"tax_id" => 4815000000044043,
                            //"tds_tax_id" => "460000000017098",
                            //"tax_name" => "Sales Tax",
                            //"tax_type" => "tax",
                            //"tax_percentage" => 12,
                            //"item_total" => 244,
                        ];
                    }
                }
            }

            $taxType = $order->getShipAddress()?->getTaxType()??'intra';
            $sTax = $em->getRepository(Tax::class)->findOneBy(['specification' => $taxType, 'percent' => 18]);

            try {
                $salesOrders = [];
                foreach ($lineItems as $lis) {
                    $params = [
                        "customer_id" => $customer->getZohoId(),
                        //"salesorder_number" => $order->orderNumber(),
                        "date" => $order->getCreatedAt()->format('Y-m-d'),
                        //"shipment_date" => "2015-06-02",
                        "reference_number" => "SO-{$order->getId()}",
                        //"terms" => "100% Advance",
                        "payment_terms" => 0,
                        "payment_terms_label" => "100% Advance",
                        //"discount" => "20.00%",
                        //"is_discount_before_tax" => true,
                        //"discount_type" => "entity_level",
                        "shipping_charge" => $this->calcShipping($order, $lis),
                        "shipping_charge_tax_id" => $sTax?->getZohoId()??"1783541000000057220",
                        "shipping_charge_sac_code" => "998549",
                        //"delivery_method" => "FedEx",
                        //"adjustment" => 0,
                        //"pricebook_id" => 4815000000044054,
                        //"salesperson_id" => 4815000000044762,
                        //"adjustment_description" => "Just an example description.",
                        "is_inclusive_tax" => false,
                        //"exchange_rate" => 1,
                        //"template_id" => 4815000000017003,
                        "shipping_address_id" => $order->getShipAddress()->getZohoId(),
                        "billing_address_id" => $order->isBillingSame() ? $order->getShipAddress()->getZohoId() : ($order->getBillAddress()?->getZohoId()??$order->getShipAddress()->getZohoId()),
                        "place_of_supply" => $order->getShipAddress()->getStateCodeGST()??$customer->getPlaceOfContact(),
                        "gst_treatment" => $customer->getGstTreatment(),
                        "gst_no" => $customer->getGstNumber(),
                        "notes" => "Payment Mode: {$transaction->getPaymentMode()}",
                        "line_items" => $lis,
                    ];
                    //dd(json_encode($params));
                    //var_dump($params);
                    // $response = $this->zoho->createSalesOrder($params); // Comment out Zoho
                    $response = $this->logicErp->createSalesOrder($params); // Add Logic ERP                    
                    if ($so = $response['salesorder'] ?? null) {

                    if (empty($so['salesorder_id'])) {
                        return [
                            'success' => false, 
                            'message' => 'Error: Logic ERP returned an invalid Sales Order ID (0 or null)'
                        ];
                    }
                        $salesOrder = new SalesOrder();
                        $salesOrder->setOrder($order);
                        $salesOrder->setSoNumber($so['salesorder_number']);
                        $salesOrder->setZohoId($so['salesorder_id']);
                        $salesOrder->setSubtotal($so['sub_total']);
                        $salesOrder->setTaxes($so['taxes']);
                        $salesOrder->setTax($so['tax_total']);
                        $salesOrder->setShipping($so['shipping_charge']);
                        $salesOrder->setTotal($so['total']);

                        foreach ($so['line_items'] as $item) {
                            $itemObj = $em->getRepository(Item::class)->findOneBy(['zohoId' => $item['item_id']]);
                            $salesOrderItem = new SalesOrderItem();
                            $salesOrderItem->setItem($itemObj);
                            $salesOrderItem->setQuantity($item['quantity']);
                            $salesOrderItem->setPrice($item['rate']);
                            $salesOrder->addItem($salesOrderItem);
                        }
                        $salesOrder->setStatus(SalesOrder::DRAFT);
                        $em->persist($salesOrder);
                        $em->flush();
                        $salesOrders[] = $so['salesorder_id'];
                        //$this->sendSalesOrder($salesOrder);
                    }else{
                        $message = $response['message'] ?? $message;
                    }
                }

                if (count($salesOrders)) {
                    $order->setStatus(Order::ORDERED);
                    $em->persist($order);
                    $em->flush();
                    $success = true;
                    $message = 'Sales Order created successfully';
                }
//                if (count($salesOrders)) {
//                    $order->setStatus(Order::PROCESSING);
//                    $em->persist($order);
//                    $em->flush();
//                    $success = true;
//                    $message = 'Sales Order created successfully';
//                    $this->zoho->updateSalesOrderStatus(implode(',', $salesOrders));
//                    $pushInvoice = false;
//                    $invoices = [];
//                    /** @var SalesOrder $salesOrder */
//                    foreach ($salesOrders as $so) {
//                        $invoiceResponse = $this->zoho->createInvoiceFromSalesOrder($so);
//                        if ($invoice = $invoiceResponse['invoice'] ?? null) {
//                            $salesOrder = $em->getRepository(SalesOrder::class)->findOneBy(['zohoId' => $so]);
//                            $salesOrder->setZohoInvoiceId($invoice['invoice_id']);
//                            $salesOrder->setInvoiceNumber($invoice['invoice_number']);
//                            $salesOrder->setStatus(SalesOrder::PAID);
//                            $invoices[] = [
//                                "invoice_id" => $invoice['invoice_id'],
//                                "amount_applied" => $invoice['total'],
//                            ];
//                            $pushInvoice = $invoice['gst_treatment']??null === 'business_gst';
//                            $salesOrder->setGstInvoice($pushInvoice);
//                            $em->persist($salesOrder);
//                        }else{
//                            $success = false;
//                            $message = $invoiceResponse['message'] ?? 'Failed to convert Sales Order to Invoice';
//                        }
//                    }
//                    if (count($invoices)) {
//                        $em->flush();
//                        $params = [
//                            "customer_id" => $customer->getZohoId(),
//                            "payment_mode" => $transaction->getPaymentMode(),
//                            "amount" => $transaction->getAmount(),
//                            "date" => $transaction->getTransactionDate()->format('Y-m-d'),
//                            "reference_number" => "{$transaction->getRazorpayPaymentId()} ({$order->getOrderNumber()})",
//                            "invoices" => $invoices,
//                        ];
//                        $paymentResponse = $this->zoho->createPayment($params);
//                        if (!$paymentResponse['payment'] ?? null) {
//                            $success = false;
//                            $message = $paymentResponse['message'] ?? 'Invoice Payment mapping Failed';
//                        }else{
//                            foreach ($salesOrders as $so) {
//                                $salesOrder = $em->getRepository(SalesOrder::class)->findOneBy(['zohoId' => $so]);
//                                $this->updateInvoice($salesOrder);
//                                if($pushInvoice and $res = $this->pushInvoice($salesOrder) and $res['success']){
//                                    $salesOrder->setPushed(true);
//                                }
//                                if($res = $this->sendInvoice($salesOrder) and $res['success']){
//                                    $salesOrder->setSent(true);
//                                }
//                                if($res = $this->downloadInvoice($salesOrder) and $res['success']){
//                                    $salesOrder->setDownloaded(true);
//                                }
//                                $em->persist($salesOrder);
//                            }
//                            $em->flush();
//                        }
//                    }
//                }
            } catch (\Exception $exception) {
                if ($_ENV['APP_ENV'] === 'dev') {
                    dd($exception->getMessage());
                }
            }
        }
        return ['success' => $success, 'message' => $message];
    }

    public function updateInvoice(Invoice $invoice): array
    {
        $success = false;
        $message = "Error while sending Invoice";
        if ($_ENV['ZOHO_SYNC']) {
            if($invoiceId = $invoice->getZohoId()) {
                $paymentModes = ['debit' => 'Debit Card', 'credit' => 'Credit Card', 'netbanking' => 'Netbanking', 'upi' => 'UPI', 'emi' => 'EMI', 'wallet' => 'Wallet'];
                $paymentMode = $invoice->getSalesOrder()?->getOrder()?->getTransaction()?->getPaymentMode();
                $payMode = $paymentModes[$paymentMode]??"Online";
                $params = [
                    "custom_fields" => [
                        [
                            "value" => $payMode,
                            "api_name" =>"cf_payment_mode"
                        ]
                    ]
                ];

                $res = $this->zoho->updateInvoice($invoiceId, $params);
                $success = !$res['code'];
                $message = $res['message'];
            }
        }
        return ['success' => $success, 'message' => $message];
    }

    public function pushInvoice(Invoice $invoice): array
    {
        $success = false;
        $message = "Error while sending Invoice";
        if ($_ENV['ZOHO_SYNC']) {
            if($invoiceId = $invoice->getZohoId()) {
                $res = $this->zoho->pushInvoice($invoiceId);
                if($errors = $res['data']['errors']??null){
                    $message = '';
                    foreach ($errors as $error){
                        $message .= "<div>{$error['message']}</div>";
                    }
                }
                $success = !$errors && !$res['code'];
                $message = $errors ? $message : $res['message'];
            }
        }
        return ['success' => $success, 'message' => $message];
    }

   public function confirmSalesOrder(SalesOrder $salesOrder): array
    {
        $success = true; // Default to true since we don't need to "confirm" via API in Logic ERP
        $message = "Sales Order Confirmed";

        // 1. OPTIONAL: Call Logic ERP status update if they support it
        // if ($_ENV['LOGIC_SYNC']) {
        //      $res = $this->logicErp->updateSalesOrderStatus($salesOrder->getZohoId(), 'confirmed');
        // }

        // 2. Send Email using Local MessageService (Recommended)
        // Since we can't use Zoho to send email anymore, use your internal mailer
        // You'll need to create a template for 'sales-order-confirmation'
        $params = [
            'ORDER_NUMBER' => $salesOrder->getSoNumber(),
            'CUSTOMER_NAME' => $salesOrder->getOrder()->getCustomer()->getName(),
            // ... add other params ...
        ];
        // $this->messageService->sendMailTemplate('sales-order-confirmation', $params, null, $salesOrder->getOrder()->getEmail());

        // 3. Update Database Status
        if($success){
            $em = $this->doctrine->getManager();
            $salesOrder->setStatus(SalesOrder::CONFIRMED);
            $em->persist($salesOrder);
            
            $order = $salesOrder->getOrder();
            $order->setStatus(Order::PROCESSING);
            $em->persist($order);
            
            $em->flush();
        }
        
        return ['success' => $success, 'message' => $message];
    }

    public function cancelOrder(Order $order): array
    {
        $success = false;
        $message = []; // Initialize as array
        $em = $this->doctrine->getManager();

        // Check if there are linked SalesOrders
        if ($salesOrders = $order->getSalesOrders() and $salesOrders->count()) {
            foreach ($salesOrders as $salesOrder) {
                // Check if we have a Logic ERP ID (stored in zohoId column)
                if ($logicId = $salesOrder->getZohoId()) {
                    
                    // --- NEW LOGIC ERP CALL ---
                    // We pass ID, SO Number, and the Items Collection
                    $res = $this->logicErp->cancelSalesOrder(
                        $logicId, 
                        $salesOrder->getSoNumber(), 
                        $salesOrder->getItems()
                    );
                    // --------------------------

                    if ($res['code'] === 0) { // 0 usually means success in your service wrapper
                        $message[] = $res['message'];
                        $salesOrder->setStatus(SalesOrder::VOID);
                        $em->persist($salesOrder);
                        $success = true;
                    } else {
                         // Extract error message
                        $errorMsg = $res['message'];
                        if (isset($res['data']['errors'])) {
                             foreach($res['data']['errors'] as $err) {
                                 $errorMsg .= " " . ($err['message'] ?? '');
                             }
                        }
                        $message[] = "<div>$errorMsg</div>";
                        $success = false;
                    }
                }
            }
        } elseif (!$order->getSalesOrders()->count()) {
            // No sales orders linked, just cancel local order
            $success = true;
            $message[] = "Order Cancelled locally";
        }

        if ($success) {
            $order->setStatus(Order::CANCELLED);
            $em->persist($order);
            $em->flush();
            
            $params = [
                'ORDER_NUMBER' => $order->getOrderNumber(),
            ];
            // Send email
            $this->messageService->sendMailTemplate('on-order-cancelled', $params, null, $order->getEmail());
        }

        return ['success' => $success, 'message' => implode("<br>", $message)];
    }

    public function getAllEmail($email, $customer): array
    {
        $email = [$email];
        if($additionalEmail = $customer->getAdditionalEmail()){
            $emails = array_map('trim', explode(',', $additionalEmail));
            foreach($emails as $recipient){
                if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    $email[] = $recipient;
                }
            }
        }
        return $email;
    }

    public function sendSalesOrder(SalesOrder $salesOrder): array
    {
        $success = false;
        $message = "Error while sending Sales Order";
        if ($_ENV['ZOHO_SYNC']) {
            if($zohoId = $salesOrder->getZohoId()) {
                //$em = $this->doctrine->getManager();
                //$setting = $em->getRepository(Setting::class)->findOneBy([]);
                //$cc = $setting?->getInvoiceCc()??[];
                $customer = $salesOrder->getOrder()->getCustomer();
                $email = $this->getAllEmail($salesOrder->getOrder()->getEmail(), $customer);
                $subject = "Sales Order #{$salesOrder->getSoNumber()} from Innovine Universal Solutions Pvt. Ltd.";
                $body = "<p>Dear {$customer->getName()},</p><p>Thanks for your interest in our services. Please find our sales order attached with this mail.</p><p>An overview of the sales order is available below for your reference:</p><p>Sales Order # : {$salesOrder->getSoNumber()}<br>Order Date : {$salesOrder->getCreatedAt()->format('d/m/Y')}<br>Amount : Rs.".(number_format($salesOrder->getTotal(), 2))."</p><p>Assuring you of our best services at all times.</p><p>Regards,<br>Innovine Universal Solutions Pvt. Ltd.</p>";
                $res = $this->zoho->emailSalesOrder($zohoId, $email, $subject, $body);
                $success = !$res['code'];
                $message = $res['message'];
            }
        }
        return ['success' => $success, 'message' => $message];
    }

    public function sendInvoice(Invoice $invoice): array
    {
        $success = false;
        $message = "Error while sending Invoice";
        if ($_ENV['ZOHO_SYNC']) {
            if($invoiceId = $invoice->getZohoId()) {
                $em = $this->doctrine->getManager();
                $setting = $em->getRepository(Setting::class)->findOneBy([]);
                $customer = $invoice->getSalesOrder()->getOrder()->getCustomer();
                $email = $this->getAllEmail($invoice->getSalesOrder()->getOrder()->getEmail(), $customer);
                $cc = $setting?->getInvoiceCc()??[];
                $res = $this->zoho->emailInvoice($invoiceId, $email, $cc);
                $success = !$res['code'];
                $message = $res['message'];
            }
        }
        return ['success' => $success, 'message' => $message];
    }

    public function downloadInvoice(Invoice $invoice): array
    {
        $success = false;
        $message = "Error while downloading Invoice";
        if ($_ENV['ZOHO_SYNC']) {
            $em = $this->doctrine->getManager();
            if($invoiceId = $invoice->getZohoId()) {
                if($pdfResponse = $this->zoho->invoicePdf($invoiceId)){
                    $fileName = sha1($invoiceId).'.pdf';
                    $filePath = $this->parameterBag->get('invoice_dir').$fileName;
                    file_put_contents($filePath, $pdfResponse);
                    $invoice->setPdf($fileName);
                    $em->persist($invoice);
                    $em->flush();
                    $success = true;
                    $message = 'Invoice downloaded successfully';
                }
            }
        }
        return ['success' => $success, 'message' => $message];
    }
}