<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use phumin\PromptParse\Library\TLV;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Payload QRIS statis tiruan, disusun lewat TLV::withCrcTag() supaya CRC-nya
 * benar-benar sah — bukan angka yang diketik lalu kebetulan lolos.
 */
function qrisStatisContoh(): string
{
    $isiMerchant = TLV::encode([
        TLV::tag('00', 'ID.CO.QRIS.WWW'),
        TLV::tag('01', '936000140123456789'),
        TLV::tag('02', '123456789012345'),
        TLV::tag('03', 'UMI'),
    ]);

    $tanpaCrc = TLV::encode([
        TLV::tag('00', '01'),
        TLV::tag('01', '11'),
        TLV::tag('26', $isiMerchant),
        TLV::tag('52', '4722'),
        TLV::tag('53', '360'),
        TLV::tag('58', 'ID'),
        TLV::tag('59', 'E-GOTO INDONESIA'),
        TLV::tag('60', 'BANDUNG'),
        TLV::tag('61', '40123'),
    ]);

    return TLV::withCrcTag($tanpaCrc, '63');
}
