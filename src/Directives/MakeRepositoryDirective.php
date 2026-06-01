<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\RepositoryGenerator;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

final class MakeRepositoryDirective extends BaseDirective
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->generator = new RepositoryGenerator($interaction);
    }

    public function getSignature(): string
    {
        return 'make-repository {name}';
    }

    public function getDescription(): string
    {
        return 'Create a new repository class';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-repository', 'make-repo');

        return $aliases;
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');

        if ($name === null) {
            $this->error('Repository name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        return parent::execute();
    }
}
