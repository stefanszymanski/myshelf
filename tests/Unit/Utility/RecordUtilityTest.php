<?php

use App\Utility\RecordUtility;

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
