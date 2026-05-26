<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\ValueObjects;

use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\Records\AbstractRecord;

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
        public readonly array $extraReplacements = [],
    ) {}
}
