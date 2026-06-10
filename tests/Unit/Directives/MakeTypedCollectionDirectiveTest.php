<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;

final class MakeTypedCollectionDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DirectiveTestingService($this->app);
    }

    protected function tearDown(): void
    {
        $this->service->destroy();
        parent::tearDown();
    }

    public function test_get_signature_returns_make_typed_collection(): void
    {
        $response = $this->service->run(MakeTypedCollectionDirective::class, ['test', '--item-type=string']);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
    }

    public function test_get_description_returns_description(): void
    {
        $response = $this->service->run(MakeTypedCollectionDirective::class, ['test', '--item-type=string']);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        $this->service->registerDirective(MakeTypedCollectionDirective::class);

        $response = $this->service->runDirective('create-collection', ['test-alias', '--item-type=string']);
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $response = $this->service->runDirective('make-collection', ['test-alias-2', '--item-type=string']);
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeTypedCollectionDirective::class, ['--item-type=string']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_execute_returns_error_when_item_type_missing(): void
    {
        $collectionName = 'user-collection';

        $response = $this->service->run(MakeTypedCollectionDirective::class, [$collectionName]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Item type is required', $response->output);
    }

    public function test_execute_creates_typed_collection_with_string_type(): void
    {
        $collectionName = 'user-collection';
        $itemType = 'string';

        $response = $this->service->run(MakeTypedCollectionDirective::class, [$collectionName, "--item-type={$itemType}"]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Collections/UserCollection.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserCollection', $content);
        $this->assertStringContainsString('extends AbstractTypedCollection', $content);
        $this->assertStringContainsString('@extends AbstractTypedCollection<string>', $content);
        $this->assertStringContainsString('parent::__construct(string::class)', $content);
    }

    public function test_execute_creates_typed_collection_with_record_type(): void
    {
        $collectionName = 'user-collection';
        $itemType = 'UserRecord';

        $response = $this->service->run(MakeTypedCollectionDirective::class, [$collectionName, "--item-type={$itemType}"]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Collections/UserCollection.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('@extends AbstractTypedCollection<UserRecord>', $content);
        $this->assertStringContainsString('parent::__construct(UserRecord::class)', $content);
    }

    public function test_execute_creates_typed_collection_in_subdirectory(): void
    {
        $collectionName = 'admin/user-collection';
        $itemType = 'UserRecord';

        $response = $this->service->run(MakeTypedCollectionDirective::class, [$collectionName, "--item-type={$itemType}"]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Collections/Admin/UserCollection.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Collections\\Admin', $content);
        $this->assertStringContainsString('class UserCollection', $content);
        $this->assertStringContainsString('extends AbstractTypedCollection', $content);
    }

    public function test_execute_adds_collection_suffix_automatically(): void
    {
        $collectionName = 'product';
        $itemType = 'ProductRecord';

        $response = $this->service->run(MakeTypedCollectionDirective::class, [$collectionName, "--item-type={$itemType}"]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Collections/ProductCollection.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ProductCollection', $content);
        $this->assertStringContainsString('extends AbstractTypedCollection', $content);
    }

    public function test_execute_does_not_double_collection_suffix(): void
    {
        $collectionName = 'UserCollection';
        $itemType = 'UserRecord';

        $response = $this->service->run(MakeTypedCollectionDirective::class, [$collectionName, "--item-type={$itemType}"]);

        $tempDir = $this->service->getContext()->getTempDir();
        $expectedPath = $tempDir . '/app/Collections/UserCollection.php';
        $content = file_get_contents($expectedPath);

        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('typed-collection created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserCollection', $content);
        $this->assertStringContainsString('extends AbstractTypedCollection', $content);
        $this->assertStringNotContainsString('UserCollectionCollection', $content);
    }

    public function test_execute_requires_item_type_parameter(): void
    {
        $collectionName = 'string-collection';

        $response = $this->service->run(MakeTypedCollectionDirective::class, [$collectionName]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Item type is required', $response->output);
    }
}
