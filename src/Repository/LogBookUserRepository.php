<?php

namespace App\Repository;

use App\Entity\LogBookUser;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LogBookUserRepository extends EntityRepository implements UserLoaderInterface
{
//    public function __construct(RegistryInterface $registry)
//    {
//        parent::__construct($registry, LogBookUser::class);
//    }

    /**
     * Loads the user for the given username.
     *
     * This method must return null if the user is not found.
     *
     * @param string $username The username
     *
     * @return UserInterface|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function loadUserByUsername($username)
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array $criteria
     * @return LogBookUser
     */
    public function create(array $criteria)
    {
        $criteria['username'] = strtoupper($criteria['username']);

        $entity = $this->findOneBy(array("username" => $criteria['username']));

        if (null === $entity) {
            $entity = new LogBookUser();
            $entity->setUsername($criteria['username']);
            $entity->setEmail($criteria['email']);
            $entity->setFullName($criteria['fullName']);
            $entity->setLastName($criteria['lastName']);
            $entity->setFirstName($criteria['firstName']);
            $entity->setAnotherId($criteria['anotherId']);
            $entity->setMobile($criteria['mobile']);
            $entity->setIsLdapUser($criteria['ldapUser']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }

        return $entity;
    }
}
