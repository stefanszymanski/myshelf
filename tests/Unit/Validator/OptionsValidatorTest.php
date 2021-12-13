<?php

use App\Validator\OptionsValidator;
use App\Validator\ValidationException;

it('accepts valid option', function () {
    $validator = new OptionsValidator(['first', 'second', 'third']);
    expect($validator->validate(1))->toBe('second');
});

it('denies invalid option', function () {
    $validator = new OptionsValidator(['first', 'second', 'third']);
    $validator->validate(4);
})->throws(ValidationException::class);

it('denies string value', function () {
    $validator = new OptionsValidator(['first', 'second', 'third']);
    $validator->validate('string');
})->throws(\InvalidArgumentException::class);
