<?php

namespace App\Controller\Admin;

use App\Entity\PageSection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PageSectionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PageSection::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextEditorField::new('heading')->setNumOfRows(1),
            TextField::new('title'),
            TextField::new('subtitle'),
            ImageField::new('image')->setCssClass('img-preview')->setBasePath($_ENV['BASE_UPLOAD_PATH'])->setUploadDir($_ENV['UPLOAD_DIR'])->setUploadedFileNamePattern('[randomhash].[extension]'),
            TextEditorField::new('description'),
        ];
    }
}
