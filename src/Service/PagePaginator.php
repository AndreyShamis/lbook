<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 23/03/18
 * Time: 15:03
 */

namespace App\Service;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PagePaginator
{
    /**
     * Paginator Helper
     *
     * Pass through a query object, current page & limit
     * the offset is calculated from the page and limit
     * returns an `Paginator` instance, which you can call the following on:
     *
     *     $paginator->getIterator()->count() # Total fetched (ie: `5` posts)
     *     $paginator->count() # Count of ALL posts (ie: `20` posts)
     *     $paginator->getIterator() # ArrayIterator
     *
     * @param Query|QueryBuilder $dql  A Doctrine ORM query or query builder.
     * @param integer            $page  Current page (defaults to 1)
     * @param integer            $limit The total number per page (defaults to 5)
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function paginate($dql, $page = 1, $limit = 20): Paginator
    {
        $paginator = new Paginator($dql);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1)) // Offset
            //->useQueryCache(true) // Defines whether the query should make use of a query cache, if available.
            //->useResultCache(true) // Set whether or not to cache the results of this query and if so, for how long and which ID to use for the cache entry.
            ->setMaxResults($limit); // Limit
        return $paginator;
    }
}