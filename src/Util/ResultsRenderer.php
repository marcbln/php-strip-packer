<?php

namespace Mcx\StripPacker\Util;

use Mcx\StripPacker\DTO\PackedBox;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * a helper class to generate SVG images of packer results
 *
 * 03/2021 created
 */
class ResultsRenderer
{

    const MAX_IMAGE_SIZE = "400x600";
    // -- boxes
    const CORNER_ROUNDNESS = 0;
    const BOX_STROKE_WIDTH = 1;
    const BOX_STROKE_COLOR = '#ffffff'; // same as CONTAINER_FILL_COLOR
    const BOX_FILL_COLOR = 'lightgreen';
    const TEXT_COLOR = '#000000';
    // -- container
    const CONTAINER_STROKE_WIDTH = 2;
    const CONTAINER_STROKE_COLOR = '#888888';
    const CONTAINER_FILL_COLOR = '#ffffff';
    // -- grid lines
    const GRID_LINES_WIDTH = 0.2; // 0 or false for no grid
    const GRID_LINES_COLOR = '#ff7700';


    /**
     * private helper to get resulting image size and scale factor
     *
     * @return array [$imgW, $imgH, $scale]
     */
    private static function _fit(int $srcW, int $srcH, int $maxW, int $maxH)
    {
        if ($srcW / $srcH > $maxW / $maxH) {
            $scale = $maxW / $srcW;
        } else {
            $scale = $maxH / $srcH;
        }

        return [$srcW * $scale, $srcH * $scale, $scale];
    }


    /**
     * for rendering a single level of the LAFF-packaged container
     *
     * 03/2021 created
     *
     * @param int $containerWidth width of the container
     * @param int $containerDepth depth of the container
     * @param PackedBox[] $packedBoxes
     * @return string the rendered SVG
     * @throws \Assert\AssertionFailedException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public static function renderLevel2d(int $containerWidth, int $containerDepth, array $packedBoxes): string
    {
        [$maxW, $maxH] = UtilStringSize::explodeInt(self::MAX_IMAGE_SIZE, 2);
        [$containerWidthScaled, $containerHeightScaled, $scale] = self::_fit($containerWidth, $containerDepth, $maxW - self::CONTAINER_STROKE_WIDTH, $maxH - self::CONTAINER_STROKE_WIDTH);

        $viewVars = [
            'packedBoxes'          => $packedBoxes,
            'imgW'                 => $containerWidthScaled + self::CONTAINER_STROKE_WIDTH,
            'imgH'                 => $containerHeightScaled + self::CONTAINER_STROKE_WIDTH,
            'containerWidth'       => $containerWidth,
            'containerDepth'       => $containerDepth,
            'scale'                => $scale,
            // -- boxes
            'cornerRoundness'      => self::CORNER_ROUNDNESS,
            'strokeWidth'          => self::BOX_STROKE_WIDTH,
            'fillColor'            => self::BOX_FILL_COLOR,
            'strokeColor'          => self::BOX_STROKE_COLOR,
            'textColor'            => self::TEXT_COLOR,
            // -- container
            'containerStrokeWidth' => self::CONTAINER_STROKE_WIDTH,
            'containerFillColor'   => self::CONTAINER_FILL_COLOR,
            'containerStrokeColor' => self::CONTAINER_STROKE_COLOR,
            // -- grid lines
            'gridLinesWidth'       => self::GRID_LINES_WIDTH,
            'gridLinesColor'       => self::GRID_LINES_COLOR,
        ];

        return UtilTwig::renderTemplate(__DIR__ . '/../../templates/ResultsRenderer_renderLevel2d.svg.twig', $viewVars);
    }
}




//    rectangles : list of namedtuple('Rectangle', ['x', 'y', 'w', 'h'])
//        A list of rectangles. This contains bottom left x and y coordinate and
//        the width and height of every rectangle.
//
//    fig = plt.figure()
//    axes = fig.add_subplot(1, 1, 1)
//    axes.add_patch(
//        patches.Rectangle(
//            (0, 0),  # (x,y)
//            width,  # width
//            height,  # height
//            hatch='x',
//            fill=False,
//        )
//    )
//    for idx, r in enumerate(rectangles):
//        axes.add_patch(
//            patches.Rectangle(
//                (r.x, r.y),  # (x,y)
//                r.w,  # width
//                r.h,  # height
//                color=(random(), random(), random()),
//            )
//        )
//        axes.text(r.x + 0.5 * r.w, r.y + 0.5 * r.h, str(idx))
//    axes.set_xlim(0, width)
//    axes.set_ylim(0, height)
//    plt.gca().set_aspect('equal', adjustable='box')
//    plt.show()
