<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Enums;

/**
 * Types d'extensions de fichiers supportés.
 */
enum ExtensionType: string
{
    case PHP = 'php';
    case HTML = 'html';
    case BLADE = 'blade.php';
    case JSON = 'json';
    case XML = 'xml';
    case YAML = 'yaml';
    case TXT = 'txt';
    case MD = 'md';
    case CSV = 'csv';
    case JS = 'js';
    case CSS = 'css';
    case SCSS = 'scss';
    case VUE = 'vue';
    case TWIG = 'twig';

    /**
     * Retourne l'extension avec le point.
     */
    public function withDot(): string
    {
        return '.'.$this->value;
    }

    /**
     * Retourne l'extension sans point.
     */
    public function withoutDot(): string
    {
        return $this->value;
    }

    /**
     * Vérifie si c'est une extension de template.
     */
    public function isTemplate(): bool
    {
        return in_array($this, [self::BLADE, self::TWIG], true);
    }

    /**
     * Vérifie si c'est une extension de code.
     */
    public function isCode(): bool
    {
        return in_array($this, [self::PHP, self::JS, self::CSS, self::SCSS, self::VUE], true);
    }

    /**
     * Retourne le type MIME associé.
     */
    public function getMimeType(): string
    {
        return match ($this) {
            self::PHP => 'text/x-php',
            self::HTML => 'text/html',
            self::BLADE => 'text/x-blade',
            self::JSON => 'application/json',
            self::XML => 'application/xml',
            self::YAML => 'text/yaml',
            self::TXT => 'text/plain',
            self::MD => 'text/markdown',
            self::CSV => 'text/csv',
            self::JS => 'text/javascript',
            self::CSS => 'text/css',
            self::SCSS => 'text/x-scss',
            self::VUE => 'text/x-vue',
            self::TWIG => 'text/x-twig',
        };
    }

    /**
     * Crée une instance depuis une chaîne.
     */
    public static function fromString(string $value): self
    {
        $value = ltrim($value, '.');

        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        throw new \InvalidArgumentException("Unknown extension: {$value}");
    }

    /**
     * Vérifie si l'extension est valide.
     */
    public static function isValid(string $value): bool
    {
        $value = ltrim($value, '.');

        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return true;
            }
        }

        return false;
    }
}
