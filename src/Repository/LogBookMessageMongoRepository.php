<?php
/**
 * User: Andrey Shamis
 * Date: 16/12/18
 * Time: 11:21
 */

namespace App\Repository;

use App\Document\LogBookMessageMongo;
use Doctrine\ODM\MongoDB\DocumentRepository;

class LogBookMessageMongoRepository extends DocumentRepository
{

    /**
     * @param array $criteria
     * @param bool $flush
     * @return LogBookMessageMongo
     */
    public function create(array $criteria, $flush = true): LogBookMessageMongo
    {
        $entity = new LogBookMessageMongo();
        $entity->setMessage($criteria['message']);
        $entity->setChain($criteria['chain']);
        $entity->setMsgType($criteria['msgType']->getId());
        $entity->setLogTime($criteria['logTime']);
        $entity->setTest($criteria['test']->getId());
        $this->dm->persist($entity);
        if ($flush) {
            $this->dm->flush($entity);
        }
        return $entity;
    }
}