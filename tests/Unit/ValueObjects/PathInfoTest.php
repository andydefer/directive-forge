<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\ValueObjects;

use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;
use AndyDefer\DomainStructures\Utils\DataObject;

final class PathInfoTest extends UnitTestCase
{
    private function createSegmentsCollection(array $segments = []): ScalarTypedCollection
    {
        $collection = new ScalarTypedCollection;
        if (! empty($segments)) {
            $collection->add(...$segments);
        }

        return $collection;
    }

    private function createPathInfo(string $className, string $subPath = '', array $segments = []): PathInfo
    {
        $segmentsCollection = $this->createSegmentsCollection($segments);

        return PathInfo::from([
            'className' => $className,
            'subPath' => $subPath,
            'segments' => $segmentsCollection,
        ]);
    }

    public function test_creates_path_info_with_simple_name(): void
    {
        // Arrange: Create path info with simple name
        $pathInfo = $this->createPathInfo('UserList', '', []);

        // Act & Assert: Verify properties
        $this->assertSame('UserList', $pathInfo->className);
        $this->assertSame('', $pathInfo->subPath);
        $this->assertSame('UserList', $pathInfo->getFullClassName());
        $this->assertSame('App\\Directives', $pathInfo->getNamespace('App\\Directives'));
    }

    public function test_creates_path_info_with_subdirectory(): void
    {
        // Arrange: Create path info with subdirectory
        $pathInfo = $this->createPathInfo('UserList', 'User', ['User']);

        // Act & Assert: Verify properties
        $this->assertSame('User', $pathInfo->subPath);
        $this->assertSame('User\\UserList', $pathInfo->getFullClassName());
        $this->assertSame('App\\Directives\\User', $pathInfo->getNamespace('App\\Directives'));
    }

    public function test_creates_path_info_with_nested_subdirectories(): void
    {
        // Arrange: Create path info with nested subdirectories
        $pathInfo = $this->createPathInfo('ShowUserAction', 'Api\\V1\\Users', ['Api', 'V1', 'Users']);

        // Act & Assert: Verify properties
        $this->assertSame('Api\\V1\\Users\\ShowUserAction', $pathInfo->getFullClassName());
        $this->assertSame('App\\Actions\\Api\\V1\\Users', $pathInfo->getNamespace('App\\Actions'));
    }

    public function test_creates_path_info_with_multiple_segments(): void
    {
        // Arrange: Create path info with multiple segments
        $pathInfo = $this->createPathInfo('ProfileAction', 'Admin\\User\\Profile', ['Admin', 'User', 'Profile']);

        // Act & Assert: Verify segments
        $segments = $pathInfo->segments->toArray();
        $this->assertCount(3, $segments);
        $this->assertSame('Admin', $segments[0]);
        $this->assertSame('User', $segments[1]);
        $this->assertSame('Profile', $segments[2]);
        $this->assertSame('Admin\\User\\Profile\\ProfileAction', $pathInfo->getFullClassName());
    }

    public function test_get_file_path_without_subpath(): void
    {
        // Arrange: Create path info without subpath
        $pathInfo = $this->createPathInfo('UserListDirective', '', []);
        $basePath = '/app/Directives/';
        $expected = getcwd() . '/app/Directives/UserListDirective.php';

        // Act: Get file path
        $filePath = $pathInfo->getFilePath($basePath);

        // Assert: Verify file path
        $this->assertSame($expected, $filePath);
    }

    public function test_get_file_path_with_subpath(): void
    {
        // Arrange: Create path info with subpath
        $pathInfo = $this->createPathInfo('UserListDirective', 'Admin', ['Admin']);
        $basePath = '/app/Directives/';
        $expected = getcwd() . '/app/Directives/Admin/UserListDirective.php';

        // Act: Get file path
        $filePath = $pathInfo->getFilePath($basePath);

        // Assert: Verify file path
        $this->assertSame($expected, $filePath);
    }

    public function test_get_file_path_with_nested_subpath(): void
    {
        // Arrange: Create path info with nested subpath
        $pathInfo = $this->createPathInfo('ShowAction', 'Admin\\User\\Profile', ['Admin', 'User', 'Profile']);
        $basePath = '/app/Actions/';
        $expected = getcwd() . '/app/Actions/Admin/User/Profile/ShowAction.php';

        // Act: Get file path
        $filePath = $pathInfo->getFilePath($basePath);

        // Assert: Verify file path
        $this->assertSame($expected, $filePath);
    }

    public function test_get_file_path_converts_backslashes_to_slashes(): void
    {
        // Arrange: Create path info with backslashes in subpath
        $pathInfo = $this->createPathInfo('TestDirective', 'Admin\\User', ['Admin', 'User']);
        $basePath = '/app/Directives/';
        $expected = getcwd() . '/app/Directives/Admin/User/TestDirective.php';

        // Act: Get file path
        $filePath = $pathInfo->getFilePath($basePath);

        // Assert: Verify backslashes converted to forward slashes
        $this->assertSame($expected, $filePath);
        $this->assertStringNotContainsString('\\', $filePath);
    }

    public function test_get_file_path_with_different_base_paths(): void
    {
        // Arrange: Create path info
        $pathInfo = $this->createPathInfo('UserListDirective', 'Admin', ['Admin']);

        $expected1 = getcwd() . '/app/Commands/Admin/UserListDirective.php';
        $expected2 = getcwd() . '/app/Handlers/Admin/UserListDirective.php';

        // Act & Assert: Test different base paths
        $this->assertSame($expected1, $pathInfo->getFilePath('/app/Commands/'));
        $this->assertSame($expected2, $pathInfo->getFilePath('/app/Handlers/'));
    }

    public function test_get_namespace_without_subpath(): void
    {
        // Arrange: Create path info without subpath
        $pathInfo = $this->createPathInfo('UserListDirective', '', []);
        $baseNamespace = 'App\\Directives';

        // Act: Get namespace
        $namespace = $pathInfo->getNamespace($baseNamespace);

        // Assert: Verify namespace
        $this->assertSame('App\\Directives', $namespace);
    }

    public function test_get_namespace_with_subpath(): void
    {
        // Arrange: Create path info with subpath
        $pathInfo = $this->createPathInfo('UserListDirective', 'Admin\\User', ['Admin', 'User']);
        $baseNamespace = 'App\\Directives';

        // Act: Get namespace
        $namespace = $pathInfo->getNamespace($baseNamespace);

        // Assert: Verify namespace includes subpath
        $this->assertSame('App\\Directives\\Admin\\User', $namespace);
    }

    public function test_get_namespace_with_different_base_namespaces(): void
    {
        // Arrange: Create path info
        $pathInfo = $this->createPathInfo('ShowAction', 'Admin\\User', ['Admin', 'User']);

        // Act & Assert: Test different base namespaces
        $this->assertSame('App\\Actions\\Admin\\User', $pathInfo->getNamespace('App\\Actions'));
        $this->assertSame('App\\Handlers\\Admin\\User', $pathInfo->getNamespace('App\\Handlers'));
        $this->assertSame('Custom\\Namespace\\Admin\\User', $pathInfo->getNamespace('Custom\\Namespace'));
    }

    public function test_get_value_returns_path_info_record(): void
    {
        // Arrange: Create path info
        $pathInfo = $this->createPathInfo('UserListDirective', 'Admin', ['Admin']);
        $segments = $pathInfo->segments;

        // Act: Get value as record
        $record = $pathInfo->getValue();

        // Assert: Verify record properties
        $this->assertSame('UserListDirective', $record->className);
        $this->assertSame('Admin', $record->subPath);
        $this->assertSame($segments->toArray(), $record->segments->toArray());
    }

    public function test_path_info_equality(): void
    {
        // Arrange: Create two identical path info objects
        $pathInfo1 = $this->createPathInfo('UserList', 'Admin', ['Admin']);
        $pathInfo2 = $this->createPathInfo('UserList', 'Admin', ['Admin']);

        // Act: Check equality
        $isEqual = $pathInfo1->equals($pathInfo2);

        // Assert: Verify equality
        $this->assertTrue($isEqual);
    }

    public function test_path_info_inequality(): void
    {
        // Arrange: Create two different path info objects
        $pathInfo1 = $this->createPathInfo('UserList', 'Admin', ['Admin']);
        $pathInfo2 = $this->createPathInfo('ProductList', 'Admin', ['Admin']);

        // Act: Check equality
        $isEqual = $pathInfo1->equals($pathInfo2);

        // Assert: Verify inequality
        $this->assertFalse($isEqual);
    }

    public function test_path_info_inequality_different_subpath(): void
    {
        // Arrange: Create path info with different subpaths
        $pathInfo1 = $this->createPathInfo('UserList', 'Admin', ['Admin']);
        $pathInfo2 = $this->createPathInfo('UserList', 'User', ['User']);

        // Act: Check equality
        $isEqual = $pathInfo1->equals($pathInfo2);

        // Assert: Verify inequality
        $this->assertFalse($isEqual);
    }

    public function test_segments_collection_uses_add_with_splat(): void
    {
        // Arrange: Create segments array
        $segments = ['Api', 'V1', 'Users', 'Profile'];

        // Act: Create path info with segments using add(...$segments)
        $pathInfo = $this->createPathInfo('ShowAction', 'Api\\V1\\Users\\Profile', $segments);

        // Assert: Verify segments were added correctly
        $this->assertCount(4, $pathInfo->segments);
        $this->assertSame('Api', $pathInfo->segments->toArray()[0]);
        $this->assertSame('V1', $pathInfo->segments->toArray()[1]);
        $this->assertSame('Users', $pathInfo->segments->toArray()[2]);
        $this->assertSame('Profile', $pathInfo->segments->toArray()[3]);
    }

    public function test_path_info_with_empty_segments(): void
    {
        // Arrange: Create path info with empty segments
        $pathInfo = $this->createPathInfo('SimpleClass', '', []);

        // Assert: Verify empty segments
        $this->assertTrue($pathInfo->segments->isEmpty());
        $this->assertCount(0, $pathInfo->segments);
    }

    public function test_path_info_from_array_hydration(): void
    {
        // Arrange: Create segments collection
        $segmentsCollection = new ScalarTypedCollection;
        $segmentsCollection->add('Admin', 'User');

        // Act: Create path info via from() hydration
        $pathInfo = PathInfo::from([
            'className' => 'UserListDirective',
            'subPath' => 'Admin\\User',
            'segments' => $segmentsCollection,
        ]);

        // Assert: Verify hydration worked correctly
        $this->assertSame('UserListDirective', $pathInfo->className);
        $this->assertSame('Admin\\User', $pathInfo->subPath);
        $this->assertCount(2, $pathInfo->segments);
        $this->assertSame('Admin', $pathInfo->segments->toArray()[0]);
        $this->assertSame('User', $pathInfo->segments->toArray()[1]);
    }

    public function test_path_info_to_string_returns_json(): void
    {
        // Arrange: Create path info
        $pathInfo = $this->createPathInfo('UserList', 'Admin', ['Admin']);

        // Act: Convert to string
        $string = (string) $pathInfo;

        // Assert: Verify JSON format
        $this->assertJson($string);

        $decoded = DataObject::fromJson($string);
        $this->assertArrayHasKey('className', $decoded);
        $this->assertArrayHasKey('subPath', $decoded);
        $this->assertArrayHasKey('segments', $decoded);
        $this->assertSame('UserList', $decoded['className']);
        $this->assertSame('Admin', $decoded['subPath']);
    }
}
