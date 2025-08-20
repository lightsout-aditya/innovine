<?php

namespace App\Controller\Admin;

use App\Entity\OrderReturn;
use App\Entity\Package;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrderReturnCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OrderReturn::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_SALES')
            ->setEntityLabelInPlural('Order Returns')
            ->setEntityLabelInSingular('Order Return')
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
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setIcon('fa fa-edit'))
            ;
    }
    public function configureFields(string $pageName): iterable
    {
        $returnDir = "/return";
        $baseUploadPath = $_ENV['BASE_UPLOAD_PATH'].$returnDir;
        $uploadDir = $_ENV['UPLOAD_DIR'].$returnDir;

        return [
            AssociationField::new('package.salesOrder', 'Sales Order')->setDisabled()->setCrudController(CustomerCrudController::class)->autocomplete(),
            AssociationField::new('package', 'Shipment')->setDisabled()->autocomplete(),
            AssociationField::new('createdBy', 'Customer')->setDisabled()->autocomplete(),
            TextField::new('subject')->setDisabled(),
            TextareaField::new('message')->setDisabled(),
            ImageField::new('attachment')->setCssClass('img-preview xs')->setFormTypeOptions(['attr' => ['data-path' => 'return']])->setBasePath($baseUploadPath)->setUploadDir($uploadDir)->setUploadedFileNamePattern('[randomhash].[extension]')->setDisabled(),
            DateTimeField::new('createdAt')->hideOnForm()->setDisabled(),
            ChoiceField::new('status')->setChoices(array_flip(OrderReturn::STATUS))->setTemplatePath('admin/_package_return_status.html.twig')
        ];
    }
}
