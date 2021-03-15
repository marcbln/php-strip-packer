<?php
namespace Mcx\StripPacker\Util;

use Assert\Assertion;

/**
 * 03/2021 created
 */
class UtilStringSize
{

    /**
     * 03/2021 created
     *
     * @param string $dimensions
     * @param int $num
     * @return int[]
     * @throws \Assert\AssertionFailedException
     */
    public static function explodeInt(string $dimensions, int $num): array
    {
        $exploded = array_map('intval', explode('x', $dimensions));
        Assertion::eq(count($exploded), $num);

        return $exploded;
    }
}
