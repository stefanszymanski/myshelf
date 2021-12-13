<?php

use App\Utility\RecordUtility;

it('creates key from a single lower case word', function() {
    expect(RecordUtility::createKey('akey'))->toBe('akey');
});

it('creates key from a camel case word', function() {
    expect(RecordUtility::createKey('ThisIsAKey'))->toBe('thisisakey');
});

it('creates key from a multiple word', function() {
    expect(RecordUtility::createKey('these', 'are', 'multiple', 'word'))->toBe('these-are-multiple-word');
});

it('creates key and normalizes non-ascii characters', function() {
    expect(RecordUtility::createKey('äöüßé'))->toBe('aousse');
});


it('converts string to string', function() {
    expect(RecordUtility::convertToString('a string'))->toBe('a string');
});

it('converts positive integer to string', function() {
    expect(RecordUtility::convertToString(10))->toBe('10');
});

it('converts negative integer to string', function() {
    expect(RecordUtility::convertToString(-10))->toBe('-10');
});

it('converts positive float to string', function() {
    expect(RecordUtility::convertToString(10.345))->toBe('10.345');
});

it('converts negative float to string', function() {
    expect(RecordUtility::convertToString(-10.345))->toBe('-10.345');
});

it('converts true to string ', function() {
    expect(RecordUtility::convertToString(true))->toBe('true');
});

it('converts false to string ', function() {
    expect(RecordUtility::convertToString(false))->toBe('false');
});

it('converts null string', function() {
    expect(RecordUtility::convertToString(null))->toBe('');
});

it('converts list to string', function() {
    expect(RecordUtility::convertToString(['one', 'two', 'three']))->toBe("one\ntwo\nthree");
});

it('converts array to string', function() {
    expect(RecordUtility::convertToString(['one' => 'two', 'three' => 'four']))->toBe("one: two\nthree: four");
});

it('converts object to string', function() {
    expect(RecordUtility::convertToString(new RecordUtility))->toBe(RecordUtility::class);
});
