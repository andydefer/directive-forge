<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class TypeDefinitionRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $type,
        public readonly string $suffix,
        public readonly string $plural
    ) {}
}
