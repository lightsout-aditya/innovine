<?php

namespace App\Controller\Admin;

use App\Entity\Package;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PackageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Package::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_SALES')
            ->setEntityLabelInPlural('Shipments')
            ->setEntityLabelInSingular('Shipment')
            ->setDefaultSort(['id' => 'DESC'])
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::BATCH_DELETE, 'ROLE_SUPER_ADMIN')
            ->disable(Action::BATCH_DELETE)
            ->disable(Action::DELETE)
            ->disable(Action::NEW)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action) => $action->setIcon('fa fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setIcon('fa fa-edit')
                ->displayIf(fn (Package $entity) => $entity->getStatus() <= Package::SHIPPED)
            )
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('packageNumber')->setDisabled(),
            TextField::new('shipmentNumber')->setDisabled(),
            TextField::new('salesOrder.soNumber', 'Sales Order#')->setDisabled(),
            AssociationField::new('salesOrder.order.customer', 'Customer')->setDisabled()->setCrudController(CustomerCrudController::class)->autocomplete()->setColumns(12),
            NumberField::new('quantity')->setDisabled(),
            DateTimeField::new('createdAt')->hideOnForm(),
            ChoiceField::new('carrier')->setChoices(array_combine(Package::COURIER_PARTNER, Package::COURIER_PARTNER)),
            TextField::new('trackingNumber'),
            NumberField::new('status')->setTemplatePath('admin/_package_status.html.twig')->hideOnForm()
        ];
    }
}
