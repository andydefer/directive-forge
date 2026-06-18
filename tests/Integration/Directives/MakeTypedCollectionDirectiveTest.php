<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeDataDirective;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective;
use AndyDefer\DirectiveForge\Directives\MakeValueObjectDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class MakeTypedCollectionDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DirectiveTestingService($this->app);

        $this->service->registerDirective(MakeRecordDirective::class);
        $this->service->registerDirective(MakeDataDirective::class);
        $this->service->registerDirective(MakeValueObjectDirective::class);

        $this->tempDir = $this->service->getTempDir();

        $this->app['config']->set('directive-forge.mode', 'app');
        $this->app['config']->set('directive-forge.namespace', 'App');
        $this->app['config']->set('directive-forge.extension', 'php');
        $this->app['config']->set('directive-forge.directory_permission', 0755);

        $this->createDirectories();
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

    private function createDirectories(): void
    {
        $filesystem = new FileSystemService;
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Collections');
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Records');
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/Datas');
        $filesystem->ensureDirectoryExists($this->tempDir.'/app/ValueObjects');
    }

    public function test_creates_collection_for_data(): void
    {

        $response = $this->service->run(MakeTypedCollectionDirective::class, [
            'user-data',
        ]);

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

    public function test_creates_collection_with_subdirectories(): void
    {

        $response = $this->service->run(MakeTypedCollectionDirective::class, [
            'posts.user-data',
        ]);

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

        $response = $this->service->run(MakeTypedCollectionDirective::class, [
            'api.v1.posts.user-record',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $recordPath = $this->tempDir.'/app/Records/Api/V1/Posts/UserRecord.php';
        $this->assertFileExists($recordPath);

        $collectionPath = $this->tempDir.'/app/Collections/Api/V1/Posts/UserRecordCollection.php';
        $this->assertFileExists($collectionPath);

        $content = file_get_contents($collectionPath);

        $this->assertStringContainsString('namespace App\\Collections\\Api\\V1\\Posts;', $content);
        $this->assertStringContainsString('use App\\Records\\Api\\V1\\Posts\\UserRecord;', $content);

    }

    public function test_creates_collection_for_value_object(): void
    {

        $response = $this->service->run(MakeTypedCollectionDirective::class, [
            'user-vo',
        ]);

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

    public function test_creates_collection_for_record(): void
    {

        $response = $this->service->run(MakeTypedCollectionDirective::class, [
            'user-record',
        ]);

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
}
