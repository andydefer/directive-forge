<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Records;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class GeneratorConfig extends AbstractRecord
{
    public function __construct(
        public readonly GeneratorType $type,
        public readonly string $basePath,
        public readonly string $baseNamespace,
        public readonly string $stubPath,
        public readonly string $suffix,
        public readonly bool $requiresType = false,
        public readonly bool $supportsType = false,
        public readonly ReplacementCollection $extraReplacements = new ReplacementCollection,
    ) {}
}
