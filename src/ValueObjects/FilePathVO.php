<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\ValueObjects;

use AndyDefer\DirectiveForge\Enums\ExtensionType;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

/**
 * Value Object représentant un chemin de fichier.
 *
 * Transforme une chaîne kebab-case/dot-case en structure de chemin normalisée.
 *
 * Règles :
 * - Les points (.) séparent les dossiers
 * - Les tirets (-) séparent les mots dans le nom
 * - Le dernier segment devient le nom du fichier en PascalCase
 * - Les autres segments deviennent des dossiers en PascalCase
 *
 * @example
 * $path = new FilePathVO('domain.users.profile-action');
 * $path->getSegments();          // StringTypedCollection(["domain", "users", "profile-action"])
 * $path->getFolders();           // StringTypedCollection(["Domain", "Users"])
 * $path->getPathFolders();       // StringTypedCollection(["Domain", "Domain/Users"])
 * $path->getPathSegments();      // StringTypedCollection(["Domain", "Domain/Users", "Domain/Users/ProfileAction.php"])
 * $path->getFileName();          // "ProfileAction"
 * $path->getFullPath();          // "Domain/Users/ProfileAction"
 * $path->getFilePath();          // "Domain/Users/ProfileAction.php"
 * $path->getNamespace();         // "Domain\Users"
 * $path->getDepth();             // 2
 */
final class FilePathVO extends AbstractValueObject
{
    private readonly string $raw;

    private readonly StringTypedCollection $segments;

    private readonly StringTypedCollection $folders;

    private readonly string $baseName;

    private readonly string $fileName;

    private readonly string $directoryPath;

    private readonly string $fullPath;

    private readonly string $filePath;

    private readonly StringTypedCollection $pathFolders;

    private readonly StringTypedCollection $pathSegments;

    public function __construct(
        string $value,
        private readonly ExtensionType $extension = ExtensionType::PHP
    ) {
        $this->validate($value);

        $this->raw = $value;

        // 1. Parser les segments (séparés par des points)
        $segmentCollection = new StringTypedCollection;
        foreach (explode('.', $value) as $segment) {
            $segmentCollection->add($segment);
        }
        $this->segments = $segmentCollection;

        // 2. Extraire le nom de base (dernier segment)
        $segmentsArray = $this->segments->toArray();
        $this->baseName = end($segmentsArray);

        // 3. Extraire les dossiers (tous les segments sauf le dernier)
        $folderCollection = new StringTypedCollection;
        $folderNames = array_slice($segmentsArray, 0, -1);
        foreach ($folderNames as $folder) {
            $folderCollection->add($this->toPascalCase($folder));
        }
        $this->folders = $folderCollection;

        // 4. Construire le nom du fichier en PascalCase
        $this->fileName = $this->toPascalCase($this->baseName);

        // 5. Construire le chemin des dossiers
        $this->directoryPath = $this->folders->join(DIRECTORY_SEPARATOR);

        // 6. Construire le chemin complet (sans extension)
        $this->fullPath = $this->directoryPath !== ''
            ? $this->directoryPath.DIRECTORY_SEPARATOR.$this->fileName
            : $this->fileName;

        // 7. Construire le chemin avec extension
        $this->filePath = $this->fullPath.$this->extension->withDot();

        // 8. Construire les chemins des dossiers progressifs
        $pathFolderCollection = new StringTypedCollection;
        $currentPath = '';
        $folderArray = $this->folders->toArray();
        foreach ($folderArray as $folder) {
            $currentPath = $currentPath !== ''
                ? $currentPath.DIRECTORY_SEPARATOR.$folder
                : $folder;
            $pathFolderCollection->add($currentPath);
        }
        $this->pathFolders = $pathFolderCollection;

        // 9. Construire les chemins complets progressifs AVEC extension
        $pathSegmentCollection = new StringTypedCollection;
        $currentPath = '';
        $allSegments = array_merge($folderArray, [$this->fileName]);
        $totalSegments = count($allSegments);

        foreach ($allSegments as $index => $segment) {
            $currentPath = $currentPath !== ''
                ? $currentPath.DIRECTORY_SEPARATOR.$segment
                : $segment;

            // Ajouter l'extension uniquement sur le dernier segment (le fichier)
            if ($index === $totalSegments - 1) {
                $pathSegmentCollection->add($currentPath.$this->extension->withDot());
            } else {
                $pathSegmentCollection->add($currentPath);
            }
        }
        $this->pathSegments = $pathSegmentCollection;
    }

    // ============ Getters ============

    /**
     * Retourne la valeur brute d'origine.
     */
    public function getRaw(): string
    {
        return $this->raw;
    }

    /**
     * Retourne tous les segments bruts.
     */
    public function getSegments(): StringTypedCollection
    {
        return $this->segments;
    }

    /**
     * Retourne les dossiers en PascalCase.
     */
    public function getFolders(): StringTypedCollection
    {
        return $this->folders;
    }

    /**
     * Retourne les chemins des dossiers progressifs.
     *
     * @example ["Domain", "Domain/Users"]
     */
    public function getPathFolders(): StringTypedCollection
    {
        return $this->pathFolders;
    }

    /**
     * Retourne les chemins complets progressifs AVEC extension sur le dernier élément.
     *
     * @example ["Domain", "Domain/Users", "Domain/Users/ProfileAction.php"]
     */
    public function getPathSegments(): StringTypedCollection
    {
        return $this->pathSegments;
    }

    /**
     * Retourne le nom de base (dernier segment brut).
     */
    public function getBaseName(): string
    {
        return $this->baseName;
    }

    /**
     * Retourne le nom du fichier en PascalCase.
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * Retourne le chemin des dossiers.
     */
    public function getDirectoryPath(): string
    {
        return $this->directoryPath;
    }

    /**
     * Retourne le chemin complet sans extension.
     */
    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    /**
     * Retourne le chemin complet avec extension.
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Retourne l'extension.
     */
    public function getExtension(): ExtensionType
    {
        return $this->extension;
    }

    // ============ Méthodes utiles ============

    /**
     * Retourne le namespace (dossiers avec backslash).
     */
    public function getNamespace(): string
    {
        return $this->folders->join('\\');
    }

    /**
     * Retourne le namespace complet avec le nom de la classe.
     */
    public function getFullNamespace(): string
    {
        $namespace = $this->getNamespace();

        return $namespace !== ''
            ? $namespace.'\\'.$this->fileName
            : $this->fileName;
    }

    /**
     * Retourne la profondeur (nombre de dossiers).
     */
    public function getDepth(): int
    {
        return $this->folders->count();
    }

    /**
     * Vérifie si le chemin a des dossiers.
     */
    public function hasFolders(): bool
    {
        return $this->folders->isNotEmpty();
    }

    /**
     * Vérifie si le chemin est dans un sous-dossier spécifique.
     */
    public function isInSubDirectory(string $directory): bool
    {
        return $this->folders->contains($directory);
    }

    /**
     * Vérifie si le chemin commence par un dossier spécifique.
     */
    public function startsWithFolder(string $folder): bool
    {
        $folders = $this->folders->toArray();

        return ! empty($folders) && $folders[0] === $folder;
    }

    /**
     * Vérifie si le chemin se termine par un dossier spécifique.
     */
    public function endsWithFolder(string $folder): bool
    {
        $folders = $this->folders->toArray();

        return ! empty($folders) && end($folders) === $folder;
    }

    /**
     * Retourne le chemin relatif depuis un dossier parent.
     */
    public function getRelativePath(string $baseDirectory): string
    {
        $base = trim($baseDirectory, '/\\');

        // Normaliser le baseDirectory en PascalCase pour correspondre aux dossiers
        $baseNormalized = $this->toPascalCase($base);

        $full = $this->getFilePath();

        if (str_starts_with($full, $baseNormalized.DIRECTORY_SEPARATOR)) {
            return substr($full, strlen($baseNormalized) + 1);
        }

        // Si on ne trouve pas le dossier, retourner le chemin complet formaté
        return $full;
    }

    /**
     * Crée une nouvelle instance avec une extension différente.
     */
    public function withExtension(ExtensionType $extension): self
    {
        return new self($this->raw, $extension);
    }

    /**
     * Crée une nouvelle instance avec un suffixe ajouté au nom du fichier.
     */
    public function withSuffix(string $suffix): self
    {
        $newRaw = $this->raw.'-'.$suffix;

        return new self($newRaw, $this->extension);
    }

    /**
     * Crée une nouvelle instance avec un préfixe ajouté au nom du fichier.
     */
    public function withPrefix(string $prefix): self
    {
        $segments = $this->segments->toArray();
        $last = array_pop($segments);
        $newLast = $prefix.'-'.$last;
        $segments[] = $newLast;
        $newRaw = implode('.', $segments);

        return new self($newRaw, $this->extension);
    }

    // ============ Implémentation AbstractValueObject ============

    /**
     * Retourne le chemin complet formatté.
     */
    public function getValue(): string
    {
        return $this->fullPath;
    }

    // ============ Méthodes privées ============

    private function validate(string $value): void
    {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('FilePathVO cannot be empty');
        }

        if (strlen($value) < 2) {
            throw new \InvalidArgumentException('FilePathVO must be at least 2 characters');
        }

        if (strlen($value) > 100) {
            throw new \InvalidArgumentException('FilePathVO cannot exceed 100 characters');
        }

        if (! preg_match('/^[a-zA-Z0-9\-\.]+$/', $value)) {
            throw new \InvalidArgumentException('FilePath contains invalid characters');
        }

        if (str_contains($value, '..')) {
            throw new \InvalidArgumentException('FilePathVO cannot contain consecutive dots');
        }

        if (str_contains($value, '--')) {
            throw new \InvalidArgumentException('FilePathVO cannot contain consecutive hyphens');
        }

        if (str_starts_with($value, '.')) {
            throw new \InvalidArgumentException('FilePathVO cannot start with a dot');
        }

        if (str_starts_with($value, '-')) {
            throw new \InvalidArgumentException('FilePathVO cannot start with a hyphen');
        }

        if (str_ends_with($value, '.')) {
            throw new \InvalidArgumentException('FilePathVO cannot end with a dot');
        }

        if (str_ends_with($value, '-')) {
            throw new \InvalidArgumentException('FilePathVO cannot end with a hyphen');
        }
    }

    private function toPascalCase(string $value): string
    {
        $value = str_replace('-', ' ', $value);
        $value = ucwords($value);

        return str_replace(' ', '', $value);
    }
}
