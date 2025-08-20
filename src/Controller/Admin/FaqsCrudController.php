<?php

namespace App\Controller\Admin;

use App\Entity\Faqs;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FaqsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Faqs::class;
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_ADMIN')
            ->setEntityLabelInSingular('Faq')
            ->setEntityLabelInPlural('Faqs')
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
            ->setDefaultSort(['position' => 'ASC'])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title'),
            TextEditorField::new('description'),
            NumberField::new('position'),
            BooleanField::new('active')->renderAsSwitch(),
        ];
    }
}
