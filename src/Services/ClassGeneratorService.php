<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Services;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\DirectiveForge\Enums\SupportedType;
use AndyDefer\DirectiveForge\ValueObjects\FilePathVO;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;

final class ClassGeneratorService
{
    public function __construct(
        private readonly DirectiveExecutorService $executor,
        private readonly FileSystemInterface $filesystem
    ) {}

    public function ensureClassExists(
        FilePathVO $filePath,
        string $type,
        string $baseNamespace,
        string $mode = 'app',
        ?string $baseDirectory = null
    ): DirectiveResponseRecord {
        $fullPath = $this->buildFullPath($filePath, $mode, $baseDirectory);

        if ($this->filesystem->exists($fullPath)) {
            return new DirectiveResponseRecord(
                ExitCode::SUCCESS,
                'Class already exists: '.$this->buildFullClass($filePath, $type, $baseNamespace)
            );
        }

        $fullClass = $this->buildFullClass($filePath, $type, $baseNamespace);

        $supportedType = SupportedType::fromType($type);
        if ($supportedType === null) {
            return new DirectiveResponseRecord(
                ExitCode::INVALID_ARGUMENT,
                'Unsupported type: '.$type
            );
        }

        $directiveClass = $supportedType->getDirectiveClass();

        $segments = $filePath->getSegments()->toArray();
        array_shift($segments);
        $itemPath = implode('.', $segments);

        $args = [
            $itemPath,
            '--description=Generated for '.$fullClass,
        ];

        $response = $this->executor->run($directiveClass, $args);

        if ($response->exit_code !== ExitCode::SUCCESS) {
            return $response;
        }

        if (! $this->filesystem->exists($fullPath)) {
            return new DirectiveResponseRecord(
                ExitCode::FAILURE,
                'Class was not created after generation: '.$fullClass
            );
        }

        return new DirectiveResponseRecord(
            ExitCode::SUCCESS,
            'Class generated successfully: '.$fullClass
        );
    }

    public function getTypeFromPath(FilePathVO $filePath): string
    {
        $segments = $filePath->getSegments()->toArray();

        return ucfirst($segments[0] ?? 'Data');
    }

    public function getClassName(FilePathVO $filePath): string
    {
        return $filePath->getFileName();
    }

    public function buildFullClass(FilePathVO $filePath, string $type, string $baseNamespace): string
    {
        $segments = $filePath->getSegments()->toArray();
        $typeName = ucfirst($type);
        $className = $filePath->getFileName();

        $folders = array_slice($segments, 1, -1);
        $folderNamespace = '';

        if (! empty($folders)) {
            $folderNamespace = '\\'.implode('\\', array_map('ucfirst', $folders));
        }

        return $baseNamespace.'\\'.$typeName.$folderNamespace.'\\'.$className;
    }

    public function buildFullPath(FilePathVO $filePath, string $mode = 'app', ?string $baseDirectory = null): string
    {
        $basePath = $mode === 'app' ? 'app' : 'src';
        $root = $baseDirectory ?? getcwd();

        return $root.'/'.$basePath.'/'.$filePath->getFilePath();
    }
}
