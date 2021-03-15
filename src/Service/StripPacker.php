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
     * @param int $width
     * @param Box[] $boxes
     * @param string $sortBy
     */
    public function pack(int $width, array $boxes, array $sortBy)
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
            if ($firstBox->getDepth() <= $width) {
                // UtilDebug::d("rotate...");
                $firstBox->rotateFootprint90Degrees();
            }
            $this->packedBoxes[] = PackedBox::create($firstBox, $x, $y, 0);
            [$x, $y, $w, $h, $DEPTH] = [$firstBox->getWidth(), $DEPTH, $width - $firstBox->getWidth(), $firstBox->getDepth(), $DEPTH + $firstBox->getDepth()];

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
            foreach ([0, 1] as $j) { // assuming D==1 //        for j in range(0, D + 1):
                if ($priority > 1 && $box->getLengthByName($wd[(0 + $j) % 2]) == $w && $box->getLengthByName($wd[(1 + $j) % 2]) == $h) {
                    [$priority, $orientation, $bestIdx] = [1, $j, $idx];
                    break; // we found a perfect match
                } elseif ($priority > 2 and $box->getLengthByName($wd[(0 + $j) % 2]) == $w and $box->getLengthByName($wd[(1 + $j) % 2]) < $h) {
                    [$priority, $orientation, $bestIdx] = [2, $j, $idx];
                } elseif ($priority > 3 and $box->getLengthByName($wd[(0 + $j) % 2]) < $w and $box->getLengthByName($wd[(1 + $j) % 2]) == $h) {
                    [$priority, $orientation, $bestIdx] = [3, $j, $idx];
                } elseif ($priority > 4 and $box->getLengthByName($wd[(0 + $j) % 2]) < $w and $box->getLengthByName($wd[(1 + $j) % 2]) < $h) {
                    [$priority, $orientation, $bestIdx] = [4, $j, $idx];
                } elseif ($priority > 5) {
                    [$priority, $orientation, $bestIdx] = [5, $j, $idx];
                }
            }
        }

        // UtilDebug::d("priority: $priority");

        if ($priority < 5) {

            // ---- add best box to result
            $bestBox = $this->remainingBoxes[$bestIdx];
            if($orientation == 1) {
                $bestBox->rotateFootprint90Degrees();
            }
            [$omega, $d] = [$bestBox->getWidth(), $bestBox->getDepth()];

            $this->packedBoxes[] = PackedBox::create($bestBox, $x, $y, 0);
            unset($this->remainingBoxes[$bestIdx]); //        indices.remove(bestIdx)

            //
            if ($priority == 2) {
                $this->recursive_packing($x, $y + $d, $w, $h - $d);
            } elseif ($priority == 3) {
                $this->recursive_packing($x + $omega, $y, $w - $omega, $h);
            } elseif ($priority == 4) {
                // -- find minWidth / minDepth
                $min_w = PHP_INT_MAX; // sys.maxsize
                $min_h = PHP_INT_MAX; // sys.maxsize
                foreach ($this->remainingBoxes as $box) {
                    $min_w = min($min_w, $box->getWidth());
                    $min_h = min($min_h, $box->getDepth());
                }
                # Because we can rotate:
                $min_w = min($min_h, $min_w);
                $min_h = $min_w;
                if ($w - $omega < $min_w) {
                    $this->recursive_packing($x, $y + $d, $w, $h - $d);
                } elseif ($h - $d < $min_h) {
                    $this->recursive_packing($x + $omega, $y, $w - $omega, $h);
                } elseif ($omega < $min_w) {
                    $this->recursive_packing($x + $omega, $y, $w - $omega, $d);
                    $this->recursive_packing($x, $y + $d, $w, $h - $d);
                } else {
                    $this->recursive_packing($x, $y + $d, $omega, $h - $d);
                    $this->recursive_packing($x + $omega, $y, $w - $omega, $h);
                }
            }
        }
    }

}




//def recursive_packing(x, y, w, h, D, remaining, indices, result):
//    """
//    Helper function to recursively fit a certain area.
//    D = 0 (no rotation allowed) or 1 (rotation allowed)
//    "remaining" = list or ALL boxes
//    """
//    logger.info(f'recursive_packing({x}, {y}, {w}, {h}, {D}, {indices}, {result})')
//    priority = 6
//    for idx in indices:
//        for j in range(0, D + 1):
//            if priority > 1 and $box->getLengthByName($wd[(0 + $j) % 2]) == w and $box->getLengthByName($wd[(1 + $j) % 2]) == h:
//                priority, orientation, best = 1, j, idx
//                break
//            elif priority > 2 and $box->getLengthByName($wd[(0 + $j) % 2]) == w and $box->getLengthByName($wd[(1 + $j) % 2]) < h:
//                priority, orientation, best = 2, j, idx
//            elif priority > 3 and $box->getLengthByName($wd[(0 + $j) % 2]) < w and $box->getLengthByName($wd[(1 + $j) % 2]) == h:
//                priority, orientation, best = 3, j, idx
//            elif priority > 4 and $box->getLengthByName($wd[(0 + $j) % 2]) < w and $box->getLengthByName($wd[(1 + $j) % 2]) < h:
//                priority, orientation, best = 4, j, idx
//            elif priority > 5:
//                priority, orientation, best = 5, j, idx
//    if priority < 5:
//        if orientation == 0:
//            omega, d = remaining[best][0], remaining[best][1]
//        else:
//            omega, d = remaining[best][1], remaining[best][0]
//        result[best] = Rectangle(x, y, omega, d)
//        indices.remove(best)
//        if priority == 2:
//            recursive_packing(x, y + d, w, h - d, D, remaining, indices, result)
//        elif priority == 3:
//            recursive_packing(x + omega, y, w - omega, h, D, remaining, indices, result)
//        elif priority == 4:
//            min_w = sys.maxsize
//            min_h = sys.maxsize
//            for idx in indices:
//                min_w = min(min_w, remaining[idx][0])
//                min_h = min(min_h, remaining[idx][1])
//            # Because we can rotate:
//            min_w = min(min_h, min_w)
//            min_h = min_w
//            if w - omega < min_w:
//                recursive_packing(x, y + d, w, h - d, D, remaining, indices, result)
//            elif h - d < min_h:
//                recursive_packing(x + omega, y, w - omega, h, D, remaining, indices, result)
//            elif omega < min_w:
//                recursive_packing(x + omega, y, w - omega, d, D, remaining, indices, result)
//                recursive_packing(x, y + d, w, h - d, D, remaining, indices, result)
//            else:
//                recursive_packing(x, y + d, omega, h - d, D, remaining, indices, result)
//                recursive_packing(x + omega, y, w - omega, h, D, remaining, indices, result)
//
