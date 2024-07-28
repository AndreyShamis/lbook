<?php
/**
 * User: Andrey Shamis
 * email: lolnik@gmail.com
 * Date: 28/07/24
 * Time: 13:54
 */

namespace App\Service;

use App\Entity\LogBookTest;

class LogBookService
{
    public function getUniqueKeys($tests): array
    {
        $uniqueKeys = [];
        /** @var LogBookTest $test */
        foreach ($tests as $test) {
            $metaData = $test->getNewMetaData();
            if ($metaData) {
                foreach ($metaData->getValue() as $key => $value) {
                    $uniqueKeys[$key] = true;
                }
            }
        }

        return $uniqueKeys;
    }
}
