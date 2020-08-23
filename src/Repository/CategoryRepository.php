<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Ищем все категории по массиву id
     *
     * @param array $ids
     * @return Category[]
     */
    public function findByIds(array $ids): array
    {
        $query = $this->createQueryBuilder('c');
        $result = $query
            ->add('where', $query->expr()->andX(
                $query->expr()->in('c.id', $ids)
            ))
            ->getQuery()
            ->getResult()
            ;

        return $result;
    }

    /**
     * @param int $id
     * @return Category|null
     * @throws EntityNotFoundException
     */
    public function findById(int $id): ?Category
    {
        $product = $this->find($id);

        if (is_null($product)) {
            throw new EntityNotFoundException('Category not found');
        }

        return $product;
    }

    // /**
    //  * @return Category[] Returns an array of Category objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Category
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
