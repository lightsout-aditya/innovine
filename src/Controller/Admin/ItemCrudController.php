<?php

namespace App\Controller\Admin;

use App\Entity\Item;
use App\Services\ZohoService;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ItemCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ZohoService $zoho
    ){}

    public static function getEntityFqcn(): string
    {
        return Item::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_ADMIN')
            ->setEntityLabelInPlural('Items')
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
            //->disable(Action::NEW)
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
        $queryBuilder->andWhere('entity.comboProduct = false');
        return $queryBuilder;
    }

    public function createEntity(string $entityFqcn): Item
    {
        $item = new Item();
        $item->setUnit('pcs');
        return $item;
    }

    public function configureFields(string $pageName): iterable
    {
        //$isEdit = $pageName == Crud::PAGE_EDIT;
        $isEdit = false;
        return [
            FormField::addTab('Overview'),
            TextField::new('name'),
            TextareaField::new('description')->hideOnIndex(),
            ChoiceField::new('unit')->setChoices(['pcs' => 'pcs', 'kg' => 'kg', 'box' => 'box'])->setDisabled($isEdit)->hideOnIndex(),
            TextField::new('sku', 'SKU')->setRequired(true)->setDisabled($isEdit),
            
            // --- NEW ITEM CODE FIELD ---
            TextField::new('itemCode', 'Item Code')->setDisabled($isEdit),
            // ---------------------------
            
            TextField::new('hsnCode', 'HSN Code')->setDisabled($isEdit),
            NumberField::new('size')->setDisabled($isEdit)->hideOnIndex(),
            AssociationField::new('group')->setDisabled($isEdit),
            AssociationField::new('category')->setDisabled($isEdit),
            AssociationField::new('grade')->setDisabled($isEdit),
            AssociationField::new('schools')->setFormTypeOptionIfNotSet('by_reference', false)->setDisabled($isEdit),
            ChoiceField::new('gender')->setRequired(false)->setDisabled($isEdit)->setChoices(array_flip(Item::GENDER)),
            MoneyField::new('rate')->setDisabled($isEdit)->setCurrency('INR')->setStoredAsCents(false),
            MoneyField::new('purchaseRate')->setDisabled($isEdit)->setCurrency('INR')->setStoredAsCents(false)->hideOnIndex(),
            BooleanField::new('taxable')->setDisabled($isEdit)->renderAsSwitch(false),
            NumberField::new('actualAvailableForSaleStock', 'Stock')->onlyOnIndex()->setTextAlign('right'),
            BooleanField::new('showInPortal')->setDisabled($isEdit)->renderAsSwitch(),
            BooleanField::new('trending')->setDisabled($isEdit)->renderAsSwitch(),
            BooleanField::new('active')->setDisabled($isEdit)->renderAsSwitch(),

            AssociationField::new('createdBy')->hideOnForm()->hideOnIndex(),
            DateField::new('createdAt')->hideOnForm()->hideOnIndex(),

            FormField::addTab('Images'),
            CollectionField::new('images', false)->addCssClass('sortable')->useEntryCrudForm()->setEntryIsComplex()->renderExpanded(false)->hideOnIndex(),

            FormField::addTab('Tax'),
            AssociationField::new('intraTax')->setDisabled($isEdit)->hideOnIndex(),
            AssociationField::new('interTax')->setDisabled($isEdit)->hideOnIndex(),

            FormField::addTab('Stock'),
            FormField::addColumn('col', 'Summary'),
            NumberField::new('stockOnHand')->setDisabled()->hideOnIndex(),
            NumberField::new('availableStock')->setDisabled()->hideOnIndex(),
            NumberField::new('actualAvailableStock')->setDisabled()->hideOnIndex(),
            NumberField::new('committedStock')->setDisabled()->hideOnIndex(),
            NumberField::new('actualCommittedStock')->setDisabled()->hideOnIndex(),
            NumberField::new('availableForSaleStock')->setDisabled()->hideOnIndex(),
            NumberField::new('actualAvailableForSaleStock')->setDisabled()->hideOnIndex(),
            FormField::addColumn('col', 'Warehouses'),
            CollectionField::new('stocks', false)->useEntryCrudForm()->setEntryIsComplex()->renderExpanded(false)->allowAdd(false)->allowDelete(false)->hideOnIndex(),

            FormField::addTab('SEO'),
            TextField::new('slug')->setFormTypeOption('disabled',true)->hideOnIndex()->hideWhenCreating(),
            TextField::new('metaTitle')->hideOnIndex(),
            TextField::new('metaKeywords')->hideOnIndex(),
            TextareaField::new('metaDescription')->hideOnIndex(),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if($_ENV['ZOHO_SYNC']) {
            $entityInstance = $this->syncZoho($entityInstance);
        }
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if($_ENV['ZOHO_SYNC']) {
            $entityInstance = $this->syncZoho($entityInstance);
        }
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function syncZoho($entityInstance): Item
    {
        /** @var Item $entityInstance */
        $params = array_merge(array_filter([
            "unit" => $entityInstance->getUnit(),
            "item_type" => "inventory",
            "product_type" => "goods",
            "is_taxable" => $entityInstance->isTaxable(),
            "description" => $entityInstance->getDescription(),
            "name" => $entityInstance->getName(),
            "rate" => $entityInstance->getRate(),
            "purchase_rate" => $entityInstance->getPurchaseRate(),
            "sku" => $entityInstance->getSku(),
            "item_tax_preferences" => array_filter([
                array_filter([
                    "tax_id" => $entityInstance->getIntraTax()?->getZohoId(),
                    "tax_specification" => $entityInstance->getIntraTax()?->getSpecification()
                ]),
                array_filter([
                    "tax_id" => $entityInstance->getInterTax()?->getZohoId(),
                    "tax_specification" => $entityInstance->getInterTax()?->getSpecification()
                ])
            ]),
            "hsn_or_sac" => $entityInstance->getHsnCode(),
            "status" => $entityInstance->isActive() ? "active" : "inactive",
        ]),
        array_filter([
            "attribute_name1" => $entityInstance->getSize() ? 'Size' : null,
            "attribute_option_name1" => $entityInstance->getSize(),
        ])
        );

        $response = $entityInstance->getZohoId() ? $this->zoho->updateItem($entityInstance->getZohoId(), $params) : $this->zoho->createItem($params);
        if($item = $response['item']??null) {
            $entityInstance->setComboProduct(false);
            $entityInstance->setZohoId($item['item_id']);
            $entityInstance->setImported(true);

            #TODO: Remove once `onFlush` warning resolve
            try {
                if($itemGroup = $entityInstance->getGroup()){
                    $params = [
                        "group_name" => $itemGroup->getName(),
                        "items" => [
                            [
                                "item_id" => $entityInstance->getZohoId(),
                                "attribute_name1" => $entityInstance->getSize() ? 'Size' : null,
                                "attribute_option_name1" => $entityInstance->getSize(),
                            ]
                        ] ,
                    ];
                    $this->zoho->addToItemGroup($itemGroup->getZohoId(), $params);
                }else{
                    $this->zoho->unlinkItemGroup($entityInstance->getZohoId());
                }
            }catch (\Exception){}

            $this->addFlash('success', $response['message']);
        }

        return $entityInstance;
    }
}
