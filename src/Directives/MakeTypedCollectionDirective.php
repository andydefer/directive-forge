<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\TypedCollectionGenerator;
use AndyDefer\Records\Collections\Utility\StringTypedCollection;

final class MakeTypedCollectionDirective extends BaseDirective
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->generator = new TypedCollectionGenerator($interaction);
    }

    public function getSignature(): string
    {
        return 'make-typed-collection {name} {--item-type}';
    }

    public function getDescription(): string
    {
        return 'Create a new typed collection class';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-collection');
        $aliases->add('make-collection');
        return $aliases;
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');

        if ($name === null) {
            $this->error('Collection name is required');
            return ExitCode::INVALID_ARGUMENT;
        }

        $itemType = $this->option('item-type');

        if (!$itemType) {
            $this->error('Item type is required. Use --item-type=<type>');
            return ExitCode::INVALID_ARGUMENT;
        }

        return parent::execute();
    }
}
