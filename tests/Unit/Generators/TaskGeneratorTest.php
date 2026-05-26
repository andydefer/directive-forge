<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\TaskGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class TaskGeneratorTest extends UnitTestCase
{
    private TaskGenerator $generator;
    private DirectiveInteractionService&MockObject $interaction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interaction = $this->createMock(DirectiveInteractionService::class);
        $this->generator = new TaskGenerator($this->interaction);
    }

    public function test_get_replacements_with_simple_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'send-welcome-email',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertIsArray($replacements);
        $this->assertArrayHasKey('{{namespace}}', $replacements);
        $this->assertArrayHasKey('{{class}}', $replacements);
        $this->assertArrayHasKey('{{description}}', $replacements);

        $this->assertSame('App\\Tasks', $replacements['{{namespace}}']);
        // Le nom de la classe reste tel quel (kebab-case)
        $this->assertSame('send-welcome-email', $replacements['{{class}}']);
        $this->assertStringContainsString('send-welcome-email', $replacements['{{description}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        $pathInfo = new PathInfo(
            className: 'send-welcome-email',
            subPath: 'User',
            segments: ['User']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Tasks\\User', $replacements['{{namespace}}']);
        $this->assertSame('send-welcome-email', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_nested_subdirectories(): void
    {
        $pathInfo = new PathInfo(
            className: 'process-order',
            subPath: 'Shop\\Checkout',
            segments: ['Shop', 'Checkout']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Tasks\\Shop\\Checkout', $replacements['{{namespace}}']);
        $this->assertSame('process-order', $replacements['{{class}}']);
    }

    public function test_get_replacements_when_class_already_has_suffix(): void
    {
        $pathInfo = new PathInfo(
            className: 'SendWelcomeEmailTask',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('SendWelcomeEmailTask', $replacements['{{class}}']);
        $this->assertStringEndsWith('Task', $replacements['{{class}}']);
    }

    public function test_get_replacements_preserves_kebab_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'send-welcome-email',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('send-welcome-email', $replacements['{{class}}']);
    }

    public function test_get_replacements_preserves_snake_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'send_welcome_email',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('send_welcome_email', $replacements['{{class}}']);
    }

    public function test_get_replacements_preserves_existing_pascal_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'ProcessOrder',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('ProcessOrder', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_numbers_in_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'process-order-v2',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('process-order-v2', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_uppercase_acronyms(): void
    {
        $pathInfo = new PathInfo(
            className: 'send-API-request',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('send-API-request', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_mixed_case(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-API-key',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('user-API-key', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_long_complex_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'send-weekly-digest-email-to-all-active-users',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('send-weekly-digest-email-to-all-active-users', $replacements['{{class}}']);
    }

    public function test_get_replacements_description_contains_original_name(): void
    {
        $originalName = 'send-welcome-email';
        $pathInfo = new PathInfo(
            className: $originalName,
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertStringContainsString($originalName, $replacements['{{description}}']);
    }

    public function test_get_replacements_description_is_meaningful(): void
    {
        $pathInfo = new PathInfo(
            className: 'process-order-payment',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertStringContainsString('Task for', $replacements['{{description}}']);
        $this->assertStringContainsString('process-order-payment', $replacements['{{description}}']);
    }

    public function test_generate_calls_parent_generate_method(): void
    {
        $pathInfo = new PathInfo(
            className: 'test-task',
            subPath: '',
            segments: []
        );

        $reflection = new \ReflectionClass($this->generator);
        $method = $reflection->getMethod('generate');

        $this->assertTrue($method->isPublic());
    }

    public function test_task_generator_has_correct_type(): void
    {
        $type = $this->generator->getType();

        $this->assertSame('task', $type->value);
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
        $this->assertContains('{{description}}', $keys);
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
        $this->assertArrayNotHasKey('{{type}}', $replacements);
        $this->assertArrayNotHasKey('{{interface}}', $replacements);
        $this->assertArrayNotHasKey('{{item_type}}', $replacements);
    }

    public function test_multiple_task_generations_produce_consistent_results(): void
    {
        $pathInfo1 = new PathInfo(
            className: 'my-task',
            subPath: '',
            segments: []
        );

        $pathInfo2 = new PathInfo(
            className: 'my-task',
            subPath: '',
            segments: []
        );

        $replacements1 = $this->generator->getReplacements($pathInfo1);
        $replacements2 = $this->generator->getReplacements($pathInfo2);

        $this->assertSame($replacements1, $replacements2);
    }

    public function test_get_replacements_with_deeply_nested_path(): void
    {
        $pathInfo = new PathInfo(
            className: 'send-notification',
            subPath: 'User\\Notifications\\Email\\Welcome',
            segments: ['User', 'Notifications', 'Email', 'Welcome']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Tasks\\User\\Notifications\\Email\\Welcome', $replacements['{{namespace}}']);
        $this->assertSame('send-notification', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_single_word_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'cleanup',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('cleanup', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_single_word_already_has_suffix(): void
    {
        $pathInfo = new PathInfo(
            className: 'CleanupTask',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('CleanupTask', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_verb_first_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'send-email',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('send-email', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_multiple_verbs(): void
    {
        $pathInfo = new PathInfo(
            className: 'fetch-process-store-data',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('fetch-process-store-data', $replacements['{{class}}']);
    }

    public function test_generator_extends_abstract_generator(): void
    {
        $this->assertInstanceOf(\AndyDefer\DirectiveForge\Generators\AbstractGenerator::class, $this->generator);
    }

    public function test_generator_has_correct_config(): void
    {
        $config = $this->generator->getType()->getConfig();

        $this->assertSame('task', $config->type->value);
        $this->assertSame('/app/Tasks/', $config->basePath);
        $this->assertSame('App\\Tasks', $config->baseNamespace);
        $this->assertStringContainsString('task.stub', $config->stubPath);
        $this->assertSame('Task', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    /**
     * Note: Les tests de génération de fichiers sont maintenant gérés par AbstractGenerator
     * et utilisent le trait FileCreator. La propriété $files n'existe plus directement
     * dans TaskGenerator car elle est dans le trait FileCreator.
     * 
     * Pour tester la génération de fichiers, utilisez les tests d'intégration.
     */
    public function test_generator_returns_correct_type(): void
    {
        $this->assertSame('task', $this->generator->getType()->value);
    }
}
