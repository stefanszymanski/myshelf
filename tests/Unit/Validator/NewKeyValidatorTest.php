<?php

use App\Validator\NewKeyValidator;
use App\Validator\ValidationException;
use SleekDB\Store;

it('passes when no conflicting record exists', function() {
    $store = mock(Store::class)->expect(
        findOneBy: fn () => null
    );
    $validator = new NewKeyValidator($store);
    expect($validator->validate('new-key'))->toBe('new-key');
});

it('fails when a conflicting record exists', function() {
    $store = mock(Store::class)->expect(
        findOneBy: fn () => ['id' => 10]
    );
    $validator = new NewKeyValidator($store);
    $validator->validate('new-key');
})->throws(ValidationException::class);

it('denies empty string', function() {
    $store = mock(Store::class)->expect();
    $validator = new NewKeyValidator($store);
    $validator->validate('');
})->throws(ValidationException::class);

it('denies null', function() {
    $store = mock(Store::class)->expect();
    $validator = new NewKeyValidator($store);
    $validator->validate(null);
})->throws(ValidationException::class);
