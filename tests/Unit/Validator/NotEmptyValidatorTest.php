<?php

use App\Validator\NotEmptyValidator;
use App\Validator\ValidationException;

it('accepts non-empty array', function() {
    $validator = new NotEmptyValidator;
    expect($validator->validate([10]))->toBe([10]);
});

it('accepts non-empty string', function() {
    $validator = new NotEmptyValidator;
    expect($validator->validate('string'))->toBe('string');
});

it('accepts positive integer', function() {
    $validator = new NotEmptyValidator;
    expect($validator->validate(10))->toBe(10);
});

it('accepts negative integer', function() {
    $validator = new NotEmptyValidator;
    expect($validator->validate(-10))->toBe(-10);
});

it('accepts integer zero', function() {
    $validator = new NotEmptyValidator;
    expect($validator->validate(0))->toBe(0);
});

it('accepts true', function() {
    $validator = new NotEmptyValidator;
    expect($validator->validate(true))->toBe(true);
});

it('accepts false', function() {
    $validator = new NotEmptyValidator;
    expect($validator->validate(false))->toBe(false);
});

it('denies empty array', function() {
    $validator = new NotEmptyValidator;
    $validator->validate([]);
})->throws(ValidationException::class);

it('denies empty string', function() {
    $validator = new NotEmptyValidator;
    $validator->validate('');
})->throws(ValidationException::class);

it('denies null', function() {
    $validator = new NotEmptyValidator;
    $validator->validate(null);
})->throws(ValidationException::class);
