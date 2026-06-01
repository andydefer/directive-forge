<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Services\DirectiveNamingService;
use AndyDefer\Directive\Services\SignatureValidationService;
use AndyDefer\DirectiveForge\Generators\AbstractGenerator;
use AndyDefer\DirectiveForge\Generators\DirectiveGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class DirectiveGeneratorTest extends UnitTestCase
{
    private DirectiveGenerator $generator;

    private DirectiveInteractionService&MockObject $interaction;

    private SignatureValidationService $signatureValidator;

    private DirectiveNamingService $namingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interaction = $this->createMock(DirectiveInteractionService::class);
        $this->signatureValidator = new SignatureValidationService;
        $this->namingService = new DirectiveNamingService;

        $this->generator = new DirectiveGenerator(
            $this->interaction,
            $this->signatureValidator,
            $this->namingService
        );
    }

    private function createPathInfo(string $className, string $subPath = '', array $segments = []): PathInfo
    {
        $segmentsCollection = new ScalarTypedCollection;
        $segmentsCollection->add(...$segments);

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

    private function assertReplacementHasKeyMatching(ReplacementCollection $replacements, string $key, string $pattern): void
    {
        $array = $replacements->toAssociativeArray();
        $this->assertArrayHasKey($key, $array);
        $this->assertMatchesRegularExpression($pattern, $array[$key]);
    }

    public function test_get_replacements_with_simple_name(): void
    {
        // Arrange: Create path info with simple name
        $pathInfo = $this->createPathInfo('user-list', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify replacement structure
        $this->assertArrayHasKey('{{namespace}}', $array);
        $this->assertArrayHasKey('{{signature}}', $array);
        $this->assertArrayHasKey('{{class}}', $array);
        $this->assertArrayHasKey('{{description}}', $array);
        $this->assertArrayHasKey('{{date}}', $array);
        $this->assertSame('App\\Directives', $array['{{namespace}}']);
        $this->assertSame('user-list', $array['{{signature}}']);
        $this->assertSame('user-list', $array['{{class}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        // Arrange: Create path info with subdirectory
        $pathInfo = $this->createPathInfo('hello-directive', 'User\\Domain', ['User', 'Domain']);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify namespace includes subdirectory
        $this->assertSame('App\\Directives\\User\\Domain', $array['{{namespace}}']);
        $this->assertSame('hello-directive', $array['{{signature}}']);
        $this->assertSame('hello-directive', $array['{{class}}']);
    }

    public function test_get_replacements_with_class_already_has_suffix(): void
    {
        // Arrange: Create path info with class that already has Directive suffix
        $pathInfo = $this->createPathInfo('UserListDirective', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify signature extraction removes suffix
        $this->assertSame('App\\Directives', $array['{{namespace}}']);
        $this->assertSame('user-list', $array['{{signature}}']);
        $this->assertSame('UserListDirective', $array['{{class}}']);
    }

    public function test_get_replacements_with_kebab_case_class_name(): void
    {
        // Arrange: Create path info with kebab-case class
        $pathInfo = $this->createPathInfo('my-custom-directive', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify signature extraction
        $this->assertSame('my-custom-directive', $array['{{class}}']);
        $this->assertSame('my-custom-directive', $array['{{signature}}']);
        $this->assertStringContainsString('my-custom-directive', $array['{{description}}']);
    }

    public function test_get_replacements_with_pascal_case_class_name(): void
    {
        // Arrange: Create path info with PascalCase class
        $pathInfo = $this->createPathInfo('MyCustomDirective', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        // Assert: Verify signature extraction converts to kebab-case
        $this->assertSame('MyCustomDirective', $array['{{class}}']);
        $this->assertSame('my-custom', $array['{{signature}}']);
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
        $this->assertContains('{{signature}}', $keys);
        $this->assertContains('{{class}}', $keys);
        $this->assertContains('{{description}}', $keys);
        $this->assertContains('{{date}}', $keys);
        $this->assertCount(5, $keys);
    }

    public function test_get_replacements_contains_date_placeholder(): void
    {
        // Arrange: Create path info
        $pathInfo = $this->createPathInfo('test', '', []);

        // Act: Get replacements
        $replacements = $this->generator->getReplacements($pathInfo);

        // Assert: Verify date format
        $this->assertReplacementHasKeyMatching($replacements, '{{date}}', '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/');
    }

    public function test_validate_valid_name(): void
    {
        // Act: Validate valid directive name
        $result = $this->generator->validate('user-list');

        // Assert: Verify valid
        $this->assertTrue($result);
    }

    public function test_validate_valid_name_with_numbers(): void
    {
        // Act: Validate name with numbers
        $result = $this->generator->validate('user-v2-list');

        // Assert: Verify valid
        $this->assertTrue($result);
    }

    public function test_validate_invalid_name_with_at_symbol(): void
    {
        // Assert: Expect error message
        $this->interaction->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid directive name'));

        // Act: Validate invalid name
        $result = $this->generator->validate('user@list');

        // Assert: Verify invalid
        $this->assertFalse($result);
    }

    public function test_validate_invalid_name_with_underscore(): void
    {
        // Assert: Expect error message
        $this->interaction->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid directive name'));

        // Act: Validate invalid name
        $result = $this->generator->validate('user_list');

        // Assert: Verify invalid
        $this->assertFalse($result);
    }

    public function test_validate_invalid_name_starts_with_number(): void
    {
        // Assert: Expect error message
        $this->interaction->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid directive name'));

        // Act: Validate invalid name
        $result = $this->generator->validate('123-user');

        // Assert: Verify invalid
        $this->assertFalse($result);
    }

    public function test_validate_invalid_name_ends_with_hyphen(): void
    {
        // Assert: Expect error message
        $this->interaction->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid directive name'));

        // Act: Validate invalid name
        $result = $this->generator->validate('user-');

        // Assert: Verify invalid
        $this->assertFalse($result);
    }

    public function test_validate_invalid_name_with_double_hyphen(): void
    {
        // Assert: Expect error message
        $this->interaction->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid directive name'));

        // Act: Validate invalid name
        $result = $this->generator->validate('user--list');

        // Assert: Verify invalid
        $this->assertFalse($result);
    }

    public function test_validate_with_subdirectory_path(): void
    {
        // Act: Validate name with subdirectory
        $result = $this->generator->validate('user/domain/hello-directive');

        // Assert: Verify valid
        $this->assertTrue($result);
    }

    public function test_generator_extends_abstract_generator(): void
    {
        // Assert: Verify inheritance
        $this->assertInstanceOf(AbstractGenerator::class, $this->generator);
    }

    public function test_generator_has_correct_type(): void
    {
        // Act: Get generator type
        $type = $this->generator->getType();

        // Assert: Verify type
        $this->assertSame('directive', $type->value);
    }

    public function test_generator_has_correct_config(): void
    {
        // Act: Get generator config
        $config = $this->generator->getType()->getConfig();

        // Assert: Verify config values
        $this->assertSame('directive', $config->type->value);
        $this->assertSame('/app/Directives/', $config->basePath);
        $this->assertSame('App\\Directives', $config->baseNamespace);
        $this->assertStringContainsString('directive.stub', $config->stubPath);
        $this->assertSame('Directive', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);

        $extraReplacements = $config->extraReplacements->toAssociativeArray();
        $this->assertArrayHasKey('{{date}}', $extraReplacements);
    }
}
