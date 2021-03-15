<?php

namespace Mcx\StripPacker\DTO;

use Mcx\StripPacker\Util\UtilStringSize;
use Assert\Assertion;

class Box
{
    private int $width;
    private int $depth;
    private int $height;
    private string $originalDimensions;

    /**
     * private constructor, use factory methods createFromXXX()
     */
    private function __construct()
    {
    }


    /**
     * factory method
     *
     * 03/2021 created
     *
     * @param string $dimensions Format: LxWxH eg "60x40x20"
     * @return static
     * @throws \Assert\AssertionFailedException
     */
    public static function createFromString(string $dimensions): self
    {
        $ret = new self();
        [$ret->width, $ret->depth, $ret->height] = UtilStringSize::explodeInt($dimensions, 3);
        $ret->originalDimensions = $dimensions;

        return $ret;
    }

    /**
     * factory method
     *
     * 03/2021 created
     *
     * @param array $array
     * @return Box
     */
    public static function createFromAssocArray(array $array): self
    {
        Assertion::allInArray(array_keys($array), ['width', 'depth', 'height']);

        $ret = new self();
        $ret->width = $array['width'];
        $ret->depth = $array['depth'];
        $ret->height = $array['height'];

        $ret->originalDimensions = $ret->getAsString();

        return $ret;
    }

    private static function _swap(int &$l1, int &$l2)
    {
        $t = $l1;
        $l1 = $l2;
        $l2 = $t;
    }


    /**
     * swap width and depth
     *
     * 03/2021 created
     *
     */
    public function rotateFootprint90Degrees()
    {
        self::_swap($this->width, $this->depth);
    }


    /**
     * Rotate box to largest footprint
     */
    public function rotateLargestSurfaceToBottom()
    {
        $bottom = $this->width * $this->depth;
        $left = $this->height * $this->depth;
        $front = $this->width * $this->height;

        if ($bottom >= $left && $bottom >= $front) {
            // no rotate
        } elseif ($left > $front) {
            self::_swap($this->height, $this->width);
        } else {
            self::_swap($this->height, $this->depth);
        }
    }


    /**
     * 03/2021 created
     *
     * @param int $maxHeight
     * @return bool true if a solution was found, false otherwise
     */
    public function rotateSmallestSurfaceToBottomWithHeightConstraint(int $maxHeight): bool
    {
        $bottom = $this->width * $this->depth;
        $left = $this->height * $this->depth;
        $front = $this->width * $this->height;

        if ($bottom <= $left && $bottom <= $front && $this->height <= $maxHeight) {
            // no rotate
            return true;
        } elseif ($left < $front && $this->width <= $maxHeight) {
            self::_swap($this->height, $this->width);
            return true;
        } elseif ($this->depth <= $maxHeight) {
            self::_swap($this->height, $this->depth);
            return true;
        } else {
            return false;
        }

    }


    public function getVolume(): int
    {
        return $this->depth * $this->width * $this->height;
    }

    public function getSurfaceAreaBottom(): int
    {
        return $this->width * $this->depth;
    }

    public function getBiggestSurfaceArea(): int
    {
        return max($this->width * $this->depth, $this->width * $this->height, $this->depth * $this->height);
    }


    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    /**
     * @param int $depth
     */
    public function setDepth(int $depth): void
    {
        $this->depth = $depth;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth(int $width): void
    {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @param int $height
     */
    public function setHeight(int $height): void
    {
        $this->height = $height;
    }

    /**
     * 03/2021 created
     *
     * @return int[]
     */
    public function getAsArray(): array
    {
        return [$this->width, $this->depth, $this->height];
    }

    /**
     * 03/2021 created
     *
     * @return int[]
     */
    public function getFootprintAsArray(): array
    {
        return [$this->width, $this->depth];
    }

    public function getAsString(): string
    {
        return implode('x', $this->getAsArray());
    }

    /**
     * for svg visualisation
     */
    public function getFootprintAsString(): string
    {
        return implode('x', $this->getFootprintAsArray());
    }

    public function getFootprintArea(): int
    {
        return $this->width * $this->depth;
    }

    public function getFootprintCircumference(): int
    {
        return 2 * ($this->width + $this->depth);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getMinLength(): int
    {
        return min($this->width, $this->depth, $this->height);
    }

    public function getLengthByName(string $name): int
    {
        Assertion::inArray($name, ['width', 'depth', 'height']);

        return $this->$name;
    }

}
