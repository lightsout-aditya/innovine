<?php

namespace App\Controller\Admin;

use App\Entity\CompositeItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

class CompositeItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CompositeItem::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('item')->setRequired(true)->autocomplete()->setRequired(true)->setColumns(8),
            NumberField::new('quantity')->setFormTypeOption('attr', ['value' => 1])->setRequired(true)->setColumns(4),
        ];
    }
}