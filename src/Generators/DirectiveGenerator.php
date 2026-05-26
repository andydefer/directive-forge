<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Generators;

use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Services\DirectiveNamingService;
use AndyDefer\Directive\Services\SignatureValidationService;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

final class DirectiveGenerator extends AbstractGenerator
{
    private SignatureValidationService $signatureValidator;
    private DirectiveNamingService $namingService;

    public function __construct(
        DirectiveInteractionService $interaction,
        SignatureValidationService $signatureValidator,
        DirectiveNamingService $namingService,
    ) {
        parent::__construct($interaction);
        $this->type = GeneratorType::DIRECTIVE;
        $this->signatureValidator = $signatureValidator;
        $this->namingService = $namingService;
    }

    public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): array
    {
        $config = $this->type->getConfig();
        $className = $pathInfo->className;
        $namespace = $pathInfo->getNamespace($config->baseNamespace);
        $signature = $this->extractSignature($className, $config->suffix);

        return [
            '{{namespace}}' => $namespace,
            '{{signature}}' => $signature,
            '{{class}}' => $className,
            '{{description}}' => "Description for {$signature}",
            '{{date}}' => date('Y-m-d H:i:s'),
        ];
    }

    public function validate(string $name): bool
    {
        $baseName = basename($name);
        $validation = $this->signatureValidator->validate($baseName);

        if (!$validation->isValid) {
            $this->interaction->error($validation->error ?? 'Invalid directive name format');
            return false;
        }

        return true;
    }
}
