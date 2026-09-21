<?php

namespace App\Repository;

use App\Entity\Produits;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produits>
 */
class ProduitsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produits::class);
    }

    /**
     * @return string[]
     */
    public function findDistinctValues(string $field): array
    {
        $allowedFields = ['forme', 'matiere', 'couleur'];
        if (!in_array($field, $allowedFields, true)) {
            throw new \InvalidArgumentException('Filtre produit invalide.');
        }

        $rows = $this->createQueryBuilder('p')
            ->select(sprintf('DISTINCT p.%s AS value', $field))
            ->andWhere(sprintf('p.%s IS NOT NULL', $field))
            ->andWhere(sprintf('p.%s <> :empty', $field))
            ->setParameter('empty', '')
            ->orderBy(sprintf('p.%s', $field), 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_column($rows, 'value')));
    }
    

    //    /**
    //     * @return Produits[] Returns an array of Produits objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Produits
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
