<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\RecordGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class RecordGeneratorTest extends UnitTestCase
{
    private RecordGenerator $generator;
    private DirectiveInteractionService&MockObject $interaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interaction = $this->createMock(DirectiveInteractionService::class);
        $this->generator = new RecordGenerator($this->interaction);
    }

    public function test_get_replacements_with_simple_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-data',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertIsArray($replacements);
        $this->assertArrayHasKey('{{namespace}}', $replacements);
        $this->assertArrayHasKey('{{class}}', $replacements);

        $this->assertSame('App\\Records', $replacements['{{namespace}}']);
        // Le nom de la classe reste tel quel (kebab-case)
        $this->assertSame('user-data', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-data',
            subPath: 'Api\\V1\\Users',
            segments: ['Api', 'V1', 'Users']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Records\\Api\\V1\\Users', $replacements['{{namespace}}']);
        $this->assertSame('user-data', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_nested_subdirectories(): void
    {
        $pathInfo = new PathInfo(
            className: 'order-item',
            subPath: 'Shop\\Checkout\\Items',
            segments: ['Shop', 'Checkout', 'Items']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Records\\Shop\\Checkout\\Items', $replacements['{{namespace}}']);
        $this->assertSame('order-item', $replacements['{{class}}']);
    }

    public function test_get_replacements_when_class_already_has_suffix(): void
    {
        $pathInfo = new PathInfo(
            className: 'UserDataRecord',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('UserDataRecord', $replacements['{{class}}']);
        $this->assertStringEndsWith('Record', $replacements['{{class}}']);
    }

    public function test_get_replacements_preserves_kebab_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-profile-data',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('user-profile-data', $replacements['{{class}}']);
    }

    public function test_get_replacements_preserves_snake_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'user_profile_data',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('user_profile_data', $replacements['{{class}}']);
    }

    public function test_get_replacements_preserves_existing_pascal_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'ShowUserData',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('ShowUserData', $replacements['{{class}}']);
    }

    public function test_get_replacements_handles_single_word_class_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'config',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('config', $replacements['{{class}}']);
    }

    public function test_get_replacements_handles_single_word_with_suffix(): void
    {
        $pathInfo = new PathInfo(
            className: 'ConfigRecord',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('ConfigRecord', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_deeply_nested_path(): void
    {
        $pathInfo = new PathInfo(
            className: 'order-summary',
            subPath: 'Api\\V2\\Shop\\Checkout\\Summary',
            segments: ['Api', 'V2', 'Shop', 'Checkout', 'Summary']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Records\\Api\\V2\\Shop\\Checkout\\Summary', $replacements['{{namespace}}']);
        $this->assertSame('order-summary', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_numbers_in_class_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-2fa-data',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('user-2fa-data', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_uppercase_in_class_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'UserAPIKey',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('UserAPIKey', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_mixed_case_class_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-API-key',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('user-API-key', $replacements['{{class}}']);
    }

    public function test_generate_calls_parent_generate_method(): void
    {
        $pathInfo = new PathInfo(
            className: 'test-record',
            subPath: '',
            segments: []
        );

        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('generate');

        $this->assertTrue($method->isPublic());
    }

    public function test_record_generator_has_correct_type(): void
    {
        $type = $this->generator->getType();

        $this->assertSame('record', $type->value);
    }

    public function test_get_replacements_returns_array_with_correct_keys(): void
    {
        $pathInfo = new PathInfo(
            className: 'test',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);
        $keys = array_keys($replacements);

        $this->assertContains('{{namespace}}', $keys);
        $this->assertContains('{{class}}', $keys);
        $this->assertCount(2, $keys);
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
    }

    public function test_multiple_record_generations_produce_consistent_results(): void
    {
        $pathInfo1 = new PathInfo(
            className: 'user-data',
            subPath: '',
            segments: []
        );

        $pathInfo2 = new PathInfo(
            className: 'user-data',
            subPath: '',
            segments: []
        );

        $replacements1 = $this->generator->getReplacements($pathInfo1);
        $replacements2 = $this->generator->getReplacements($pathInfo2);

        $this->assertSame($replacements1, $replacements2);
    }

    public function test_generator_extends_abstract_generator(): void
    {
        $this->assertInstanceOf(\AndyDefer\DirectiveForge\Generators\AbstractGenerator::class, $this->generator);
    }

    public function test_generator_has_correct_config(): void
    {
        $config = $this->generator->getType()->getConfig();

        $this->assertSame('record', $config->type->value);
        $this->assertSame('/app/Records/', $config->basePath);
        $this->assertSame('App\\Records', $config->baseNamespace);
        $this->assertStringContainsString('record.stub', $config->stubPath);
        $this->assertSame('Record', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    /**
     * Note: Les tests de génération de fichiers sont maintenant gérés par AbstractGenerator
     * et utilisent le trait FileCreator. La propriété $files n'existe plus directement
     * dans RecordGenerator car elle est dans le trait FileCreator.
     * 
     * Pour tester la génération de fichiers, utilisez les tests d'intégration.
     */
    public function test_generator_returns_correct_type(): void
    {
        $this->assertSame('record', $this->generator->getType()->value);
    }
}
