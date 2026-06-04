<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge;

use AndyDefer\Directive\DirectiveKernel;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Services\DirectiveNamingService;
use AndyDefer\Directive\Services\SignatureValidationService;
use AndyDefer\DirectiveForge\Directives\MakeActionDirective;
use AndyDefer\DirectiveForge\Directives\MakeConfigDirective;
use AndyDefer\DirectiveForge\Directives\MakeDirective;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective;
use AndyDefer\DirectiveForge\Directives\MakeRequestDirective;
use AndyDefer\DirectiveForge\Directives\MakeServiceDirective;
use AndyDefer\DirectiveForge\Directives\MakeTaskDirective;
use AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective;
use AndyDefer\DirectiveForge\Directives\MakeValueObjectDirective;
use Illuminate\Support\ServiceProvider;

final class DirectiveForgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // MakeDirective
        $this->app->singleton(MakeDirective::class, function ($app) {
            return new MakeDirective(
                interaction: $app->make(DirectiveInteractionService::class),
                signatureValidator: $app->make(SignatureValidationService::class),
                namingService: $app->make(DirectiveNamingService::class),
            );
        });

        // MakeActionDirective - avec injection du Kernel
        $this->app->singleton(MakeActionDirective::class, function ($app) {
            return new MakeActionDirective(
                interaction: $app->make(DirectiveInteractionService::class),
                kernel: $app->make(DirectiveKernel::class),
            );
        });

        // MakeTaskDirective
        $this->app->singleton(MakeTaskDirective::class, function ($app) {
            return new MakeTaskDirective(
                interaction: $app->make(DirectiveInteractionService::class),
            );
        });

        // MakeRepositoryDirective
        $this->app->singleton(MakeRepositoryDirective::class, function ($app) {
            return new MakeRepositoryDirective(
                interaction: $app->make(DirectiveInteractionService::class),
            );
        });

        // MakeRecordDirective
        $this->app->singleton(MakeRecordDirective::class, function ($app) {
            return new MakeRecordDirective(
                interaction: $app->make(DirectiveInteractionService::class),
            );
        });

        // MakeTypedCollectionDirective
        $this->app->singleton(MakeTypedCollectionDirective::class, function ($app) {
            return new MakeTypedCollectionDirective(
                interaction: $app->make(DirectiveInteractionService::class),
            );
        });

        // MakeServiceDirective
        $this->app->singleton(MakeServiceDirective::class, function ($app) {
            return new MakeServiceDirective(
                interaction: $app->make(DirectiveInteractionService::class),
            );
        });

        // MakeRequestDirective
        $this->app->singleton(MakeRequestDirective::class, function ($app) {
            return new MakeRequestDirective(
                interaction: $app->make(DirectiveInteractionService::class),
            );
        });

        // MakeValueObjectDirective
        $this->app->singleton(MakeValueObjectDirective::class, function ($app) {
            return new MakeValueObjectDirective(
                interaction: $app->make(DirectiveInteractionService::class),
            );
        });

        // MakeConfigDirective
        $this->app->singleton(MakeConfigDirective::class, function ($app) {
            return new MakeConfigDirective(
                interaction: $app->make(DirectiveInteractionService::class),
            );
        });
    }

    public function boot(): void
    {
        // No bootstrapping needed
    }
}
