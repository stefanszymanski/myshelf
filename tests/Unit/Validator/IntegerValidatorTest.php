<?php

use App\Validator\IntegerValidator;
use App\Validator\ValidationException;

it('accepts positive integer', function() {
    $validator = new IntegerValidator;
    expect($validator->validate(10))->toBe(10);
});

it('accepts negative integer', function() {
    $validator = new IntegerValidator;
    expect($validator->validate(-10))->toBe(-10);
});

it ('accepts positive integer as string', function() {
    $validator = new IntegerValidator;
    expect($validator->validate("10"))->toBe(10);
});

it ('accepts negative integer as string', function() {
    $validator = new IntegerValidator;
    expect($validator->validate("-10"))->toBe(-10);
});

it('accepts integer in range', function() {
    $validator = new IntegerValidator(min: 10, max: 20);
    expect($validator->validate(10))->toBe(10);
    expect($validator->validate(20))->toBe(20);
    expect($validator->validate(11))->toBe(11);
    expect($validator->validate(19))->toBe(19);
});

it ('accepts empty string', function() {
    $validator = new IntegerValidator;
    expect($validator->validate(""))->toBe("");
});

it ('accepts null', function() {
    $validator = new IntegerValidator;
    expect($validator->validate(null))->toBe(null);
});

it('denies too small integer', function() {
    (new IntegerValidator(min: 10, max: 20))->validate(9);
})->throws(ValidationException::class);

it('denies too big integer', function() {
    (new IntegerValidator(min: 10, max: 20))->validate(21);
})->throws(ValidationException::class);

it('denies non-numeric string', function() {
    (new IntegerValidator)->validate('string');
})->throws(ValidationException::class);

it('denies boolean', function() {
    (new IntegerValidator)->validate(true);
})->throws(\InvalidArgumentException::class);
