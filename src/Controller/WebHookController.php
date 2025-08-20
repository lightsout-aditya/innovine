<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\Package;
use App\Entity\SalesOrder;
use App\Services\OrderService;
use App\Services\ZohoService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WebHookController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly ZohoService $zoho,
        private readonly OrderService $orderService
    ){}

    #[Route(path: '/webhook/zoho/so', name: 'soZohoWebhook', methods: ['POST'])]
    public function soZohoWebhookAction(Request $request): JsonResponse
    {
        $webhookBody = $request->getContent();
        $webhookHeader = $request->headers->get('X-ZOHO');
        //$webhookBody = '{"salesorder":{"can_send_in_mail":false,"submitted_by_email":"","order_status":"confirmed","gst_no":"27AAHFI4542E1ZI","bcy_shipping_charge_tax":"","has_qty_cancelled":false,"tax_specification":"inter","estimate_id":"","submitted_date_formatted":"","contact_person_details":[],"status_formatted":"Shipped","packages":[{"date":"2024-11-28","status_message":"","detailed_status":"","quantity":1,"shipment_order":{"expected_delivery_date":"","delivery_days":"","shipment_date":"2024-11-28","associated_packages_count":1,"shipment_id":"1783541000007368164","shipment_delivered_date_formatted":"","delivery_date_with_time":"","shipment_date_formatted":"28/11/2024","shipment_date_with_time":"2024-11-28 00:00","delivery_date_with_time_formatted":"","delivery_guarantee":false,"is_carrier_shipment":false,"delivery_date":"","carrier":"BLUE DART ","delivery_date_formatted":"","expected_delivery_date_formatted":"","service":"","shipment_delivered_date":"","shipment_date_with_time_formatted":"28/11/2024 12:00 AM","tracking_number":"51564857374","shipment_number":"SHP-00559","tracking_url":"","shipment_type":"single_piece_shipment"},"delivery_days":"","shipment_date":"2024-11-28","package_id":"1783541000007368138","status_formatted":"Shipped","shipment_id":"1783541000007368164","quantity_formatted":"1.00","shipment_date_formatted":"28/11/2024","package_number":"PKG-00577","shipment_status_formatted":"Shipped","delivery_guarantee":false,"carrier":"BLUE DART ","is_tracking_enabled":false,"date_formatted":"28/11/2024","service":"","delivery_method":"BLUE DART ","tracking_number":"51564857374","shipment_number":"SHP-00559","status":"shipped","shipment_status":"shipped"}],"shipping_charge_tax_id":"","purchaseorders":[],"location_name":"Head Office","shipped_status_formatted":"Shipped","has_discount":false,"tax_treatment_formatted":"Registered Business - Regular","shipping_charge_tax_name":"","discount_total":0,"integration_id":"","tax_total":0,"invoiced_status":"invoiced","salesorder_id":"1783541000007331300","shipping_details":{},"merchant_gst_no":"","shipment_date_formatted":"","sub_statuses":[],"tracking_url":"","adjustment_description":"","currency_symbol":"Rs.","gst_treatment_formatted":"Registered Business - Regular","transaction_rounding_type":"no_rounding","template_name":"","roundoff_value":0,"salesorder_number":"SO-00823","has_unconfirmed_line_item":false,"template_id":"1783541000000000239","customer_name":"Ajay Gupta","total_formatted":"Rs.1,005.00","discount_total_formatted":"Rs.0.00","is_reverse_charge_applied":false,"payment_terms_label":"100% Advance","notes":"Payment Mode: UPI","documents":[],"discount_amount":0,"source":"Api","entity_tags":"","tds_override_preference":"no_override","shipping_charge_inclusive_of_tax":800,"contact":{"is_credit_limit_migration_completed":true,"unused_customer_credits_formatted":"Rs.5,583.26","credit_limit_formatted":"Rs.0.00","customer_balance_formatted":"Rs.1,005.00","unused_customer_credits":5583.26,"credit_limit":0,"customer_balance":1005},"contact_category":"business_gst","template_type":"standard","contact_persons":[],"billing_address_id":"1783541000000528233","shipping_charge_tax":"","gst_treatment":"business_gst","created_time":"2024-11-27T20:18:45+0530","created_date_formatted":"27/11/2024","is_inclusive_tax":true,"price_precision":2,"sub_total_inclusive_of_tax_formatted":"Rs.0.00","submitted_by_photo_url":"","tax_treatment":"business_gst","approvers_list":[],"shipping_charge_tax_percentage":"","zcrm_potential_name":"","adjustment":0,"current_sub_status":"confirmed","discount_amount_formatted":"Rs.0.00","offline_created_date_with_time_formatted":"","shipping_charge_inclusive_of_tax_formatted":"Rs.800.00","merchant_id":"","is_backordered":false,"contact_persons_associated":[],"shipping_charge_exclusive_of_tax_formatted":"Rs.800.00","current_sub_status_id":"","custom_field_hash":{"cf_remark":"TEST","cf_remark_unformatted":"TEST"},"shipping_charge_tax_type":"","paid_status":"unpaid","is_manually_fulfilled":false,"last_modified_time_formatted":"28/11/2024 02:52 PM","warehouses":[{"zip":"400705","country":"India","address":"12th FLOOR, 1207, CYBER ONE IT PARK, PLOT NO 4 AND 6,SECTOR 30 A","is_primary":false,"city":"Mumbai","status_formatted":"Active","warehouse_name":"Quarterfold Printabilities","phone":"9987121572","sales_channels":[],"state":"Maharashtra","email":"mswarehouse@quarterfoldltd.com","warehouse_id":"1783541000000123682","status":"active"}],"is_emailed":true,"offline_created_date_with_time":"","has_shipping_address":true,"shipping_charge":800,"bcy_adjustment":0,"currency_id":"1783541000000000064","zcrm_potential_id":"","place_of_supply":"GJ","discount":0,"taxes":[{"tax_amount":0,"tax_name":"IGST0","tax_amount_formatted":"Rs.0.00"}],"shipment_date":"","billing_address":{"zip":"396370","country":"India","country_code":"IN","address":"A-123, Test Villa","city":"Kachholi","phone":"9773511680","attention":"Ajay Gupta","street2":"Plot 5, Sector 10","state":"Gujarat","state_code":"GJ","fax":""},"total_quantity_formatted":"1.00","line_items":[{"line_item_id":"1783541000007331305","item_type":"inventory","item_type_formatted":"Inventory Items","discount":0,"document_id":"","quantity_cancelled":0,"image_name":"","track_serial_number":false,"sales_rate_formatted":"Rs.205.49","discounts":[],"project_id":"","product_id":"1783541000000069304","attribute_option_name2":"","attribute_option_name3":"","is_fulfillable":0,"is_returnable":true,"sku":"BMO-LM-G1-T2-TB-MATH-21","pricebook_id":"","image_type":"","attribute_name2":"","attribute_name3":"","bcy_rate_formatted":"Rs.205.00","image_document_id":"","group_name":"G1 Mathematics","attribute_name1":"","item_total":205,"tax_id":"1783541000000057112","tags":[],"unit":"pcs","warehouse_name":"Quarterfold Printabilities","tax_type":"tax","name":"G1 Mathematics","track_batch_number":false,"item_sub_total_formatted":"Rs.205.00","bcy_rate":205,"item_total_formatted":"Rs.205.00","is_combo_product":false,"quantity_shipped":1,"quantity_returned":0,"rate_formatted":"Rs.205.00","header_id":"","quantity_invoiced_cancelled":0,"attribute_option_data3":"","description":"","attribute_option_data2":"","attribute_option_data1":"","tds_tax_amount_formatted":"Rs.0.00","gst_treatment_code":"","quantity_dropshipped":0,"item_order":1,"is_unconfirmed_product":false,"item_sub_total":205,"variant_id":"1783541000000069306","tds_tax_percentage":"","rate":205,"hsn_or_sac":"49011010","custom_field_hash":{"cf_category_unformatted":"Learning Material","cf_category":"Learning Material"},"package_details":{"weight_unit":"kg","length":"","width":"","weight":"","dimension_unit":"cm","height":""},"quantity_packed":1,"line_item_type":"goods","sales_rate":205.49,"quantity":1,"quantity_manuallyfulfilled":0,"item_id":"1783541000000069306","advanced_tracking_missing_quantity":0,"attribute_option_name1":"","tax_name":"IGST0","quantity_delivered":0,"tds_tax_name":"","header_name":"","item_custom_fields":[{"field_id":"1783541000004933299","customfield_id":"1783541000004933299","show_in_store":false,"show_in_portal":false,"is_active":true,"index":1,"label":"Category","show_on_pdf":false,"edit_on_portal":false,"edit_on_store":false,"api_name":"cf_category","show_in_all_pdf":false,"selected_option_id":"1783541000004933315","value_formatted":"Learning Material","search_entity":"item","data_type":"dropdown","placeholder":"cf_category","value":"Learning Material","is_dependent_field":false}],"tds_tax_amount":0,"line_item_taxes":[],"is_invoiced":true,"tds_tax_id":"","product_type":"goods","quantity_invoiced":1,"quantity_backordered":0,"tax_percentage":0,"warehouse_id":"1783541000000123682"}],"location_id":"1783541000000651024","is_test_order":false,"invoices":[],"balance":1005,"terms":"","created_time_formatted":"27/11/2024 08:18 PM","total_quantity":1,"picklists":[],"sub_total_inclusive_of_tax":0,"exchange_rate":1,"approver_id":"","sales_channel":"direct_sales","merchant_name":"","shipping_charge_formatted":"Rs.800.00","reference_number":"innovineschoolshop.com Order #9","reverse_charge_tax_total_formatted":"Rs.0.00","sub_total_exclusive_of_discount":205,"is_dropshipped":false,"is_pre_gst":false,"discount_percent":0,"page_height":"11.69in","status":"shipped","shipped_status":"shipped","adjustment_formatted":"Rs.0.00","balance_formatted":"Rs.1,005.00","is_advanced_tracking_missing":false,"payments":[],"currency_code":"INR","page_width":"8.27in","refunds":[],"bcy_total":1005,"is_adv_tracking_in_package":false,"delivery_method_id":"","date_formatted":"27/11/2024","delivery_method":"","tax_rounding":"item_level","last_modified_time":"2024-11-28T14:52:55+0530","paid_status_formatted":"Unpaid","discount_type":"entity_level","sales_channel_formatted":"Direct Sales","customer_id":"1783541000000528228","roundoff_value_formatted":"Rs.0.00","is_taxable":false,"invoiced_status_formatted":"Invoiced","date":"2024-11-27","submitted_date":"","template_type_formatted":"Standard","pickup_location_id":"","created_by_name":"","order_status_formatted":"Confirmed","tds_summary":[],"invoice_conversion_type":"invoice","last_modified_by_id":"1783541000003508005","shipping_charge_tax_exemption_code":"","color_code":"","bcy_tax_total":0,"shipping_address_id":"1783541000000528233","custom_fields":[{"field_id":"1783541000007214019","customfield_id":"1783541000007214019","show_in_store":false,"show_in_portal":false,"is_active":true,"index":1,"label":"Remark","show_on_pdf":false,"edit_on_portal":false,"edit_on_store":false,"api_name":"cf_remark","show_in_all_pdf":false,"value_formatted":"TEST","search_entity":"salesorder","data_type":"string","placeholder":"cf_remark","value":"TEST","is_dependent_field":false}],"salesreturns":[],"shipping_charge_tax_exemption_id":"","discount_applied_on_amount_formatted":"Rs.0.00","place_of_supply_formatted":"Gujarat","current_sub_status_formatted":"Confirmed","so_cycle_preference":{"socycle_status":"not_triggered","is_feature_enabled":false,"can_create_invoice":false,"invoice_preference":{"mark_as_sent":false,"payment_account_id":"1783541000000000459","record_payment":false,"payment_mode_id":"1783541000000000223"},"can_create_package":false,"socycle_status_formatted":"Not Triggered","shipment_preference":{"default_carrier":"","deliver_shipments":false,"send_notification":false},"can_create_shipment":false},"tds_calculation_type":"tds_item_level","submitted_by_name":"","created_by_id":"1783541000003508005","is_discount_before_tax":true,"attachment_name":"","payment_terms":0,"shipping_charge_exclusive_of_tax":800,"sub_total_exclusive_of_discount_formatted":"Rs.205.00","total":1005,"branch_id":"1783541000000651024","tax_total_formatted":"Rs.0.00","creditnotes":[],"branch_name":"Head Office","sub_total_formatted":"Rs.205.00","shipping_address":{"zip":"396370","country":"India","country_code":"IN","address":"A-123, Test Villa","city":"Kachholi","phone":"9773511680","attention":"Ajay Gupta","street2":"Plot 5, Sector 10","state":"Gujarat","state_code":"GJ","fax":""},"bcy_shipping_charge":800,"can_manually_fulfill":false,"created_by_email":"","shipping_charge_tax_formatted":"","bcy_discount_total":0,"orientation":"portrait","discount_applied_on_amount":0,"account_identifier":"","submitter_id":"","submitted_by":"","reverse_charge_tax_total":0,"bcy_sub_total":205,"salesperson_name":"","salesperson_id":"","sub_total":205,"computation_type":"basic","shipping_charge_sac_code":"","created_date":"2024-11-27"}}';

        $success = false;
        if ($webhookBody and ($_ENV['APP_ENV'] === 'dev' or ($webhookHeader and $_ENV['ZOHO_WEBHOOK_KEY'] === $webhookHeader))) {
            $request = json_decode($webhookBody, true);
            try {
                if ($salesOrder = $request['salesorder'] ?? null) {
                    $invoicedStatus = $salesOrder['invoiced_status'];
                    $shippedStatus = $salesOrder['shipment_status'];
                    $salesOrderId = $salesOrder['salesorder_id'];
                    if($salesOrderId and (str_contains($invoicedStatus, 'invoiced') or str_contains($shippedStatus, 'shipped'))) {
                        $em = $this->doctrine->getManager();
                        $so = $em->getRepository(SalesOrder::class)->findOneBy(['zohoId' => $salesOrderId]);
                        if($so){
                            $res = $this->zoho->getSalesOrder($salesOrderId);
                            if($invoices = $res['salesorder']['invoices']??null){
                                $pushInvoice = $res['gst_treatment']??null === 'business_gst';
                                foreach ($invoices as $inv){
                                    $invoice = $em->getRepository(Invoice::class)->findOneBy(['zohoId' => $inv['invoice_id']]);
                                    if(!$invoice){
                                        $invoice = new Invoice();
                                        $invoice->setSalesOrder($so);
                                        $invoice->setZohoId($inv['invoice_id']);
                                    }
                                    $invoice->setInvoiceNumber($inv['invoice_number']);
                                    $invoice->setGstInvoice($pushInvoice);
                                    if(!$invoice->isSent()) {
                                        if ($r = $this->orderService->sendInvoice($invoice) and $r['success']) {
                                            $invoice->setSent(true);
                                            $invoice->setStatus(Invoice::SENT);
                                        }
                                    }
                                    if($r = $this->orderService->downloadInvoice($invoice) and $r['success']){
                                        $invoice->setDownloaded(true);
                                    }
                                    $invoice->setTotal($inv['total']);
                                    $em->persist($invoice);
                                    $em->flush();
                                }
                                $success = true;
                            }
                            if($packages =  $res['salesorder']['packages']??null){
                                foreach ($packages as $pac){
                                    $package = $em->getRepository(Package::class)->findOneBy(['zohoId' => $pac['package_id']]);
                                    if(!$package){
                                        $package = new Package();
                                        $package->setSalesOrder($so);
                                        $package->setZohoId($pac['package_id']);
                                        $package->setStatus(Package::PACKED);
                                    }
                                    $package->setPackageNumber($pac['package_number']);
                                    $package->setShipmentNumber($pac['shipment_order']['shipment_number']??null);
                                    $package->setQuantity($pac['quantity']);
                                    if($carrier = trim($pac['carrier'])){
                                        $carrier = $carrier === 'BLUE DART' ? 'BlueDart' : ($carrier === 'safexpress' ? 'SaFex' : null);
                                        $package->setCarrier($carrier);
                                        $package->setTrackingNumber($pac['tracking_number']);
                                        $package->setStatus(Package::SHIPPED);
                                    }
                                    $em->persist($package);
                                    $em->flush();
                                }
                                $success = true;
                            }
                        }
                    }
                }
            } catch (\Exception $exception) {
                $webhookBody = "Exception: {$exception->getMessage()}  {$webhookBody}";
            }

            $logDir = $this->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . 'zoho';
            if (!is_dir($logDir)) {
                mkdir($logDir);
            }
            file_put_contents($logDir . DIRECTORY_SEPARATOR . 'webhook_' . date('y.m.d') . '.log', date('Y-m-d H:i:s') . ": " . ($success ? 'Processed > ' : 'Skipped > ') . "\n" . $webhookBody . "\n\n", FILE_APPEND);
        }

        return new JsonResponse(['success' => $success], $success ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
    }
}