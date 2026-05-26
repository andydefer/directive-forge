<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\ActionGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class ActionGeneratorTest extends UnitTestCase
{
    private ActionGenerator $generator;
    private DirectiveInteractionService&MockObject $interaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interaction = $this->createMock(DirectiveInteractionService::class);
        $this->generator = new ActionGenerator($this->interaction);
    }

    // ==================== Tests de base pour getReplacements ====================

    public function test_get_replacements_with_simple_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'show-user',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertArrayHasKey('{{ namespace }}', $replacements);
        $this->assertArrayHasKey('{{ class }}', $replacements);
        $this->assertArrayHasKey('{{ view }}', $replacements);

        $this->assertSame('App\\Actions', $replacements['{{ namespace }}']);
        $this->assertSame('show-user', $replacements['{{ class }}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        $pathInfo = new PathInfo(
            className: 'show-user',
            subPath: 'Api\\V1\\Users',
            segments: ['Api', 'V1', 'Users']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Actions\\Api\\V1\\Users', $replacements['{{ namespace }}']);
        $this->assertSame('show-user', $replacements['{{ class }}']);
    }

    public function test_action_already_has_suffix(): void
    {
        $pathInfo = new PathInfo(
            className: 'ShowUserAction',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('ShowUserAction', $replacements['{{ class }}']);
    }

    // ==================== Tests de génération de vue ====================

    public function test_get_replacements_generates_view_from_pascal_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'ShowDashboardAction',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('ShowDashboardAction', $replacements['{{ class }}']);
        // ShowDashboardAction -> enlève Action -> ShowDashboard -> Dashboard/Show
        $this->assertSame('Dashboard/Show', $replacements['{{ view }}']);
    }

    public function test_get_replacements_generates_view_from_complex_pascal_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'AdminUserProfileAction',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('AdminUserProfileAction', $replacements['{{ class }}']);
        // AdminUserProfileAction -> enlève Action -> AdminUserProfile -> Profile/Admin/User
        $this->assertSame('Profile/Admin/User', $replacements['{{ view }}']);
    }

    public function test_get_replacements_generates_view_from_single_word_action(): void
    {
        $pathInfo = new PathInfo(
            className: 'ProfileAction',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('ProfileAction', $replacements['{{ class }}']);
        // ProfileAction -> enlève Action -> Profile
        $this->assertSame('Profile', $replacements['{{ view }}']);
    }

    public function test_get_replacements_generates_view_from_kebab_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-profile',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('user-profile', $replacements['{{ class }}']);
        $this->assertSame('user-profile', $replacements['{{ view }}']);
    }

    public function test_get_replacements_generates_view_from_snake_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'user_profile_data',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('user_profile_data', $replacements['{{ class }}']);
        $this->assertSame('user_profile_data', $replacements['{{ view }}']);
    }

    public function test_get_replacements_generates_view_from_pascal_case_with_numbers(): void
    {
        $pathInfo = new PathInfo(
            className: 'User2faAction',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('User2faAction', $replacements['{{ class }}']);
        // User2faAction -> enlève Action -> User2fa
        $this->assertSame('User2fa', $replacements['{{ view }}']);
    }

    public function test_get_replacements_generates_view_with_uppercase_acronyms(): void
    {
        $pathInfo = new PathInfo(
            className: 'APIKeyAction',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('APIKeyAction', $replacements['{{ class }}']);
        // APIKeyAction -> enlève Action -> APIKey
        $this->assertSame('APIKey', $replacements['{{ view }}']);
    }

    // ==================== Tests avec noms vides ou limites ====================

    public function test_get_replacements_with_empty_class_name(): void
    {
        $pathInfo = new PathInfo(
            className: '',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('', $replacements['{{ class }}']);
        $this->assertSame('', $replacements['{{ view }}']);
    }

    public function test_get_replacements_with_class_name_containing_only_spaces(): void
    {
        $pathInfo = new PathInfo(
            className: '   ',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('   ', $replacements['{{ class }}']);
    }

    public function test_get_replacements_with_special_characters(): void
    {
        $pathInfo = new PathInfo(
            className: 'user@profile#action',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('user@profile#action', $replacements['{{ class }}']);
    }

    // ==================== Tests avec paramètre type ====================

    public function test_get_replacements_with_type_parameter_is_ignored(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-profile',
            subPath: '',
            segments: []
        );

        $replacementsWithoutType = $this->generator->getReplacements($pathInfo);
        $replacementsWithApiType = $this->generator->getReplacements($pathInfo, 'api');
        $replacementsWithWebType = $this->generator->getReplacements($pathInfo, 'web');

        $this->assertSame($replacementsWithoutType, $replacementsWithApiType);
        $this->assertSame($replacementsWithoutType, $replacementsWithWebType);
    }

    // ==================== Tests avec paramètre itemType ====================

    public function test_get_replacements_with_item_type_parameter_is_ignored(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-profile',
            subPath: '',
            segments: []
        );

        $replacementsWithoutItemType = $this->generator->getReplacements($pathInfo);
        $replacementsWithItemType = $this->generator->getReplacements($pathInfo, null, 'UserRecord');

        $this->assertSame($replacementsWithoutItemType, $replacementsWithItemType);
    }

    // ==================== Tests de structure ====================

    public function test_get_replacements_returns_array_with_correct_keys(): void
    {
        $pathInfo = new PathInfo(
            className: 'test',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);
        $keys = array_keys($replacements);

        $this->assertContains('{{ namespace }}', $keys);
        $this->assertContains('{{ class }}', $keys);
        $this->assertContains('{{ view }}', $keys);
        $this->assertCount(3, $keys);
    }

    public function test_get_replacements_never_contains_extra_placeholders(): void
    {
        $pathInfo = new PathInfo(
            className: 'test',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertArrayNotHasKey('{{signature}}', $replacements);
        $this->assertArrayNotHasKey('{{description}}', $replacements);
        $this->assertArrayNotHasKey('{{type}}', $replacements);
        $this->assertArrayNotHasKey('{{interface}}', $replacements);
        $this->assertArrayNotHasKey('{{item_type}}', $replacements);
    }

    // ==================== Tests de cohérence ====================

    public function test_multiple_replacements_calls_produce_consistent_results(): void
    {
        $pathInfo1 = new PathInfo(
            className: 'my-action',
            subPath: '',
            segments: []
        );

        $pathInfo2 = new PathInfo(
            className: 'my-action',
            subPath: '',
            segments: []
        );

        $replacements1 = $this->generator->getReplacements($pathInfo1);
        $replacements2 = $this->generator->getReplacements($pathInfo2);

        $this->assertSame($replacements1, $replacements2);
    }

    public function test_get_replacements_preserves_namespace_without_subpath(): void
    {
        $pathInfo = new PathInfo(
            className: 'test',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Actions', $replacements['{{ namespace }}']);
    }

    public function test_get_replacements_with_deeply_nested_subdirectories(): void
    {
        $pathInfo = new PathInfo(
            className: 'process-order',
            subPath: 'Api\\V2\\Shop\\Checkout\\Payment',
            segments: ['Api', 'V2', 'Shop', 'Checkout', 'Payment']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Actions\\Api\\V2\\Shop\\Checkout\\Payment', $replacements['{{ namespace }}']);
        $this->assertSame('process-order', $replacements['{{ class }}']);
    }

    // ==================== Tests d'héritage et de configuration ====================

    public function test_generator_extends_abstract_generator(): void
    {
        $this->assertInstanceOf(\AndyDefer\DirectiveForge\Generators\AbstractGenerator::class, $this->generator);
    }

    public function test_generator_has_correct_type(): void
    {
        $type = $this->generator->getType();

        $this->assertSame('action', $type->value);
    }

    public function test_generator_has_correct_config(): void
    {
        $config = $this->generator->getType()->getConfig();

        $this->assertSame('action', $config->type->value);
        $this->assertSame('/app/Actions/', $config->basePath);
        $this->assertSame('App\\Actions', $config->baseNamespace);
        $this->assertStringContainsString('action.stub', $config->stubPath);
        $this->assertSame('Action', $config->suffix);
        $this->assertTrue($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    // ==================== Tests de vue avec sous-dossiers ====================

    public function test_get_replacements_generates_view_with_subdirectory_in_class_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'AdminUserProfileAction',
            subPath: 'V2',
            segments: ['V2']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Actions\\V2', $replacements['{{ namespace }}']);
        $this->assertSame('AdminUserProfileAction', $replacements['{{ class }}']);
        // AdminUserProfileAction -> enlève Action -> AdminUserProfile -> Profile/Admin/User
        $this->assertSame('Profile/Admin/User', $replacements['{{ view }}']);
    }

    public function test_get_replacements_generates_view_from_pascal_case_with_multiple_acronyms(): void
    {
        $pathInfo = new PathInfo(
            className: 'XMLHTTPRequestAction',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('XMLHTTPRequestAction', $replacements['{{ class }}']);
        // XMLHTTPRequestAction -> enlève Action -> XMLHTTPRequest
        $this->assertSame('XMLHTTPRequest', $replacements['{{ view }}']);
    }
}
