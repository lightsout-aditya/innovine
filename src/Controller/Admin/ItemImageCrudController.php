<?php

namespace App\Controller\Admin;

use App\Entity\ItemImage;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

class ItemImageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ItemImage::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            NumberField::new('position')->addCssClass('position-field d-none'),
            ImageField::new('image')->setFileConstraints([])->setCssClass('img-preview')->setBasePath($_ENV['BASE_UPLOAD_PATH'])->setUploadDir($_ENV['UPLOAD_DIR'])->setUploadedFileNamePattern('[randomhash].[extension]')->hideOnIndex(),
        ];
    }
}
