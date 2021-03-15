<?php

namespace Mcx\StripPacker\DTO;

/**
 * wrapper for a Box .. with position in 3d space
 *
 * 03/2021 created
 */
class PackedBox
{
    private Box $box;
    private int $x;
    private int $y;
    private int $z;

    /**
     * factory method
     *
     * 03/2021 created
     *
     * @param Box $box
     * @param int $x
     * @param int $y
     * @param int $z
     * @return PackedBox
     */
    public static function create(Box $box, int $x, int $y, int $z): PackedBox
    {
        $ret = new self();
        $ret->box = $box;
        $ret->x = $x;
        $ret->y = $y;
        $ret->z = $z;

        return $ret;
    }

    /**
     * @return int
     */
    public function getX(): int
    {
        return $this->x;
    }

    /**
     * @param int $x
     */
    public function setX(int $x): void
    {
        $this->x = $x;
    }

    /**
     * @return int
     */
    public function getY(): int
    {
        return $this->y;
    }

    /**
     * @param int $y
     */
    public function setY(int $y): void
    {
        $this->y = $y;
    }

    /**
     * @return int
     */
    public function getZ(): int
    {
        return $this->z;
    }

    /**
     * @param int $z
     */
    public function setZ(int $z): void
    {
        $this->z = $z;
    }

    /**
     * @return Box
     */
    public function getBox(): Box
    {
        return $this->box;
    }

    /**
     * @param Box $box
     */
    public function setBox(Box $box): void
    {
        $this->box = $box;
    }


}
