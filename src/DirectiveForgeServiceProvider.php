<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge;

use AndyDefer\Directive\Services\DirectiveParserService;
use AndyDefer\DirectiveForge\Services\ClassGeneratorService;
use AndyDefer\DirectiveForge\Services\DirectiveExecutorService;
use AndyDefer\DirectiveForge\Services\GeneratorService;
use AndyDefer\PhpServices\Contracts\FileSystemInterface;
use AndyDefer\PhpServices\Services\FileSystemService;
use Illuminate\Support\ServiceProvider;

final class DirectiveForgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FileSystemInterface::class, FileSystemService::class);

        $this->app->singleton(DirectiveExecutorService::class, function ($app) {
            return new DirectiveExecutorService(
                $app,
                $app->make(DirectiveParserService::class)
            );
        });

        $this->app->singleton(ClassGeneratorService::class, function ($app) {
            return new ClassGeneratorService(
                $app->make(DirectiveExecutorService::class),
                $app->make(FileSystemInterface::class)
            );
        });

        $this->app->singleton(GeneratorService::class, function ($app) {
            return new GeneratorService(
                $app->make(FileSystemInterface::class)
            );
        });
    }
}
