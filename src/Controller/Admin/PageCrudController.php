<?php

namespace App\Controller\Admin;

use App\Entity\Page;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use FOS\CKEditorBundle\Form\Type\CKEditorType;

class PageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Page::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_ADMIN')
            ->setEntityLabelInPlural('Pages')
            ->setDefaultSort(['name' => 'ASC'])
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
            //->setDefaultSort(['createdAt' => 'ASC'])
            ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $queryBuilder->andWhere('entity.public = true');
        return $queryBuilder;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addTab('General'),
            TextField::new('name'),
            TextField::new('title'),
            TextEditorField::new('body')->setFormType(CKEditorType::class),
            NumberField::new('position'),
            BooleanField::new('footerNav')->renderAsSwitch(),
            BooleanField::new('publish')->renderAsSwitch(),
            FormField::addTab('Advanced'),
            ImageField::new('featuredImage')->setCssClass('img-preview')->setBasePath($_ENV['BASE_UPLOAD_PATH'])->setUploadDir($_ENV['UPLOAD_DIR'])->setUploadedFileNamePattern('[randomhash].[extension]')->hideOnIndex(),
            CollectionField::new('sections')->useEntryCrudForm()->setEntryIsComplex()->renderExpanded(false)->hideOnIndex(),

            FormField::addTab('SEO'),
            TextField::new('slug')->setFormTypeOption('disabled',true)->onlyWhenUpdating(),
            TextField::new('metaTitle')->hideOnIndex(),
            TextField::new('metaKeywords')->hideOnIndex(),
            TextareaField::new('metaDescription')->hideOnIndex(),

        ];
    }
}
