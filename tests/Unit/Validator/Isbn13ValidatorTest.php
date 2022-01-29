<?php

use App\Validator\Isbn13Validator;
use App\Validator\ValidationException;

it('accepts formatted 13-digits ISBN', function() {
    $validator = new Isbn13Validator;
    expect($validator->validate('978-3-518-38006-2'))->toBe('978-3-518-38006-2');
});

it('accepts and formats unformatted 13-digits ISBN', function() {
    $validator = new Isbn13Validator;
    expect($validator->validate('9783518380062'))->toBe('978-3-518-38006-2');
});

it('denies formatted 10-digits ISBN', function() {
    $validator = new Isbn13Validator;
    $validator->validate('3-518-38006-0');
})->throws(ValidationException::class);

it('denies unformatted 10-digits ISBN', function() {
    $validator = new Isbn13Validator;
    $validator->validate('3518380060');
})->throws(ValidationException::class);

it('denies invalid check digit on 10-digits ISBN', function() {
    $validator = new Isbn13Validator;
    $validator->validate('3-518-38006-1');
})->throws(ValidationException::class);

it('denies invalid check digit on 13-digits ISBN', function() {
    $validator = new Isbn13Validator;
    $validator->validate('978-3-518-38006-3');
})->throws(ValidationException::class);

it('denies random string', function() {
    $validator = new Isbn13Validator;
    $validator->validate('string');
})->throws(ValidationException::class);
