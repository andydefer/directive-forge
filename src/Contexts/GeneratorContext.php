<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Contexts;

use AndyDefer\DirectiveForge\ValueObjects\FilePathVO;
use AndyDefer\DirectiveForge\ValueObjects\StubVO;
use AndyDefer\DirectiveForge\ValueObjects\UnixTimestampVO;

/**
 * Contexte de génération de fichier.
 *
 * Contient toutes les informations relatives à une opération de génération.
 */
final class GeneratorContext
{
    private bool $success;

    private string $fullPath;

    private ?string $error;

    private ?int $bytes;

    private ?string $content;

    private ?StubVO $stub;

    private ?FilePathVO $path;

    private ?string $baseDirectory;

    private UnixTimestampVO $startTime;

    private UnixTimestampVO $endTime;

    public function __construct()
    {
        $this->success = false;
        $this->fullPath = '';
        $this->error = null;
        $this->bytes = null;
        $this->content = null;
        $this->stub = null;
        $this->path = null;
        $this->baseDirectory = null;
        $this->startTime = new UnixTimestampVO;
        $this->endTime = new UnixTimestampVO;
    }

    // ============ Setters ============

    public function setSuccess(bool $success): self
    {
        $this->success = $success;

        return $this;
    }

    public function setFullPath(string $fullPath): self
    {
        $this->fullPath = $fullPath;

        return $this;
    }

    public function setError(string $error): self
    {
        $this->error = $error;

        return $this;
    }

    public function setBytes(int $bytes): self
    {
        $this->bytes = $bytes;

        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function setStub(StubVO $stub): self
    {
        $this->stub = $stub;

        return $this;
    }

    public function setPath(FilePathVO $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function setBaseDirectory(string $baseDirectory): self
    {
        $this->baseDirectory = $baseDirectory;

        return $this;
    }

    public function setEndTime(UnixTimestampVO $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    // ============ Getters ============

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getBytes(): ?int
    {
        return $this->bytes;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getStub(): ?StubVO
    {
        return $this->stub;
    }

    public function getPath(): ?FilePathVO
    {
        return $this->path;
    }

    public function getBaseDirectory(): ?string
    {
        return $this->baseDirectory;
    }

    public function getStartTime(): UnixTimestampVO
    {
        return $this->startTime;
    }

    public function getEndTime(): UnixTimestampVO
    {
        return $this->endTime;
    }

    /**
     * Retourne la durée en secondes (avec décimales).
     */
    public function getDuration(): float
    {
        return (float) ($this->endTime->getValue() - $this->startTime->getValue());
    }

    /**
     * Retourne la durée en millisecondes.
     */
    public function getDurationInMilliseconds(): float
    {
        return $this->getDuration() * 1000;
    }

    public function getMessage(): string
    {
        if ($this->success) {
            return sprintf(
                'File created successfully: %s (%d bytes) in %.2f ms',
                $this->fullPath,
                $this->bytes ?? 0,
                $this->getDurationInMilliseconds()
            );
        }

        return sprintf(
            'Failed to create file: %s - %s',
            $this->fullPath,
            $this->error ?? 'Unknown error'
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'fullPath' => $this->fullPath,
            'error' => $this->error,
            'bytes' => $this->bytes,
            'content' => $this->content,
            'baseDirectory' => $this->baseDirectory,
            'startTime' => $this->startTime->getValue(),
            'endTime' => $this->endTime->getValue(),
            'duration' => $this->getDuration(),
            'durationInMilliseconds' => $this->getDurationInMilliseconds(),
            'message' => $this->getMessage(),
        ];
    }
}
