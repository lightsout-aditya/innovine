<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserCrudController extends AbstractCrudController
{

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_SUPER_ADMIN')
            ->setDefaultSort(['lastLogin' => 'DESC'])
            ->setEntityLabelInPlural('Admins')
            ->setEntityLabelInSingular('Admin')
            ;
    }

    public function configureActions(Actions $actions): Actions {
        $user = $this->getUser();

        $impersonate = Action::new('impersonate', 'Impersonate', 'fa fa-fw fa-user-lock')
            ->linkToUrl(function (User $entity) {
                return '/?_switch_user='.$entity->getEmail();
            })
            ->displayIf(static function ($entity) use ($user){
                return $entity !== $user;
            })
        ;

        return $actions
            ->disable(Action::BATCH_DELETE)
            ->disable(Action::DELETE)
            ->add(Crud::PAGE_INDEX, $impersonate)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::INDEX, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('enabled')
            ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $queryBuilder
            ->andWhere('entity.id > 1')
            ->andWhere('entity.roles LIKE :role1 OR entity.roles LIKE :role2')
            ->setParameter('role1', '%_ADMIN%')
            ->setParameter('role2', '%_SALES%')
        ;
        return $queryBuilder;
    }

    public function configureFields(string $pageName): iterable
    {
        $profileDir = "/profile";
        $baseUploadPath = $_ENV['BASE_UPLOAD_PATH'].$profileDir;
        $uploadDir = $_ENV['UPLOAD_DIR'].$profileDir;

        return [
            FormField::addTab('Details'),
            FormField::addPanel('Personal')->addCssClass('col-sm-6'),
            TextField::new('firstName')->setRequired(true)->setColumns(12),
            TextField::new('lastName')->setRequired(true)->setColumns(12),
            EmailField::new('email')->setColumns(12),
            TelephoneField::new('mobile')->setColumns(12),
            //TextField::new('company')->setColumns(12),
            //TextField::new('pan')->setColumns(12),
            //TextField::new('gstNumber')->setColumns(12),

            FormField::addPanel('Authorization')->addCssClass('col-sm-6'),
            TextField::new('plainPassword')->setColumns(12)->setFormType(PasswordType::class)->setLabel('Password')->setRequired(Crud::PAGE_NEW === $pageName)->onlyOnForms(),
            ChoiceField::new('gender')->setColumns(12)->setChoices(array_flip(User::GENDERS))->hideOnIndex(),
            ImageField::new('avatar')->setColumns(12)->setCssClass('img-preview xs')->setFormTypeOptions(['attr' => ['data-path' => 'profile']])->setBasePath($baseUploadPath)->setUploadDir($uploadDir)->setUploadedFileNamePattern('[randomhash].[extension]'),
            DateTimeField::new('lastLogin')->hideOnForm(),
            TextField::new('role')->setTemplatePath('admin/_role.html.twig')->hideOnForm(),
            ChoiceField::new('role')->setRequired(true)->setChoices(array_flip(User::ROLES))->renderExpanded(false)->onlyOnForms(),
            BooleanField::new('emailVerified')->renderAsSwitch(false)->onlyOnIndex(),
            BooleanField::new('mobileVerified')->renderAsSwitch(false)->onlyOnIndex(),
            BooleanField::new('emailVerified')->setDisabled(!$this->isGranted('ROLE_SUPER_ADMIN'))->hideOnIndex(),
            BooleanField::new('mobileVerified')->setDisabled(!$this->isGranted('ROLE_SUPER_ADMIN'))->hideOnIndex(),
            BooleanField::new('enabled'),

            FormField::addTab('Addresses'),
            CollectionField::new('addresses', false)->useEntryCrudForm()->setEntryIsComplex()->renderExpanded(false)->allowAdd()->allowDelete()->hideOnIndex(),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityInstance->setRoles(['ROLE_ADMIN']);
        if($entityInstance->getPlainPassword()) {
            $entityInstance->setPassword($entityInstance->encodePassword($entityInstance->getPlainPassword()));
        }
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if($entityInstance->getPlainPassword()) {
            $entityInstance->setPassword($entityInstance->encodePassword($entityInstance->getPlainPassword()));
        }
        parent::updateEntity($entityManager, $entityInstance);
    }
}
