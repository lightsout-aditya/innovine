<?php

namespace App\Controller\Admin;

use App\Entity\Coupon;
use App\Entity\User;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CouponCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Coupon::class;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('createdBy')
            ->add('active')
            ;
    }

    public function configureActions(Actions $actions): Actions {
        return $actions
            ->disable('delete')
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        if(!$isAdmin) {
            $queryBuilder
                ->andWhere('entity.createdBy = :current')
                ->setParameter('current', $this->getUser());
        }
        return $queryBuilder;
    }

    private function generate(): string
    {
        $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $em = $this->container->get('doctrine')->getManager();
        while(true) {
            $code = "";
            for ($i = 0; $i < 10; $i++) {
                $code .= $chars[mt_rand(0, strlen($chars)-1)];
            }
            $coupon = $em->getRepository(Coupon::class)->findOneBy(['code' => $code]);
            if(!$coupon){
                break;
            }
        }
        return $code;
    }

    public function createEntity(string $entityFqcn)
    {
        $entity = new Coupon();
        $entity->setCode($this->generate());
        /*if($type = $this->getUser()->getType()){
            $maxDiscountPercent = User::COUPON_PCT[$type]??null;
            $entity->setMaxDiscount($maxDiscountPercent);
        }*/
        $entity->setMaxDiscount(50);
        return $entity;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Coupon')
            ->setEntityLabelInPlural('Coupons')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $new = Crud::PAGE_NEW === $pageName;
        return [
            TextField::new('code')->setDisabled(!$new)->setFormTypeOption('attr', ['readonly' => true]),
            PercentField::new('discountPercent')->setStoredAsFractional(false)->setNumDecimals(2)->setDisabled(!$new),
            TextareaField::new('description')->hideOnIndex(),
            NumberField::new('maxLimit')->setDisabled(),
            NumberField::new('usage')->setDisabled()->hideOnForm(),
            TextField::new('allowedEmail')->setDisabled(!$new),
            TextField::new('allowedMobile')->setDisabled(!$new),
            DateField::new('createdAt')->onlyOnIndex(),
            AssociationField::new('createdBy')->onlyOnIndex(),
            BooleanField::new('active')->renderAsSwitch(false),
        ];
    }
}
