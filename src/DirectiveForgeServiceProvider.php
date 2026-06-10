<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge;

use AndyDefer\Directive\Contexts\DirectiveContext;
use AndyDefer\Directive\Contexts\LaravelBootstrapperContext;
use AndyDefer\Directive\Records\DirectiveBlueprintRecord;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Services\DirectiveNamingService;
use AndyDefer\Directive\Services\FileCreatorService;
use AndyDefer\Directive\Services\SignatureValidationService;
use AndyDefer\DirectiveForge\Directives\MakeActionDirective;
use AndyDefer\DirectiveForge\Directives\MakeConfigDirective;
use AndyDefer\DirectiveForge\Directives\MakeDataDirective;
use AndyDefer\DirectiveForge\Directives\MakeDirective;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective;
use AndyDefer\DirectiveForge\Directives\MakeRequestDirective;
use AndyDefer\DirectiveForge\Directives\MakeServiceDirective;
use AndyDefer\DirectiveForge\Directives\MakeTaskDirective;
use AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective;
use AndyDefer\DirectiveForge\Directives\MakeValueObjectDirective;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use Illuminate\Support\ServiceProvider;

final class DirectiveForgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MakeDirective::class, function ($app) {
            return new MakeDirective(
                context: $this->createDirectiveContext(),
                interaction: $app->make(DirectiveInteractionService::class),
                fileCreator: $app->make(FileCreatorService::class),
                signatureValidator: $app->make(SignatureValidationService::class),
                namingService: $app->make(DirectiveNamingService::class),
            );
        });

        $this->app->singleton(MakeActionDirective::class, function ($app) {
            return new MakeActionDirective(
                context: $this->createDirectiveContext(),
                interaction: $app->make(DirectiveInteractionService::class),
                fileCreator: $app->make(FileCreatorService::class),
            );
        });

        $this->app->singleton(MakeTaskDirective::class, function ($app) {
            return new MakeTaskDirective(
                context: $this->createDirectiveContext(),
                interaction: $app->make(DirectiveInteractionService::class),
                fileCreator: $app->make(FileCreatorService::class),
            );
        });

        $this->app->singleton(MakeRepositoryDirective::class, function ($app) {
            return new MakeRepositoryDirective(
                context: $this->createDirectiveContext(),
                interaction: $app->make(DirectiveInteractionService::class),
                fileCreator: $app->make(FileCreatorService::class),
            );
        });

        $this->app->singleton(MakeRecordDirective::class, function ($app) {
            return new MakeRecordDirective(
                context: $this->createDirectiveContext(),
                interaction: $app->make(DirectiveInteractionService::class),
                fileCreator: $app->make(FileCreatorService::class),
            );
        });

        $this->app->singleton(MakeTypedCollectionDirective::class, function ($app) {
            return new MakeTypedCollectionDirective(
                context: $this->createDirectiveContext(),
                interaction: $app->make(DirectiveInteractionService::class),
                fileCreator: $app->make(FileCreatorService::class),
            );
        });

        $this->app->singleton(MakeServiceDirective::class, function ($app) {
            return new MakeServiceDirective(
                context: $this->createDirectiveContext(),
                interaction: $app->make(DirectiveInteractionService::class),
                fileCreator: $app->make(FileCreatorService::class),
            );
        });

        $this->app->singleton(MakeRequestDirective::class, function ($app) {
            return new MakeRequestDirective(
                context: $this->createDirectiveContext(),
                interaction: $app->make(DirectiveInteractionService::class),
                fileCreator: $app->make(FileCreatorService::class),
            );
        });

        $this->app->singleton(MakeValueObjectDirective::class, function ($app) {
            return new MakeValueObjectDirective(
                context: $this->createDirectiveContext(),
                interaction: $app->make(DirectiveInteractionService::class),
                fileCreator: $app->make(FileCreatorService::class),
            );
        });

        $this->app->singleton(MakeConfigDirective::class, function ($app) {
            return new MakeConfigDirective(
                context: $this->createDirectiveContext(),
                interaction: $app->make(DirectiveInteractionService::class),
                fileCreator: $app->make(FileCreatorService::class),
            );
        });

        $this->app->singleton(MakeDataDirective::class, function ($app) {
            return new MakeDataDirective(
                context: $this->createDirectiveContext(),
                interaction: $app->make(DirectiveInteractionService::class),
                fileCreator: $app->make(FileCreatorService::class),
            );
        });
    }

    private function createDirectiveContext(): DirectiveContext
    {
        return new DirectiveContext(
            laravelBootstrapper: $this->app->make(LaravelBootstrapperContext::class),
            blueprint: new DirectiveBlueprintRecord('', '', ''),
            aliases: new StringTypedCollection,
            shouldBootLaravel: true,
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/directive-forge'),
        ], 'directive-forge-stubs');
    }
}
