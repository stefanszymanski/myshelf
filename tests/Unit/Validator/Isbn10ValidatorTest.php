<?php

use App\Validator\Isbn10Validator;
use App\Validator\ValidationException;

it('accepts formatted 10-digits ISBN', function() {
    $validator = new Isbn10Validator;
    expect($validator->validate('3-518-38006-0'))->toBe('3-518-38006-0');
});

it('accepts and formats unformatted 10-digits ISBN', function() {
    $validator = new Isbn10Validator;
    expect($validator->validate('3518380060'))->toBe('3-518-38006-0');
});

it('denies formatted 13-digits ISBN', function() {
    $validator = new Isbn10Validator;
    $validator->validate('978-3-518-38006-2');
})->throws(ValidationException::class);

it('denies unformatted 13-digits ISBN', function() {
    $validator = new Isbn10Validator;
    $validator->validate('9783518380062');
})->throws(ValidationException::class);

it('denies invalid check digit on 10-digits ISBN', function() {
    $validator = new Isbn10Validator;
    $validator->validate('3-518-38006-1');
})->throws(ValidationException::class);

it('denies invalid check digit on 13-digits ISBN', function() {
    $validator = new Isbn10Validator;
    $validator->validate('978-3-518-38006-3');
})->throws(ValidationException::class);

it('denies random string', function() {
    $validator = new Isbn10Validator;
    $validator->validate('string');
})->throws(ValidationException::class);
