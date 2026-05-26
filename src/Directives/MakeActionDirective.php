<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\ActionGenerator;
use AndyDefer\Records\Collections\Utility\StringTypedCollection;

final class MakeActionDirective extends BaseDirective
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->generator = new ActionGenerator($interaction);
    }

    public function getSignature(): string
    {
        return 'make-action {name} {--type=api}';
    }

    public function getDescription(): string
    {
        return 'Create a new action class';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-action');
        $aliases->add('make-act');
        return $aliases;
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');

        if ($name === null) {
            $this->error('Action name is required');
            return ExitCode::INVALID_ARGUMENT;
        }

        $type = $this->option('type');

        if ($type && !in_array($type, ['api', 'web'])) {
            $this->error("Invalid type '{$type}'. Allowed types: api, web");
            return ExitCode::INVALID_ARGUMENT;
        }

        return parent::execute();
    }
}
