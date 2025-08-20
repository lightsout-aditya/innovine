<?php

namespace App\Controller\Admin;

use App\Entity\Enquiry;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EnquiryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Enquiry::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            TextField::new('mobile'),
            EmailField::new('email'),
            TextField::new('subject'),
            TextEditorField::new('message'),
        ];
    }
}
