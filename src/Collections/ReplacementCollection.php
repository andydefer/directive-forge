<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Collections;

use AndyDefer\DirectiveForge\Records\ReplacementRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Collection of ReplacementRecord objects.
 *
 * Used to manage multiple text replacements for stub generation.
 */
final class ReplacementCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(ReplacementRecord::class);
    }

    /**
     * Add a replacement to the collection.
     *
     * @param  string  $placeholder  The placeholder key
     * @param  string  $value  The value to substitute
     */
    public function addReplacement(string $placeholder, string $value): self
    {
        $this->add(new ReplacementRecord($placeholder, $value));

        return $this;
    }

    /**
     * Check if a placeholder exists in the collection.
     *
     * @param  string  $placeholder  The placeholder key
     * @return bool True if the placeholder exists
     */
    public function hasPlaceholder(string $placeholder): bool
    {
        /** @var ReplacementRecord $record */
        foreach ($this->items as $record) {
            if ($record->placeholder === $placeholder) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the value for a specific placeholder.
     *
     * @param  string  $placeholder  The placeholder key
     * @return string|null The replacement value or null if not found
     */
    public function getValue(string $placeholder): ?string
    {
        /** @var ReplacementRecord $record */
        foreach ($this->items as $record) {
            if ($record->placeholder === $placeholder) {
                return $record->value;
            }
        }

        return null;
    }

    /**
     * Remove a replacement by placeholder.
     *
     * @param  string  $placeholder  The placeholder key to remove
     */
    public function remove(string $placeholder): self
    {
        $items = $this->toArray();
        $filtered = array_filter(
            $items,
            fn (ReplacementRecord $record) => $record->placeholder !== $placeholder
        );

        $this->items = array_values($filtered);

        return $this;
    }

    /**
     * Get all placeholder keys.
     *
     * @return array<int, string> Array of all placeholders
     */
    public function getPlaceholders(): array
    {
        $placeholders = [];

        /** @var ReplacementRecord $record */
        foreach ($this->items as $record) {
            $placeholders[] = $record->placeholder;
        }

        return $placeholders;
    }

    /**
     * Get all replacement values.
     *
     * @return array<int, string> Array of all values
     */
    public function getValues(): array
    {
        $values = [];

        /** @var ReplacementRecord $record */
        foreach ($this->items as $record) {
            $values[] = $record->value;
        }

        return $values;
    }
}
