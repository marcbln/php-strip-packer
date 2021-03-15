<?php

namespace Mcx\StripPacker\Service;

use Mcx\StripPacker\DTO\Box;
use Mcx\StripPacker\DTO\PackedBox;
use Assert\Assertion;
use Mcx\StripPacker\Util\UtilSort;

/**
 * 03/2021 created
 */
class StripPacker
{
    /**
     * @var Box[]
     */
    private array $remainingBoxes;

    /**
     * @var PackedBox[]
     */
    private array $packedBoxes;


    /**
     *    The PH heuristic for the Strip Packing Problem. This is the RG variant, which means that rotations by
     *    90 degrees are allowed and that there is a guillotine constraint.
     *
     *    Parameters
     *    ----------
     *    width
     *        The width of the strip.
     *
     *    rectangles
     *        List of list containing width and height of every rectangle, [[w_1, h_1], ..., [w_n,h_h]].
     *        It is assumed that all rectangles can fit into the strip.
     *
     *    sorting : string, {'width', 'height'}, default='width'
     *        The heuristic uses sorting to determine which rectangles to place first.
     *        By default sorting happens on the width but can be changed to height.
     *
     *    Returns
     *    -------
     *    height
     *        The height of the strip needed to pack all the items.
     *    rectangles : list of namedtuple('Rectangle', ['x', 'y', 'w', 'h'])
     *        A list of rectangles, in the same order as the input list. This contains bottom left x and y coordinate and
     *        the width and height (which can be flipped compared to input).
     *
     * 03/2021 created
     *
     * @param int $containerWidth
     * @param Box[] $boxes
     * @param string $sortBy
     */
    public function pack(int $containerWidth, array $boxes, array $sortBy)
    {
        $this->remainingBoxes = $boxes; // remaining = deepcopy(rectangles)
        Assertion::allInArray(array_keys($sortBy), ['width', 'depth', 'footprintArea', 'footprintCircumference', 'longestFootprintEdge'], 'The algorithm only supports sorting by width, depth, footprintArea, footprintCircumference, longestFootprintEdge');

        $this->packedBoxes = [];  //    result = [None] * len(rectangles)

        foreach ($this->remainingBoxes as $idx => $rect) { //    for idx, firstBox in enumerate(remaining):
            if ($rect->getWidth() > $rect->getDepth()) {//        if firstBox->getWidth() > firstBox->getDepth():
                $rect->rotateFootprint90Degrees(); //            remaining[idx][0], remaining[idx][1] = remaining[idx][1], remaining[idx][0]
            }
        }
        // UtilDebug::d('Swapped some widths and depths with the following result', $this->remainingBoxes);

        $this->remainingBoxes = UtilSort::multisortDocuments($this->remainingBoxes, $sortBy);
        // UtilDebug::d('The sorted array is: ', $this->remainingBoxes);

        [$x, $y, $w, $h, $DEPTH] = [0, 0, 0, 0, 0];
        while (!empty($this->remainingBoxes)) {
            $firstBox = array_shift($this->remainingBoxes);
            // UtilDebug::d("next box at next level..." . $firstBox->getFootprintAsString());
            if ($firstBox->getDepth() <= $containerWidth) {
                // UtilDebug::d("rotate...");
                $firstBox->rotateFootprint90Degrees();
            }
            $this->packedBoxes[] = PackedBox::create($firstBox, $x, $y, 0);
            [$x, $y, $w, $h, $DEPTH] = [$firstBox->getWidth(), $DEPTH, $containerWidth - $firstBox->getWidth(), $firstBox->getDepth(), $DEPTH + $firstBox->getDepth()];

            $this->recursive_packing($x, $y, $w, $h);
            [$x, $y] = [0, $DEPTH];
        }

        return [$this->packedBoxes, $DEPTH];
    }

    /**
     *  Helper function to recursively fit a certain area.
     *    D = 0 (no rotation allowed) or 1 (rotation allowed)
     *    remaining = list or ALL boxes
     */

    private function recursive_packing($x, $y, $w, $h)
    {
        $priority = 6;
        # for each unplaced item...
        $wd = ['width', 'depth'];
        foreach ($this->remainingBoxes as $idx => $box) { //    for idx in indices:
            foreach ([0, 1] as $orientation) { // assuming D==1 //        for orientation in range(0, D + 1):
                if ($priority > 1 && $box->getLengthByName($wd[(0 + $orientation) % 2]) == $w && $box->getLengthByName($wd[(1 + $orientation) % 2]) == $h) {
                    [$priority, $bestOrientation, $bestIdx] = [1, $orientation, $idx];
                    break; // we found a perfect match
                } elseif ($priority > 2 and $box->getLengthByName($wd[(0 + $orientation) % 2]) == $w and $box->getLengthByName($wd[(1 + $orientation) % 2]) < $h) {
                    [$priority, $bestOrientation, $bestIdx] = [2, $orientation, $idx];
                } elseif ($priority > 3 and $box->getLengthByName($wd[(0 + $orientation) % 2]) < $w and $box->getLengthByName($wd[(1 + $orientation) % 2]) == $h) {
                    [$priority, $bestOrientation, $bestIdx] = [3, $orientation, $idx];
                } elseif ($priority > 4 and $box->getLengthByName($wd[(0 + $orientation) % 2]) < $w and $box->getLengthByName($wd[(1 + $orientation) % 2]) < $h) {
                    [$priority, $bestOrientation, $bestIdx] = [4, $orientation, $idx];
                } elseif ($priority > 5) {
                    // nothing found
                    [$priority, $bestOrientation, $bestIdx] = [5, $orientation, $idx];
                }
            }
        }

        if ($priority < 5) {

            // ---- add best box to result
            $bestBox = $this->remainingBoxes[$bestIdx];
            if ($bestOrientation == 1) {
                $bestBox->rotateFootprint90Degrees();
            }

            $this->packedBoxes[] = PackedBox::create($bestBox, $x, $y, 0);
            unset($this->remainingBoxes[$bestIdx]); //        indices.remove(bestIdx)


            if ($priority == 1) {
                // fitted perfectly - we are done
            } elseif ($priority == 2) {
                $this->recursive_packing($x, $y + $bestBox->getDepth(), $w, $h - $bestBox->getDepth());
            } elseif ($priority == 3) {
                $this->recursive_packing($x + $bestBox->getWidth(), $y, $w - $bestBox->getWidth(), $h);
            } elseif ($priority == 4) {
                // -- find minWidth / minDepth of remaining boxes
                $minLengthOfRemaining = PHP_INT_MAX; // sys.maxsize
                foreach ($this->remainingBoxes as $box) {
                    $minLengthOfRemaining = min($minLengthOfRemaining, $box->getWidth(), $box->getDepth());
                }
                if ($w - $bestBox->getWidth() < $minLengthOfRemaining) {
                    $this->recursive_packing($x, $y + $bestBox->getDepth(), $w, $h - $bestBox->getDepth());
                } elseif ($h - $bestBox->getDepth() < $minLengthOfRemaining) {
                    $this->recursive_packing($x + $bestBox->getWidth(), $y, $w - $bestBox->getWidth(), $h);
                } elseif ($bestBox->getWidth() < $minLengthOfRemaining) {
                    $this->recursive_packing($x + $bestBox->getWidth(), $y, $w - $bestBox->getWidth(), $bestBox->getDepth());
                    $this->recursive_packing($x, $y + $bestBox->getDepth(), $w, $h - $bestBox->getDepth());
                } else {
                    $this->recursive_packing($x, $y + $bestBox->getDepth(), $bestBox->getWidth(), $h - $bestBox->getDepth());
                    $this->recursive_packing($x + $bestBox->getWidth(), $y, $w - $bestBox->getWidth(), $h);
                }
            }
        }
    }

}




