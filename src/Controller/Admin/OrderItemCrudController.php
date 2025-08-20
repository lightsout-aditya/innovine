<?php

namespace App\Controller\Admin;

use App\Entity\OrderItem;
use Doctrine\DBAL\Query\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

class OrderItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OrderItem::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('item')->setRequired(true)
                //->setQueryBuilder(fn (QueryBuilder $queryBuilder) => $queryBuilder->orderBy('entity.name', 'ASC'))
                ->setCrudController(AllItemCrudController::class)
                ->autocomplete()->setRequired(true)->setColumns(8),
            NumberField::new('quantity')->setFormTypeOption('attr', ['value' => 1])->setRequired(true)->setColumns(4),
            //MoneyField::new('price')->setRequired(true)->setCurrency('INR')->setStoredAsCents(false)->setColumns(3),
        ];
    }
}