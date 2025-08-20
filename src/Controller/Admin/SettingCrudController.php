<?php

namespace App\Controller\Admin;

use App\Entity\Setting;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class SettingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Setting::class;
    }

    public function configureActions(Actions $actions): Actions {
        return $actions
            ->disable(Action::INDEX, Action::NEW, Action::DELETE, Action::DETAIL)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN)
            ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Settings')
            ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig')
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $baseUploadPath = $_ENV['BASE_UPLOAD_PATH'];
        $uploadDir = $_ENV['UPLOAD_DIR'];

        return [
            FormField::addTab('General'),
            FormField::addPanel()->addCssClass('col-md-6'),
            ImageField::new('logo')->setCssClass('img-preview')->setBasePath($baseUploadPath)->setUploadDir($uploadDir)->setUploadedFileNamePattern('[randomhash].[extension]')->setColumns(12),
            ImageField::new('logoFooter')->setCssClass('img-preview')->setBasePath($baseUploadPath)->setUploadDir($uploadDir)->setUploadedFileNamePattern('[randomhash].[extension]')->setColumns(12),
            TextField::new('tagline')->setColumns(12),
            TextField::new('contactPhone')->setColumns(12),
            TextField::new('contactEmail')->setColumns(12),
            TextField::new('mapLink')->setColumns(12),
            TextEditorField::new('address')->setColumns(12),
            FormField::addPanel()->addCssClass('col-md-6'),
            ArrayField::new('invoiceCc', 'Invoice CC Emails')->setColumns(12),

            FormField::addTab('Homepage'),
            TextEditorField::new('homeBannerTitle', 'Banner Title'),
            TextEditorField::new('homeBannerDescription', 'Banner Description'),
            ImageField::new('homeBannerImage', 'Banner Image')->setCssClass('img-preview')->setBasePath($baseUploadPath)->setUploadDir($uploadDir)->setUploadedFileNamePattern('[randomhash].[extension]'),


            FormField::addTab('Trending Products'),
            TextEditorField::new('trendingProductsTitle', 'Title'),
            TextEditorField::new('trendingProductsDescription', 'Description'),

            FormField::addTab('Combined Kit'),
            TextEditorField::new('combinedKitTitle', 'Title'),
            TextEditorField::new('combinedKitDescription', 'Description'),

            FormField::addTab('How Works'),
            TextEditorField::new('howWorksTitle', 'Title'),
            TextEditorField::new('howWorksDescription', 'Description'),
            CollectionField::new('howWorks')->useEntryCrudForm()->setEntryIsComplex()->renderExpanded(false),

            FormField::addTab('Key Features'),
            CollectionField::new('keyFeatures')->useEntryCrudForm()->setEntryIsComplex()->renderExpanded(false),

            FormField::addTab('Contact Us'),
            TextEditorField::new('contactUSTitle', 'Title'),
            TextEditorField::new('contactUSDescription', 'Description'),
            ImageField::new('contactUSImage', 'Image')->setCssClass('img-preview')->setBasePath($baseUploadPath)->setUploadDir($uploadDir)->setUploadedFileNamePattern('[randomhash].[extension]'),
            TextField::new('contactUsMap', 'Map Link')->setColumns(12),

            FormField::addTab('Social'),
            FormField::addPanel('Social Link')->addCssClass('col-md-6'),
            UrlField::new('facebookLink')->setColumns(12),
            UrlField::new('instagramLink')->setColumns(12),
            UrlField::new('linkedinLink')->setColumns(12),

            FormField::addTab('SEO'),
            TextField::new('googleAnalyticsId'),
            TextField::new('googleTagManagerCode'),
            TextField::new('googleSiteVerificationCode'),
            TextField::new('metaTitle'),
            TextField::new('metaKeywords'),
            TextareaField::new('metaDescription'),
        ];
    }
}
