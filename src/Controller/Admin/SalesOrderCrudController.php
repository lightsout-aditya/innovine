<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Filter\AssociationFilter;
use App\Entity\SalesOrder;
use App\Entity\User;
use App\Services\OrderService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SalesOrderCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly OrderService $orderService,
    ){}

    public static function getEntityFqcn(): string
    {
        return SalesOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_SALES')
            ->setEntityLabelInPlural('Sales Orders')
            ->setEntityLabelInSingular('Sales Order')
            ->setDefaultSort(['id' => 'DESC'])
            ;
    }
    public function configureActions(Actions $actions): Actions
    {
        $sendSalesOrder = Action::new('sendSalesOrder', 'Send Sales Order')
            ->displayIf(static function (SalesOrder $entity){
                return $entity->getStatus() > SalesOrder::DRAFT and $entity->getStatus() != SalesOrder::VOID;
            })
            ->linkToCrudAction('sendSalesOrder')
            ->addCssClass('confirm-action')
            ->setIcon('fa-solid fa-envelope')
        ;

        $confirmSalesOrder = Action::new('confirmSalesOrder', 'Mark as Confirmed')
            ->displayIf(static function (SalesOrder $entity){
                return $entity->getStatus() === SalesOrder::DRAFT;
            })
            ->linkToCrudAction('confirmSalesOrder')
            ->addCssClass('confirm-action')
            ->setIcon('fa-solid fa-envelope')
        ;

        return $actions
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::BATCH_DELETE, 'ROLE_SUPER_ADMIN')
            ->disable(Action::BATCH_DELETE)
            ->disable(Action::DELETE)
            ->disable(Action::NEW)
            ->disable(Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $sendSalesOrder)
            ->add(Crud::PAGE_INDEX, $confirmSalesOrder)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action) => $action->setIcon('fa fa-eye'))
            ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(AssociationFilter::new('order.customer', 'Customer', User::class))
            ->add('createdAt')
            ->add(ChoiceFilter::new('status')->setChoices(array_flip(SalesOrder::STATUS)))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('soNumber', 'Sales Order#')->setDisabled(),
            AssociationField::new('order.customer', 'Customer')->setSortable(true)->setColumns(12)->setDisabled()->setCrudController(CustomerCrudController::class)->autocomplete(),
            TextField::new('order.customer.location', 'Location')->hideOnForm()->setColumns('6'),
            AssociationField::new('order')->setDisabled()->autocomplete()->setColumns(12),
            AssociationField::new('items')->setDisabled()->setSortable(false)->onlyOnIndex(),
            MoneyField::new('total')->setDisabled()->setCurrency('INR')->setStoredAsCents(false),
            //AssociationField::new('invoices', 'Invoices')->setTextAlign('center')->setSortable(false)->setTemplatePath('admin/_invoice_pdf.html.twig')->onlyOnIndex(),
            NumberField::new('order.transaction.offline', 'Payment')->setTemplatePath('admin/_payment_mode.html.twig')->hideOnForm(),
            DateTimeField::new('createdAt')->hideOnForm(),
            AssociationField::new('invoices')->setDisabled()->setSortable(false)->onlyOnIndex(),
            AssociationField::new('packages', 'Shipments')->setDisabled()->setSortable(false)->onlyOnIndex(),
            NumberField::new('status')->setTemplatePath('admin/_so_status.html.twig')->hideOnForm(),
            TextField::new('invoice', false)->onlyOnDetail()->setTemplatePath('admin/_invoice.html.twig'),
        ];
    }

    public function confirmSalesOrder(AdminContext $context): RedirectResponse
    {
        /** @var SalesOrder $salesOrder */
        $salesOrder = $context->getEntity()->getInstance();
        $result = $this->orderService->confirmSalesOrder($salesOrder);
        $this->addFlash($result['success'] ? 'success' : 'danger', $result['message']);

        return $this->redirect($this->adminUrlGenerator->setAction(Action::INDEX)->unset(EA::ENTITY_ID)->generateUrl());
    }

    public function sendSalesOrder(AdminContext $context): RedirectResponse
    {
        /** @var SalesOrder $salesOrder */
        $salesOrder = $context->getEntity()->getInstance();
        $result = $this->orderService->sendSalesOrder($salesOrder);
        $this->addFlash($result['success'] ? 'success' : 'danger', $result['message']);

        return $this->redirect($this->adminUrlGenerator->setAction(Action::INDEX)->unset(EA::ENTITY_ID)->generateUrl());
    }
}
