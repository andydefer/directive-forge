<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Services;

use AndyDefer\Directive\Records\ReplacementRecord;
use AndyDefer\DirectiveForge\Enums\ExtensionType;
use AndyDefer\DirectiveForge\Services\GeneratorService;
use AndyDefer\DirectiveForge\ValueObjects\FilePathVO;
use AndyDefer\DirectiveForge\ValueObjects\StubVO;
use AndyDefer\DirectiveForge\ValueObjects\UnixTimestampVO;
use AndyDefer\PhpServices\Services\FileSystemService;
use PHPUnit\Framework\TestCase;

final class GeneratorServiceTest extends TestCase
{
    private string $tempDir;

    private GeneratorService $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/generator_test_'.uniqid();
        mkdir($this->tempDir, 0777, true);

        $this->generator = new GeneratorService(new FileSystemService);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
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

    /**
     * Crée un répertoire en lecture seule pour les tests.
     */
    private function createReadonlyDirectory(): string
    {
        $dir = sys_get_temp_dir().'/readonly_'.uniqid();
        mkdir($dir, 0555, true);

        return $dir;
    }

    /**
     * Supprime un répertoire en lecture seule (en rétablissant les permissions d'abord).
     */
    private function removeReadonlyDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        chmod($dir, 0777);
        $this->removeDirectory($dir);
    }

    // ==================== TESTS GENERATE ====================

    public function test_generate_creates_file_successfully(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));

        $path = new FilePathVO('Greetings.hello');

        $context = $this->generator->generate($stub, $path, $this->tempDir);

        $this->assertTrue($context->isSuccess());
        $this->assertSame($this->tempDir.'/Greetings/Hello.php', $context->getFullPath());
        $this->assertSame('Hello John!', $context->getContent());
        $this->assertGreaterThan(0, $context->getBytes());
        $this->assertNull($context->getError());
        $this->assertGreaterThanOrEqual(0, $context->getDuration());
        $this->assertGreaterThanOrEqual(0, $context->getDurationInMilliseconds());
        $this->assertInstanceOf(UnixTimestampVO::class, $context->getStartTime());
        $this->assertInstanceOf(UnixTimestampVO::class, $context->getEndTime());

        $this->assertFileExists($context->getFullPath());
        $this->assertSame('Hello John!', file_get_contents($context->getFullPath()));
    }

    public function test_generate_with_custom_extension(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'World'));

        $path = new FilePathVO('Greetings.hello', ExtensionType::HTML);

        $context = $this->generator->generate($stub, $path, $this->tempDir);

        $this->assertTrue($context->isSuccess());
        $this->assertSame($this->tempDir.'/Greetings/Hello.html', $context->getFullPath());
        $this->assertFileExists($context->getFullPath());
        $this->assertSame('Hello World!', file_get_contents($context->getFullPath()));
    }

    public function test_generate_with_subdirectories(): void
    {
        $stub = new StubVO('Content of {{file}}');
        $stub->replace(new ReplacementRecord('file', 'test'));

        $path = new FilePathVO('App.domain.users.profile');

        $context = $this->generator->generate($stub, $path, $this->tempDir);

        $expectedPath = $this->tempDir.'/App/Domain/Users/Profile.php';
        $this->assertTrue($context->isSuccess());
        $this->assertSame($expectedPath, $context->getFullPath());
        $this->assertFileExists($expectedPath);
        $this->assertDirectoryExists($this->tempDir.'/App/Domain/Users');
        $this->assertSame('Content of test', file_get_contents($expectedPath));
    }

    public function test_generate_returns_error_when_file_exists(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));

        $path = new FilePathVO('Greetings.hello');

        $context1 = $this->generator->generate($stub, $path, $this->tempDir);
        $this->assertTrue($context1->isSuccess());

        $context2 = $this->generator->generate($stub, $path, $this->tempDir);

        $this->assertFalse($context2->isSuccess());
        $this->assertStringContainsString('File already exists', $context2->getError());
        $this->assertSame($context1->getFullPath(), $context2->getFullPath());
        $this->assertNotNull($context2->getError());
    }

    public function test_generate_creates_directory_recursively(): void
    {
        $stub = new StubVO('Test content');
        $path = new FilePathVO('Level1.level2.level3.test');

        $context = $this->generator->generate($stub, $path, $this->tempDir);

        $expectedPath = $this->tempDir.'/Level1/Level2/Level3/Test.php';
        $this->assertTrue($context->isSuccess());
        $this->assertFileExists($expectedPath);
        $this->assertDirectoryExists($this->tempDir.'/Level1/Level2/Level3');
        $this->assertSame('Test content', file_get_contents($expectedPath));
    }

    public function test_generate_returns_error_when_directory_not_writable(): void
    {
        $stub = new StubVO('Test content');
        $path = new FilePathVO('test');

        $readonlyDir = $this->createReadonlyDirectory();

        $context = $this->generator->generate($stub, $path, $readonlyDir);

        $this->assertFalse($context->isSuccess());
        $this->assertStringContainsString('Cannot write file', $context->getError());

        $this->removeReadonlyDirectory($readonlyDir);
    }

    // ==================== TESTS DRY_RUN ====================

    public function test_dry_run_does_not_create_file(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));

        $path = new FilePathVO('Greetings.hello');

        $context = $this->generator->dryRun($stub, $path, $this->tempDir);

        $this->assertTrue($context->isSuccess());
        $this->assertSame($this->tempDir.'/Greetings/Hello.php', $context->getFullPath());
        $this->assertSame('Hello John!', $context->getContent());
        $this->assertGreaterThan(0, $context->getBytes());
        $this->assertFileDoesNotExist($context->getFullPath());
    }

    public function test_dry_run_returns_error_when_file_exists(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));

        $path = new FilePathVO('Greetings.hello');

        $this->generator->generate($stub, $path, $this->tempDir);

        $context = $this->generator->dryRun($stub, $path, $this->tempDir);

        $this->assertFalse($context->isSuccess());
        $this->assertStringContainsString('File would be overwritten', $context->getError());
    }

    // ==================== TESTS CAN_WRITE ====================

    public function test_can_write_returns_true_for_writable_directory(): void
    {
        $path = new FilePathVO('test.file');

        $this->assertTrue($this->generator->canWrite($path, $this->tempDir));
    }

    public function test_can_write_returns_false_for_non_writable_directory(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Permission tests are not reliable on Windows');
        }

        $path = new FilePathVO('test.file');
        $readonlyDir = $this->createReadonlyDirectory();

        $this->assertFalse($this->generator->canWrite($path, $readonlyDir));

        $this->removeReadonlyDirectory($readonlyDir);
    }

    // ==================== TESTS GENERATE_MANY ====================

    public function test_generate_many_creates_multiple_files(): void
    {
        $stub1 = new StubVO('File 1: {{name}}');
        $stub1->replace(new ReplacementRecord('name', 'One'));

        $stub2 = new StubVO('File 2: {{name}}');
        $stub2->replace(new ReplacementRecord('name', 'Two'));

        $stub3 = new StubVO('File 3: {{name}}');
        $stub3->replace(new ReplacementRecord('name', 'Three'));

        $path1 = new FilePathVO('File.one');
        $path2 = new FilePathVO('File.two');
        $path3 = new FilePathVO('File.three');

        $items = [
            ['stub' => $stub1, 'path' => $path1],
            ['stub' => $stub2, 'path' => $path2],
            ['stub' => $stub3, 'path' => $path3],
        ];

        $contexts = $this->generator->generateMany($items, $this->tempDir);

        $this->assertCount(3, $contexts);
        foreach ($contexts as $context) {
            $this->assertTrue($context->isSuccess());
            $this->assertFileExists($context->getFullPath());
        }

        $this->assertFileExists($this->tempDir.'/File/One.php');
        $this->assertFileExists($this->tempDir.'/File/Two.php');
        $this->assertFileExists($this->tempDir.'/File/Three.php');

        $this->assertSame('File 1: One', file_get_contents($this->tempDir.'/File/One.php'));
        $this->assertSame('File 2: Two', file_get_contents($this->tempDir.'/File/Two.php'));
        $this->assertSame('File 3: Three', file_get_contents($this->tempDir.'/File/Three.php'));
    }

    public function test_generate_many_stops_on_error(): void
    {
        $stub1 = new StubVO('File 1');
        $stub2 = new StubVO('File 2');

        $path1 = new FilePathVO('File.one');
        $path2 = new FilePathVO('File.two');

        $items = [
            ['stub' => $stub1, 'path' => $path1],
            ['stub' => $stub2, 'path' => $path2],
        ];

        $readonlyDir = $this->createReadonlyDirectory();

        $contexts = $this->generator->generateMany($items, $readonlyDir);

        $this->assertCount(2, $contexts);
        $this->assertFalse($contexts[0]->isSuccess());
        $this->assertStringContainsString('Cannot create directory', $contexts[0]->getError());
        $this->assertFalse($contexts[1]->isSuccess());
        $this->assertStringContainsString('Cannot create directory', $contexts[1]->getError());

        $this->removeReadonlyDirectory($readonlyDir);
    }

    // ==================== TESTS CONTEXT ====================

    public function test_context_to_array(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));

        $path = new FilePathVO('Greetings.hello');

        $context = $this->generator->generate($stub, $path, $this->tempDir);

        $array = $context->toArray();

        $this->assertTrue($array['success']);
        $this->assertSame($this->tempDir.'/Greetings/Hello.php', $array['fullPath']);
        $this->assertSame('Hello John!', $array['content']);
        $this->assertNull($array['error']);
        $this->assertIsArray($array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('startTime', $array);
        $this->assertArrayHasKey('endTime', $array);
        $this->assertArrayHasKey('duration', $array);
        $this->assertArrayHasKey('durationInMilliseconds', $array);
        $this->assertIsInt($array['startTime']);
        $this->assertIsInt($array['endTime']);
        $this->assertIsFloat($array['duration']);
        $this->assertIsFloat($array['durationInMilliseconds']);
        $this->assertGreaterThanOrEqual(0, $array['duration']);
        $this->assertGreaterThanOrEqual(0, $array['durationInMilliseconds']);
    }

    public function test_context_message_on_success(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));

        $path = new FilePathVO('Greetings.hello');

        $context = $this->generator->generate($stub, $path, $this->tempDir);

        $message = $context->getMessage();
        $this->assertStringContainsString('File created successfully', $message);
        $this->assertStringContainsString($context->getFullPath(), $message);
        $this->assertStringContainsString((string) $context->getBytes(), $message);
        $this->assertStringContainsString('ms', $message);
    }

    public function test_context_message_on_error(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));

        $path = new FilePathVO('Greetings.hello');

        $this->generator->generate($stub, $path, $this->tempDir);
        $context = $this->generator->generate($stub, $path, $this->tempDir);

        $message = $context->getMessage();
        $this->assertStringContainsString('Failed to create file', $message);
        $this->assertStringContainsString($context->getFullPath(), $message);
        $this->assertStringContainsString($context->getError() ?? '', $message);
    }

    public function test_context_get_duration(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));

        $path = new FilePathVO('Greetings.hello');

        $context = $this->generator->generate($stub, $path, $this->tempDir);

        $duration = $context->getDuration();
        $this->assertIsFloat($duration);
        $this->assertGreaterThanOrEqual(0, $duration);

        $durationMs = $context->getDurationInMilliseconds();
        $this->assertIsFloat($durationMs);
        $this->assertGreaterThanOrEqual(0, $durationMs);
    }
}
