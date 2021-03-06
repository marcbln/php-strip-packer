<?php

require(__DIR__ . '/../vendor/autoload.php');

/**
 * 03/2021 created
 */


use Mcx\StripPacker\DTO\Box;
use Mcx\StripPacker\Service\StripPacker;
use Mcx\StripPacker\Util\ResultsRenderer;
use Mcx\StripPacker\Util\UtilTwig;


function _generateRandomBoxes(int $containerWidth)
{
    $numBoxes = max(2, rand($containerWidth * 0.2, $containerWidth));
    foreach (range(1, $numBoxes) as $foo) {

        do {
            [$width, $depth] = [rand(1, $containerWidth / 2), rand(1, $containerWidth * 1.2)];
        } while ($width > $containerWidth && $depth > $containerWidth);

        $ret[] = Box::createFromAssocArray([
            'width'  => $width,
            'depth'  => $depth,
            'height' => 0,
        ]);
    }

    return $ret;
}

$tests = [
    // ----  first: the "perfect showcase"
    [
        'boxes'          => [
            Box::createFromString('20x14x0'),
            Box::createFromString('10x20x0'),
            Box::createFromString('10x20x0'),
            Box::createFromString('5x3x0'),
            Box::createFromString('5x3x0'),
            Box::createFromString('2x4x0'),
            Box::createFromString('30x8x0'),
            Box::createFromString('5x5x0'),
            Box::createFromString('5x5x0'),
            Box::createFromString('10x10x0'),
            Box::createFromString('10x5x0'),
            Box::createFromString('6x4x0'),
            Box::createFromString('1x10x0'),
            Box::createFromString('8x4x0'),
            Box::createFromString('6x6x0'),
        ],
        'containerWidth' => 30,
    ],
    [
        'boxes'          => [
            Box::createFromString('19x11x0'),
            Box::createFromString('7x5x0'),
            Box::createFromString('7x1x0'),
            Box::createFromString('21x2x0'),
            Box::createFromString('1x27x0'),
        ],
        'containerWidth' => 26,
    ],
    [
        'boxes'          => [
            Box::createFromString('4x33x0'),
            Box::createFromString('24x5x0'),
            Box::createFromString('4x22x0'),
            Box::createFromString('8x13x0'),
            Box::createFromString('3x10x0'),
            Box::createFromString('6x32x0'),
        ],
        'containerWidth' => 28,
    ],
    [
        'boxes'          => [
            Box::createFromString('16x39x0'),
            Box::createFromString('5x37x0'),
            Box::createFromString('12x3x0'),
            Box::createFromString('10x28x0'),
            Box::createFromString('2x11x0'),
            Box::createFromString('13x7x0'),
        ],
        'containerWidth' => 33,
    ],

];

// ---- random tests
foreach (range(2, 33, 1) as $containerWidth) {
    $tests[] = [
        'boxes'          => _generateRandomBoxes($containerWidth),
        'containerWidth' => $containerWidth,
    ];
}

$packer = new StripPacker();

$sortBy = [
    'longestFootprintEdge'   => SORT_DESC,
    'footprintCircumference' => SORT_DESC,
    'width'                  => SORT_DESC,
    'depth'                  => SORT_DESC,
    'footprintArea'          => SORT_DESC,
];

$results = [];
foreach ($tests as $testInput) {
    [$packedBoxes, $containerDepth] = $packer->pack($testInput['containerWidth'], $testInput['boxes'], $sortBy);

    $results[] = [
        'containerWidth' => $testInput['containerWidth'],
        'containerDepth' => $containerDepth,
        'numBoxes'       => count($testInput['boxes']),
        'svg'            => ResultsRenderer::renderLevel2d($testInput['containerWidth'], $containerDepth, $packedBoxes),
    ];
    // file_put_contents(__DIR__ . "/../demo_images/demo_{$testInput['containerWidth']}_$containerDepth.svg", ResultsRenderer::renderLevel2d($testInput['containerWidth'], $containerDepth, $packedBoxes));
}


echo UtilTwig::renderTemplate(__DIR__ . '/../templates/strip_packer_demo.html.twig', ['results' => $results]);


