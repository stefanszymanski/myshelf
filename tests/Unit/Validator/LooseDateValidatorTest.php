<?php

use App\Validator\LooseDateValidator;
use App\Validator\ValidationException;

it('accepts positive year', function() {
    $validator = new LooseDateValidator;
    expect($validator->validate('2020'))->toBe('2020');
});

it('accepts neagtive year', function() {
    $validator = new LooseDateValidator;
    expect($validator->validate('-100'))->toBe('-100');
});

it('accepts positive year as  integer', function() {
    $validator = new LooseDateValidator;
    expect($validator->validate(2020))->toBe('2020');
});

it('accepts neagtive year as integer', function() {
    $validator = new LooseDateValidator;
    expect($validator->validate(-100))->toBe('-100');
});

it('accepts year + month', function() {
    $validator = new LooseDateValidator;
    expect($validator->validate('2020-01'))->toBe('2020-01');
});

it('accepts year + month + day', function() {
    $validator = new LooseDateValidator;
    expect($validator->validate('2020-08-01'))->toBe('2020-08-01');
});

it('normalizes month', function() {
    $validator = new LooseDateValidator;
    expect($validator->validate('2020-1'))->toBe('2020-01');
});

it('normalizes month + day', function() {
    $validator = new LooseDateValidator;
    expect($validator->validate('2020-8-1'))->toBe('2020-08-01');
});

it('denies invalid month', function() {
    $validator = new LooseDateValidator;
    $validator->validate('2020-13-01');
})->throws(ValidationException::class);

it('denies invalid day', function() {
    $validator = new LooseDateValidator;
    $validator->validate('2020-02-30');
})->throws(ValidationException::class);

it('denies random string', function() {
    $validator = new LooseDateValidator;
    $validator->validate('string');
})->throws(ValidationException::class);
