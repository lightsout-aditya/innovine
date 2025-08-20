<?php

namespace App\Controller\Admin;

use App\Entity\Item;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BundleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Item::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_ADMIN')
            ->setEntityLabelInSingular('Bundle')
            ->setEntityLabelInPlural('Bundles')
            ->setPaginatorPageSize(100)
            ->setDefaultSort(['name' => 'ASC'])
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $preview = Action::new('preview')
            ->setLabel('Preview')
            ->setIcon('fas fa-eye')
            ->linkToUrl(function (Item $entity) {
                return '/product/'.$entity->getSlug();
            })
            ->setHtmlAttributes(['target' => '_blank'])
            ->displayIf(fn ($entity) => $entity->isActive())
        ;

        return $actions
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::BATCH_DELETE, 'ROLE_SUPER_ADMIN')
            ->disable(Action::BATCH_DELETE)
            ->disable(Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $preview)
            ->add(Crud::PAGE_EDIT, $preview)
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $preview)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action) => $action->setIcon('fa fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setIcon('fa fa-edit'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->setIcon('fa fa-trash')
                    ->displayIf(fn (Item $entity) => !$entity->isImported())
            )
            ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $queryBuilder->andWhere('entity.comboProduct = true');
        return $queryBuilder;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addTab('General'),
            TextField::new('name')->setColumns(12),
            TextareaField::new('description')->hideOnIndex()->setColumns(12),
            AssociationField::new('compositeItems')->onlyOnIndex(),
            AssociationField::new('category'),
            AssociationField::new('grade'),
            AssociationField::new('schools')->setFormTypeOptionIfNotSet('by_reference', false),
            BooleanField::new('featured')->renderAsSwitch()->setColumns(12),
            BooleanField::new('active')->renderAsSwitch()->setColumns(12),

            //AssociationField::new('createdBy')->hideOnForm(),
            DateField::new('createdAt')->hideOnForm(),

            FormField::addTab('items'),
            CollectionField::new('compositeItems')->onlyOnForms()->addCssClass('sortable')->setLabel(false)->useEntryCrudForm()->setEntryIsComplex()->renderExpanded(false)->setColumns(12),

            FormField::addTab('Images'),
            CollectionField::new('images', false)->addCssClass('sortable')->useEntryCrudForm()->setEntryIsComplex()->renderExpanded(false)->hideOnIndex(),

            FormField::addTab('Mandatory'),
            CollectionField::new('itemsMandatory', false)->useEntryCrudForm()->setEntryIsComplex()->renderExpanded(false)->allowAdd()->allowDelete()->hideOnIndex()->setColumns(12),

            FormField::addTab('SEO'),
            TextField::new('slug')->setFormTypeOption('disabled',true)->hideOnIndex()->hideWhenCreating()->setColumns(12),
            TextField::new('metaTitle')->hideOnIndex()->setColumns(12),
            TextField::new('metaKeywords')->hideOnIndex()->setColumns(12),
            TextareaField::new('metaDescription')->hideOnIndex()->setColumns(12),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityInstance->setComboProduct(true);
        $entityInstance->setComboProduct(true);
        parent::persistEntity($entityManager, $entityInstance);
    }
}
