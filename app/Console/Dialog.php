<?php

declare(strict_types=1);

namespace App\Console;

use App\Context;
use App\Persistence\Database;
use App\Persistence\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class Dialog
{
    protected InputInterface $input;
    protected SymfonyStyle $output;
    protected Database $db;

    public function __construct(protected Context $context, protected Table $table)
    {
        $this->input = $context->input;
        $this->output = $context->output;
        $this->db = $context->db;
    }

    public function error(string $message): void
    {
        $this->context->enqueue(fn() => $this->output->error($message));
    }

    public function success(string $message): void
    {
        $this->context->enqueue(fn() => $this->output->success($message));
    }

    public function warning(string $message): void
    {
        $this->context->enqueue(fn() => $this->output->warning($message));
    }

    public function note(string $message): void
    {
        $this->context->enqueue(fn() => $this->output->note($message));
    }
}
