<?php

namespace App\Controller\Admin;

use App\Entity\ItemGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ItemGroupCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ItemGroup::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_ADMIN')
            ->setEntityLabelInPlural('Item Groups')
            ->setEntityLabelInSingular('Item Group')
            ->setDefaultSort(['name' => 'ASC'])
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW)
            ->disable(Action::BATCH_DELETE)
            ->disable(Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action) => $action->setIcon('fa fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setIcon('fa fa-edit'))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $baseUploadPath = $_ENV['BASE_UPLOAD_PATH'];
        $uploadDir = $_ENV['UPLOAD_DIR'];

        return [
            TextField::new('name')->setDisabled(),
            ImageField::new('sizeChart')->setCssClass('img-preview')->setBasePath($baseUploadPath)->setUploadDir($uploadDir)->setUploadedFileNamePattern('[randomhash].[extension]'),
            BooleanField::new('active')->setDisabled()->renderAsSwitch(),
        ];
    }
}
