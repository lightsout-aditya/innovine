<?php

namespace App\Controller\Admin\Filter;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;

class AssociationFilter implements FilterInterface
{
    use FilterTrait;

    protected $tables;

    public static function new(string $propertyName, $label, $class): self
    {
        $filter = (new self());
        $tables = explode('.', $propertyName);

        return $filter
            ->setFilterFqcn(__CLASS__)
            ->setTables($tables)
            ->setProperty(str_replace('.','_',$propertyName))
            ->setLabel($label)
            ->setFormType(EntityFilterType::class)
            ->setFormTypeOption('value_type_options', [
                'class' => $class,
                'multiple' => false,
                'query_builder' => function (EntityRepository $repository) use ($class) {
                    return $repository
                        ->createQueryBuilder('c')
                        ->orderBy(str_contains($class, 'User') ? 'CONCAT(c.firstName, c.lastName, c.company, c.displayName)' : 'c.name', 'ASC')
                        ;
                }
            ])
            ;
    }

    public function setTables($tables)
    {
        $this->tables = $tables;
        return $this;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $comparison = $filterDataDto->getComparison();
        $parameterName = $filterDataDto->getParameterName();
        $parameterValue = $filterDataDto->getValue();
        //$em = $queryBuilder->getEntityManager();

        $table = "entity";
        for ($count=0; $count<count($this->tables)-1; $count++) {
            $idTable = substr($this->tables[$count], 0, 1) . "_" . $count;
            $baseQuery = $queryBuilder->getDQL();
            //var_dump($baseQuery, $idTable, $this->tables[$count]);
            if (!str_contains($baseQuery, $idTable)) {
                $queryBuilder->join($table . '.' . $this->tables[$count], $idTable);
            }
            $table = $idTable;
        }
        $property = $this->tables[$count];

        $queryBuilder
            ->andWhere(sprintf('%s.%s %s :%s', $table, $property, $comparison, $parameterName))
            ->setParameter($parameterName, $parameterValue);
    }
}
