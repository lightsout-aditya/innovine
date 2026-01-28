<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Cart;
use App\Entity\Item;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderReturn;
use App\Entity\Package;
use App\Entity\Tax;
use App\Entity\Transaction;
use App\Entity\User;
use App\Services\OrderService;
use Doctrine\Persistence\ManagerRegistry;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CartController extends AbstractController
{
    public function  __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly RequestStack $requestStack,
        private readonly OrderService $orderService
    ){}

    #[Route('/cart', name: 'cart', methods: ['GET'])]
    public function index(): Response
    {
        if(!$this->getUser()) {
            return $this->redirectToRoute('login');
        }

        return $this->render("cart/index.html.twig", [
            'carts' => $this->cart(false, true),
        ]);
    }

    #[Route('/cart/add', name: 'cartAdd', methods: ['POST'])]
    #[Route('/cart/update', name: 'cartUpdate', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $success = false;
        $showPrice = $_ENV['PRICE_PUBLIC'] || $this->getUser();
        if($showPrice) {
            $route = $request->get('_route');
            $em = $this->doctrine->getManager();
            $id = $request->get('id');
            $quantity = $request->get('qty');
            $item = $em->getRepository(Item::class)->findOneBy(['slug' => $id, 'active' => 1]);

            if ($item and $quantity) {
                if ($user = $this->getUser()) {
                    $cart = $em->getRepository(Cart::class)->findOneBy(['item' => $item, 'createdBy' => $user, 'parent' => null]);
                    if (!$cart) {
                        $cart = new Cart();
                        $cart->setItem($item);
                    }
                    $cart->setQuantity(min(($route == 'cartAdd' ? $cart->getQuantity() : 0) + $quantity, $_ENV['MAX_QTY']));
                    $em->persist($cart);
                    $em->flush();

                    if(count($item->getItemsMandatory())){
                        if($mItems = $request->get('mItems')) {
                            $mandatoryGroups = [];
                            $groupQty = [];
                            foreach ($item->getItemsMandatory() as $mItem) {
                                $mandatoryGroups[$mItem->getGroup()->getSlug()] = $mItem->getQuantity() * $quantity;
                                $groupQty[$mItem->getGroup()->getSlug()] = $mItem->getQuantity();
                            }

                            $submittedGroups = [];
                            foreach ($mItems as $slug => $q) {
                                $mandatoryItem = $em->getRepository(Item::class)->findOneBy(['slug' => $slug, 'active' => 1]);
                                $groupSlug = $mandatoryItem->getGroup()->getSlug();
                                $submittedGroups[$groupSlug] = ($submittedGroups[$groupSlug] ?? 0) + ($q * $groupQty[$groupSlug] ?? 1);
                            }

                            if ($mandatoryGroups === $submittedGroups) {
                                foreach ($mItems as $slug => $q) {
                                    if($q) {
                                        $mandatoryItem = $em->getRepository(Item::class)->findOneBy(['slug' => $slug, 'active' => 1]);
                                        $mandatoryItemQty = $q * $groupQty[$mandatoryItem->getGroup()->getSlug()] ?? 1;
                                        $childCart = $em->getRepository(Cart::class)->findOneBy(['parent' => $cart, 'item' => $mandatoryItem, 'createdBy' => $user]);
                                        if (!$childCart) {
                                            $childCart = new Cart();
                                            $childCart->setParent($cart);
                                            $childCart->setItem($mandatoryItem);
                                        }
                                        $childCart->setQuantity($childCart->getQuantity() + $mandatoryItemQty);
                                        $em->persist($childCart);
                                        $em->flush();
                                    }
                                }
                            } else {
                                $em->remove($cart);
                                $em->flush();
                                $this->addFlash('error', 'compulsory items with kit are missing.');
                            }
                            //dd($request->request->all(), $mandatoryGroups, $submittedGroups, $mandatoryGroups === $submittedGroups);
                        }else{
                            $this->addFlash('error', 'compulsory items with kit are missing.');
                        }
                    }
                } else {
                    $session = $request->getSession();
                    $cart = $session->get('cart');
                    $qty = ($route == 'cartAdd' ? $cart[$item->getId()]['quantity'] ?? 0 : 0) + $quantity;
                    $cart[$item->getId()] = ['item' => $item, 'quantity' => $qty, 'total' => $qty * $item->getPrice()];
                    $session->set('cart', $cart);
                }
                $success = true;
            }
        }
        return $this->json(['success' => $success, 'carts' => $this->cart(false)]);
    }

    #[Route(path: '/cart/delete/{id}', name: 'cartDelete')]
    public function delete($id, Request $request): JsonResponse
    {
        $em = $this->doctrine->getManager();
        $item = $em->getRepository(Item::class)->findOneBy(['slug' => $id]);
        $success = false;
        if($user = $this->getUser()) {
            $cart = $em->getRepository(Cart::class)->findOneBy(['item' => $item, 'createdBy' => $user]);
            if($cart) {
                $em->remove($cart);
                $em->flush();
                $success = true;
            }
        }else{
            $session = $request->getSession();
            if($cartItems = $session->get('cart')) {
                unset($cartItems[$item->getId()??null]);
                $session->set('cart', $cartItems);
                $success = true;
            }
        }
        return $this->json(['success' => $success, 'carts' => $this->cart(false)]);
    }

    public function cart($render = true, $raw = false)
    {
        $session = $this->requestStack->getSession();
        $em = $this->doctrine->getManager();
        if($user = $this->getUser()) {
            if($cartItems = $session->get('cart')) {
                $em = $this->doctrine->getManager();
                foreach ($cartItems as $key => $cartItem) {
                    $item = $em->getRepository(Item::class)->findOneBy(['id' => $key, 'active' => true]);
                    if($item) {
                        $cart = $em->getRepository(Cart::class)->findOneBy(['item' => $item, 'createdBy' => $user]);
                        if (!$cart) {
                            $cart = new Cart();
                            $cart->setItem($item);
                        }
                        $cart->setQuantity(min($cart->getQuantity() + $cartItem['quantity'], $_ENV['MAX_QTY']));
                        $em->persist($cart);
                    }
                }
                $em->flush();
                $session->remove('cart');
            }
            $carts = $em->getRepository(Cart::class)->findBy(['createdBy' => $user]);
        }else{
            $carts = $this->requestStack->getSession()->get('cart');
        }

        $session->set('cartCount', count($carts??[]));

        if($raw){
            return $carts;
        }

        if(!$render){
            return $this->renderView('cart/_cart_offcanvas.html.twig', [
                'carts' => $carts,
            ]);
        }

        return $this->render('cart/_cart_offcanvas.html.twig', [
            'carts' => $carts,
        ]);
    }

    private function getTaxType($address): string
    {
        $state = $address?->getState()??'Maharashtra';
        /*if($zip = $address?->getPincode()){
            $res = json_decode(@file_get_contents("https://api.postalpincode.in/pincode/{$zip}"),true);
            $state = $res[0]['PostOffice'][0]['State']??'Maharashtra';
        }*/
        return in_array($state, ['Maharashtra', 'MH']) ? 'intra' : 'inter';
    }

    #[Route('/checkout', name: 'checkout')]
    #[Route('/checkout/offline', name: 'checkoutOffline', methods: ['POST'])]
    #[Route('/checkout/summary', name: 'summary', methods: ['POST'])]
    public function checkout(Request $request): Response
    {
        /** @var User $user */
        if(!$user = $this->getUser()) {
            return $this->redirectToRoute('cart');
        }

        $em = $this->doctrine->getManager();
        $carts = $em->getRepository(Cart::class)->findBy(['createdBy' => $user]);

        if(!$carts) {
            return $this->redirectToRoute('cart');
        }

        $session = $request->getSession();
        if($addressId = $request->get('address')){
            $session->set('address', $addressId);
        }

        $address = $em->getRepository(Address::class)->findOneBy(array_filter(['id' => $session->get('address'), 'user' => $user]), ['default' => 'DESC', 'id' => 'DESC']);
        $taxType = $address?->getTaxType()??'intra';
        $route = $request->get('_route');
        $session->set('taxType', $taxType);

        $subtotal = 0;
        $taxTotal = 0;
        $taxes = [];

        $inStock = true;
        foreach ($carts as $cart){
            $subtotal = $subtotal + $cart->getTotal();
            foreach ($cart->getTax($taxType) as $k => $tax){
                $taxes[$k] = ($taxes[$k]??0) + $tax;
                $taxTotal = $taxTotal + $tax;
            }
            if($inStock) {
                $inStock = $cart->getItem()->inStock($cart->getQuantity(), $carts);
            }
        }

        $shippingFees = Cart::getShippingFees($subtotal);
        $sTax = $em->getRepository(Tax::class)->findOneBy(['specification' => $taxType, 'percent' => 18]);
        $shippingTax = 0;
        if($shippingFees and $sTax) {
            $shipTax = $shippingFees * $sTax->getPercent() / 100;
            $taxes[$sTax->getName()] = ($taxes[$sTax->getName()]??0) + $shipTax;
            $taxTotal = $taxTotal + $shipTax;
        }

        if(in_array($route, ['checkout', 'checkoutOffline'] ) and $request->isMethod('POST')){
            $offline = ($route === 'checkoutOffline') && $user->isOfflinePayment();
            $success = false;
            $message = null;
            $options = null;
            $amount = round($subtotal + $taxTotal + $shippingFees + $shippingTax, 2);
            $name = $user->getName();
            $mobile = $user->getMobile();
            $email = $user->getEmail();
            $attachment = $offline ? $request->files->get('attachment') : null;
            if(!$inStock){
                $message = "Please remove out of Stock items to proceed";
            }elseif(!$amount){
                $message = "Amount is invalid";
            }elseif($offline and $request->get('transactionAmount') != $amount){
                $message = "Amount is not matching";
            }elseif($offline and !$attachment){
                $message = "Attachment is matching";
            }elseif($offline and $attachment and !in_array($attachment->guessExtension(), ['jpg', 'jpeg', 'png'])){
                $message = "Attachment - only JPG and PNG image file are allowed";
            }elseif($offline and $attachment and $attachment->getSize() > 1048576){
                $message = "Attachment - maximum 1 MB file size is allowed";
            }elseif($offline and (!$request->get('transactionAmount') or !$request->get('transactionNumber') or !$request->get('transactionDate'))){
                $message = "Required fields are missing";
            }elseif(!$address or !$address->getPincode() or !$address->getState() or !$address->getCity()){
                $message = "Shipping address is invalid";
            }elseif(!$name or !$mobile or !$email){
                $message = "Please complete your <a href='{$this->generateUrl('profile')}'>Profile</a>.";
            }else {
                if($offline) {
                    $razorpayOrderId = "offline_" . md5(time() . rand() . rand());
                }
                else {
                    $orderId = "INO" . md5(time() . rand() . rand());
                    $callbackUrl = $this->generateUrl('pay', [], UrlGeneratorInterface::ABSOLUTE_URL);

                    $api = new Api($_ENV['RAZORPAY_KEY_ID'], $_ENV['RAZORPAY_KEY_SECRET']);
                    $razorpayOrder = $api->order->create(
                        [
                            'receipt' => $orderId,
                            'amount' => round($amount * 100),
                            'currency' => 'INR',
                            'notes' => [
                                "name" => $name,
                                "email" => $email,
                                "contact" => $mobile
                            ]
                        ]
                    );
                    $razorpayOrderId = $razorpayOrder['id'];
                }
                $session->set('razorpayOrderId', $razorpayOrderId);

                $cartItems['subtotal'] = $subtotal;
                $cartItems['tax'] = $taxTotal;
                $cartItems['shipping'] = $shippingFees;
                $cartItems['total'] = $amount;
                $cartItems['taxes'] = $taxes;
                foreach ($carts as $cart){
                    $cartItems['items'][] = ['id' => $cart->getItem()->getId(), 'qty' => $cart->getQuantity()];
                }

                $transaction = new Transaction();
                $transaction->setAmount($amount);
                $transaction->setRazorpayOrderId($razorpayOrderId);
                $transaction->setName($name);
                $transaction->setMobile($mobile);
                $transaction->setEmail($email);
                $transaction->setShipAddress($address);
                $transaction->setAddress($address->getAddress());
                $transaction->setAddress1($address->getStreet());
                $transaction->setState($address->getState());
                $transaction->setCity($address->getCity());
                $transaction->setPincode($address->getPincode());
                $transaction->setCart($cartItems);

                if($request->get('sameShipping') === 'false'){
                    $billing = $em->getRepository(Address::class)->findOneBy(['id' => $request->get('billing'), 'user' => $user]);
                    if($billing) {
                        $transaction->setBillingSame(false);
                        $transaction->setBillAddress($billing);
                        $transaction->setBillingName($billing->getName());
                        $transaction->setBillingPhone($billing->getPhone());
                        $transaction->setBillingAddress($billing->getAddress());
                        $transaction->setBillingStreet($billing->getStreet());
                        $transaction->setBillingState($billing->getState());
                        $transaction->setBillingCity($billing->getCity());
                        $transaction->setBillingPincode($billing->getPincode());
                    }
                }

                if($offline) {
                    $transaction->setRazorpayPaymentId($request->get('transactionNumber'));
                    $transaction->setPaymentMode($request->get('paymentMode'));
                    $transaction->setAmount($request->get('transactionAmount'));
                    $transaction->setFee(0);
                    $transaction->setTax(0);
                    $transaction->setTransactionDate(date_create($request->get('transactionDate')));
                    $transaction->setCurrency('INR');
                    $transaction->setStatus('captured');
                    if ($attachment instanceof UploadedFile) {
                        $attachmentFileName = sha1(time().rand().$attachment->getClientOriginalName()).".{$attachment->guessExtension()}";
                        $attachment->move($this->getParameter('receipt_dir'), $attachmentFileName);
                        $transaction->setAttachment($attachmentFileName);
                    }
                    $transaction->setOffline(true);
                    $em->persist($transaction);
                    $user->setOfflinePayment(false);
                    $em->persist($user);
                    $em->flush();
                    $order = $this->orderService->createOrder($transaction);;
                    $session->set('transaction', $transaction);
                    try {
                        //Generate SalesOrder in Draft mode
                        $em->refresh($order);
                        // $this->orderService->generateSalesOrder($order);
                    }catch (\Exception){}

                    return new JsonResponse(['success' => true, 'redirect' => $this->generateUrl('paymentSuccess')]);
                }

                $em->persist($transaction);
                $em->flush();

                $urlPackage = new UrlPackage(
                    $this->generateUrl('home', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    new StaticVersionStrategy('v1')
                );

                $success = true;
                $options = [
                    "key" => $_ENV['RAZORPAY_KEY_ID'],
                    "amount" => $amount,
                    "currency" => "INR",
                    "name" => $_ENV['SITE_NAME'],
                    "description" => 'Order',
                    "image" => $urlPackage->getUrl('images/logo.png'),
                    "order_id" => $razorpayOrderId,
                    "callback_url" => $callbackUrl,
                    "prefill" => [
                        "name" => $name,
                        "email" => $email,
                        "contact" => $mobile
                    ],
                    "notes" => array_filter([
                        "order" => $orderId,
                        "address" => $address->complete(),
                    ]),
                    "theme" => [
                        "color" => "#FFCE00"
                    ],
                ];
            }

            return new JsonResponse([
                'success' => $success,
                'message' => $message,
                'options' => $options,
            ]);
        }

        return $this->render($route === 'summary' ? 'cart/_summary.html.twig' :'cart/checkout.html.twig', [
            'carts' => $carts,
            'inStock' => $inStock,
            'subtotal' => $subtotal,
            'taxTotal' => $taxTotal,
            'taxes' => $taxes,
            'shippingFees' => $shippingFees,
        ]);
    }

    #[Route(path: '/pay', name: 'pay', methods: ['POST'])]
    public function pay(Request $request): Response
    {
        //dd($request->request->all());
        $paymentId = $request->get('razorpay_payment_id');
        $signature = $request->get('razorpay_signature');

        if($paymentId and $signature) {
            $em = $this->doctrine->getManager();
            $session = $this->requestStack->getSession();
            $orderId = $session->get('razorpayOrderId');

            /** @var Transaction $transaction */
            $transaction = $em->getRepository(Transaction::class)->findOneBy(['razorpayOrderId' => $orderId, 'status' => null]);
            if($transaction){
                try
                {
                    $api = new Api($_ENV['RAZORPAY_KEY_ID'], $_ENV['RAZORPAY_KEY_SECRET']);
                    $attributes = array_filter([
                        'razorpay_order_id' => $orderId,
                        'razorpay_payment_id' => $paymentId,
                        'razorpay_signature' => $signature
                    ]);

                    $api->utility->verifyPaymentSignature($attributes);
                    $verified = true;
                }
                catch(SignatureVerificationError $e)
                {
                    $verified = false;
                    //var_dump($e->getMessage()); exit;
                    $this->addFlash('error', 'Something went Wrong.');
                }

                if($verified) {
                    $payment = $api->payment->fetch($paymentId);
                    $transaction->setRazorpayPaymentId($paymentId);
                    $transaction->setRazorpaySignature($signature);
                    $transaction->setResponse($request->request->all());
                    $transaction->setPaymentMode($payment->method);
                    $transaction->setFee((isset($payment->fee) and $payment->fee) ? ($payment->fee/100) : 0);
                    $transaction->setTax((isset($payment->tax) and $payment->tax) ? ($payment->tax/100) : 0);
                    $transaction->setTransactionDate(date_create("@{$payment->created_at}")->setTimezone(new \DateTimeZone(date_default_timezone_get())));
                    $transaction->setCurrency($payment->currency);
                    $transaction->setStatus($payment->status);
                    $em->persist($transaction);
                    $em->flush();

                    if($payment->status === 'captured'){
                        $order = $this->orderService->createOrder($transaction);
                        $session->set('transaction', $transaction);
                        $session->remove('razorpayOrderId');

                        /*$templateName = 'on-checkout';
                        $address = trim("{$transaction->getAddress()} {$transaction->getAddress1()} {$transaction->getState()} {$transaction->getCity()} {$transaction->getPincode()}");
                        $params = [
                            'PAYMENT_MODE' => $transaction->getPaymentMode(),
                            'TRANSACTION_DATE' => $transaction->getTransactionDate()->format('d/m/Y'),
                            'AMOUNT' => $transaction->getAmount(),
                            'NAME' => $transaction->getName(),
                            'EMAIL' => $transaction->getEmail(),
                            'MOBILE' => $transaction->getMobile(),
                            'ADDRESS' => $address,
                        ];
                        $this->messageService->sendMailTemplate($templateName, $params, null, $transaction->getEmail());*/

                        try {
                            //Generate SalesOrder in Draft mode
                            $em->refresh($order);
                            // $this->orderService->generateSalesOrder($order);
                        }catch (\Exception){}

                        return $this->redirectToRoute('paymentSuccess');
                    }else{
                        $this->addFlash('error', $request->get('RESPMSG')??'Payment has been Failed.');
                        return $this->redirectToRoute('myOrders');
                    }
                }
            }else{
                return $this->redirectToRoute('home');
            }
        }
        return $this->redirectToRoute('home');
    }

    #[Route(path: '/payment/success', name: 'paymentSuccess', methods: ['GET'])]
    public function paymentSuccess(): Response
    {
        $session = $this->requestStack->getSession();
        if($transaction = $session->get('transaction')) {
            /*$order = null;
            if ($transaction->getOrder() and $id = $transaction->getOrder()->getId()) {
                $em = $this->doctrine->getManager();
                $order = $em->getRepository(Order::class)->find($id);
            }*/
            $session->remove('transaction');
            $session->remove('address');
            return $this->render("cart/success.html.twig", ['transaction' => $transaction]);
        }else{
            return $this->redirectToRoute('home');
        }
    }

    #[IsGranted('ROLE_USER')]
    #[Route(path: 'order/return', name: 'orderReturn', methods: ['POST'])]
    public function orderReturn(Request $request, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        $em = $this->doctrine->getManager();
        $subject = $request->get('subject');
        $packageId = $request->get('order');
        $returnMessage = $request->get('message');
        $submittedToken = $request->get('token');
        $success = false;
        $message = "Something went wrong";
        $package = $em->getRepository(Package::class)->findOneBy(['id' => $packageId, 'createdBy' => $this->getUser()?->getId()]);
        $attachment = $request->files->get('attachment');

        if (!$this->isCsrfTokenValid('orderReturn', $submittedToken)) {
            $message = "Session Expired, Please reload and try again.";
        }elseif($attachment and !in_array($attachment->guessExtension(), ['jpg', 'jpeg', 'png'])){
            $message = "Attachment - only JPG and PNG image file are allowed";
        }elseif($attachment and $attachment->getSize() > 2097152){
            $message = "Attachment - maximum 2 MB file size is allowed";
        }elseif ($package and $subject and $returnMessage) {
            $submission = new OrderReturn();
            $submission->setSubject($subject);
            $submission->setMessage($returnMessage);
            $submission->setPackage($package);

            $returnDir = $this->getParameter('return_dir');
            if ($attachment instanceof UploadedFile){
                $attachmentFileName = sha1(time().rand().$attachment->getClientOriginalName()).".{$attachment->guessExtension()}";
                $attachment->move($returnDir, $attachmentFileName);
                $submission->setAttachment($attachmentFileName);
            }

            $em->persist($submission);
            $em->flush();
            $success = true;
            $message = null;
            $this->addFlash('success', 'Your return request has been successfully submitted.');
            $csrfTokenManager->removeToken('orderReturn');
        }
        return new JsonResponse(['success' => $success, 'message' => $message]);
    }
}
