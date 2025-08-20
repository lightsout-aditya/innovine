<?php

namespace App\Controller\Admin;

use App\Entity\Address;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AddressCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Address::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name')->setColumns(12),
            TextField::new('address')->setColumns(12),
            TextField::new('street')->setColumns(12),
            TextField::new('city')->setColumns(12),
            TextField::new('state')->setColumns(12),
            TextField::new('pincode')->setColumns(12),
            BooleanField::new('default')->renderAsSwitch(),
        ];
    }
}
