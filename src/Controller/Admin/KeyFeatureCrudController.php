<?php

namespace App\Controller\Admin;

use App\Entity\KeyFeature;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class KeyFeatureCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return KeyFeature::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $baseUploadPath = $_ENV['BASE_UPLOAD_PATH'];
        $uploadDir = $_ENV['UPLOAD_DIR'];

        return [
            TextField::new('title'),
            TextEditorField::new('description'),
            ImageField::new('icon')->setCssClass('img-preview')->setBasePath($baseUploadPath)->setUploadDir($uploadDir)->setUploadedFileNamePattern('[randomhash].[extension]'),
        ];
    }
}
