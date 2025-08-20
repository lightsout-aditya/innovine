<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Filter\AssociationFilter;
use App\Entity\Order;
use App\Entity\User;
use App\Services\OrderService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OrderCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly OrderService $orderService,
    ){}

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_SALES')
            ->setEntityLabelInPlural('Orders')
            ->setDefaultSort(['id' => 'DESC'])
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $generateSalesOrder = Action::new('generateSalesOrder', 'Generate Sales Order')
            ->displayIf(static function (Order $entity){
                return $entity->getStatus() == Order::ORDERED and !$entity->getSalesOrders()->count();
            })
            ->linkToCrudAction('generateSalesOrder')
            ->addCssClass('confirm-action')
            ->setIcon('fa-solid fa-receipt')
        ;

        $cancelOrder = Action::new('cancelOrder', 'Cancel Order')
            ->displayIf(static function (Order $entity){
                return $entity->getStatus() < Order::COMPLETED;
            })
            ->linkToCrudAction('cancelOrder')
            ->addCssClass('confirm-action text-danger')
            ->setIcon('fa-solid fa-ban text-danger')
        ;

        $sendInvoice = Action::new('sendInvoice', 'Send Invoice')
            ->displayIf(static function (Order $entity){
                return $entity->getStatus() == Order::PROCESSING;
            })
            ->linkToCrudAction('sendInvoice')
            ->addCssClass('confirm-action')
            ->setIcon('fa-solid fa-envelope')
        ;

        $downloadInvoice = Action::new('downloadInvoice', 'Download Invoice')
            ->displayIf(static function (Order $entity){
                return $entity->getStatus() == Order::PROCESSING;
            })
            ->linkToCrudAction('downloadInvoice')
            ->addCssClass('confirm-action')
            ->setIcon('fa-solid fa-download')
        ;

        return $actions
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::BATCH_DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission('cancelOrder', 'ROLE_ADMIN')
            ->disable(Action::BATCH_DELETE)
            ->disable(Action::DELETE)
            ->disable(Action::NEW)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $generateSalesOrder)
            ->add(Crud::PAGE_INDEX, $cancelOrder)
            ->add(Crud::PAGE_INDEX, $sendInvoice)
            ->add(Crud::PAGE_INDEX, $downloadInvoice)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action) => $action->setIcon('fa fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setIcon('fa fa-edit')
                ->displayIf(fn (Order $entity) => !$entity->getStatus())
            )
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->setIcon('fa fa-trash')
                    ->displayIf(fn (Order $entity) => !$entity->getStatus())
            )
            ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            //->add('customer')
            ->add(AssociationFilter::new('customer', 'Customer', User::class))
            ->add('createdAt')
            ->add(ChoiceFilter::new('status')->setChoices(array_flip(Order::STATUS)))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addTab('Order Details'),
            TextField::new('orderNumber')->hideOnForm(),
            AssociationField::new('customer')->setSortable(true)->setCrudController(CustomerCrudController::class)->setRequired(true)->autocomplete()->setColumns(12),
            TextField::new('customer.location', 'Location')->setSortable(true)->hideOnForm()->setColumns('6'),
            CollectionField::new('items')->setRequired(true)->onlyOnForms()->useEntryCrudForm()->setEntryIsComplex()->renderExpanded(false)->setColumns(12),
            AssociationField::new('items')->setSortable(false)->onlyOnIndex(),
            MoneyField::new('Total')->setCurrency('INR')->setStoredAsCents(false)->hideOnForm(),

            AssociationField::new('createdBy')->onlyOnDetail(),
            NumberField::new('transaction.offline', 'Payment')->setTemplatePath('admin/_payment_mode.html.twig')->hideOnForm(),
            AssociationField::new('salesOrders')->setSortable(false)->hideOnForm(),
            //AssociationField::new('salesOrders', 'Invoices')->setTextAlign('center')->setSortable(false)->setTemplatePath('admin/_invoice_pdf.html.twig')->onlyOnIndex(),
            DateTimeField::new('createdAt')->onlyOnIndex(),
            NumberField::new('status')->setTemplatePath('admin/_order_status.html.twig')->hideOnForm(),

            FormField::addTab('Shipping')->onlyOnDetail(),
            FormField::addPanel()->addCssClass('col-md-6'),
            TextField::new('shippingName', 'Name')->onlyOnDetail()->setColumns('6'),
            EmailField::new('email')->onlyOnDetail()->setDisabled()->setColumns('6'),
            TelephoneField::new('shippingPhone', 'Phone')->onlyOnDetail()->setColumns('6'),
            FormField::addPanel()->addCssClass('col-md-6'),
            TextField::new('shippingAddress', 'Address')->onlyOnDetail()->setColumns('6'),
            TextField::new('shippingStreet', 'Street')->onlyOnDetail()->setColumns('6'),
            TextField::new('shippingState', 'State')->onlyOnDetail()->setColumns('6'),
            TextField::new('shippingCity', 'City')->onlyOnDetail()->setColumns('6'),
            TextField::new('shippingPincode', 'Pin Code')->onlyOnDetail()->setColumns('6'),

            FormField::addTab('Payment & Transaction')->onlyOnDetail(),
            FormField::addPanel()->addCssClass('col-md-6'),
            MoneyField::new('subtotal')->setCurrency('INR')->setStoredAsCents(false)->onlyOnDetail(),
            MoneyField::new('shipping')->setCurrency('INR')->setStoredAsCents(false)->onlyOnDetail(),
            ArrayField::new('tax')->onlyOnDetail(),
            //MoneyField::new('discount')->setCurrency('INR')->setStoredAsCents(false)->onlyOnDetail(),
            MoneyField::new('Total')->setCurrency('INR')->setStoredAsCents(false)->onlyOnDetail(),
            FormField::addPanel()->addCssClass('col-md-6'),
            AssociationField::new('transaction', false)->setTemplatePath('admin/_transaction.html.twig')->onlyOnDetail(),

            FormField::addTab('Sales Orders')->onlyOnDetail(),
            AssociationField::new('salesOrders', false)->onlyOnDetail()->setTemplatePath('admin/_invoices.html.twig'),
        ];
    }

    public function generateSalesOrder(AdminContext $context): RedirectResponse
    {
        $order = $context->getEntity()->getInstance();
        $result = $this->orderService->generateSalesOrder($order);
        $this->addFlash($result['success']?'success':'danger', $result['message']);
        return $this->redirect($this->adminUrlGenerator->setAction(Action::INDEX)->unset(EA::ENTITY_ID)->generateUrl());
    }

    public function cancelOrder(AdminContext $context): RedirectResponse
    {
        $order = $context->getEntity()->getInstance();
        $result = $this->orderService->cancelOrder($order);
        $this->addFlash($result['success']?'success':'danger', $result['message']);
        return $this->redirect($this->adminUrlGenerator->setAction(Action::INDEX)->unset(EA::ENTITY_ID)->generateUrl());
    }

    public function sendInvoice(AdminContext $context): RedirectResponse
    {
        /** @var Order $order */
        $order = $context->getEntity()->getInstance();
        $salesOrders = $order->getSalesOrders();
        $success = false;
        $message = null;
        foreach ($salesOrders as $so) {
            foreach ($so->getInvoices() as $invoice) {
                $result = $this->orderService->sendInvoice($invoice);
                $success = $result['success'];
                $message = $result['message'];
            }
        }
        $this->addFlash($success ? 'success' : 'danger', $message);
        return $this->redirect($this->adminUrlGenerator->setAction(Action::INDEX)->unset(EA::ENTITY_ID)->generateUrl());
    }

    public function downloadInvoice(AdminContext $context): RedirectResponse
    {
        /** @var Order $order */
        $order = $context->getEntity()->getInstance();
        $salesOrders = $order->getSalesOrders();
        $success = false;
        $message = null;
        foreach ($salesOrders as $so) {
            foreach ($so->getInvoices() as $invoice) {
                $result = $this->orderService->downloadInvoice($invoice);
                $success = $result['success'];
                $message = $result['message'];
            }
        }
        $this->addFlash($success ? 'success' : 'danger', $message);
        return $this->redirect($this->adminUrlGenerator->setAction(Action::INDEX)->unset(EA::ENTITY_ID)->generateUrl());
    }
}
