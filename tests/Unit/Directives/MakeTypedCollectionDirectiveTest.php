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
        $directive = $this->getDirective();
        $this->registerDirective($directive);

        return $this->runDirective($signature, $arguments);
    }

    public function test_get_signature_returns_make_typed_collection(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the signature
        $signature = $directive->getSignature();

        // Assert: Verify the signature is correct
        $this->assertSame('make-typed-collection {name} {--item-type}', $signature);
    }

    public function test_get_description_returns_description(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the description
        $description = $directive->getDescription();

        // Assert: Verify the description is correct
        $this->assertSame('Create a new typed collection class', $description);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the aliases
        $aliases = $directive->getAliases();

        // Assert: Verify the aliases are correct
        $this->assertTrue($aliases->contains('create-collection'));
        $this->assertTrue($aliases->contains('make-collection'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        // Arrange: No arguments provided

        // Act: Run the directive without name argument
        $response = $this->registerAndRun('make-typed-collection');

        // Assert: Verify invalid argument error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_execute_returns_error_when_item_type_missing(): void
    {
        // Arrange: Provide name but no item-type option
        $collectionName = 'user-collection';

        // Act: Run the directive without item-type
        $response = $this->registerAndRun('make-typed-collection', [$collectionName]);

        // Assert: Verify item type required error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Item type is required', $response->output);
    }

    public function test_execute_creates_typed_collection_with_string_type(): void
    {
        // Arrange: Prepare collection name and string type
        $collectionName = 'user-collection';
        $itemType = 'string';

        // Act: Run the directive with string type
        $response = $this->registerAndRun('make-typed-collection', [$collectionName, "--item-type={$itemType}"]);

        $expectedPath = $this->directiveTempDir.'/app/Collections/UserCollection.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserCollection', $content);
        $this->assertStringContainsString('extends TypedCollection', $content);
        $this->assertStringContainsString('@extends TypedCollection<string>', $content);
        $this->assertStringContainsString('parent::__construct(string::class)', $content);
    }

    public function test_execute_creates_typed_collection_with_record_type(): void
    {
        // Arrange: Prepare collection name and record type
        $collectionName = 'user-collection';
        $itemType = 'UserRecord';

        // Act: Run the directive with record type
        $response = $this->registerAndRun('make-typed-collection', [$collectionName, "--item-type={$itemType}"]);

        $expectedPath = $this->directiveTempDir.'/app/Collections/UserCollection.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and file content with record type
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('@extends TypedCollection<UserRecord>', $content);
        $this->assertStringContainsString('parent::__construct(UserRecord::class)', $content);
    }

    public function test_execute_creates_typed_collection_in_subdirectory(): void
    {
        // Arrange: Prepare collection name with subdirectory and record type
        $collectionName = 'admin/user-collection';
        $itemType = 'UserRecord';

        // Act: Run the directive
        $response = $this->registerAndRun('make-typed-collection', [$collectionName, "--item-type={$itemType}"]);

        $expectedPath = $this->directiveTempDir.'/app/Collections/Admin/UserCollection.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and correct namespace
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Collections\\Admin', $content);
        $this->assertStringContainsString('class UserCollection', $content);
    }

    public function test_execute_adds_collection_suffix_automatically(): void
    {
        // Arrange: Prepare collection name without suffix
        $collectionName = 'product';
        $itemType = 'ProductRecord';

        // Act: Run the directive
        $response = $this->registerAndRun('make-typed-collection', [$collectionName, "--item-type={$itemType}"]);

        $expectedPath = $this->directiveTempDir.'/app/Collections/ProductCollection.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix was added automatically
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ProductCollection', $content);
    }

    public function test_execute_does_not_double_collection_suffix(): void
    {
        // Arrange: Prepare collection name that already has suffix
        $collectionName = 'UserCollection';
        $itemType = 'UserRecord';

        // Act: Run the directive
        $response = $this->registerAndRun('make-typed-collection', [$collectionName, "--item-type={$itemType}"]);

        $expectedPath = $this->directiveTempDir.'/app/Collections/UserCollection.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix is not duplicated
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserCollection', $content);
        $this->assertStringNotContainsString('UserCollectionCollection', $content);
    }

    public function test_execute_requires_item_type_parameter(): void
    {
        // Arrange: Provide only name without item-type
        $collectionName = 'string-collection';

        // Act: Run the directive without item-type
        $response = $this->registerAndRun('make-typed-collection', [$collectionName]);

        // Assert: Verify item type required error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Item type is required', $response->output);
    }
}
