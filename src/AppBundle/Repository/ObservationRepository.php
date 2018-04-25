<?php

namespace AppBundle\Repository;

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * ObservationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ObservationRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Get observations by bird name
     *
     * @param $name
     * @return array
     */
    public function byNomCourant($espece)
    {
        return $this->createQueryBuilder('o')
            ->select('o.id', 'o.date', 'o.latitude', 'o.longitude', 'o.bird')
            ->where('o.espece LIKE :name')
            ->setParameter('name', '%'.$espece.'%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get validated observations by bird id
     *
     * @param $birdId
     * @return array
     */
    public function byBirdId($birdId)
    {
        return $this->createQueryBuilder('o')
            ->where('o.bird = :id', 'o.validation = 1')
            ->setParameter('id', $birdId)
            ->getQuery()
            ->getResult();
    }


    /**
     * Get only validated observations
     *
     * @return array
     */
    public function findAllValidatedBirds($page=1, $maxperpage=10)
    {
        $q = $this->createQueryBuilder('o')
            ->where('o.validation = 1')
            ->orderBy('o.date', 'DESC');
            /*
            ->getQuery()
            ->getResult();
            */
        $q->setFirstResult(($page-1) * $maxperpage)->setMaxResults($maxperpage);

        return new Paginator($q);
    }

    /**
     * Get only validated observations
     *
     * @return array
     */
    public function findAllNoValidatedBirds()
    {
        return $this->createQueryBuilder('o')
            ->where('o.validation = 0')
            ->getQuery()
            ->getResult();
    }
}
