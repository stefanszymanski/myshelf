<?php

use App\Context;
use App\Persistence\Database;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

beforeEach(function() {
    $input = mock(InputInterface::class)->expect();
    $output = mock(SymfonyStyle::class)->expect();
    $db = mock(Database::class)->expect();
    $this->context = new Context($input, $output, $db);
});

it('can add and remove one layer', function() {
    try {
        $layer = $this->context->addLayer('layer 1');
        $layer->finish();
        $exceptionWasThrown = false;
    } catch (\Exception $e) {
        $exceptionWasThrown = true;
    }
    expect($exceptionWasThrown)->toBeFalse();
});

it('can add and remove multiple layers in correct order', function() {
    try {
        $layer1 = $this->context->addLayer('layer 1');
        $layer2 = $this->context->addLayer('layer 2');
        $layer3 = $this->context->addLayer('layer 3');
        $layer3->finish();
        $layer2->finish();
        $layer1->finish();
        $exceptionWasThrown = false;
    } catch (\Exception $e) {
        $exceptionWasThrown = true;
    }
    expect($exceptionWasThrown)->toBeFalse();
});

it('fails when layers are closed in wrong order', function() {
    $layer1 = $this->context->addLayer('layer 1');
    $layer2 = $this->context->addLayer('layer 2');
    $layer1->finish();
    $layer2->finish();
    $exceptionWasThrown = false;
})->throws(\InvalidArgumentException::class);
