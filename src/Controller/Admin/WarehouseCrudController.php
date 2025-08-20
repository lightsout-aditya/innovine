<?php

namespace App\Controller\Admin;

use App\Entity\Warehouse;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class WarehouseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Warehouse::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_ADMIN')
            ->setEntityLabelInPlural('Warehouses')
            ->setDefaultSort(['name' => 'ASC'])
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::BATCH_DELETE, 'ROLE_SUPER_ADMIN')
            ->disable(Action::NEW)
            ->disable(Action::BATCH_DELETE)
            ->disable(Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action) => $action->setIcon('fa fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setIcon('fa fa-edit'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->setIcon('fa fa-trash')
                    ->displayIf(fn (Warehouse $entity) => !$entity->isImported())
            )
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addTab('General'),
            TextField::new('name'),
            EmailField::new('email'),
            TextField::new('phone'),
            BooleanField::new('primary')->renderAsSwitch()->setDisabled(),
            BooleanField::new('active')->renderAsSwitch()->setDisabled(),

            AssociationField::new('createdBy')->hideOnForm()->hideOnIndex(),
            DateField::new('createdAt')->hideOnForm()->hideOnIndex(),

            FormField::addTab('Address'),
            TextField::new('address')->hideOnIndex(),
            TextField::new('state')->hideOnIndex(),
            TextField::new('city')->hideOnIndex(),
            TextField::new('zip')->hideOnIndex(),
        ];
    }
}
