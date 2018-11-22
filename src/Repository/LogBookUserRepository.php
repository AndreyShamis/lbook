<?php

namespace App\Repository;

use App\Entity\LogBookUser;
use App\Utils\RandomString;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LogBookUserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, LogBookUser::class);
    }

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
    public function loadUserByUsername($username): ?UserInterface
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
     * @throws \Doctrine\ORM\ORMException
     */
    public function create(array $criteria): LogBookUser
    {
        $criteria['username'] = strtolower($criteria['username']);
        $criteria['email'] = strtolower($criteria['email']);
        $entity = $this->findOneBy(array('username' => $criteria['username']));
        $entity_email = null;
        if ($entity === null) {
            $entity_email = $this->findOneBy(array('email' => $criteria['email']));
        }

        if (null === $entity) {
            if ($entity_email !== null) {
                // User added but not contains all fields - need to Update
                $entity = $entity_email;
            } else {
                $entity = new LogBookUser();
            }
            $entity->setUsername($criteria['username']);
            $entity->setEmail($criteria['email']);
            $entity->setFullName($criteria['fullName']);
            $entity->setLastName($criteria['lastName']);
            $entity->setFirstName($criteria['firstName']);
            $entity->setAnotherId($criteria['anotherId']);
            $entity->setMobile($criteria['mobile']);
            $entity->setIsLdapUser($criteria['ldapUser']);
            $entity->setPassword($criteria['dummyPassword']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }

        return $entity;
    }

    /**
     * @param $email
     * @param $fullName
     * @return LogBookUser
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createByEmail($email, $fullName): LogBookUser
    {
        $criteria = array();

        $nameArray = explode(' ', $fullName);
        $firstName = array_shift($nameArray);
        $lastName = implode(' ', $nameArray);
        $criteria['username'] = trim($fullName);
        $criteria['email'] = strtolower(trim($email));
        $criteria['fullName'] = trim($fullName);
        $criteria['lastName'] = trim($lastName);
        $criteria['firstName'] = trim($firstName);
        $criteria['dummyPassword'] = RandomString::generateRandomString(20);

        $entity = $this->findOneBy(array('email' => $criteria['email']));

        if (null === $entity) {
            $entity = new LogBookUser();
            $entity->setUsername($criteria['username']);
            $entity->setEmail($criteria['email']);
            $entity->setFullName($criteria['fullName']);
            $entity->setLastName($criteria['lastName']);
            $entity->setFirstName($criteria['firstName']);
            $entity->setPassword($criteria['dummyPassword']);
            $this->_em->persist($entity);
            $this->_em->flush($entity);
        }

        return $entity;
    }
}
