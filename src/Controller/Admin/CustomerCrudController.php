<?php

namespace App\Controller\Admin;

use App\Entity\InviteUser;
use App\Entity\User;
use App\Services\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
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
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;


class CustomerCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly MessageService $messageService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ){}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_SALES')
            ->setDefaultSort(['lastLogin' => 'DESC'])
            ->setEntityLabelInPlural('Customers')
            ->setEntityLabelInSingular('Customer')
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

        $invite = Action::new('invite')
            ->linkToCrudAction('invite')
            ->setLabel('Invite')
            ->setTemplatePath('admin/invite.html.twig')
            ->createAsGlobalAction()
        ;

        return $actions
            ->disable(Action::BATCH_DELETE)
            ->disable(Action::DELETE)
            ->add(Crud::PAGE_INDEX, $impersonate)
            ->add(Crud::PAGE_INDEX, $invite)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::INDEX, 'ROLE_SALES')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission('impersonate', 'ROLE_SUPER_ADMIN')
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
            ->andWhere('entity.roles LIKE :role1 OR entity.roles LIKE :role2')
            ->setParameter('role1', '%[]%')
            ->setParameter('role2', '%ROLE_USER%')
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
            TextField::new('company')->setColumns(12),
            TextField::new('location')->setColumns(12),
            TextField::new('pan')->setColumns(12)->hideOnIndex(),
            TextField::new('gstNumber')->setColumns(12)->hideOnIndex(),
            AssociationField::new('school')
    ->setLabel('School')
    ->setColumns(12),


            FormField::addPanel('Authorization')->addCssClass('col-sm-6'),
            TextField::new('plainPassword')->setColumns(12)->setFormType(PasswordType::class)->setLabel('Password')->setRequired(Crud::PAGE_NEW === $pageName)->onlyOnForms(),
            ChoiceField::new('gender')->setColumns(12)->setChoices(array_flip(User::GENDERS))->hideOnIndex(),
            ImageField::new('avatar')->setColumns(12)->setCssClass('img-preview xs')->setFormTypeOptions(['attr' => ['data-path' => 'profile']])->setBasePath($baseUploadPath)->setUploadDir($uploadDir)->setUploadedFileNamePattern('[randomhash].[extension]'),
            DateTimeField::new('lastLogin')->hideOnForm(),
            //TextField::new('role')->setTemplatePath('admin/_role.html.twig')->hideOnForm(),
            //ChoiceField::new('role')->setRequired(true)->setChoices(array_flip(User::ROLES))->renderExpanded(false)->onlyOnForms(),
            BooleanField::new('emailVerified')->renderAsSwitch(false)->onlyOnIndex(),
            BooleanField::new('mobileVerified')->renderAsSwitch(false)->onlyOnIndex()->hideOnIndex(),
            BooleanField::new('emailVerified')->setDisabled(!$this->isGranted('ROLE_ADMIN'))->hideOnIndex(),
            BooleanField::new('mobileVerified')->setDisabled(!$this->isGranted('ROLE_ADMIN'))->hideOnIndex(),
            BooleanField::new('enabled'),
            BooleanField::new('offlinePayment')->setDisabled(!$this->isGranted('ROLE_ADMIN'))->onlyWhenUpdating(),

            FormField::addTab('Addresses'),
            CollectionField::new('addresses', false)->useEntryCrudForm()->setEntryIsComplex()->renderExpanded(false)->allowAdd()->allowDelete()->hideOnIndex(),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityInstance->setRoles(['ROLE_USER']);
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

    public function invite(AdminContext $context): RedirectResponse
    {
        $request = $context->getRequest();
        $em = $this->container->get('doctrine')->getManager();
        if($email = $request->get('email')){
            $user = $em->getRepository(InviteUser::class)->findOneBy(['email' => $email]);
            if (!$user){
                $user = new InviteUser();
                $user->setEmail($email);
            }
            $token = sha1(time().rand().time());
            $user->setToken($token);
            $em->persist($user);
            $em->flush();
            $success = true;

            $signLink = $this->urlGenerator->generate('signup', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
            $templateName = 'on-invite';
            $params = [
                'EMAIL' => strtolower($email),
                'SIGNUP_URL' => $signLink,
            ];
            $this->messageService->sendMailTemplate($templateName, $params, null, $email);

            $this->addFlash($success ? 'success' : 'danger', 'Invitation sent successfully.');
        }
        return $this->redirect($this->adminUrlGenerator->setAction(Action::INDEX)->unset(EA::ENTITY_ID)->generateUrl());
    }
}
