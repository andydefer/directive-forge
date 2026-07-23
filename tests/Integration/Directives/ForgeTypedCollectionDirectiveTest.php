<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class ForgeTypedCollectionDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DirectiveTestingService(
            application: $this->app,
            sourcePaths: []
        );

        $this->tempDir = $this->service->getTempDir();

        $this->app['config']->set('directive-forge.mode', 'app');
        $this->app['config']->set('directive-forge.namespace', 'App');
        $this->app['config']->set('directive-forge.extension', 'php');
        $this->app['config']->set('directive-forge.directory_permission', 0755);

        $filesystem = new FileSystemService;
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Collections');
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Records');
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Datas');
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/ValueObjects');
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Enums');
    }

    protected function tearDown(): void
    {
        $this->service->destroy();

        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$file;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    public function test_creates_collection_for_data(): void
    {
        $response = $this->service->run('forge:typed-collection user-data data');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $dataPath = $this->tempDir.'/app/Datas/UserData.php';
        $this->assertFileExists($dataPath);

        $collectionPath = $this->tempDir.'/app/Collections/UserDataCollection.php';
        $this->assertFileExists($collectionPath);

        $content = file_get_contents($collectionPath);
        $this->assertStringContainsString('class UserDataCollection extends AbstractTypedCollection', $content);
        $this->assertStringContainsString('use App\\Datas\\UserData;', $content);
        $this->assertStringContainsString('parent::__construct(UserData::class)', $content);
    }

    public function test_creates_collection_for_data_with_short_type(): void
    {
        $response = $this->service->run('forge:typed-collection user-data d');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $dataPath = $this->tempDir.'/app/Datas/UserData.php';
        $this->assertFileExists($dataPath);

        $collectionPath = $this->tempDir.'/app/Collections/UserDataCollection.php';
        $this->assertFileExists($collectionPath);

        $content = file_get_contents($collectionPath);
        $this->assertStringContainsString('class UserDataCollection extends AbstractTypedCollection', $content);
        $this->assertStringContainsString('use App\\Datas\\UserData;', $content);
    }

    public function test_creates_collection_for_data_without_suffix(): void
    {
        $response = $this->service->run('forge:typed-collection user data');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $dataPath = $this->tempDir.'/app/Datas/UserData.php';
        $this->assertFileExists($dataPath);

        $collectionPath = $this->tempDir.'/app/Collections/UserDataCollection.php';
        $this->assertFileExists($collectionPath);

        $content = file_get_contents($collectionPath);
        $this->assertStringContainsString('class UserDataCollection extends AbstractTypedCollection', $content);
        $this->assertStringContainsString('use App\\Datas\\UserData;', $content);
    }

    public function test_creates_collection_for_record(): void
    {
        $response = $this->service->run('forge:typed-collection user-record record');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $recordPath = $this->tempDir.'/app/Records/UserRecord.php';
        $this->assertFileExists($recordPath);

        $collectionPath = $this->tempDir.'/app/Collections/UserRecordCollection.php';
        $this->assertFileExists($collectionPath);

        $content = file_get_contents($collectionPath);
        $this->assertStringContainsString('class UserRecordCollection extends AbstractTypedCollection', $content);
        $this->assertStringContainsString('use App\\Records\\UserRecord;', $content);
        $this->assertStringContainsString('parent::__construct(UserRecord::class)', $content);
    }

    public function test_creates_collection_for_record_with_short_type(): void
    {
        $response = $this->service->run('forge:typed-collection user-record r');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $recordPath = $this->tempDir.'/app/Records/UserRecord.php';
        $this->assertFileExists($recordPath);

        $collectionPath = $this->tempDir.'/app/Collections/UserRecordCollection.php';
        $this->assertFileExists($collectionPath);

        $content = file_get_contents($collectionPath);
        $this->assertStringContainsString('class UserRecordCollection extends AbstractTypedCollection', $content);
        $this->assertStringContainsString('use App\\Records\\UserRecord;', $content);
    }

    public function test_creates_collection_for_vo(): void
    {
        $response = $this->service->run('forge:typed-collection user-vo vo');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $voPath = $this->tempDir.'/app/ValueObjects/UserVO.php';
        $this->assertFileExists($voPath);

        $collectionPath = $this->tempDir.'/app/Collections/UserVOCollection.php';
        $this->assertFileExists($collectionPath);

        $content = file_get_contents($collectionPath);
        $this->assertStringContainsString('class UserVOCollection extends AbstractTypedCollection', $content);
        $this->assertStringContainsString('use App\\ValueObjects\\UserVO;', $content);
        $this->assertStringContainsString('parent::__construct(UserVO::class)', $content);
    }

    public function test_creates_collection_for_vo_with_long_type(): void
    {
        $response = $this->service->run('forge:typed-collection user-vo value-object');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $voPath = $this->tempDir.'/app/ValueObjects/UserVO.php';
        $this->assertFileExists($voPath);

        $collectionPath = $this->tempDir.'/app/Collections/UserVOCollection.php';
        $this->assertFileExists($collectionPath);

        $content = file_get_contents($collectionPath);
        $this->assertStringContainsString('class UserVOCollection extends AbstractTypedCollection', $content);
        $this->assertStringContainsString('use App\\ValueObjects\\UserVO;', $content);
    }

    public function test_creates_collection_for_enum(): void
    {
        $response = $this->service->run('forge:typed-collection user-status enum');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $enumPath = $this->tempDir.'/app/Enums/UserStatusEnum.php';
        $this->assertFileExists($enumPath);

        $collectionPath = $this->tempDir.'/app/Collections/UserStatusEnumCollection.php';
        $this->assertFileExists($collectionPath);

        $content = file_get_contents($collectionPath);
        $this->assertStringContainsString('class UserStatusEnumCollection extends AbstractTypedCollection', $content);
        $this->assertStringContainsString('use App\\Enums\\UserStatusEnum;', $content);
        $this->assertStringContainsString('parent::__construct(UserStatusEnum::class)', $content);
    }

    public function test_creates_collection_for_enum_with_short_type(): void
    {
        $response = $this->service->run('forge:typed-collection user-status e');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $enumPath = $this->tempDir.'/app/Enums/UserStatusEnum.php';
        $this->assertFileExists($enumPath);

        $collectionPath = $this->tempDir.'/app/Collections/UserStatusEnumCollection.php';
        $this->assertFileExists($collectionPath);

        $content = file_get_contents($collectionPath);
        $this->assertStringContainsString('class UserStatusEnumCollection extends AbstractTypedCollection', $content);
        $this->assertStringContainsString('use App\\Enums\\UserStatusEnum;', $content);
    }

    public function test_creates_collection_with_subdirectories(): void
    {
        $response = $this->service->run('forge:typed-collection posts.user-data data');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $dataPath = $this->tempDir.'/app/Datas/Posts/UserData.php';
        $this->assertFileExists($dataPath);

        $collectionPath = $this->tempDir.'/app/Collections/Posts/UserDataCollection.php';
        $this->assertFileExists($collectionPath);

        $content = file_get_contents($collectionPath);
        $this->assertStringContainsString('namespace App\\Collections\\Posts;', $content);
        $this->assertStringContainsString('use App\\Datas\\Posts\\UserData;', $content);
    }

    public function test_creates_collection_with_deep_subdirectories(): void
    {
        $response = $this->service->run('forge:typed-collection api.v1.posts.user-record record');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $recordPath = $this->tempDir.'/app/Records/Api/V1/Posts/UserRecord.php';
        $this->assertFileExists($recordPath);

        $collectionPath = $this->tempDir.'/app/Collections/Api/V1/Posts/UserRecordCollection.php';
        $this->assertFileExists($collectionPath);

        $content = file_get_contents($collectionPath);
        $this->assertStringContainsString('namespace App\\Collections\\Api\\V1\\Posts;', $content);
        $this->assertStringContainsString('use App\\Records\\Api\\V1\\Posts\\UserRecord;', $content);
    }

    public function test_returns_error_when_type_missing(): void
    {
        $response = $this->service->run('forge:typed-collection user-data');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Type is required', $response->output);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run('forge:typed-collection');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Item name is required', $response->output);
    }

    public function test_returns_error_when_type_invalid(): void
    {
        $response = $this->service->run('forge:typed-collection user-data invalid');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Type is required. Use "vo", "value-object", "data", "d", "record", "r", "enum", or "e"', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run('forge:typed-collection  data');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Item name is required', $response->output);
    }

    public function test_skips_item_creation_when_already_exists(): void
    {
        $this->service->run('forge:data user-data');

        $response = $this->service->run('forge:typed-collection user-data data');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $collectionPath = $this->tempDir.'/app/Collections/UserDataCollection.php';
        $this->assertFileExists($collectionPath);

        $content = file_get_contents($collectionPath);
        $this->assertStringContainsString('class UserDataCollection extends AbstractTypedCollection', $content);
        $this->assertStringContainsString('use App\\Datas\\UserData;', $content);
    }

    public function test_returns_success_code_on_success(): void
    {
        $response = $this->service->run('forge:typed-collection success-test data');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        $response = $this->service->run('forge:typed-collection invalid..name data');

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }

    public function test_works_with_create_collection_alias(): void
    {
        $response = $this->service->run('create-collection alias-test data');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $collectionPath = $this->tempDir.'/app/Collections/AliasTestDataCollection.php';
        $this->assertFileExists($collectionPath);
    }

    public function test_works_with_make_collection_alias(): void
    {
        $response = $this->service->run('make-collection alias-test-2 data');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $collectionPath = $this->tempDir.'/app/Collections/AliasTest2DataCollection.php';
        $this->assertFileExists($collectionPath);
    }
}
