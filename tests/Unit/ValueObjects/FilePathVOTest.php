<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\ValueObjects;

use AndyDefer\DirectiveForge\Enums\ExtensionType;
use AndyDefer\DirectiveForge\ValueObjects\FilePathVO;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FilePathVOTest extends TestCase
{
    // ==================== TESTS CONSTRUCTEUR ET VALIDATION ====================

    public function test_constructor_creates_valid_instance(): void
    {
        $path = new FilePathVO('domain.users.profile-action');

        $this->assertInstanceOf(FilePathVO::class, $path);
        $this->assertInstanceOf(AbstractValueObject::class, $path);
    }

    public function test_constructor_throws_exception_for_empty_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FilePathVO cannot be empty');

        new FilePathVO('');
    }

    public function test_constructor_throws_exception_for_empty_name_with_spaces(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FilePathVO cannot be empty');

        new FilePathVO('   ');
    }

    public function test_constructor_throws_exception_for_invalid_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FilePath contains invalid characters');

        new FilePathVO('user@profile');
    }

    public function test_constructor_throws_exception_for_invalid_characters_with_space(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FilePath contains invalid characters');

        new FilePathVO('user profile');
    }

    public function test_constructor_throws_exception_for_consecutive_dots(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FilePathVO cannot contain consecutive dots');

        new FilePathVO('domain..users.profile');
    }

    public function test_constructor_throws_exception_for_consecutive_dashes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FilePathVO cannot contain consecutive hyphens');

        new FilePathVO('domain.users.profile--action');
    }

    public function test_constructor_throws_exception_starting_with_dot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FilePathVO cannot start with a dot');

        new FilePathVO('.domain.users.profile');
    }

    public function test_constructor_throws_exception_starting_with_dash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FilePathVO cannot start with a hyphen');

        new FilePathVO('-domain.users.profile');
    }

    public function test_constructor_throws_exception_ending_with_dot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FilePathVO cannot end with a dot');

        new FilePathVO('domain.users.profile.');
    }

    public function test_constructor_throws_exception_ending_with_dash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FilePathVO cannot end with a hyphen');

        new FilePathVO('domain.users.profile-');
    }

    public function test_constructor_throws_exception_too_short(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FilePathVO must be at least 2 characters');

        new FilePathVO('a');
    }

    public function test_constructor_throws_exception_too_long(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('FilePathVO cannot exceed 100 characters');

        $longName = str_repeat('a', 101);
        new FilePathVO($longName);
    }

    // ==================== TESTS GETTERS ====================

    public function test_get_raw(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertSame('domain.users.profile-action', $path->getRaw());
    }

    public function test_get_segments(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $segments = $path->getSegments();

        $this->assertInstanceOf(StringTypedCollection::class, $segments);
        $this->assertSame(['domain', 'users', 'profile-action'], $segments->toArray());
        $this->assertCount(3, $segments);
    }

    public function test_get_segments_single_segment(): void
    {
        $path = new FilePathVO('user-profile');
        $segments = $path->getSegments();

        $this->assertSame(['user-profile'], $segments->toArray());
        $this->assertCount(1, $segments);
    }

    public function test_get_folders(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $folders = $path->getFolders();

        $this->assertInstanceOf(StringTypedCollection::class, $folders);
        $this->assertSame(['Domain', 'Users'], $folders->toArray());
        $this->assertCount(2, $folders);
    }

    public function test_get_folders_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $folders = $path->getFolders();

        $this->assertSame([], $folders->toArray());
        $this->assertCount(0, $folders);
    }

    public function test_get_path_folders(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $pathFolders = $path->getPathFolders();

        $this->assertInstanceOf(StringTypedCollection::class, $pathFolders);
        $this->assertSame(['Domain', 'Domain/Users'], $pathFolders->toArray());
        $this->assertCount(2, $pathFolders);
    }

    public function test_get_path_folders_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $pathFolders = $path->getPathFolders();

        $this->assertSame([], $pathFolders->toArray());
        $this->assertCount(0, $pathFolders);
    }

    public function test_get_path_folders_deep_nesting(): void
    {
        $path = new FilePathVO('api.v1.admin.user-management');
        $pathFolders = $path->getPathFolders();

        $this->assertSame(['Api', 'Api/V1', 'Api/V1/Admin'], $pathFolders->toArray());
        $this->assertCount(3, $pathFolders);
    }

    public function test_get_path_segments(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $pathSegments = $path->getPathSegments();

        $this->assertInstanceOf(StringTypedCollection::class, $pathSegments);
        $this->assertSame(['Domain', 'Domain/Users', 'Domain/Users/ProfileAction.php'], $pathSegments->toArray());
        $this->assertCount(3, $pathSegments);
    }

    public function test_get_path_segments_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $pathSegments = $path->getPathSegments();

        $this->assertSame(['UserProfile.php'], $pathSegments->toArray());
        $this->assertCount(1, $pathSegments);
    }

    public function test_get_path_segments_deep_nesting(): void
    {
        $path = new FilePathVO('api.v1.admin.user-management');
        $pathSegments = $path->getPathSegments();

        $this->assertSame(
            ['Api', 'Api/V1', 'Api/V1/Admin', 'Api/V1/Admin/UserManagement.php'],
            $pathSegments->toArray()
        );
        $this->assertCount(4, $pathSegments);
    }

    public function test_get_path_segments_with_extension_blade(): void
    {
        $path = new FilePathVO('domain.users.profile-action', ExtensionType::BLADE);
        $pathSegments = $path->getPathSegments();

        $this->assertSame(['Domain', 'Domain/Users', 'Domain/Users/ProfileAction.blade.php'], $pathSegments->toArray());
    }

    public function test_get_base_name(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertSame('profile-action', $path->getBaseName());
    }

    public function test_get_base_name_single_segment(): void
    {
        $path = new FilePathVO('user-profile');
        $this->assertSame('user-profile', $path->getBaseName());
    }

    public function test_get_file_name(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertSame('ProfileAction', $path->getFileName());
    }

    public function test_get_file_name_multiple_hyphens(): void
    {
        $path = new FilePathVO('domain.users.user-profile-data');
        $this->assertSame('UserProfileData', $path->getFileName());
    }

    public function test_get_file_name_single_segment(): void
    {
        $path = new FilePathVO('user-profile');
        $this->assertSame('UserProfile', $path->getFileName());
    }

    public function test_get_directory_path(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertSame('Domain/Users', $path->getDirectoryPath());
    }

    public function test_get_directory_path_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $this->assertSame('', $path->getDirectoryPath());
    }

    public function test_get_full_path(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertSame('Domain/Users/ProfileAction', $path->getFullPath());
    }

    public function test_get_full_path_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $this->assertSame('UserProfile', $path->getFullPath());
    }

    public function test_get_file_path(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertSame('Domain/Users/ProfileAction.php', $path->getFilePath());
    }

    public function test_get_file_path_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $this->assertSame('UserProfile.php', $path->getFilePath());
    }

    public function test_get_file_path_with_blade_extension(): void
    {
        $path = new FilePathVO('domain.users.profile-action', ExtensionType::BLADE);
        $this->assertSame('Domain/Users/ProfileAction.blade.php', $path->getFilePath());
    }

    public function test_get_file_path_with_html_extension(): void
    {
        $path = new FilePathVO('domain.users.profile-action', ExtensionType::HTML);
        $this->assertSame('Domain/Users/ProfileAction.html', $path->getFilePath());
    }

    public function test_get_extension(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertSame(ExtensionType::PHP, $path->getExtension());
    }

    public function test_get_extension_with_custom_extension(): void
    {
        $path = new FilePathVO('domain.users.profile-action', ExtensionType::BLADE);
        $this->assertSame(ExtensionType::BLADE, $path->getExtension());
    }

    // ==================== TESTS MÉTHODES UTILES ====================

    public function test_get_namespace(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertSame('Domain\\Users', $path->getNamespace());
    }

    public function test_get_namespace_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $this->assertSame('', $path->getNamespace());
    }

    public function test_get_namespace_deep_nesting(): void
    {
        $path = new FilePathVO('api.v1.admin.user-management');
        $this->assertSame('Api\\V1\\Admin', $path->getNamespace());
    }

    public function test_get_full_namespace(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertSame('Domain\\Users\\ProfileAction', $path->getFullNamespace());
    }

    public function test_get_full_namespace_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $this->assertSame('UserProfile', $path->getFullNamespace());
    }

    public function test_get_depth(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertSame(2, $path->getDepth());
    }

    public function test_get_depth_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $this->assertSame(0, $path->getDepth());
    }

    public function test_has_folders(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertTrue($path->hasFolders());
    }

    public function test_has_folders_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $this->assertFalse($path->hasFolders());
    }

    public function test_is_in_sub_directory(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertTrue($path->isInSubDirectory('Users'));
        $this->assertFalse($path->isInSubDirectory('Other'));
    }

    public function test_is_in_sub_directory_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $this->assertFalse($path->isInSubDirectory('Users'));
    }

    public function test_starts_with_folder(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertTrue($path->startsWithFolder('Domain'));
        $this->assertFalse($path->startsWithFolder('Users'));
    }

    public function test_starts_with_folder_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $this->assertFalse($path->startsWithFolder('Domain'));
    }

    public function test_ends_with_folder(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertTrue($path->endsWithFolder('Users'));
        $this->assertFalse($path->endsWithFolder('Domain'));
    }

    public function test_ends_with_folder_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $this->assertFalse($path->endsWithFolder('Users'));
    }

    public function test_get_relative_path(): void
    {
        $path = new FilePathVO('app.domain.users.profile-action');
        $this->assertSame('Domain/Users/ProfileAction.php', $path->getRelativePath('app'));
    }

    public function test_get_relative_path_without_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $this->assertSame('UserProfile.php', $path->getRelativePath('app'));
    }

    public function test_get_relative_path_no_match(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertSame('Domain/Users/ProfileAction.php', $path->getRelativePath('other'));
    }

    // ==================== TESTS WITH METHODS ====================

    public function test_with_extension(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $newPath = $path->withExtension(ExtensionType::BLADE);

        $this->assertNotSame($path, $newPath);
        $this->assertSame('domain.users.profile-action', $newPath->getRaw());
        $this->assertSame(ExtensionType::BLADE, $newPath->getExtension());
        $this->assertSame('Domain/Users/ProfileAction.blade.php', $newPath->getFilePath());
        $this->assertSame(ExtensionType::PHP, $path->getExtension());
    }

    public function test_with_suffix(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $newPath = $path->withSuffix('admin');

        $this->assertNotSame($path, $newPath);
        $this->assertSame('domain.users.profile-action-admin', $newPath->getRaw());
        $this->assertSame('ProfileActionAdmin', $newPath->getFileName());
        $this->assertSame('Domain/Users/ProfileActionAdmin.php', $newPath->getFilePath());
    }

    public function test_with_suffix_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $newPath = $path->withSuffix('admin');

        $this->assertSame('user-profile-admin', $newPath->getRaw());
        $this->assertSame('UserProfileAdmin', $newPath->getFileName());
        $this->assertSame('UserProfileAdmin.php', $newPath->getFilePath());
    }

    public function test_with_prefix(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $newPath = $path->withPrefix('api');

        $this->assertNotSame($path, $newPath);
        $this->assertSame('domain.users.api-profile-action', $newPath->getRaw());
        $this->assertSame('ApiProfileAction', $newPath->getFileName());
        $this->assertSame('Domain/Users/ApiProfileAction.php', $newPath->getFilePath());
    }

    public function test_with_prefix_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $newPath = $path->withPrefix('api');

        $this->assertSame('api-user-profile', $newPath->getRaw());
        $this->assertSame('ApiUserProfile', $newPath->getFileName());
        $this->assertSame('ApiUserProfile.php', $newPath->getFilePath());
    }

    // ==================== TESTS AbstractValueObject ====================

    public function test_get_value_returns_full_path(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertSame('Domain/Users/ProfileAction', $path->getValue());
    }

    public function test_get_value_no_folders(): void
    {
        $path = new FilePathVO('user-profile');
        $this->assertSame('UserProfile', $path->getValue());
    }

    public function test_equals_same_value(): void
    {
        $path1 = new FilePathVO('domain.users.profile-action');
        $path2 = new FilePathVO('domain.users.profile-action');

        $this->assertTrue($path1->equals($path2));
    }

    public function test_equals_different_values(): void
    {
        $path1 = new FilePathVO('domain.users.profile-action');
        $path2 = new FilePathVO('domain.users.other-action');

        $this->assertFalse($path1->equals($path2));
    }

    public function test_to_string_returns_json(): void
    {
        $path = new FilePathVO('domain.users.profile-action');
        $this->assertSame('"Domain\\/Users\\/ProfileAction"', (string) $path);
    }

    // ==================== TESTS INTÉGRATION ====================

    public function test_complete_workflow_without_folders(): void
    {
        $path = new FilePathVO('user-profile');

        $this->assertSame('user-profile', $path->getRaw());
        $this->assertSame(['user-profile'], $path->getSegments()->toArray());
        $this->assertSame([], $path->getFolders()->toArray());
        $this->assertSame([], $path->getPathFolders()->toArray());
        $this->assertSame(['UserProfile.php'], $path->getPathSegments()->toArray());
        $this->assertSame('user-profile', $path->getBaseName());
        $this->assertSame('UserProfile', $path->getFileName());
        $this->assertSame('', $path->getDirectoryPath());
        $this->assertSame('UserProfile', $path->getFullPath());
        $this->assertSame('UserProfile.php', $path->getFilePath());
        $this->assertSame('', $path->getNamespace());
        $this->assertSame('UserProfile', $path->getFullNamespace());
        $this->assertSame(0, $path->getDepth());
        $this->assertFalse($path->hasFolders());
    }

    public function test_complete_workflow_with_folders(): void
    {
        $path = new FilePathVO('domain.users.profile-action');

        $this->assertSame('domain.users.profile-action', $path->getRaw());
        $this->assertSame(['domain', 'users', 'profile-action'], $path->getSegments()->toArray());
        $this->assertSame(['Domain', 'Users'], $path->getFolders()->toArray());
        $this->assertSame(['Domain', 'Domain/Users'], $path->getPathFolders()->toArray());
        $this->assertSame(['Domain', 'Domain/Users', 'Domain/Users/ProfileAction.php'], $path->getPathSegments()->toArray());
        $this->assertSame('profile-action', $path->getBaseName());
        $this->assertSame('ProfileAction', $path->getFileName());
        $this->assertSame('Domain/Users', $path->getDirectoryPath());
        $this->assertSame('Domain/Users/ProfileAction', $path->getFullPath());
        $this->assertSame('Domain/Users/ProfileAction.php', $path->getFilePath());
        $this->assertSame('Domain\\Users', $path->getNamespace());
        $this->assertSame('Domain\\Users\\ProfileAction', $path->getFullNamespace());
        $this->assertSame(2, $path->getDepth());
        $this->assertTrue($path->hasFolders());
    }

    public function test_complete_workflow_deep_nesting(): void
    {
        $path = new FilePathVO('api.v1.admin.user-management');

        $this->assertSame(['api', 'v1', 'admin', 'user-management'], $path->getSegments()->toArray());
        $this->assertSame(['Api', 'V1', 'Admin'], $path->getFolders()->toArray());
        $this->assertSame(['Api', 'Api/V1', 'Api/V1/Admin'], $path->getPathFolders()->toArray());
        $this->assertSame(['Api', 'Api/V1', 'Api/V1/Admin', 'Api/V1/Admin/UserManagement.php'], $path->getPathSegments()->toArray());
        $this->assertSame('UserManagement', $path->getFileName());
        $this->assertSame('Api/V1/Admin/UserManagement.php', $path->getFilePath());
        $this->assertSame('Api\\V1\\Admin', $path->getNamespace());
        $this->assertSame('Api\\V1\\Admin\\UserManagement', $path->getFullNamespace());
        $this->assertSame(3, $path->getDepth());
    }

    public function test_chained_modifications(): void
    {
        $path = new FilePathVO('domain.users.profile-action');

        $path2 = $path->withExtension(ExtensionType::BLADE);
        $path3 = $path2->withSuffix('admin');
        $path4 = $path3->withPrefix('api');

        $this->assertSame('domain.users.profile-action', $path->getRaw());
        $this->assertSame('domain.users.profile-action', $path2->getRaw());
        $this->assertSame('domain.users.profile-action-admin', $path3->getRaw());
        $this->assertSame('domain.users.api-profile-action-admin', $path4->getRaw());

        $this->assertSame('ProfileAction.php', basename($path->getFilePath()));
        $this->assertSame('ProfileAction.blade.php', basename($path2->getFilePath()));
        $this->assertSame('ProfileActionAdmin.blade.php', basename($path3->getFilePath()));
        $this->assertSame('ApiProfileActionAdmin.blade.php', basename($path4->getFilePath()));
    }

    public function test_real_world_example_generator_usage(): void
    {
        // Le préfixe 'app' est un dossier et devient 'App' en PascalCase
        // C'est le comportement attendu car tous les segments sont transformés
        $path = new FilePathVO('app.domain.users.profile-action');

        $baseNamespace = 'App\\Generators';
        $baseDirectory = 'app/Generators';

        $fullNamespace = $baseNamespace.'\\'.$path->getNamespace();
        $fullPath = $baseDirectory.'/'.$path->getFilePath();

        $this->assertSame('App\\Generators\\App\\Domain\\Users', $fullNamespace);
        $this->assertSame('app/Generators/App/Domain/Users/ProfileAction.php', $fullPath);

        // Création des répertoires progressifs
        $directories = $path->getPathFolders()->toArray();
        $expectedDirectories = ['App', 'App/Domain', 'App/Domain/Users'];
        $this->assertSame($expectedDirectories, $directories);
    }

    public function test_real_world_example_generator_usage_without_prefix(): void
    {
        // Si on ne veut pas le préfixe 'app', on l'omet
        $path = new FilePathVO('domain.users.profile-action');

        $baseNamespace = 'App\\Generators';
        $baseDirectory = 'app/Generators';

        $fullNamespace = $baseNamespace.'\\'.$path->getNamespace();
        $fullPath = $baseDirectory.'/'.$path->getFilePath();

        $this->assertSame('App\\Generators\\Domain\\Users', $fullNamespace);
        $this->assertSame('app/Generators/Domain/Users/ProfileAction.php', $fullPath);

        $directories = $path->getPathFolders()->toArray();
        $expectedDirectories = ['Domain', 'Domain/Users'];
        $this->assertSame($expectedDirectories, $directories);
    }
}
