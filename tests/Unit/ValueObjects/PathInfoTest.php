<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\ValueObjects;

use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

final class PathInfoTest extends UnitTestCase
{
    public function test_creates_path_info_with_simple_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'UserList',
            subPath: '',
            segments: []
        );

        $this->assertSame('UserList', $pathInfo->className);
        $this->assertSame('', $pathInfo->subPath);
        $this->assertSame('UserList', $pathInfo->getFullClassName());
        $this->assertSame('App\\Directives', $pathInfo->getNamespace('App\\Directives'));
    }

    public function test_creates_path_info_with_subdirectory(): void
    {
        $pathInfo = new PathInfo(
            className: 'UserList',
            subPath: 'User',
            segments: ['User']
        );

        $this->assertSame('User', $pathInfo->subPath);
        $this->assertSame('User\\UserList', $pathInfo->getFullClassName());
        $this->assertSame('App\\Directives\\User', $pathInfo->getNamespace('App\\Directives'));
    }

    public function test_creates_path_info_with_nested_subdirectories(): void
    {
        $pathInfo = new PathInfo(
            className: 'ShowUserAction',
            subPath: 'Api\\V1\\Users',
            segments: ['Api', 'V1', 'Users']
        );

        $this->assertSame('Api\\V1\\Users\\ShowUserAction', $pathInfo->getFullClassName());
        $this->assertSame('App\\Actions\\Api\\V1\\Users', $pathInfo->getNamespace('App\\Actions'));
    }

    public function test_get_file_path_without_subpath(): void
    {
        $pathInfo = new PathInfo(
            className: 'UserListDirective',
            subPath: '',
            segments: []
        );

        $basePath = '/app/Directives/';
        $expected = getcwd() . '/app/Directives/UserListDirective.php';

        $this->assertSame($expected, $pathInfo->getFilePath($basePath));
    }

    public function test_get_file_path_with_subpath(): void
    {
        $pathInfo = new PathInfo(
            className: 'UserListDirective',
            subPath: 'Admin',
            segments: ['Admin']
        );

        $basePath = '/app/Directives/';
        $expected = getcwd() . '/app/Directives/Admin/UserListDirective.php';

        $this->assertSame($expected, $pathInfo->getFilePath($basePath));
    }
}
