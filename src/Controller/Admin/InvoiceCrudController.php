<?php

namespace App\Controller\Admin;

use App\Entity\Invoice;
use App\Services\OrderService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class InvoiceCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly OrderService $orderService,
    ){}

    public static function getEntityFqcn(): string
    {
        return Invoice::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_SALES')
            ->setEntityLabelInPlural('Invoices')
            ->setEntityLabelInSingular('Invoice')
            ->setDefaultSort(['id' => 'DESC'])
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $pushInvoice = Action::new('pushInvoice', 'Push Invoice')
            ->displayIf(static function (Invoice $entity){
                return $entity->getStatus() >= Invoice::INVOICED and $entity->isGstInvoice() and !$entity->isPushed();
            })
            ->linkToCrudAction('pushInvoice')
            ->addCssClass('confirm-action')
            ->setIcon('fa-solid fa-file-arrow-up')
        ;

        $sendInvoice = Action::new('sendInvoice', 'Send Invoice')
            ->displayIf(static function (Invoice $entity){
                return $entity->getStatus() >= Invoice::INVOICED;
            })
            ->linkToCrudAction('sendInvoice')
            ->addCssClass('confirm-action')
            ->setIcon('fa-solid fa-envelope')
        ;

        $downloadInvoice = Action::new('downloadInvoice', 'Download Invoice')
            ->displayIf(static function (Invoice $entity){
                return $entity->getStatus() >= Invoice::INVOICED;
            })
            ->linkToCrudAction('downloadInvoice')
            ->addCssClass('confirm-action')
            ->setIcon('fa-solid fa-download')
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
            ->add(Crud::PAGE_INDEX, $downloadInvoice)
            ->add(Crud::PAGE_INDEX, $sendInvoice)
            //->add(Crud::PAGE_INDEX, $pushInvoice)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action) => $action->setIcon('fa fa-eye'))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('invoiceNumber')->setDisabled(),
            TextField::new('salesOrder.soNumber', 'Sales Order#')->setDisabled(),
            AssociationField::new('salesOrder.order.customer', 'Customer')->setDisabled()->setCrudController(CustomerCrudController::class)->autocomplete()->setColumns(12),
            MoneyField::new('total')->setDisabled()->setCurrency('INR')->setStoredAsCents(false),
            TextField::new('pdf', 'PDF')->setTextAlign('center')->setSortable(false)->setTemplatePath('admin/_invoice_pdf.html.twig')->hideOnForm(),
            DateTimeField::new('createdAt')->hideOnForm(),
            NumberField::new('status')->setTemplatePath('admin/_invoice_status.html.twig')->hideOnForm(),
        ];
    }

    public function pushInvoice(AdminContext $context): RedirectResponse
    {
        /** @var Invoice $invoice */
        $invoice = $context->getEntity()->getInstance();
        $result = $this->orderService->pushInvoice($invoice);
        $this->addFlash($result['success'] ? 'success' : 'danger', $result['message']);

        return $this->redirect($this->adminUrlGenerator->setAction(Action::INDEX)->unset(EA::ENTITY_ID)->generateUrl());
    }

    public function sendInvoice(AdminContext $context): RedirectResponse
    {
        /** @var Invoice $invoice */
        $invoice = $context->getEntity()->getInstance();
        $result = $this->orderService->sendInvoice($invoice);
        $this->addFlash($result['success'] ? 'success' : 'danger', $result['message']);

        return $this->redirect($this->adminUrlGenerator->setAction(Action::INDEX)->unset(EA::ENTITY_ID)->generateUrl());
    }

    public function downloadInvoice(AdminContext $context): RedirectResponse
    {
        /** @var Invoice $invoice */
        $invoice = $context->getEntity()->getInstance();
        $result = $this->orderService->downloadInvoice($invoice);
        $success = $result['success'];
        $message = $result['message'];
        $this->addFlash($success ? 'success' : 'danger', $message);

        return $this->redirect($this->adminUrlGenerator->setAction(Action::INDEX)->unset(EA::ENTITY_ID)->generateUrl());
    }
}
