<?php

namespace App\Repository;

use App\Entity\Package;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Package>
 */
class PackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Package::class);
    }

    /**
     * @return Package[]
     */
    public function findActivePackages(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.state = :state')
            ->setParameter('state', Package::STATE_ACTIVE)
            ->orderBy('p.price', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPopularPackage(): ?Package
    {
        return $this->createQueryBuilder('p')
            ->where('p.state = :state')
            ->andWhere('p.isPopular = :popular')
            ->setParameter('state', Package::STATE_ACTIVE)
            ->setParameter('popular', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByStripePriceId(string $priceId): ?Package
    {
        return $this->findOneBy(['stripePriceId' => $priceId]);
    }
}
