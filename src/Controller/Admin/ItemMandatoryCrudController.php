<?php

namespace App\Controller\Admin;

use App\Entity\ItemMandatory;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

class ItemMandatoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ItemMandatory::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('group')->setColumns(12),
            NumberField::new('quantity')/*->setFormTypeOption('attr', ['value' => 1])*/->setRequired(true)->setColumns(12),
        ];
    }
}
