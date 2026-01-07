<?php

namespace App\Repository;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    public function findActiveSubscriptionForUser(User $user): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.status = :status')
            ->andWhere('s.endDate > :now')
            ->setParameter('user', $user)
            ->setParameter('status', Subscription::STATUS_ACTIVE)
            ->setParameter('now', new \DateTime())
            ->orderBy('s.endDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Subscription[]
     */
    public function findAllSubscriptionsForUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStripeSubscriptionId(string $stripeSubscriptionId): ?Subscription
    {
        return $this->findOneBy(['stripeSubscriptionId' => $stripeSubscriptionId]);
    }

    public function findByStripePaymentIntentId(string $paymentIntentId): ?Subscription
    {
        return $this->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);
    }

    /**
     * @return Subscription[]
     */
    public function findExpiringSubscriptions(int $daysBeforeExpiry = 7): array
    {
        $now = new \DateTime();
        $expiryDate = (new \DateTime())->modify("+{$daysBeforeExpiry} days");

        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.endDate BETWEEN :now AND :expiry')
            ->setParameter('status', Subscription::STATUS_ACTIVE)
            ->setParameter('now', $now)
            ->setParameter('expiry', $expiryDate)
            ->getQuery()
            ->getResult();
    }
}
