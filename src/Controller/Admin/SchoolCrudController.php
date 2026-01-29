<?php

namespace App\Controller\Admin;

use App\Entity\School;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SchoolCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return School::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_ADMIN')
            ->setEntityLabelInPlural('Schools')
            ->setEntityLabelInSingular('School')
            ->setDefaultSort(['name' => 'ASC'])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            
            TextField::new('schoolCode')
                ->setLabel('School Code')
                ->setColumns(4), // Adjusted width to fit 3 items in a row

            ChoiceField::new('brandCode')
                ->setLabel('Brand')
                ->setChoices(School::BRANDS)
                ->renderExpanded(false)
                ->setColumns(4),

            // --- NEW ZONE FIELD ---
            TextField::new('zone')
                ->setLabel('Zone')
                ->setColumns(4),
            // ----------------------

            BooleanField::new('active')->renderAsSwitch(),
            AssociationField::new('items')->hideOnIndex(),
        ];
    }
}