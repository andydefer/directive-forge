<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\Tests\Fixtures\TestGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class AbstractGeneratorTest extends UnitTestCase
{
    private TestGenerator $generator;
    private DirectiveInteractionService&MockObject $interaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interaction = $this->createMock(DirectiveInteractionService::class);
        $this->generator = new TestGenerator($this->interaction);

        // Set the type via reflection for testing
        $reflection = new \ReflectionClass($this->generator);
        $property = $reflection->getProperty('type');
        $property->setValue($this->generator, GeneratorType::DIRECTIVE);
    }

    public function test_normalize_class_name_adds_suffix_when_missing(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('normalizeClassName');

        $result = $method->invoke($this->generator, 'UserList', 'Directive');

        $this->assertSame('UserListDirective', $result);
    }

    public function test_normalize_class_name_does_not_add_suffix_when_already_present(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('normalizeClassName');

        $result = $method->invoke($this->generator, 'UserListDirective', 'Directive');

        $this->assertSame('UserListDirective', $result);
    }

    public function test_normalize_class_name_converts_kebab_case_to_pascal_case(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('normalizeClassName');

        $result = $method->invoke($this->generator, 'user-list', 'Directive');

        $this->assertSame('UserListDirective', $result);
    }

    public function test_normalize_class_name_converts_snake_case_to_pascal_case(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('normalizeClassName');

        $result = $method->invoke($this->generator, 'user_list', 'Directive');

        $this->assertSame('UserListDirective', $result);
    }

    public function test_extract_signature_removes_suffix_and_converts_to_kebab_case(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('extractSignature');

        $result = $method->invoke($this->generator, 'UserListDirective', 'Directive');

        $this->assertSame('user-list', $result);
    }

    public function test_extract_signature_handles_multiple_words(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('extractSignature');

        $result = $method->invoke($this->generator, 'SendWelcomeEmailTask', 'Task');

        $this->assertSame('send-welcome-email', $result);
    }

    public function test_extract_signature_handles_single_word(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('extractSignature');

        $result = $method->invoke($this->generator, 'HelloDirective', 'Directive');

        $this->assertSame('hello', $result);
    }

    public function test_get_replacements_returns_expected_array(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-list',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertIsArray($replacements);
        $this->assertArrayHasKey('{{namespace}}', $replacements);
        $this->assertArrayHasKey('{{class}}', $replacements);
        $this->assertArrayHasKey('{{test}}', $replacements);
        $this->assertSame('test_value', $replacements['{{test}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-list',
            subPath: 'Admin\\User',
            segments: ['Admin', 'User']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Directives\\Admin\\User', $replacements['{{namespace}}']);
        $this->assertSame('UserListDirective', $replacements['{{class}}']);
    }

    public function test_generate_returns_success(): void
    {
        $pathInfo = new PathInfo(
            className: 'test',
            subPath: '',
            segments: []
        );

        $reflection = new \ReflectionClass($this->generator);

        $filesProperty = null;
        $currentReflection = $reflection;
        while ($currentReflection) {
            if ($currentReflection->hasProperty('files')) {
                $filesProperty = $currentReflection->getProperty('files');
                break;
            }
            $currentReflection = $currentReflection->getParentClass();
        }

        $filesMock = $this->createMock(Filesystem::class);
        $filesMock->expects($this->once())
            ->method('exists')
            ->willReturn(false);
        $filesMock->expects($this->once())
            ->method('get')
            ->willReturn('{{namespace}} {{class}}');
        $filesMock->expects($this->once())
            ->method('put')
            ->willReturn(123);
        $filesMock->expects($this->any())
            ->method('isDirectory')
            ->willReturn(true);

        if ($filesProperty) {
            $filesProperty->setValue($this->generator, $filesMock);
        }

        $this->interaction->expects($this->once())
            ->method('info')
            ->with($this->stringContains('directive created successfully!'));

        $result = $this->generator->generate($pathInfo);

        $this->assertSame(ExitCode::SUCCESS, $result);
    }

    public function test_generate_returns_failure_when_file_already_exists(): void
    {
        $pathInfo = new PathInfo(
            className: 'test',
            subPath: '',
            segments: []
        );

        $reflection = new \ReflectionClass($this->generator);

        $filesProperty = null;
        $currentReflection = $reflection;
        while ($currentReflection) {
            if ($currentReflection->hasProperty('files')) {
                $filesProperty = $currentReflection->getProperty('files');
                break;
            }
            $currentReflection = $currentReflection->getParentClass();
        }

        $filesMock = $this->createMock(Filesystem::class);
        $filesMock->expects($this->once())
            ->method('exists')
            ->willReturn(true);
        $filesMock->expects($this->any())
            ->method('isDirectory')
            ->willReturn(true);

        if ($filesProperty) {
            $filesProperty->setValue($this->generator, $filesMock);
        }

        $this->interaction->expects($this->once())
            ->method('error')
            ->with($this->stringContains('File already exists'));

        $result = $this->generator->generate($pathInfo);

        $this->assertSame(ExitCode::FAILURE, $result);
    }

    public function test_to_pascal_case_converts_kebab_case(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('toPascalCase');

        $result = $method->invoke($this->generator, 'user-list');

        $this->assertSame('UserList', $result);
    }

    public function test_to_pascal_case_converts_snake_case(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('toPascalCase');

        $result = $method->invoke($this->generator, 'user_list');

        $this->assertSame('UserList', $result);
    }

    public function test_to_pascal_case_converts_mixed_case(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('toPascalCase');

        $result = $method->invoke($this->generator, 'user-list_profile');

        $this->assertSame('UserListProfile', $result);
    }

    public function test_to_kebab_case_converts_pascal_case(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('toKebabCase');

        $result = $method->invoke($this->generator, 'UserList');

        $this->assertSame('user-list', $result);
    }

    public function test_to_kebab_case_converts_camel_case(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('toKebabCase');

        $result = $method->invoke($this->generator, 'userList');

        $this->assertSame('user-list', $result);
    }

    public function test_to_kebab_case_handles_multiple_words(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('toKebabCase');

        $result = $method->invoke($this->generator, 'SendWelcomeEmail');

        $this->assertSame('send-welcome-email', $result);
    }

    public function test_extract_path_segments_with_simple_name(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('extractPathSegments');

        $result = $method->invoke($this->generator, 'user-list');

        $this->assertIsArray($result);
        $this->assertSame([], $result['segments']);
        $this->assertSame('user-list', $result['className']);
        $this->assertSame('', $result['subPath']);
        $this->assertSame('user-list', $result['fullPath']);
    }

    public function test_extract_path_segments_with_subdirectory(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('extractPathSegments');

        $result = $method->invoke($this->generator, 'user/domain/hello-directive');

        $this->assertSame(['user', 'domain'], $result['segments']);
        $this->assertSame('hello-directive', $result['className']);
        $this->assertSame('User/Domain', $result['subPath']);
        $this->assertSame('User/Domain/hello-directive', $result['fullPath']);
    }

    public function test_build_namespace_without_subpath(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('buildNamespace');

        $result = $method->invoke($this->generator, 'App\\Directives', '');

        $this->assertSame('App\\Directives', $result);
    }

    public function test_build_namespace_with_subpath(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('buildNamespace');

        $result = $method->invoke($this->generator, 'App\\Directives', 'User\\Domain');

        $this->assertSame('App\\Directives\\User\\Domain', $result);
    }

    public function test_build_namespace_converts_slashes_to_backslashes(): void
    {
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('buildNamespace');

        $result = $method->invoke($this->generator, 'App\\Actions', 'User/Domain');

        $this->assertSame('App\\Actions\\User\\Domain', $result);
    }

    public function test_ensure_directory_exists_creates_directory_when_missing(): void
    {
        $tempDir = sys_get_temp_dir() . '/abstract_test_' . uniqid();
        $testPath = $tempDir . '/nested/directory/path';

        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('ensureDirectoryExists');

        $filesProperty = null;
        $classReflection = new \ReflectionClass($this->generator);
        while ($classReflection) {
            if ($classReflection->hasProperty('files')) {
                $filesProperty = $classReflection->getProperty('files');
                break;
            }
            $classReflection = $classReflection->getParentClass();
        }

        $filesMock = $this->createMock(Filesystem::class);
        $filesMock->expects($this->once())
            ->method('isDirectory')
            ->with($testPath)
            ->willReturn(false);
        $filesMock->expects($this->once())
            ->method('makeDirectory')
            ->with($testPath, 0755, true);

        if ($filesProperty) {
            $filesProperty->setValue($this->generator, $filesMock);
        }

        $method->invoke($this->generator, $testPath);

        $this->removeDirectory($tempDir);
    }

    public function test_ensure_directory_exists_does_nothing_when_directory_exists(): void
    {
        $tempDir = sys_get_temp_dir() . '/abstract_test_' . uniqid();
        mkdir($tempDir, 0777, true);

        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('ensureDirectoryExists');

        $filesProperty = null;
        $classReflection = new \ReflectionClass($this->generator);
        while ($classReflection) {
            if ($classReflection->hasProperty('files')) {
                $filesProperty = $classReflection->getProperty('files');
                break;
            }
            $classReflection = $classReflection->getParentClass();
        }

        $filesMock = $this->createMock(Filesystem::class);
        $filesMock->expects($this->once())
            ->method('isDirectory')
            ->with($tempDir)
            ->willReturn(true);
        $filesMock->expects($this->never())
            ->method('makeDirectory');

        if ($filesProperty) {
            $filesProperty->setValue($this->generator, $filesMock);
        }

        $method->invoke($this->generator, $tempDir);

        $this->removeDirectory($tempDir);
    }

    public function test_init_file_creator_initializes_filesystem(): void
    {
        $reflection = new \ReflectionClass($this->generator);

        $filesProperty = null;
        $classReflection = new \ReflectionClass($this->generator);
        while ($classReflection) {
            if ($classReflection->hasProperty('files')) {
                $filesProperty = $classReflection->getProperty('files');
                break;
            }
            $classReflection = $classReflection->getParentClass();
        }

        $this->assertNotNull($filesProperty, 'Property $files not found in class hierarchy');

        $files = $filesProperty->getValue($this->generator);

        $this->assertNotNull($files);
        $this->assertInstanceOf(Filesystem::class, $files);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
