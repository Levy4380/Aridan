<?php

use App\Support\ColorContrast;

test('the light employee palette uses dark text', function (string $hex) {
    expect(ColorContrast::textOn($hex))->toBe(ColorContrast::DARK_TEXT);
})->with(['#4fc3f7', '#81c784', '#f06292', '#ffb74d', '#ba68c8', '#b0bec5', '#4db6ac', '#fff176', '#ff8a65', '#9fa8da']);

test('dark custom colors use white text', function (string $hex) {
    expect(ColorContrast::textOn($hex))->toBe(ColorContrast::LIGHT_TEXT);
})->with(['#1e3a8a', '#000000', '#5b2c6f']);

test('invalid values fall back to dark text', function () {
    expect(ColorContrast::textOn('rosa'))->toBe(ColorContrast::DARK_TEXT);
});
