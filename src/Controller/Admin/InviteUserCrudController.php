<?php

namespace App\Controller\Admin;

use App\Entity\InviteUser;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;

class InviteUserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return InviteUser::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            EmailField::new('email'),
        ];
    }
}
