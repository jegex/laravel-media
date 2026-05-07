<?php

use Jegex\Media\ResponsiveImages\WidthCalculator\FileSizeOptimizedWidthCalculator;

it('can calculate responsive widths', function () {
    $calculator = new FileSizeOptimizedWidthCalculator;
    $widths = $calculator->calculateWidths(500000, 1920, 1080);

    expect($widths)->toBeArray()
        ->and(count($widths))->toBeGreaterThan(0)
        ->and(max($widths))->toBeLessThanOrEqual(1920);
});

it('widths are sorted', function () {
    $calculator = new FileSizeOptimizedWidthCalculator;
    $widths = $calculator->calculateWidths(500000, 1920, 1080);

    $sorted = $widths;
    sort($sorted);

    expect($widths)->toBe($sorted);
});

it('widths are unique', function () {
    $calculator = new FileSizeOptimizedWidthCalculator;
    $widths = $calculator->calculateWidths(500000, 1920, 1080);

    expect(count($widths))->toBe(count(array_unique($widths)));
});
