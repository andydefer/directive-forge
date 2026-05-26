<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Contracts;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

interface GeneratorInterface
{
    public function generate(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): ExitCode;

    public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): array;
}
