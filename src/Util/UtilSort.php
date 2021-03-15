<?php

namespace Mcx\StripPacker\Util;

/**
 * 03/2021 created
 */
class UtilSort
{


    /**
     * private helper
     *
     * 03/2021 created
     *
     * @param array $documents
     * @param string $name
     * @return array
     */
    private static function _getDocumentsField(array &$documents, string $name)
    {
        $getterName = 'get' . ucfirst($name);

        return array_map(function ($doc) use ($getterName) {
            return $doc->$getterName();
        }, $documents);
    }


    /**
     * 03/2021 created
     *
     * @param array $documents
     * @param array $sortBy, eg: ['width' => SORT_DESC, 'depth' => SORT_DESC]
     * @return array the array sorted
     * @throws \Exception
     */
    public static function multisortDocuments(array &$documents, array $sortBy)
    {
        $funArgs = [];
        foreach ($sortBy as $columnName => $sortOrder) {
            $funArgs[] = self::_getDocumentsField($documents, $columnName);
            $funArgs[] = $sortOrder;
        }
        $funArgs[] = &$documents;

        if (call_user_func_array('array_multisort', $funArgs) === FALSE) {
            throw new \Exception("multisort failed");
        }

        return $documents;
    }


}
