<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Services;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Services\DirectiveHydratorService;
use AndyDefer\Directive\Services\DirectiveParserService;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use Illuminate\Contracts\Container\Container;
use Throwable;

final class DirectiveExecutorService
{
    private ?string $baseDirectory = null;

    public function __construct(
        private readonly Container $container,
        private readonly DirectiveParserService $parser
    ) {}

    public function setBaseDirectory(string $directory): self
    {
        $this->baseDirectory = $directory;

        return $this;
    }

    public function run(string $class, array $arguments = []): DirectiveResponseRecord
    {
        try {
            if (! class_exists($class)) {
                return new DirectiveResponseRecord(
                    ExitCode::NOT_FOUND,
                    "Directive class {$class} does not exist"
                );
            }

            $originalCwd = null;
            if ($this->baseDirectory !== null && is_dir($this->baseDirectory)) {
                $originalCwd = getcwd();
                chdir($this->baseDirectory);
            }

            try {
                $directive = $this->hydrateDirective($class, $arguments);

                ob_start();
                try {
                    $exitCode = $directive->execute();
                    $output = ob_get_clean();

                    if ($originalCwd !== null) {
                        chdir($originalCwd);
                    }

                    return new DirectiveResponseRecord($exitCode, $output);
                } catch (Throwable $e) {
                    ob_end_clean();
                    if ($originalCwd !== null) {
                        chdir($originalCwd);
                    }

                    return new DirectiveResponseRecord(ExitCode::FAILURE, $e->getMessage());
                }
            } catch (Throwable $e) {
                if ($originalCwd !== null) {
                    chdir($originalCwd);
                }
                throw $e;
            }

        } catch (Throwable $e) {
            return new DirectiveResponseRecord(ExitCode::FAILURE, $e->getMessage());
        }
    }

    private function hydrateDirective(string $class, array $arguments): AbstractDirective
    {
        $reflection = new \ReflectionClass($class);
        $tempInstance = $reflection->newInstanceWithoutConstructor();

        $signature = $tempInstance->getSignature();

        $argv = new StringTypedCollection;
        foreach ($arguments as $arg) {
            $argv->add((string) $arg);
        }

        $parsed = $this->parser->parse($signature, $argv);

        $hydrator = $this->container->make(DirectiveHydratorService::class);

        return $hydrator->hydrate($class, $parsed);
    }
}
