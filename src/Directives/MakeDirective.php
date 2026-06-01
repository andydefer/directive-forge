<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Services\DirectiveNamingService;
use AndyDefer\Directive\Services\SignatureValidationService;
use AndyDefer\DirectiveForge\Generators\DirectiveGenerator;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

final class MakeDirective extends BaseDirective
{
    public function __construct(
        DirectiveInteractionService $interaction,
        private readonly SignatureValidationService $signatureValidator,
        private readonly DirectiveNamingService $namingService,
    ) {
        parent::__construct($interaction);
        $this->generator = new DirectiveGenerator($interaction, $signatureValidator, $namingService);
    }

    public function getSignature(): string
    {
        return 'make-directive {name}';
    }

    public function getDescription(): string
    {
        return 'Create a new directive class';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-directive', 'make-cmd');

        return $aliases;
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');

        if ($name === null) {
            $this->error('Directive name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        $baseName = basename($name);

        /** @var DirectiveGenerator $generator */
        $generator = $this->generator;
        $validation = $generator->validate($baseName);

        if (! $validation) {
            return ExitCode::INVALID_ARGUMENT;
        }

        return parent::execute();
    }
}
