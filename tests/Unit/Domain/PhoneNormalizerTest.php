<?php

use App\Domain\PhoneNormalizer;
use App\Support\Format;

test('the same Argentine mobile written many ways is one phone', function (string $written) {
    expect(PhoneNormalizer::normalize($written))->toBe('5491155550142');
})->with([
    '11 5555-0142',
    '11 15 5555-0142',
    '011 15 5555-0142',
    '011 5555-0142',
    '+54 9 11 5555-0142',
    '+54 11 5555-0142',
    '54 9 11 5555 0142',
    '0054 9 11 5555 0142',
    '(011) 15-5555-0142',
]);

test('area codes of 3 and 4 digits keep their 15 rule', function () {
    expect(PhoneNormalizer::normalize('0351 15 555-0142'))->toBe('5493515550142')
        ->and(PhoneNormalizer::normalize('351 555-0142'))->toBe('5493515550142')
        ->and(PhoneNormalizer::normalize('02284 15 55-0142'))->toBe('5492284550142');
});

test('numbers that do not look Argentine are kept as digits', function () {
    expect(PhoneNormalizer::normalize('+598 99 123 456'))->toBe('59899123456')
        ->and(PhoneNormalizer::normalize('555-0142'))->toBe('5550142')
        ->and(PhoneNormalizer::normalize(''))->toBe('');
});

test('canonical phones and documents are shown in a readable way', function () {
    expect(Format::phone('5491155550142'))->toBe('11 5555-0142')
        ->and(Format::phone('5493515550142'))->toBe('351 555-0142')
        ->and(Format::phone('59899123456'))->toBe('59899123456')
        ->and(Format::document('30123456'))->toBe('30.123.456')
        ->and(Format::document('20301234567'))->toBe('20-30123456-7');
});

test('money is parsed however it is typed and shown in pesos', function () {
    expect(Format::parseMoney('$ 80.000'))->toBe(80000.0)
        ->and(Format::parseMoney('80000'))->toBe(80000.0)
        ->and(Format::parseMoney('80.000,50'))->toBe(80000.5)
        ->and(Format::parseMoney(''))->toBeNull()
        ->and(Format::money(200000))->toBe('$200.000')
        ->and(Format::money('80000.50'))->toBe('$80.000,50');
});
