<?php

namespace App\Controller\Admin;

use App\Entity\Coupon;
use App\Entity\EmailTemplate;
use App\Entity\ErrorLog;
use App\Entity\Faqs;
use App\Entity\Grade;
use App\Entity\Invoice;
use App\Entity\Item;
use App\Entity\ItemCategory;
use App\Entity\ItemGroup;
use App\Entity\Order;
use App\Entity\OrderReturn;
use App\Entity\Package;
use App\Entity\Page;
use App\Entity\SalesOrder;
use App\Entity\School;
use App\Entity\Setting;
use App\Entity\User;
use App\Entity\Warehouse;
use App\Services\MessageService;
use App\Services\ZohoService;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/control')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly ManagerRegistry $doctrine
    )
    {}
    #[Route('/', name: 'admin')]
    public function index(): Response
    {
        $em = $this->doctrine->getManager();
        $connection = $this->doctrine->getConnection();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $user = $this->getUser();

        $sql = "SELECT COUNT(*) FROM `order` u WHERE u.status IN (1,2,3)";
        $count['orders'] = $connection->fetchOne($sql);
        $sql = "SELECT COUNT(*) FROM `sales_order` u WHERE u.status = 1";
        $count['sales orders'] = $connection->fetchOne($sql);
        $sql = "SELECT COUNT(*) FROM `invoice` u";
        $count['invoices'] = $connection->fetchOne($sql);
        $sql = "SELECT COUNT(*) FROM `package` u";
        $count['shipments'] = $connection->fetchOne($sql);
        $sql = "SELECT COUNT(*) FROM item u WHERE u.active = 1 AND u.combo_product = 0";
        $count['items'] = $connection->fetchOne($sql);
        $sql = "SELECT COUNT(*) FROM item u WHERE u.active = 1 AND u.combo_product = 1";
        $count['bundles'] = $connection->fetchOne($sql);
        $sql = "SELECT COUNT(*) FROM warehouse u WHERE u.active = 1";
        $count['warehouses'] = $connection->fetchOne($sql);
        $connection = $this->doctrine->getConnection();
        $sql = "SELECT COUNT(*) FROM coupon c WHERE c.active = 1";
        if(!$isAdmin){
            $sql .= " AND c.created_by = {$user->getId()}";
        }
        $count['coupons'] = $connection->fetchOne($sql);
        $sql = "SELECT COUNT(*) FROM user u WHERE u.enabled = 1 AND (u.roles LIKE '%[]%' OR u.roles LIKE '%ROLE_USER%')";
        $count['customers'] = $connection->fetchOne($sql);
        $sql = "SELECT COUNT(*) FROM user u WHERE u.enabled = 1 AND (u.roles LIKE '%ROLE_ADMIN%' OR u.roles LIKE '%ROLE_SUPER_ADMIN%')";
        $count['admins'] = $connection->fetchOne($sql);

        $query  = $em->getRepository(SalesOrder::class)
            ->createQueryBuilder('s')
            ->where('s.status >= 1')
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
        ;
        $recentOrders = $query->getResult();

        return $this->render('admin/dashboard/dashboard.html.twig', [
            'count' => $count,
            'recentOrders' => $recentOrders
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle("<img src='/images/logo.png' alt='Innovine Solutions'/>")
            ->setFaviconPath('favicon.ico')
            ->renderContentMaximized()
            ;
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('control/styles.css')
            ->addJsFile('js/jquery.min.js')
            ->addJsFile('js/Sortable.min.js')
            ->addJsFile('js/chart.js')
            ->addJsFile('bundles/fosckeditor/ckeditor.js')
            ->addJsFile('control/scripts.js')
            ;
    }

    public function configureMenuItems(): iterable
    {
        $em = $this->doctrine->getManager();
        $setting = $em->getRepository(Setting::class)->findOneBy([]);
        if(!$setting){
            $setting = new Setting();
            $em->persist($setting);
            $em->flush();
        }

        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        if ($this->isGranted('ROLE_SALES')) {
            yield MenuItem::linkToCrud('Orders', 'fas fa-cart-shopping', Order::class);
            yield MenuItem::linkToCrud('Sales Orders', 'fa-solid fa-receipt', SalesOrder::class);
            yield MenuItem::linkToCrud('Invoices', 'fa-solid fa-file-invoice', Invoice::class);
            yield MenuItem::linkToCrud('Shipments', 'fa-solid fa-truck-fast', Package::class);
            yield MenuItem::linkToCrud('Order Returns', 'fa-solid fa-arrow-rotate-left', OrderReturn::class);

            if ($this->isGranted('ROLE_ADMIN')) {
                yield MenuItem::linkToCrud('Items', 'fas fa-th', Item::class)->setController(ItemCrudController::class);
                yield MenuItem::linkToCrud('Items Group', 'fas fa-layer-group', ItemGroup::class)->setController(ItemGroupCrudController::class);
                yield MenuItem::linkToCrud('Bundles', 'fas fa-th-large', Item::class)->setController(BundleCrudController::class);
            }

            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                yield MenuItem::linkToCrud('Warehouses', 'fas fa-store', Warehouse::class);
                yield MenuItem::linkToCrud('Grades', 'fa-solid fa-graduation-cap', Grade::class);
            }

            if ($this->isGranted('ROLE_ADMIN')) {
                yield MenuItem::linkToCrud('Item Categories', 'fas fa-tags', ItemCategory::class);
                yield MenuItem::linkToCrud('School', 'fas fa-school', School::class);
                yield MenuItem::subMenu('Content', 'fas fa-database')->setSubItems([
                    MenuItem::linkToCrud('Pages', 'fa fa-file', Page::class)->setController(PageCrudController::class),
                    MenuItem::linkToCrud('Pages (SEO)', 'fa fa-file', Page::class)->setController(PagePrivateCrudController::class),
                ]);
                yield MenuItem::linkToCrud("FAQs", 'fas fa-hashtag', Faqs::class);
                //yield MenuItem::linkToCrud('Coupons', 'fas fa-tags', Coupon::class);
            }

            yield MenuItem::linkToCrud('Customers', 'fa-solid fa-user-group', User::class)->setController(CustomerCrudController::class);

            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                yield MenuItem::linkToCrud('Email Templates', 'fas fa-envelope', EmailTemplate::class);
                yield MenuItem::linkToCrud('Admins', 'fas fa-users-cog', User::class)->setController(UserCrudController::class);
                yield MenuItem::linkToCrud('Settings', 'fas fa-cog', Setting::class)->setController(SettingCrudController::class)->setAction('edit')->setEntityId($setting->getId());
                yield MenuItem::linkToCrud('Error Log', 'fas fa-exclamation-circle', ErrorLog::class);
            }
        }

        yield MenuItem::linkToUrl('Shop', 'fas fa-globe', '/');
        yield MenuItem::linkToLogout('Logout', 'fas fa-power-off');
    }

    #[Route('/styles.css', name: 'styles')]
    public function styles(): Response
    {
        return new Response(
            $this->renderView("admin/styles.css.twig"),
            Response::HTTP_OK,
            ['content-type' => 'text/css']
        );
    }

    #[Route('/scripts.js', name: 'scripts')]
    public function scripts(): Response
    {
        return new Response(
            $this->renderView("admin/scripts.js.twig"),
            Response::HTTP_OK,
            ['content-type' => 'application/javascript']
        );
    }

    #[Route('/sample/{fileName}', name: 'downloadSampleFormat')]
    public function downloadSampleFormatAction($fileName, KernelInterface $kernel)
    {
        $fileSystem = new Filesystem();
        $docPath = $kernel->getProjectDir() . "/assets/samples/{$fileName}";
        if($fileSystem->exists($docPath)){
            $file = new File($docPath);
            return $this->file($file);
        }
    }

    #[Route('/email-test', name: 'emailTest')]
    public function emailTestAction(Request $request, MessageService $messageService): Response
    {
        if($request->isMethod('POST')){
            $body = "<p>This is a <strong>TEST</strong> Email</p>";
            $messageService->sendMail($request->get('email'), 'Email Test', $body);
            echo "<p>Email Sent</p>";
        }
        $form = "<form method='post'><input type='email' name='email' placeholder='Send To' required><button type='submit'>SEND</button></form>";
        return new Response($form);
    }

    #[Route('/zoho-test', name: 'zohoTest')]
    public function zohoTest(ZohoService $zoho)
    {
        $token = $zoho->refreshToken();
        var_dump($token); exit;
    }

    #[Route('/report/weekly-sales', name: 'weeklySales')]
    public function weeklySales(): JsonResponse
    {
        $connection = $this->doctrine->getConnection();
        $currFinYearStart = date('Y-m-d', strtotime('-12 months'));
        $currFinYearEnd = date('Y-m-d');

        $sql = "SELECT DATE_FORMAT(DATE_ADD(s.`created_at`, INTERVAL(1 -DAYOFWEEK(s.`created_at`))DAY), '%Y-%m-%d')AS M, ROUND(SUM(s.total), 0) AS X
                FROM `order` s
                WHERE s.`status` IN (1,2,3) AND DATE(s.`created_at`) BETWEEN '{$currFinYearStart}' AND '{$currFinYearEnd}'
                GROUP BY WEEK(s.`created_at`, 1)
                ORDER BY s.`created_at`";
        $results = $connection->fetchAllAssociative($sql);

        $sales = [];
        foreach ($results as $result){
            $d = ($result['M'] < $currFinYearStart) ? $currFinYearStart : (($result['M'] > $currFinYearEnd) ? $currFinYearEnd : $result['M']);
            $date = date('M d', strtotime($d));
            $sales[] = ['x' => $date, 'y' => $result['X']];
        }
        $fmt = new \NumberFormatter('en_IN', \NumberFormatter::CURRENCY );
        $fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, 0);
        $totalSales = numfmt_format_currency($fmt,array_sum(array_column($sales, 'y')), 'INR');

        return new JsonResponse([
            'success'=> true,
            'sales' => $sales,
            'totalSales' => $totalSales,
        ]);
    }
    #[Route('/report/weekly-orders', name: 'weeklyOrders')]
    public function weeklyOrders(): JsonResponse
    {
        $connection = $this->doctrine->getConnection();
        $currFinYearStart = date('Y-m-d', strtotime('-12 months'));
        $currFinYearEnd = date('Y-m-d');

        $sql = "SELECT DATE_FORMAT(DATE_ADD(s.`created_at`, INTERVAL(1-DAYOFWEEK(s.`created_at`))DAY), '%Y-%m-%d')AS M, COUNT(*)AS X 
                FROM `order` s
                WHERE s.`status` IN (1,2,3) AND DATE(s.`created_at`) BETWEEN '{$currFinYearStart}' AND '{$currFinYearEnd}'
                GROUP BY WEEK(s.`created_at`, 1)
                ORDER BY s.`created_at`";
        $results = $connection->fetchAllAssociative($sql);

        $orders = [];
        foreach ($results as $result){
            $d = ($result['M'] < $currFinYearStart) ? $currFinYearStart : (($result['M'] > $currFinYearEnd) ? $currFinYearEnd : $result['M']);
            $date = date('M d', strtotime($d));
            $orders[] = ['x' => $date, 'y' => $result['X']];
        }
        $totalOrders = array_sum(array_column($orders, 'y'));

        return new JsonResponse([
            'success'=> true,
            'orders' => $orders,
            'totalOrders' => $totalOrders,
        ]);
    }

    #[Route('/migration/sku', name: 'migration')]
    public function migration()
    {
        $connection = $this->doctrine->getConnection();
        $skus = [
            "BOM-UN-WS-GIRL-44" => "5555000011105",
            "BOM-UN-WS-GIRL-28" => "5555000011129",
            "BOM-UN-WS-GIRL-30" => "5555000011136",
            "BOM-UN-WS-GIRL-32" => "5555000011143",
            "BOM-UN-WS-GIRL-34" => "5555000011150",
            "BOM-UN-WS-GIRL-36" => "5555000011167",
            "BOM-UN-WS-GIRL-38" => "5555000011174",
            "BOM-UN-WS-GIRL-40" => "5555000011181",
            "BOM-UN-WS-GIRL-42" => "5555000011198",
            "BOM-PP-UN-SOC-1" => "5555111122219",
            "BOM-PP-UN-SOC-2" => "5555111122226",
            "BOM-PP-UN-SOC-3" => "5555111122233",
            "BOM-UN-SHORT-36" => "5555111155507",
            "BOM-UN-SHORT-18" => "5555111155514",
            "BOM-UN-SHORT-20" => "5555111155521",
            "BOM-UN-SHORT-22" => "5555111155538",
            "BOM-UN-SHORT-24" => "5555111155545",
            "BOM-UN-SHORT-26" => "5555111155552",
            "BOM-UN-SHORT-28" => "5555111155569",
            "BOM-UN-SHORT-30" => "5555111155576",
            "BOM-UN-SHORT-32" => "5555111155583",
            "BOM-UN-SHORT-34" => "5555111155590",
            "BOM-UN-SHORT-38" => "5555111155613",
            "BOM-UN-SHORT-40" => "5555111155620",
            "BOM-UN-TR-42" => "5555111166213",
            "BOM-UN-TR-44" => "5555111166220",
            "BOM-UN-TR-46" => "5555111166237",
            "BOM-UN-TR-48" => "5555111166244",
            "BOM-UN-TR-50" => "5555111166251",
            "BOM-UN-TR-52" => "5555111166268",
            "BOM-UN-TR-40" => "5555111166602",
            "BOM-UN-TR-24" => "5555111166626",
            "BOM-UN-TR-26" => "5555111166633",
            "BOM-UN-TR-28" => "5555111166640",
            "BOM-UN-TR-30" => "5555111166657",
            "BOM-UN-TR-32" => "5555111166664",
            "BOM-UN-TR-34" => "5555111166671",
            "BOM-UN-TR-36" => "5555111166688",
            "BOM-UN-TR-38" => "5555111166695",
            "BOM-UN-SKIRT-40" => "5555111177219",
            "BOM-UN-SKIRT-42" => "5555111177226",
            "BOM-UN-SKIRT-44" => "5555111177233",
            "BOM-UN-SKIRT-46" => "5555111177240",
            "BOM-UN-SKIRT-38" => "5555111177707",
            "BOM-UN-SKIRT-20" => "5555111177714",
            "BOM-UN-SKIRT-22" => "5555111177721",
            "BOM-UN-SKIRT-24" => "5555111177738",
            "BOM-UN-SKIRT-26" => "5555111177745",
            "BOM-UN-SKIRT-28" => "5555111177752",
            "BOM-UN-SKIRT-30" => "5555111177769",
            "BOM-UN-SKIRT-32" => "5555111177776",
            "BOM-UN-SKIRT-34" => "5555111177783",
            "BOM-UN-SKIRT-36" => "5555111177790",
            "BOM-UN-GTR-46" => "5555111188802",
            "BOM-UN-GTR-28" => "5555111188819",
            "BOM-UN-GTR-30" => "5555111188826",
            "BOM-UN-GTR-32" => "5555111188833",
            "BOM-UN-GTR-34" => "5555111188840",
            "BOM-UN-GTR-36" => "5555111188857",
            "BOM-UN-GTR-38" => "5555111188864",
            "BOM-UN-GTR-40" => "5555111188871",
            "BOM-UN-GTR-42" => "5555111188888",
            "BOM-UN-GTR-44" => "5555111188895",
            "BOM-UN-GTR-26" => "5555111188918",
            "BOM-UN-SOCKS-3" => "5555222211123",
            "BOM-UN-SOCKS-4" => "5555222211130",
            "BOM-UN-SOCKS-5" => "5555222211147",
            "BOM-UN-SOCKS-6" => "5555222211154",
            "BOM-UN-SOCKS-7" => "5555222211161",
            "BOM-PP-UN-JOG-20" => "5555333311125",
            "BOM-PP-UN-JOG-22" => "5555333311132",
            "BOM-PP-UN-JOG-24" => "5555333311149",
            "BOM-PP-UN-JOG-26" => "5555333311156",
            "BOM-PP-UN-JOG-28" => "5555333311163",
            "BOM-PP-UN-JOG-30" => "5555333311170",
            "BOM-PP-UN-JOG-32" => "5555333311187",
            "BOM-UN-ST-YELLOW-26" => "5555444411202",
            "BOM-UN-ST-BLUE-24" => "5555444411233",
            "BOM-UN-ST-GREEN-24" => "5555444411240",
            "BOM-UN-ST-RED-24" => "5555444411257",
            "BOM-UN-ST-YELLOW-24" => "5555444411264",
            "BOM-UN-ST-BLUE-26" => "5555444411271",
            "BOM-UN-ST-GREEN-26" => "5555444411288",
            "BOM-UN-ST-RED-26" => "5555444411295",
            "BOM-UN-ST-GREEN-32" => "5555444411301",
            "BOM-UN-ST-BLUE-28" => "5555444411318",
            "BOM-UN-ST-GREEN-28" => "5555444411325",
            "BOM-UN-ST-RED-28" => "5555444411332",
            "BOM-UN-ST-YELLOW-28" => "5555444411349",
            "BOM-UN-ST-BLUE-30" => "5555444411356",
            "BOM-UN-ST-GREEN-30" => "5555444411363",
            "BOM-UN-ST-RED-30" => "5555444411370",
            "BOM-UN-ST-YELLOW-30" => "5555444411387",
            "BOM-UN-ST-BLUE-32" => "5555444411394",
            "BOM-UN-ST-YELLOW-36" => "5555444411400",
            "BOM-UN-ST-RED-32" => "5555444411417",
            "BOM-UN-ST-YELLOW-32" => "5555444411424",
            "BOM-UN-ST-BLUE-34" => "5555444411431",
            "BOM-UN-ST-GREEN-34" => "5555444411448",
            "BOM-UN-ST-RED-34" => "5555444411455",
            "BOM-UN-ST-YELLOW-34" => "5555444411462",
            "BOM-UN-ST-BLUE-36" => "5555444411479",
            "BOM-UN-ST-GREEN-36" => "5555444411486",
            "BOM-UN-ST-RED-36" => "5555444411493",
            "BOM-UN-ST-GREEN-42" => "5555444411509",
            "BOM-UN-ST-BLUE-38" => "5555444411516",
            "BOM-UN-ST-GREEN-38" => "5555444411523",
            "BOM-UN-ST-RED-38" => "5555444411530",
            "BOM-UN-ST-YELLOW-38" => "5555444411547",
            "BOM-UN-ST-BLUE-40" => "5555444411554",
            "BOM-UN-ST-GREEN-40" => "5555444411561",
            "BOM-UN-ST-RED-40" => "5555444411578",
            "BOM-UN-ST-YELLOW-40" => "5555444411585",
            "BOM-UN-ST-BLUE-42" => "5555444411592",
            "BOM-UN-ST-RED-42" => "5555444411615",
            "BOM-UN-ST-YELLOW-42" => "5555444411622",
            "BOM-UN-ST-BLUE-44" => "5555444411639",
            "BOM-UN-ST-GREEN-44" => "5555444411646",
            "BOM-UN-ST-RED-44" => "5555444411653",
            "BOM-UN-ST-YELLOW-44" => "5555444411660",
            "BOM-UN-ST-BLUE-46" => "5555444411677",
            "BOM-UN-STR-YELLOW-26" => "5555666611206",
            "BOM-UN-STR-BLUE-24" => "5555666611237",
            "BOM-UN-STR-GREEN-24" => "5555666611244",
            "BOM-UN-STR-YELLOW-24" => "5555666611268",
            "BOM-UN-STR-BLUE-26" => "5555666611275",
            "BOM-UN-STR-GREEN-26" => "5555666611282",
            "BOM-UN-STR-RED-26" => "5555666611299",
            "BOM-UN-STR-GREEN-32" => "5555666611305",
            "BOM-UN-STR-BLUE-28" => "5555666611312",
            "BOM-UN-STR-GREEN-28" => "5555666611329",
            "BOM-UN-STR-RED-28" => "5555666611336",
            "BOM-UN-STR-YELLOW-28" => "5555666611343",
            "BOM-UN-STR-BLUE-30" => "5555666611350",
            "BOM-UN-STR-GREEN-30" => "5555666611367",
            "BOM-UN-STR-RED-30" => "5555666611374",
            "BOM-UN-STR-YELLOW-30" => "5555666611381",
            "BOM-UN-STR-BLUE-32" => "5555666611398",
            "BOM-UN-STR-YELLOW-36" => "5555666611404",
            "BOM-UN-STR-RED-32" => "5555666611411",
            "BOM-UN-STR-YELLOW-32" => "5555666611428",
            "BOM-UN-STR-BLUE-34" => "5555666611435",
            "BOM-UN-STR-GREEN-34" => "5555666611442",
            "BOM-UN-STR-RED-34" => "5555666611459",
            "BOM-UN-STR-YELLOW-34" => "5555666611466",
            "BOM-UN-STR-BLUE-36" => "5555666611473",
            "BOM-UN-STR-GREEN-36" => "5555666611480",
            "BOM-UN-STR-RED-36" => "5555666611497",
            "BOM-UN-STR-GREEN-42" => "5555666611503",
            "BOM-UN-STR-BLUE-38" => "5555666611510",
            "BOM-UN-STR-GREEN-38" => "5555666611527",
            "BOM-UN-STR-RED-38" => "5555666611534",
            "BOM-UN-STR-YELLOW-38" => "5555666611541",
            "BOM-UN-STR-BLUE-40" => "5555666611558",
            "BOM-UN-STR-GREEN-40" => "5555666611565",
            "BOM-UN-STR-RED-40" => "5555666611572",
            "BOM-UN-STR-YELLOW-40" => "5555666611589",
            "BOM-UN-STR-BLUE-42" => "5555666611596",
            "BOM-UN-STR-RED-42" => "5555666611619",
            "BOM-UN-STR-YELLOW-42" => "5555666611626",
            "BOM-UN-STR-BLUE-44" => "5555666611633",
            "BOM-UN-STR-GREEN-44" => "5555666611640",
            "BOM-UN-STR-RED-44" => "5555666611657",
            "BOM-UN-STR-YELLOW-44" => "5555666611664",
            "BOM-TS-UN-WT-36" => "5555777711109",
            "BOM-TS-UN-WT-20" => "5555777711123",
            "BOM-TS-UN-WT-22" => "5555777711130",
            "BOM-TS-UN-WT-24" => "5555777711147",
            "BOM-TS-UN-WT-26" => "5555777711154",
            "BOM-TS-UN-WT-28" => "5555777711161",
            "BOM-TS-UN-WT-30" => "5555777711178",
            "BOM-TS-UN-WT-32" => "5555777711185",
            "BOM-TS-UN-WT-34" => "5555777711192",
            "BOM-TS-UN-WT-40" => "5555777711222",
            "BOM-UN-BELT-38" => "5555888811125",
            "BOM-UN-BELT-42" => "5555888811132",
            "BOM-UN-BELT-48" => "5555888811149",
            "BOM-UN-WS-BOY-44" => "5555999911103",
            "BOM-UN-WS-BOY-28" => "5555999911127",
            "BOM-UN-WS-BOY-30" => "5555999911134",
            "BOM-UN-WS-BOY-32" => "5555999911141",
            "BOM-UN-WS-BOY-34" => "5555999911158",
            "BOM-UN-WS-BOY-36" => "5555999911165",
            "BOM-UN-WS-BOY-38" => "5555999911172",
            "BOM-UN-WS-BOY-40" => "5555999911189",
            "BOM-UN-WS-BOY-42" => "5555999911196",
            "BOM-UN-WS-BOY-46" => "5555999911219"
        ];

        foreach ($skus as $oldSku => $newSku) {
            $sql = "SELECT i.id FROM item i JOIN item_image ii ON i.id = ii.item_id WHERE i.sku = '$oldSku' GROUP BY i.id";
            if($old = $connection->fetchOne($sql)){
                $sql = "SELECT i.id FROM item i WHERE i.sku = '$newSku'";
                if($new = $connection->fetchOne($sql)){
                    $sql = "UPDATE `item_image` SET `item_id` = '{$new}' WHERE `item_id` = '{$old}';";
                    $r = $connection->executeStatement($sql);
                    echo nl2br("$oldSku => $newSku : $sql\n$r row(s) affected\n\n");
                }
            }
        }
        die('DONE');
    }
}
