<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\Tests\Fixtures\TestGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;
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

    private function createPathInfo(string $className, string $subPath = '', array $segments = []): PathInfo
    {
        $segmentsCollection = new ScalarTypedCollection;
        if (! empty($segments)) {
            $segmentsCollection->add(...$segments);
        }

        return PathInfo::from([
            'className' => $className,
            'subPath' => $subPath,
            'segments' => $segmentsCollection,
        ]);
    }

    public function test_normalize_class_name_adds_suffix_when_missing(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('normalizeClassName');

        // Act: Normalize class name without suffix
        $result = $method->invoke($this->generator, 'UserList', 'Directive');

        // Assert: Verify suffix was added
        $this->assertSame('UserListDirective', $result);
    }

    public function test_normalize_class_name_does_not_add_suffix_when_already_present(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('normalizeClassName');

        // Act: Normalize class name with suffix already present
        $result = $method->invoke($this->generator, 'UserListDirective', 'Directive');

        // Assert: Verify suffix was not duplicated
        $this->assertSame('UserListDirective', $result);
    }

    public function test_normalize_class_name_converts_kebab_case_to_pascal_case(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('normalizeClassName');

        // Act: Normalize kebab-case name
        $result = $method->invoke($this->generator, 'user-list', 'Directive');

        // Assert: Verify conversion to PascalCase
        $this->assertSame('UserListDirective', $result);
    }

    public function test_normalize_class_name_converts_snake_case_to_pascal_case(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('normalizeClassName');

        // Act: Normalize snake_case name
        $result = $method->invoke($this->generator, 'user_list', 'Directive');

        // Assert: Verify conversion to PascalCase
        $this->assertSame('UserListDirective', $result);
    }

    public function test_extract_signature_removes_suffix_and_converts_to_kebab_case(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('extractSignature');

        // Act: Extract signature from class name
        $result = $method->invoke($this->generator, 'UserListDirective', 'Directive');

        // Assert: Verify signature is kebab-case without suffix
        $this->assertSame('user-list', $result);
    }

    public function test_extract_signature_handles_multiple_words(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('extractSignature');

        // Act: Extract signature from multi-word class name
        $result = $method->invoke($this->generator, 'SendWelcomeEmailTask', 'Task');

        // Assert: Verify signature is kebab-case
        $this->assertSame('send-welcome-email', $result);
    }

    public function test_extract_signature_handles_single_word(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('extractSignature');

        // Act: Extract signature from single word class name
        $result = $method->invoke($this->generator, 'HelloDirective', 'Directive');

        // Assert: Verify signature is kebab-case
        $this->assertSame('hello', $result);
    }

    public function test_get_replacements_returns_replacement_collection(): void
    {
        // Arrange: Create path info
        $pathInfo = $this->createPathInfo('user-list', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);

        // Assert: Verify replacement collection structure
        $this->assertInstanceOf(ReplacementCollection::class, $replacements);

        $associativeArray = $replacements->toAssociativeArray();
        $this->assertArrayHasKey('{{namespace}}', $associativeArray);
        $this->assertArrayHasKey('{{class}}', $associativeArray);
        $this->assertArrayHasKey('{{test}}', $associativeArray);
        $this->assertSame('test_value', $associativeArray['{{test}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        // Arrange: Create path info with subdirectory
        $pathInfo = $this->createPathInfo('user-list', 'Admin\\User', ['Admin', 'User']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $associativeArray = $replacements->toAssociativeArray();

        // Assert: Verify namespace includes subdirectory
        $this->assertSame('App\\Directives\\Admin\\User', $associativeArray['{{namespace}}']);
        $this->assertSame('UserListDirective', $associativeArray['{{class}}']);
    }

    public function test_generate_returns_success(): void
    {
        // Arrange: Create path info and mock filesystem
        $pathInfo = $this->createPathInfo('test', '', []);

        $reflection = new \ReflectionClass($this->generator);

        // Find files property in class hierarchy
        $filesProperty = $this->findProperty($reflection, 'files');
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

        // Permettre plusieurs appels à isDirectory (au moins une fois)
        $filesMock->expects($this->atLeastOnce())
            ->method('isDirectory')
            ->willReturn(true);

        $filesProperty->setValue($this->generator, $filesMock);

        $this->interaction->expects($this->once())
            ->method('info')
            ->with($this->stringContains('directive created successfully!'));

        // Act: Generate file
        $result = $this->generator->generate($pathInfo);

        // Assert: Verify success
        $this->assertSame(ExitCode::SUCCESS, $result);
    }

    public function test_generate_returns_failure_when_file_already_exists(): void
    {
        // Arrange: Create path info and mock filesystem with existing file
        $pathInfo = $this->createPathInfo('test', '', []);

        $reflection = new \ReflectionClass($this->generator);
        $filesProperty = $this->findProperty($reflection, 'files');
        $filesMock = $this->createMock(Filesystem::class);

        $filesMock->expects($this->once())
            ->method('exists')
            ->willReturn(true);
        $filesMock->expects($this->once())
            ->method('isDirectory')
            ->willReturn(true);

        $filesProperty->setValue($this->generator, $filesMock);

        $this->interaction->expects($this->once())
            ->method('error')
            ->with($this->stringContains('File already exists'));

        // Act: Generate file
        $result = $this->generator->generate($pathInfo);

        // Assert: Verify failure
        $this->assertSame(ExitCode::FAILURE, $result);
    }

    public function test_to_pascal_case_converts_kebab_case(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('toPascalCase');

        // Act: Convert kebab-case
        $result = $method->invoke($this->generator, 'user-list');

        // Assert: Verify PascalCase
        $this->assertSame('UserList', $result);
    }

    public function test_to_pascal_case_converts_snake_case(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('toPascalCase');

        // Act: Convert snake_case
        $result = $method->invoke($this->generator, 'user_list');

        // Assert: Verify PascalCase
        $this->assertSame('UserList', $result);
    }

    public function test_to_pascal_case_converts_mixed_case(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('toPascalCase');

        // Act: Convert mixed case
        $result = $method->invoke($this->generator, 'user-list_profile');

        // Assert: Verify PascalCase
        $this->assertSame('UserListProfile', $result);
    }

    public function test_to_kebab_case_converts_pascal_case(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('toKebabCase');

        // Act: Convert PascalCase
        $result = $method->invoke($this->generator, 'UserList');

        // Assert: Verify kebab-case
        $this->assertSame('user-list', $result);
    }

    public function test_to_kebab_case_converts_camel_case(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('toKebabCase');

        // Act: Convert camelCase
        $result = $method->invoke($this->generator, 'userList');

        // Assert: Verify kebab-case
        $this->assertSame('user-list', $result);
    }

    public function test_to_kebab_case_handles_multiple_words(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('toKebabCase');

        // Act: Convert multi-word PascalCase
        $result = $method->invoke($this->generator, 'SendWelcomeEmail');

        // Assert: Verify kebab-case
        $this->assertSame('send-welcome-email', $result);
    }

    public function test_extract_path_segments_with_simple_name(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('extractPathSegments');

        // Act: Extract segments from simple name
        $result = $method->invoke($this->generator, 'user-list');

        // Assert: Verify structure
        $this->assertIsArray($result);
        $this->assertSame([], $result['segments']);
        $this->assertSame('user-list', $result['className']);
        $this->assertSame('', $result['subPath']);
        $this->assertSame('user-list', $result['fullPath']);
    }

    public function test_extract_path_segments_with_subdirectory(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('extractPathSegments');

        // Act: Extract segments from path with subdirectories
        $result = $method->invoke($this->generator, 'user/domain/hello-directive');

        // Assert: Verify structure
        $this->assertSame(['user', 'domain'], $result['segments']);
        $this->assertSame('hello-directive', $result['className']);
        $this->assertSame('User/Domain', $result['subPath']);
        $this->assertSame('User/Domain/hello-directive', $result['fullPath']);
    }

    public function test_build_namespace_without_subpath(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('buildNamespace');

        // Act: Build namespace without subpath
        $result = $method->invoke($this->generator, 'App\\Directives', '');

        // Assert: Verify namespace unchanged
        $this->assertSame('App\\Directives', $result);
    }

    public function test_build_namespace_with_subpath(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('buildNamespace');

        // Act: Build namespace with subpath
        $result = $method->invoke($this->generator, 'App\\Directives', 'User\\Domain');

        // Assert: Verify namespace combined
        $this->assertSame('App\\Directives\\User\\Domain', $result);
    }

    public function test_build_namespace_converts_slashes_to_backslashes(): void
    {
        // Arrange: Get the protected method
        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('buildNamespace');

        // Act: Build namespace with forward slashes
        $result = $method->invoke($this->generator, 'App\\Actions', 'User/Domain');

        // Assert: Verify slashes converted
        $this->assertSame('App\\Actions\\User\\Domain', $result);
    }

    public function test_ensure_directory_exists_creates_directory_when_missing(): void
    {
        // Arrange: Create temporary path
        $tempDir = sys_get_temp_dir().'/abstract_test_'.uniqid();
        $testPath = $tempDir.'/nested/directory/path';

        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('ensureDirectoryExists');
        $filesProperty = $this->findProperty($reflection, 'files');

        $filesMock = $this->createMock(Filesystem::class);
        $filesMock->expects($this->once())
            ->method('isDirectory')
            ->with($testPath)
            ->willReturn(false);
        $filesMock->expects($this->once())
            ->method('makeDirectory')
            ->with($testPath, 0755, true);

        $filesProperty->setValue($this->generator, $filesMock);

        // Act: Ensure directory exists
        $method->invoke($this->generator, $testPath);

        // Assert: No exception thrown
        $this->assertTrue(true);

        // Cleanup
        $this->removeDirectory($tempDir);
    }

    public function test_ensure_directory_exists_does_nothing_when_directory_exists(): void
    {
        // Arrange: Create existing directory
        $tempDir = sys_get_temp_dir().'/abstract_test_'.uniqid();
        mkdir($tempDir, 0777, true);

        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('ensureDirectoryExists');
        $filesProperty = $this->findProperty($reflection, 'files');

        $filesMock = $this->createMock(Filesystem::class);
        $filesMock->expects($this->once())
            ->method('isDirectory')
            ->with($tempDir)
            ->willReturn(true);
        $filesMock->expects($this->never())
            ->method('makeDirectory');

        $filesProperty->setValue($this->generator, $filesMock);

        // Act: Ensure directory exists
        $method->invoke($this->generator, $tempDir);

        // Assert: No exception thrown
        $this->assertTrue(true);

        // Cleanup
        $this->removeDirectory($tempDir);
    }

    public function test_init_file_creator_initializes_filesystem(): void
    {
        // Assert: Verify filesystem property is initialized
        $reflection = new \ReflectionClass($this->generator);
        $filesProperty = $this->findProperty($reflection, 'files');
        $files = $filesProperty->getValue($this->generator);

        $this->assertNotNull($files);
        $this->assertInstanceOf(Filesystem::class, $files);
    }

    private function findProperty(\ReflectionClass $reflection, string $propertyName): \ReflectionProperty
    {
        $currentReflection = $reflection;
        while ($currentReflection) {
            if ($currentReflection->hasProperty($propertyName)) {
                return $currentReflection->getProperty($propertyName);
            }
            $currentReflection = $currentReflection->getParentClass();
            if ($currentReflection === false) {
                break;
            }
        }
        throw new \RuntimeException("Property {$propertyName} not found in class hierarchy");
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
