<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\AbstractGenerator;
use AndyDefer\DirectiveForge\Generators\TaskGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;
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

    private function assertReplacementHasKey(ReplacementCollection $replacements, string $key, string $expectedValue): void
    {
        $array = $replacements->toAssociativeArray();
        $this->assertArrayHasKey($key, $array);
        $this->assertSame($expectedValue, $array[$key]);
    }

    private function assertReplacementNotHasKey(ReplacementCollection $replacements, string $key): void
    {
        $array = $replacements->toAssociativeArray();
        $this->assertArrayNotHasKey($key, $array);
    }

    public function test_get_replacements_with_simple_name(): void
    {
        // Arrange: Create path info with simple name
        $pathInfo = $this->createPathInfo('send-welcome-email', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify replacement structure
        $this->assertArrayHasKey('{{namespace}}', $array);
        $this->assertArrayHasKey('{{class}}', $array);
        $this->assertArrayHasKey('{{description}}', $array);
        $this->assertSame('App\\Tasks', $array['{{namespace}}']);
        $this->assertSame('send-welcome-email', $array['{{class}}']);
        $this->assertStringContainsString('send-welcome-email', $array['{{description}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        // Arrange: Create path info with subdirectory
        $pathInfo = $this->createPathInfo('send-welcome-email', 'User', ['User']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify namespace includes subdirectory
        $this->assertSame('App\\Tasks\\User', $array['{{namespace}}']);
        $this->assertSame('send-welcome-email', $array['{{class}}']);
    }

    public function test_get_replacements_with_nested_subdirectories(): void
    {
        // Arrange: Create path info with nested subdirectories
        $pathInfo = $this->createPathInfo('process-order', 'Shop\\Checkout', ['Shop', 'Checkout']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify nested namespace
        $this->assertSame('App\\Tasks\\Shop\\Checkout', $array['{{namespace}}']);
        $this->assertSame('process-order', $array['{{class}}']);
    }

    public function test_get_replacements_when_class_already_has_suffix(): void
    {
        // Arrange: Create path info with class that already has Task suffix
        $pathInfo = $this->createPathInfo('SendWelcomeEmailTask', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify class name preserved
        $this->assertSame('SendWelcomeEmailTask', $array['{{class}}']);
        $this->assertStringEndsWith('Task', $array['{{class}}']);
    }

    public function test_get_replacements_preserves_kebab_case(): void
    {
        // Arrange: Create path info with kebab-case
        $pathInfo = $this->createPathInfo('send-welcome-email', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify kebab-case preserved
        $this->assertSame('send-welcome-email', $array['{{class}}']);
    }

    public function test_get_replacements_preserves_snake_case(): void
    {
        // Arrange: Create path info with snake_case
        $pathInfo = $this->createPathInfo('send_welcome_email', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify snake_case preserved
        $this->assertSame('send_welcome_email', $array['{{class}}']);
    }

    public function test_get_replacements_preserves_existing_pascal_case(): void
    {
        // Arrange: Create path info with PascalCase
        $pathInfo = $this->createPathInfo('ProcessOrder', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify PascalCase preserved
        $this->assertSame('ProcessOrder', $array['{{class}}']);
    }

    public function test_get_replacements_with_numbers_in_name(): void
    {
        // Arrange: Create path info with numbers
        $pathInfo = $this->createPathInfo('process-order-v2', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify numbers preserved
        $this->assertSame('process-order-v2', $array['{{class}}']);
    }

    public function test_get_replacements_with_uppercase_acronyms(): void
    {
        // Arrange: Create path info with uppercase acronyms
        $pathInfo = $this->createPathInfo('send-API-request', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify acronyms preserved
        $this->assertSame('send-API-request', $array['{{class}}']);
    }

    public function test_get_replacements_with_mixed_case(): void
    {
        // Arrange: Create path info with mixed case
        $pathInfo = $this->createPathInfo('user-API-key', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify mixed case preserved
        $this->assertSame('user-API-key', $array['{{class}}']);
    }

    public function test_get_replacements_with_long_complex_name(): void
    {
        // Arrange: Create path info with long complex name
        $pathInfo = $this->createPathInfo('send-weekly-digest-email-to-all-active-users', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify long name preserved
        $this->assertSame('send-weekly-digest-email-to-all-active-users', $array['{{class}}']);
    }

    public function test_get_replacements_description_contains_original_name(): void
    {
        // Arrange: Create path info
        $originalName = 'send-welcome-email';
        $pathInfo = $this->createPathInfo($originalName, '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify description contains original name
        $this->assertStringContainsString($originalName, $array['{{description}}']);
    }

    public function test_get_replacements_description_is_meaningful(): void
    {
        // Arrange: Create path info
        $pathInfo = $this->createPathInfo('process-order-payment', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify description format
        $this->assertStringContainsString('Task for', $array['{{description}}']);
        $this->assertStringContainsString('process-order-payment', $array['{{description}}']);
    }

    public function test_task_generator_has_correct_type(): void
    {
        // Act: Get generator type
        $type = $this->generator->getType();

        // Assert: Verify type
        $this->assertSame('task', $type->value);
    }

    public function test_get_replacements_returns_collection_with_correct_keys(): void
    {
        // Arrange: Create path info
        $pathInfo = $this->createPathInfo('test', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $keys = array_keys($replacements->toAssociativeArray());

        // Assert: Verify correct keys
        $this->assertContains('{{namespace}}', $keys);
        $this->assertContains('{{class}}', $keys);
        $this->assertContains('{{description}}', $keys);
        $this->assertCount(3, $keys);
    }

    public function test_get_replacements_never_contains_extra_placeholders(): void
    {
        // Arrange: Create path info
        $pathInfo = $this->createPathInfo('test', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);

        // Assert: Verify no extra placeholders
        $this->assertReplacementNotHasKey($replacements, '{{signature}}');
        $this->assertReplacementNotHasKey($replacements, '{{type}}');
        $this->assertReplacementNotHasKey($replacements, '{{interface}}');
        $this->assertReplacementNotHasKey($replacements, '{{item_type}}');
    }

    public function test_multiple_task_generations_produce_consistent_results(): void
    {
        // Arrange: Create two identical path info objects
        $pathInfo1 = $this->createPathInfo('my-task', '', []);
        $pathInfo2 = $this->createPathInfo('my-task', '', []);

        // Act: Get replacements from both
        $replacements1 = $this->generator->getReplacements($pathInfo1);
        $replacements2 = $this->generator->getReplacements($pathInfo2);

        // Assert: Verify results are identical
        $this->assertSame(
            $replacements1->toAssociativeArray(),
            $replacements2->toAssociativeArray()
        );
    }

    public function test_get_replacements_with_deeply_nested_path(): void
    {
        // Arrange: Create path info with deeply nested path
        $pathInfo = $this->createPathInfo(
            'send-notification',
            'User\\Notifications\\Email\\Welcome',
            ['User', 'Notifications', 'Email', 'Welcome']
        );

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify deeply nested namespace
        $this->assertSame('App\\Tasks\\User\\Notifications\\Email\\Welcome', $array['{{namespace}}']);
        $this->assertSame('send-notification', $array['{{class}}']);
    }

    public function test_get_replacements_with_single_word_name(): void
    {
        // Arrange: Create path info with single word
        $pathInfo = $this->createPathInfo('cleanup', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify single word preserved
        $this->assertSame('cleanup', $array['{{class}}']);
    }

    public function test_get_replacements_with_single_word_already_has_suffix(): void
    {
        // Arrange: Create path info with single word that has suffix
        $pathInfo = $this->createPathInfo('CleanupTask', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify class name preserved
        $this->assertSame('CleanupTask', $array['{{class}}']);
    }

    public function test_get_replacements_with_verb_first_name(): void
    {
        // Arrange: Create path info starting with verb
        $pathInfo = $this->createPathInfo('send-email', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify name preserved
        $this->assertSame('send-email', $array['{{class}}']);
    }

    public function test_get_replacements_with_multiple_verbs(): void
    {
        // Arrange: Create path info with multiple verbs
        $pathInfo = $this->createPathInfo('fetch-process-store-data', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify name preserved
        $this->assertSame('fetch-process-store-data', $array['{{class}}']);
    }

    public function test_generator_extends_abstract_generator(): void
    {
        // Assert: Verify inheritance
        $this->assertInstanceOf(AbstractGenerator::class, $this->generator);
    }

    public function test_generator_has_correct_config(): void
    {
        // Act: Get generator config
        $config = $this->generator->getType()->getConfig();

        // Assert: Verify config values
        $this->assertSame('task', $config->type->value);
        $this->assertSame('/app/Tasks/', $config->basePath);
        $this->assertSame('App\\Tasks', $config->baseNamespace);
        $this->assertStringContainsString('task.stub', $config->stubPath);
        $this->assertSame('Task', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    public function test_generator_returns_correct_type(): void
    {
        // Act: Get generator type value
        $typeValue = $this->generator->getType()->value;

        // Assert: Verify type value
        $this->assertSame('task', $typeValue);
    }
}
