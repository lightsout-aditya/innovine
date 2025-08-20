<?php

namespace App\Controller\Admin;

use App\Entity\ItemStock;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

class ItemStockCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ItemStock::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('warehouse')->setDisabled()->setRequired(true)->autocomplete()->setRequired(true)->setColumns(12),
            NumberField::new('stockOnHand')->setDisabled()->hideOnIndex()->setColumns(12),
            NumberField::new('availableStock')->setDisabled()->hideOnIndex()->setColumns(12),
            NumberField::new('actualAvailableStock')->setDisabled()->hideOnIndex()->setColumns(12),
            NumberField::new('committedStock')->setDisabled()->hideOnIndex()->setColumns(12),
            NumberField::new('actualCommittedStock')->setDisabled()->hideOnIndex()->setColumns(12),
            NumberField::new('availableForSaleStock')->setDisabled()->hideOnIndex()->setColumns(12),
            NumberField::new('actualAvailableForSaleStock')->setDisabled()->hideOnIndex()->setColumns(12),
        ];
    }
}