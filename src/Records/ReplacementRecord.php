<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record representing a text replacement operation for stub generation.
 *
 * Used by the GeneratorService to replace placeholders in stub files
 * with actual values during code generation.
 */
final class ReplacementRecord extends AbstractRecord
{
    /**
     * Create a new replacement record.
     *
     * @param  string  $placeholder  The placeholder key to replace (e.g., 'namespace', 'class')
     * @param  string  $value  The value to substitute for the placeholder
     */
    public function __construct(
        public readonly string $placeholder,
        public readonly string $value,
    ) {}
}
