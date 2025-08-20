<?php

namespace App\Controller\Admin;

use App\Entity\ErrorLog;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ErrorLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ErrorLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Error Logs')
            ->setDefaultSort(['id' => 'DESC'])
            ;
    }

    public function configureActions(Actions $actions): Actions {
        return $actions
            ->disable(Action::NEW, Action::DELETE, Action::EDIT)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('url'),
            TextField::new('ipAddress'),
            TextField::new('errorCode'),
            TextareaField::new('errorMessage'),
            ArrayField::new('errorDetail')->setTemplatePath('admin/array.html.twig'),
            DateTimeField::new('createdAt'),
        ];
    }
}
