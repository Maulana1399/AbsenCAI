<?php

use App\Services\Import\Support\PersonIdentityNormalizer;
use Illuminate\Support\Carbon;

uses(Tests\TestCase::class);

function pin(): PersonIdentityNormalizer
{
    return app(PersonIdentityNormalizer::class);
}

// ---------------------------------------------------------------------------
// Empty / null → null (import validators reject empty tanggal lahir)
// ---------------------------------------------------------------------------

test('null becomes null', function () {
    expect(pin()->normalizeTanggalLahir(null))->toBeNull();
});

test('empty string becomes null', function () {
    expect(pin()->normalizeTanggalLahir(''))->toBeNull();
});

test('whitespace becomes null', function () {
    expect(pin()->normalizeTanggalLahir('   '))->toBeNull();
});

// ---------------------------------------------------------------------------
// Object dates
// ---------------------------------------------------------------------------

test('DateTime becomes Y-m-d', function () {
    expect(pin()->normalizeTanggalLahir(new DateTime('1999-09-16')))->toBe('1999-09-16');
});

test('Carbon becomes Y-m-d', function () {
    expect(pin()->normalizeTanggalLahir(Carbon::parse('1985-12-31')))->toBe('1985-12-31');
});

// ---------------------------------------------------------------------------
// String formats
// ---------------------------------------------------------------------------

test('YYYY-MM-DD stays Y-m-d', function () {
    expect(pin()->normalizeTanggalLahir('1999-09-16'))->toBe('1999-09-16');
});

test('DD slash MM slash YYYY normalizes to Y-m-d', function () {
    expect(pin()->normalizeTanggalLahir('16/09/1999'))->toBe('1999-09-16');
});

test('DD dash MM dash YYYY normalizes to Y-m-d', function () {
    expect(pin()->normalizeTanggalLahir('31-12-1985'))->toBe('1985-12-31');
});

test('YYYY/MM/DD normalizes to Y-m-d', function () {
    expect(pin()->normalizeTanggalLahir('1999/09/16'))->toBe('1999-09-16');
});

test('DD.MM.YYYY normalizes to Y-m-d', function () {
    expect(pin()->normalizeTanggalLahir('01.01.2000'))->toBe('2000-01-01');
});

test('Excel serial date number normalizes to Y-m-d', function () {
    // 33044 = 1990-06-20 (PhpSpreadsheet native serial).
    expect(pin()->normalizeTanggalLahir('33044'))->toBe('1990-06-20');
});

// ---------------------------------------------------------------------------
// Invalid dates are left unchanged so the validator can reject them
// ---------------------------------------------------------------------------

test('invalid date string is left unchanged', function () {
    expect(pin()->normalizeTanggalLahir('abc'))->toBe('abc');
});

test('impossible date 31/02/2000 is left unchanged', function () {
    expect(pin()->normalizeTanggalLahir('31/02/2000'))->toBe('31/02/2000');
});

test('invalid date 2025-99-99 is left unchanged', function () {
    expect(pin()->normalizeTanggalLahir('2025-99-99'))->toBe('2025-99-99');
});

// ---------------------------------------------------------------------------
// Nama normalization → canonical Proper / Title Case
// ---------------------------------------------------------------------------

test('all caps single word becomes proper case', function () {
    expect(pin()->normalizeNama('REFIANTITO'))->toBe('Refiantito');
});

test('all caps multi-word becomes proper case', function () {
    expect(pin()->normalizeNama('YUDHISTIRA AHMAD'))->toBe('Yudhistira Ahmad')
        ->and(pin()->normalizeNama('BINTI CHUSNA'))->toBe('Binti Chusna')
        ->and(pin()->normalizeNama('DERICKA MILLENIA TRIANNA'))->toBe('Dericka Millenia Trianna');
});

test('all lowercase becomes proper case', function () {
    expect(pin()->normalizeNama('royan chiyarul ichsan'))->toBe('Royan Chiyarul Ichsan')
        ->and(pin()->normalizeNama('vivi nurhaliza audia'))->toBe('Vivi Nurhaliza Audia');
});

test('mixed case is normalized to proper case', function () {
    expect(pin()->normalizeNama('Rizka Aulyia kharisma hasyim'))->toBe('Rizka Aulyia Kharisma Hasyim')
        ->and(pin()->normalizeNama('charissa firda hasyim'))->toBe('Charissa Firda Hasyim');
});

test('multiple whitespace is collapsed before title casing', function () {
    expect(pin()->normalizeNama('  MUHAMMAD   ALI  '))->toBe('Muhammad Ali')
        ->and(pin()->normalizeNama("AHMAD\u{00A0}\u{2009}WIJAYA"))->toBe('Ahmad Wijaya');
});

test('already proper case name stays identical', function () {
    expect(pin()->normalizeNama('Muhammad Ali'))->toBe('Muhammad Ali')
        ->and(pin()->normalizeNama('Yudhistira Ahmad'))->toBe('Yudhistira Ahmad');
});

test('single uppercase letter keeps its case', function () {
    expect(pin()->normalizeNama('Aditya Javan A.'))->toBe('Aditya Javan A.');
});

test('empty and whitespace-only names stay empty', function () {
    expect(pin()->normalizeNama(''))->toBe('')
        ->and(pin()->normalizeNama('   '))->toBe('')
        ->and(pin()->normalizeNama(null))->toBe('');
});

test('normalized names remain case-insensitive duplicates', function () {
    $first = pin()->normalizeNama('MUHAMMAD ALI');
    $second = pin()->normalizeNama('muhammad ali');

    expect($first)->toBe('Muhammad Ali')
        ->and($second)->toBe($first)
        ->and(mb_strtolower($first))->toBe(mb_strtolower($second));
});

// ---------------------------------------------------------------------------
// Acronym / initialism preservation
// ---------------------------------------------------------------------------

test('fully uppercase acronym tokens are preserved in mixed-case names', function () {
    expect(pin()->normalizeNama('Ahmad Agung FF'))->toBe('Ahmad Agung FF')
        ->and(pin()->normalizeNama('Muhammad Aldafi Zanuar AF'))->toBe('Muhammad Aldafi Zanuar AF')
        ->and(pin()->normalizeNama('  Muhammad Aldafi Zanuar AF '))->toBe('Muhammad Aldafi Zanuar AF');
});

test('single uppercase letter is not treated as an acronym', function () {
    expect(pin()->normalizeNama('Ahmad Agung F'))->toBe('Ahmad Agung F')
        ->and(pin()->normalizeNama('Muhammad a'))->toBe('Muhammad A');
});

test('all-caps full names still collapse to proper case (no acronym guess)', function () {
    expect(pin()->normalizeNama('BINTI CHUSNA'))->toBe('Binti Chusna')
        ->and(pin()->normalizeNama('MUHAMMAD ANDRA RAFA REQUELMI'))->toBe('Muhammad Andra Rafa Requelmi')
        ->and(pin()->normalizeNama('AHMAD AGUNG FF'))->toBe('Ahmad Agung Ff');
});

// ---------------------------------------------------------------------------
// Hyphenated names
// ---------------------------------------------------------------------------

test('hyphenated names are normalized per segment', function () {
    expect(pin()->normalizeNama('Azifah Mayyasah Al-Banie'))->toBe('Azifah Mayyasah Al-Banie')
        ->and(pin()->normalizeNama('Daffa Al-Fikriy Rachmad'))->toBe('Daffa Al-Fikriy Rachmad')
        ->and(pin()->normalizeNama('Fahira Al-Tofi Zakia'))->toBe('Fahira Al-Tofi Zakia')
        ->and(pin()->normalizeNama('Muhammad Khodir as-sidiq'))->toBe('Muhammad Khodir As-Sidiq');
});

// ---------------------------------------------------------------------------
// Apostrophe names
// ---------------------------------------------------------------------------

test('apostrophe names are normalized per segment', function () {
    expect(pin()->normalizeNama("Zivana Zayyana Naf'An"))->toBe("Zivana Zayyana Naf'An")
        ->and(pin()->normalizeNama("Nurul Azizah Mut'Mainnah"))->toBe("Nurul Azizah Mut'Mainnah")
        ->and(pin()->normalizeNama("Ummu Fathinmah Rif'At Khanifah"))->toBe("Ummu Fathinmah Rif'At Khanifah")
        ->and(pin()->normalizeNama("rif'at"))->toBe("Rif'At");
});

// ---------------------------------------------------------------------------
// Dot / initial
// ---------------------------------------------------------------------------

test('dot tokens keep structure and normalize the initial after the dot', function () {
    expect(pin()->normalizeNama('Febria Putri Nuraini.R'))->toBe('Febria Putri Nuraini.R')
        ->and(pin()->normalizeNama('Febria Putri Nuraini.r'))->toBe('Febria Putri Nuraini.R')
        ->and(pin()->normalizeNama('Aditya Javan A.'))->toBe('Aditya Javan A.');
});
