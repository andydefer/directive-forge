<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class MakeTypedCollectionDirectiveTest extends UnitTestCase
{
    use InteractsWithDirectives;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initDirectiveTesting();
    }

    protected function tearDown(): void
    {
        $this->destroyDirectiveTesting();
        parent::tearDown();
    }

    public function test_get_signature_returns_make_typed_collection(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective::class);

        $this->assertSame('make-typed-collection {name} {--item-type}', $directive->getSignature());
    }

    public function test_get_description_returns_description(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective::class);

        $this->assertSame('Create a new typed collection class', $directive->getDescription());
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $directive = $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective::class);
        $aliases = $directive->getAliases();

        $this->assertTrue($aliases->contains('create-collection'));
        $this->assertTrue($aliases->contains('make-collection'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective::class);

        $response = $this->runDirective('make-typed-collection');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->getExitCode());
        // Le message d'erreur réel vient du kernel Laravel Directive
        $this->assertStringContainsString('Not enough arguments', $response->getOutput());
    }

    public function test_execute_returns_error_when_item_type_missing(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective::class);

        $response = $this->runDirective('make-typed-collection', ['user-collection']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->getExitCode());
        // Ce message vient de la directive elle-même (option manquante)
        $this->assertStringContainsString('Item type is required', $response->getOutput());
    }

    public function test_execute_creates_typed_collection_with_string_type(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective::class);

        $response = $this->runDirective('make-typed-collection', ['user-collection', '--item-type=string']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->getOutput()));

        $expectedPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserCollection', $content);
        $this->assertStringContainsString('extends TypedCollection', $content);
        $this->assertStringContainsString('@extends TypedCollection<string>', $content);
        // Le stub utilise string::class et non 'string'
        $this->assertStringContainsString("parent::__construct(string::class)", $content);
    }

    public function test_execute_creates_typed_collection_with_record_type(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective::class);

        $response = $this->runDirective('make-typed-collection', ['user-collection', '--item-type=UserRecord']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->getOutput()));

        $expectedPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('@extends TypedCollection<UserRecord>', $content);
        $this->assertStringContainsString("parent::__construct(UserRecord::class)", $content);
    }

    public function test_execute_creates_typed_collection_in_subdirectory(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective::class);

        $response = $this->runDirective('make-typed-collection', ['admin/user-collection', '--item-type=UserRecord']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->getOutput()));

        $expectedPath = $this->directiveTempDir . '/app/Collections/Admin/UserCollection.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('namespace App\\Collections\\Admin', $content);
        $this->assertStringContainsString('class UserCollection', $content);
    }

    public function test_execute_adds_collection_suffix_automatically(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective::class);

        $response = $this->runDirective('make-typed-collection', ['product', '--item-type=ProductRecord']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());

        $expectedPath = $this->directiveTempDir . '/app/Collections/ProductCollection.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class ProductCollection', $content);
    }

    public function test_execute_does_not_double_collection_suffix(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective::class);

        $response = $this->runDirective('make-typed-collection', ['UserCollection', '--item-type=UserRecord']);

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());

        $expectedPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UserCollection', $content);
        $this->assertStringNotContainsString('UserCollectionCollection', $content);
    }

    public function test_execute_requires_item_type_parameter(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective::class);

        // Le test doit échouer car item-type est requis
        $response = $this->runDirective('make-typed-collection', ['string-collection']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->getExitCode());
        $this->assertStringContainsString('Item type is required', $response->getOutput());
    }
}
