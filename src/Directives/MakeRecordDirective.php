<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\RecordGenerator;
use AndyDefer\Records\Collections\Utility\StringTypedCollection;

final class MakeRecordDirective extends BaseDirective
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->generator = new RecordGenerator($interaction);
    }

    public function getSignature(): string
    {
        return 'make-record {name}';
    }

    public function getDescription(): string
    {
        return 'Create a new record class';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-record');
        $aliases->add('make-dto');
        return $aliases;
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');

        if ($name === null) {
            $this->error('Record name is required');
            return ExitCode::INVALID_ARGUMENT;
        }

        return parent::execute();
    }
}
