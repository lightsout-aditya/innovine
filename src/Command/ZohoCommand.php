<?php

namespace App\Command;

use App\Entity\Address;
use App\Entity\Item;
use App\Entity\ItemGroup;
use App\Entity\ItemStock;
use App\Entity\Setting;
use App\Entity\Tax;
use App\Entity\User;
use App\Entity\Warehouse;
use App\Services\ZohoService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

//php bin/console IS:Zoho refreshToken
//php bin/console IS:Zoho fetchTaxes
//php bin/console IS:Zoho fetchWarehouses
//php bin/console IS:Zoho fetchContacts
//php bin/console IS:Zoho fetchContactsWithAddress
//php bin/console IS:Zoho fetchItemGroup
//php bin/console IS:Zoho fetchItems
//php bin/console IS:Zoho fetchStocks

//*/5 * * * * cd /web/innovine && php bin/console IS:Zoho fetchItems --env=prod >> var/log/items_`date '+\%m-\%d-\%Y'`.log

#0 */4 * * * /home/u942107814/domains/staging.innovineschoolshop.com/innovine/sync.sh --tax --warehouse
#10 * * * * /home/u942107814/domains/staging.innovineschoolshop.com/innovine/sync.sh --stock
#5 */2 * * * /home/u942107814/domains/staging.innovineschoolshop.com/innovine/sync.sh --group --item
#45 23 * * * /home/u942107814/domains/staging.innovineschoolshop.com/innovine/sync.sh --contact-address

#[AsCommand(
    name: 'IS:Zoho',
    description: 'Zoho Command',
)]
class ZohoCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly ZohoService $zoho,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Action: refreshToken | fetchItems');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        set_time_limit(0);
        $action = $input->getArgument('action');
        $io = new SymfonyStyle($input, $output);
        switch ($action) {
            case 'refreshToken':
                $this->refreshToken($io);
                break;
            case 'fetchItemGroup':
                $this->fetchItemGroup($io);
                break;
            case 'fetchItems':
                $this->fetchItems($io);
                break;
            case 'fetchWarehouses':
                $this->fetchWarehouses($io);
                break;
            case 'fetchStocks':
                $this->fetchStocks($io);
                break;
            case 'fetchContacts':
                $this->fetchContacts($io);
                break;
            case 'fetchContactsWithAddress':
                $this->fetchContacts($io, true);
                break;
            case 'fetchTaxes':
                $this->fetchTaxes($io);
                break;
            default:
                $io->error("Invalid Action '{$action}'");
                break;
        }

        return Command::SUCCESS;
    }

    protected function refreshToken($io): void
    {
        $io->write("Date: " . date('Y-m-d H:i:s'));
        $io->newLine();
        if($token = $this->zoho->refreshToken()){
            $em = $this->doctrine->getManager();
            $setting = $em->getRepository(Setting::class)->findOneBy([]);
            $setting->setZohoToken($token);
            $em->persist($setting);
            $em->flush();
        }
        $io->write("Token: {$token}");
    }

    protected function fetchItemGroup($io): void
    {
        $io->write("Date: " . date('Y-m-d H:i:s'));
        $io->newLine();
        $em = $this->doctrine->getManager();
        $page = 1;
        if($result = $this->zoho->getItemGroup($page)) {
            //var_dump($result['itemgroups']); exit;
            $counter = 1;
            do {
                if ($items = $result['itemgroups']) {
                    foreach ($items as $data) {
                        $zohoId = $data['group_id'];
                        $itemGroup = $em->getRepository(ItemGroup::class)->findOneBy(['zohoId' => $zohoId]);
                        if (!$itemGroup) {
                            $itemGroup = new ItemGroup();
                            $itemGroup->setZohoId($zohoId);
                            $itemGroup->setImported(true);
                        }
                        $itemGroup->setName($data['group_name']);
                        $itemGroup->setUnit($data['unit']);
                        //$itemGroup->setActive($data['status'] === 'active');
                        $itemGroup->setActive(true);
                        $em->persist($itemGroup);
                        $em->flush();
                        $io->write("\n{$counter}. Item Group id: {$zohoId} - {$data['group_name']}");
                        $counter++;
                    }
                } else {
                    $io->write("No Item Group found");
                }

                if ($more = $result['page_context']['has_more_page'] ?? false) {
                    $io->write("\n\nAFTER: {$counter}\n");
                    $result = $this->zoho->getItemGroup(++$page);
                }
            } while ($more);
        }
    }

    protected function fetchItems($io): void
    {
        $io->write("Date: " . date('Y-m-d H:i:s'));
        $io->newLine();
        $em = $this->doctrine->getManager();
        $page = 1;
        if($result = $this->zoho->getItems($page)) {
            //echo "<pre>"; print_r($result); exit;
            $counter = 1;
            do {
                if ($items = $result['items']) {
                    foreach ($items as $data) {
                        //Ignore Combo Items
                        if($data['is_combo_product']){continue;}
                        $zohoId = $data['item_id'];
                        $item = $em->getRepository(Item::class)->findOneBy(['zohoId' => $zohoId]);
                        if (!$item) {
                            $item = new Item();
                            $item->setZohoId($zohoId);
                            $item->setImported(true);
                        }

                        if($groupId = $data['group_id']??null){
                            $itemGroup = $em->getRepository(ItemGroup::class)->findOneBy(['zohoId' => $groupId]);
                            $item->setGroup($itemGroup);
                        }

                        if($attrName = $data['attribute_name1']??null and $attrName === 'Size'){
                            $item->setSize($data['attribute_option_name1']??null);
                        }

                        $item->setName($data['name']);
                        $item->setActive($data['status'] === 'active');
                        $item->setDescription($data['description']);
                        $item->setSku($data['sku']);
                        $item->setUnit($data['unit']);
                        $item->setHsnCode($data['hsn_or_sac']);
                        $item->setRate($data['rate']);
                        $item->setPurchaseRate($data['purchase_rate']);
                        $item->setComboProduct($data['is_combo_product']);
                        $item->setTaxable($data['is_taxable']);

                        $taxes = $data['item_tax_preferences'];
                        if($taxes){
                            foreach ($taxes as $tax) {
                                $zohoTax = $em->getRepository(Tax::class)->findOneBy(['zohoId' => $tax['tax_id']]);
                                if($zohoTax){
                                    switch ($tax['tax_specification']) {
                                        case 'intra':
                                            $item->setIntraTax($zohoTax);
                                            break;
                                        case 'inter':
                                            $item->setInterTax($zohoTax);
                                            break;
                                    }
                                }
                            }
                        }
                        //$item->setStockOnHand($data['stock_on_hand']??0);
                        //$item->setAvailableStock($data['available_stock']??0);
                        //$item->setActualAvailableStock($data['actual_available_stock']??0);
                        $em->persist($item);
                        $em->flush();
                        $io->write("\n{$counter}. Item id: {$zohoId} - {$data['name']}");
                        $counter++;
                    }
                } else {
                    $io->write("No Item found");
                }

                if ($more = $result['page_context']['has_more_page'] ?? false) {
                    $io->write("\n\nAFTER: {$counter}\n");
                    $result = $this->zoho->getItems(++$page);
                }
                //if($page === 5){exit;}
            } while ($more);
        }
    }

    protected function fetchWarehouses($io): void
    {
        $io->write("Date: " . date('Y-m-d H:i:s'));
        $io->newLine();
        $em = $this->doctrine->getManager();
        $page = 1;
        if($result = $this->zoho->getWarehouses($page)) {
            $counter = 1;
            do {
                if ($items = $result['warehouses']) {
                    foreach ($items as $data) {
                        $zohoId = $data['warehouse_id'];
                        $warehouse = $em->getRepository(Warehouse::class)->findOneBy(['zohoId' => $zohoId]);
                        if (!$warehouse) {
                            $warehouse = new Warehouse();
                            $warehouse->setZohoId($zohoId);
                            $warehouse->setImported(true);
                        }
                        $warehouse->setName($data['warehouse_name']);
                        $warehouse->setActive($data['status'] === 'active');
                        $warehouse->setPrimary($data['is_primary']);
                        $warehouse->setEmail($data['email']);
                        $warehouse->setPhone($data['phone']);
                        $warehouse->setAddress($data['address']);
                        $warehouse->setState($data['state']);
                        $warehouse->setCity($data['city']);
                        $warehouse->setZip($data['zip']);
                        $em->persist($warehouse);
                        $em->flush();
                        $io->write("\n{$counter}. Warehouse id: {$zohoId} - {$data['warehouse_name']}");
                        $counter++;
                    }
                } else {
                    $io->write("No Warehouse found");
                }

                if ($more = $result['page_context']['has_more_page'] ?? false) {
                    $io->write("\n\nAFTER: {$counter}\n");
                    $result = $this->zoho->getWarehouses(++$page);
                }
            } while ($more);
        }
    }

    protected function fetchStocks($io): void
    {
        //$result = $this->zoho->getItemDetails('1783541000000069110');
        //echo "<pre>"; print_r($result); exit;
        $io->write("Date: " . date('Y-m-d H:i:s'));
        $io->newLine();
        $em = $this->doctrine->getManager();
        $limit = 200;
        $offset = 1;
        $products = $em->getRepository(Item::class)->findBy(['imported' => true, 'comboProduct' => false], ['id' => 'ASC'], $limit, 0);
        $ids = [];
        foreach ($products as $product){
            $ids[] = $product->getZohoId();
        }
        //$ids = ['1783541000000069110'];
        if(count($ids) and $result = $this->zoho->getItemDetails(implode(',', $ids))) {
            $counter = 1;
            do {
                if ($items = $result['items']??null) {
                    foreach ($items as $data) {
                        $zohoId = $data['item_id'];
                        $item = $em->getRepository(Item::class)->findOneBy(['zohoId' => $zohoId]);
                        if ($item) {
                            $item->setStockOnHand(intval($data['stock_on_hand']??0));
                            $item->setAvailableStock(intval($data['available_stock']??0));
                            $item->setActualAvailableStock(intval($data['actual_available_stock']??0));
                            $item->setCommittedStock(intval($data['committed_stock']??0));
                            $item->setActualCommittedStock(intval($data['actual_committed_stock']??0));
                            $item->setAvailableForSaleStock(intval($data['available_for_sale_stock']??0));
                            $item->setActualAvailableForSaleStock(intval($data['actual_available_for_sale_stock']??0));
                            $em->persist($item);
                            $em->flush();
                            $warehouses = $data['warehouses'];
                            if($warehouses){
                                $stockOnHand = 0;
                                $stockAvailableStock = 0;
                                $stockActualAvailableStock = 0;
                                $stockCommittedStock = 0;
                                $stockActualCommittedStock = 0;
                                $stockAvailableForSaleStock = 0;
                                $stockActualAvailableForSaleStock = 0;
                                foreach ($warehouses as $wh) {
                                    $warehouse = $em->getRepository(Warehouse::class)->findOneBy(['zohoId' => $wh['warehouse_id']]);
                                    if ($warehouse) {
                                        $itemStock = $em->getRepository(ItemStock::class)->findOneBy(['item' => $item, 'warehouse' => $warehouse]);
                                        if ($wh['is_primary'] or in_array($wh['warehouse_id'], ['1783541000004152490'])) {
                                            if (!$itemStock) {
                                                $itemStock = new ItemStock();
                                                $itemStock->setItem($item);
                                                $itemStock->setWarehouse($warehouse);
                                            }
                                            $itemStock->setStockOnHand(intval($wh['warehouse_stock_on_hand'] ?? 0));
                                            $itemStock->setAvailableStock(intval($wh['warehouse_available_stock'] ?? 0));
                                            $itemStock->setActualAvailableStock(intval($wh['warehouse_actual_available_stock'] ?? 0));
                                            $itemStock->setCommittedStock(intval($wh['warehouse_committed_stock'] ?? 0));
                                            $itemStock->setActualCommittedStock(intval($wh['warehouse_actual_committed_stock'] ?? 0));
                                            $itemStock->setAvailableForSaleStock(intval($wh['warehouse_available_for_sale_stock'] ?? 0));
                                            $itemStock->setActualAvailableForSaleStock(intval($wh['warehouse_actual_available_for_sale_stock'] ?? 0));
                                            $em->persist($itemStock);

                                            $stockOnHand += $itemStock->getStockOnHand();
                                            $stockAvailableStock += $itemStock->getAvailableStock();
                                            $stockActualAvailableStock += $itemStock->getActualAvailableStock();
                                            $stockCommittedStock += $itemStock->getCommittedStock();
                                            $stockActualCommittedStock += $itemStock->getActualCommittedStock();
                                            $stockAvailableForSaleStock += $itemStock->getAvailableForSaleStock();
                                            $stockActualAvailableForSaleStock += $itemStock->getActualAvailableForSaleStock();

                                            $io->write("\n{$counter}. Item: {$item->getName()}, Warehouse: {$warehouse->getName()} - {$wh['warehouse_actual_available_for_sale_stock']}");
                                            $counter++;
                                        } elseif ($itemStock) {
                                            $em->remove($itemStock);
                                        }
                                    }
                                }
                                $item->setStockOnHand($stockOnHand);
                                $item->setAvailableStock($stockAvailableStock);
                                $item->setActualAvailableStock($stockActualAvailableStock);
                                $item->setCommittedStock($stockCommittedStock);
                                $item->setActualCommittedStock($stockActualCommittedStock);
                                $item->setAvailableForSaleStock($stockAvailableForSaleStock);
                                $item->setActualAvailableForSaleStock($stockActualAvailableForSaleStock);
                                $em->persist($item);
                                $em->flush();
                            }
                        }
                    }
                } else {
                    $io->write("No Item found");
                }

                if ($more = (count($ids) and (count($ids) == $limit))) {
                    $io->write("\n\nAFTER: {$counter}\n");
                    $products = $em->getRepository(Item::class)->findBy(['imported' => true, 'comboProduct' => false], ['id' => 'ASC'], $limit, $limit * $offset);
                    $offset++;
                    $ids = [];
                    foreach ($products as $product){
                        $ids[] = $product->getZohoId();
                    }
                    $result = $this->zoho->getItemDetails(implode(',', $ids));
                }
            } while ($more);
        }
    }

    protected function fetchContacts($io, $fetchAddress = false): void
    {
        $io->write("Date: " . date('Y-m-d H:i:s'));
        $io->newLine();
        $em = $this->doctrine->getManager();
        $page = 1;
        $states = Address::STATES;
        $statesFlip = array_flip(array_map( 'strtolower', Address::STATES));
        if($result = $this->zoho->getContacts($page)) {
            $counter = 1;
            do {
                if ($items = $result['contacts']) {
                    foreach ($items as $data) {
                        $email = strtolower($data['email']);
                        $zohoId = $data['contact_id'];
                        $contactType = $data['contact_type'];
                        if ($email and $contactType === 'customer') {
                            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                            $mobile = substr(str_replace([' ', '+91'], [], ltrim(trim($data['mobile']?:$data['phone']), '0')), 0, 10);
                            if (!$user) {
                                $user = new User();
                                $user->setImported(true);
                                $user->setEmail($data['email']);
                                $user->setRoles(['ROLE_USER']);
                                $user->setPassword($user->encodePassword(sha1($email.rand())));
                                $user->setEmailVerified(true);
                                $user->setMobileVerified((bool)$mobile);
                                $user->setSendEmail(false);
                            }

                            $user->setZohoId($zohoId);
                            $user->setFirstName($data['first_name']);
                            $user->setLastName($data['last_name']);
                            $user->setDisplayName($data['contact_name']);
                            $user->setEnabled($data['status'] === 'active');
                            $user->setCompany($data['company_name']);
                            $user->setType($data['contact_type']);
                            $user->setMobile($mobile);
                            $user->setPhone($data['phone']);
                            $user->setPan($data['pan_no']);
                            $user->setGstNumber($data['gst_no']);
                            $user->setGstTreatment($data['gst_treatment']);
                            $user->setPlaceOfContact($data['place_of_contact']);
                            $user->setLifecycleCallback(false);
                            $em->persist($user);
                            $io->write("\nUPDATED - {$counter}. Contact id: {$zohoId} - {$data['contact_name']} - $email");

                            if($fetchAddress and $contactAddress = $this->zoho->getContactAddress($zohoId)) {
                                foreach ($user->getAddresses() as $address) {
                                    $address->setActive(false);
                                    $em->persist($address);
                                }
                                if ($addresses = $contactAddress['addresses']) {
                                    //echo "<pre>"; print_r($addresses);
                                    foreach ($addresses as $ad) {
                                        $zohoAddressId = $ad['address_id'];
                                        $address = $em->getRepository(Address::class)->findOneBy(['zohoId' => $zohoAddressId]);
                                        if (!$address) {
                                            $address = new Address();
                                            $address->setZohoId($zohoAddressId);
                                            $address->setUser($user);
                                        }
                                        if(!$stateCode = $ad['state_code'] and $ad['state']){
                                            $stateCode = $statesFlip[trim(strtolower($ad['state']))]??null;
                                            $ad['state'] = $states[$stateCode]??$ad['state'];
                                        }
                                        $address->setName($ad['attention']);
                                        $address->setAddress($ad['address']);
                                        $address->setStreet($ad['street2']);
                                        $address->setCity($ad['city']);
                                        $address->setState($ad['state']);
                                        $address->setStateCode($stateCode);
                                        $address->setPincode($ad['zip']);
                                        $address->setPhone($ad['phone']);
                                        $address->setActive(true);
                                        $address->setLifecycleCallback(false);
                                        $em->persist($address);
                                        $io->write("\nAddress: {$zohoAddressId} - {$ad['city']}, {$stateCode}");
                                    }
                                }
                                foreach ($user->getAddresses() as $address) {
                                    if(!$address->isActive()){
                                        $em->remove($address);
                                    }
                                }
                            }
                            $em->flush();
                        }else{
                            $io->write("\nSKIPPED - {$counter}. Contact id: {$zohoId} - {$data['contact_name']}");
                        }
                        $counter++;
                    }
                } else {
                    $io->write("No Contact found");
                }

                if ($more = $result['page_context']['has_more_page'] ?? false) {
                    $io->write("\n\nAFTER: {$counter}\n");
                    $result = $this->zoho->getContacts(++$page);
                }
            } while ($more);
        }
    }

    protected function fetchTaxes($io): void
    {
        $io->write("Date: " . date('Y-m-d H:i:s'));
        $io->newLine();
        $em = $this->doctrine->getManager();
        $page = 1;
        if($result = $this->zoho->getTaxes($page)) {
            $counter = 1;
            do {
                if ($items = $result['taxes']) {
                    foreach ($items as $data) {
                        $zohoId = $data['tax_id'];
                        $tax = $em->getRepository(Tax::class)->findOneBy(['zohoId' => $zohoId]);
                        if (!$tax) {
                            $tax = new Tax();
                            $tax->setZohoId($zohoId);
                            $tax->setImported(true);
                        }
                        $tax->setName($data['tax_name']);
                        $tax->setPercent($data['tax_percentage']);
                        $tax->setActive($data['status'] === 'active');
                        $tax->setSpecification($data['tax_specification']);
                        $em->persist($tax);
                        $em->flush();
                        $io->write("\n{$counter}. Tax id: {$zohoId} - {$data['tax_name']}");
                        $counter++;
                    }
                } else {
                    $io->write("No Tax found");
                }

                if ($more = $result['page_context']['has_more_page'] ?? false) {
                    $io->write("\n\nAFTER: {$counter}\n");
                    $result = $this->zoho->getTaxes(++$page);
                }
            } while ($more);
        }
    }
}
