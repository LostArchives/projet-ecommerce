<?php

namespace AppBundle\Repository;
use AppBundle\Entity\Product;

/**
 * ProductRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProductRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Returns the N last active products
     *
     * @param null $limit
     */
    public function getLastActiveProducts($limit = null)
    {
        return $this->findBy(
            ['status' => true],
            ['id' => 'DESC'],
            $limit
        );
    }

    public function getRandomProducts(Product $product)
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.id <> :id')
            ->setParameter('id', $product->getId())
            ->andWhere('p.category = :category')
            ->setParameter('category', $product->getCategory())
            ->andWhere('p.status = true')
        ;

        return $qb->getQuery()->getResult();
    }

    public function getMostViewedProducts($product, $limit)
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.id <> :id')
            ->setParameter('id', $product->getId())
            ->orderBy('p.hits', 'desc')
            ->setMaxResults($limit)
            ->andWhere('p.status = true')
        ;

        return $qb->getQuery()->getResult();
    }
}
