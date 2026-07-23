<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\ValueObjects;

use AndyDefer\DirectiveForge\Collections\ReplacementCollection;
use AndyDefer\DirectiveForge\Records\ReplacementRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

/**
 * Value Object représentant un contenu de stub avec ses remplacements.
 *
 * @example
 * $stub = new StubVO('Hello {{name}}!');
 * $stub->replace(new ReplacementRecord('name', 'John'));
 * echo $stub->getValue(); // "Hello John!"
 */
final class StubVO extends AbstractValueObject
{
    private readonly string $content;

    private ReplacementCollection $replacements;

    public function __construct(string $content)
    {
        $this->content = $content;
        $this->replacements = new ReplacementCollection;
    }

    /**
     * Ajoute ou modifie un remplacement.
     */
    public function replace(ReplacementRecord $replacement): self
    {
        // Supprimer l'ancien si présent
        if ($this->replacements->hasPlaceholder($replacement->placeholder)) {
            $items = $this->replacements->toArray();
            $filtered = array_filter(
                $items,
                fn (ReplacementRecord $r) => $r->placeholder !== $replacement->placeholder
            );

            // Réinitialiser avec les éléments filtrés
            $this->replacements = new ReplacementCollection;
            foreach ($filtered as $record) {
                $this->replacements->add($record);
            }
        }

        // Ajouter le nouveau
        $this->replacements->add($replacement);

        return $this;
    }

    /**
     * Ajoute ou modifie plusieurs remplacements.
     */
    public function replaceMany(ReplacementCollection $replacements): self
    {
        foreach ($replacements as $replacement) {
            $this->replace($replacement);
        }

        return $this;
    }

    /**
     * Supprime un remplacement.
     */
    public function remove(string $placeholder): self
    {
        if (! $this->replacements->hasPlaceholder($placeholder)) {
            return $this;
        }

        $items = $this->replacements->toArray();
        $filtered = array_filter(
            $items,
            fn (ReplacementRecord $r) => $r->placeholder !== $placeholder
        );

        $this->replacements = new ReplacementCollection;
        foreach ($filtered as $record) {
            $this->replacements->add($record);
        }

        return $this;
    }

    /**
     * Vérifie si un placeholder a un remplacement.
     */
    public function has(string $placeholder): bool
    {
        return $this->replacements->hasPlaceholder($placeholder);
    }

    /**
     * Récupère la valeur d'un remplacement.
     */
    public function get(string $placeholder): ?string
    {
        foreach ($this->replacements as $replacement) {
            if ($replacement->placeholder === $placeholder) {
                return $replacement->value;
            }
        }

        return null;
    }

    /**
     * Récupère tous les remplacements.
     */
    public function getReplacements(): ReplacementCollection
    {
        return $this->replacements;
    }

    /**
     * Trouve tous les placeholders dans le contenu.
     */
    public function findPlaceholders(): StringTypedCollection
    {
        $placeholders = new StringTypedCollection;
        preg_match_all('/\{\{[^}]+\}\}/', $this->content, $matches);

        foreach ($matches[0] ?? [] as $match) {
            $clean = trim(trim($match, '{}'));
            $placeholders->add($clean);
        }

        return $placeholders;
    }

    /**
     * Vérifie si un placeholder existe dans le contenu.
     */
    public function hasPlaceholder(string $placeholder): bool
    {
        return str_contains($this->content, '{{'.$placeholder.'}}') ||
               str_contains($this->content, '{{ '.$placeholder.' }}');
    }

    /**
     * Rend le contenu avec tous les remplacements.
     */
    private function render(): string
    {
        $content = $this->content;

        foreach ($this->replacements as $replacement) {
            $placeholder = $replacement->placeholder;
            $value = $replacement->value;

            // Support de plusieurs formats
            $content = str_replace(
                [
                    '{{'.$placeholder.'}}',
                    '{{ '.$placeholder.' }}',
                    '{{'.$placeholder.' }}',
                    '{{ '.$placeholder.'}}',
                ],
                $value,
                $content
            );
        }

        return $content;
    }

    // ============ Implémentation AbstractValueObject ============

    /**
     * Retourne le contenu formaté après remplacement.
     */
    public function getValue(): string
    {
        return $this->render();
    }
}
