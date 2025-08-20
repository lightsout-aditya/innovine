<?php

namespace App\Controller\Admin;

use App\Entity\EmailTemplate;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

class EmailTemplateCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EmailTemplate::class;
    }

    public function configureActions(Actions $actions): Actions {

        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            $actions
                ->disable(Action::NEW)
            ;
        }

        return $actions
            ->disable(Action::BATCH_DELETE)
            ->disable(Action::DELETE)
            ->update(
                Crud::PAGE_INDEX,
                Action::NEW, function (Action $action) {
                return $action->setCssClass('d-none');
            });
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_SUPER_ADMIN')
            ->setEntityLabelInSingular('Email Template')
            ->setEntityLabelInPlural('Email Templates')
            ->setDefaultSort(['name' => 'ASC'])
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(6),
            TextField::new('name')->setDisabled($pageName === Crud::PAGE_EDIT),
            /*EmailField::new('from'),
            TextField::new('fromName')->hideOnIndex(),*/
            ArrayField::new('to')->setRequired(true),
            ArrayField::new('cc'),
            ArrayField::new('bcc')->hideOnIndex(),
            FormField::addColumn(6),
            TextField::new('Subject')->setRequired(true),
            TextEditorField::new('body')->setFormType(CKEditorType::class),
        ];
    }
}
