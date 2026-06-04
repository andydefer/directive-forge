<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective;
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

    private function getDirective(): MakeTypedCollectionDirective
    {
        return new MakeTypedCollectionDirective($this->interaction);
    }

    private function registerAndRun(string $signature, array $arguments = []): DirectiveResponseRecord
    {
        // Arrange: Register the directive
        $directive = $this->getDirective();
        $this->registerDirective($directive);

        // Act: Run the directive
        return $this->runDirective($signature, $arguments);
    }

    public function test_get_signature_returns_make_typed_collection(): void
    {
        // Arrange
        $directive = $this->getDirective();

        // Act
        $signature = $directive->getSignature();

        // Assert
        $this->assertSame('make-typed-collection {name} {--item-type}', $signature);
    }

    public function test_get_description_returns_description(): void
    {
        // Arrange
        $directive = $this->getDirective();

        // Act
        $description = $directive->getDescription();

        // Assert
        $this->assertSame('Create a new typed collection class', $description);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        // Arrange
        $directive = $this->getDirective();

        // Act
        $aliases = $directive->getAliases();

        // Assert
        $this->assertTrue($aliases->contains('create-collection'));
        $this->assertTrue($aliases->contains('make-collection'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        // Arrange: No arguments provided

        // Act
        $response = $this->registerAndRun('make-typed-collection');

        // Assert
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_execute_returns_error_when_item_type_missing(): void
    {
        // Arrange
        $collectionName = 'user-collection';

        // Act
        $response = $this->registerAndRun('make-typed-collection', [$collectionName]);

        // Assert
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Item type is required', $response->output);
    }

    public function test_execute_creates_typed_collection_with_string_type(): void
    {
        // Arrange
        $collectionName = 'user-collection';
        $itemType = 'string';

        // Act
        $response = $this->registerAndRun('make-typed-collection', [$collectionName, "--item-type={$itemType}"]);

        $expectedPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserCollection', $content);
        // ✅ Correction : vérifier AbstractTypedCollection
        $this->assertStringContainsString('extends AbstractTypedCollection', $content);
        $this->assertStringContainsString('@extends AbstractTypedCollection<string>', $content);
        $this->assertStringContainsString('parent::__construct(string::class)', $content);
    }

    public function test_execute_creates_typed_collection_with_record_type(): void
    {
        // Arrange
        $collectionName = 'user-collection';
        $itemType = 'UserRecord';

        // Act
        $response = $this->registerAndRun('make-typed-collection', [$collectionName, "--item-type={$itemType}"]);

        $expectedPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        // ✅ Correction : vérifier AbstractTypedCollection
        $this->assertStringContainsString('@extends AbstractTypedCollection<UserRecord>', $content);
        $this->assertStringContainsString('parent::__construct(UserRecord::class)', $content);
    }

    public function test_execute_creates_typed_collection_in_subdirectory(): void
    {
        // Arrange
        $collectionName = 'admin/user-collection';
        $itemType = 'UserRecord';

        // Act
        $response = $this->registerAndRun('make-typed-collection', [$collectionName, "--item-type={$itemType}"]);

        $expectedPath = $this->directiveTempDir . '/app/Collections/Admin/UserCollection.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Collections\\Admin', $content);
        $this->assertStringContainsString('class UserCollection', $content);
        $this->assertStringContainsString('extends AbstractTypedCollection', $content);
    }

    public function test_execute_adds_collection_suffix_automatically(): void
    {
        // Arrange
        $collectionName = 'product';
        $itemType = 'ProductRecord';

        // Act
        $response = $this->registerAndRun('make-typed-collection', [$collectionName, "--item-type={$itemType}"]);

        $expectedPath = $this->directiveTempDir . '/app/Collections/ProductCollection.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ProductCollection', $content);
        $this->assertStringContainsString('extends AbstractTypedCollection', $content);
    }

    public function test_execute_does_not_double_collection_suffix(): void
    {
        // Arrange
        $collectionName = 'UserCollection';
        $itemType = 'UserRecord';

        // Act
        $response = $this->registerAndRun('make-typed-collection', [$collectionName, "--item-type={$itemType}"]);

        $expectedPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserCollection', $content);
        $this->assertStringContainsString('extends AbstractTypedCollection', $content);
        $this->assertStringNotContainsString('UserCollectionCollection', $content);
    }

    public function test_execute_requires_item_type_parameter(): void
    {
        // Arrange
        $collectionName = 'string-collection';

        // Act
        $response = $this->registerAndRun('make-typed-collection', [$collectionName]);

        // Assert
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Item type is required', $response->output);
    }
}
