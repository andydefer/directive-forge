<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Services\DirectiveNamingService;
use AndyDefer\Directive\Services\SignatureValidationService;
use AndyDefer\DirectiveForge\Generators\DirectiveGenerator;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;
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
        $this->signatureValidator = new SignatureValidationService();
        $this->namingService = new DirectiveNamingService();

        $this->generator = new DirectiveGenerator(
            $this->interaction,
            $this->signatureValidator,
            $this->namingService
        );
    }

    public function test_get_replacements_with_simple_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'user-list',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertArrayHasKey('{{namespace}}', $replacements);
        $this->assertArrayHasKey('{{signature}}', $replacements);
        $this->assertArrayHasKey('{{class}}', $replacements);
        $this->assertArrayHasKey('{{description}}', $replacements);
        $this->assertArrayHasKey('{{date}}', $replacements);

        $this->assertSame('App\\Directives', $replacements['{{namespace}}']);
        $this->assertSame('user-list', $replacements['{{signature}}']);
        $this->assertSame('user-list', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_subdirectory(): void
    {
        $pathInfo = new PathInfo(
            className: 'hello-directive',
            subPath: 'User\\Domain',
            segments: ['User', 'Domain']
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Directives\\User\\Domain', $replacements['{{namespace}}']);
        $this->assertSame('hello-directive', $replacements['{{signature}}']);
        $this->assertSame('hello-directive', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_class_already_has_suffix(): void
    {
        $pathInfo = new PathInfo(
            className: 'UserListDirective',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('App\\Directives', $replacements['{{namespace}}']);
        // extractSignature enlève le suffixe 'Directive' -> UserList -> user-list
        $this->assertSame('user-list', $replacements['{{signature}}']);
        $this->assertSame('UserListDirective', $replacements['{{class}}']);
    }

    public function test_get_replacements_with_kebab_case_class_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'my-custom-directive',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('my-custom-directive', $replacements['{{class}}']);
        // extractSignature cherche le suffixe 'Directive' (avec majuscule)
        // 'my-custom-directive' ne se termine pas par 'Directive' donc rien n'est enlevé
        $this->assertSame('my-custom-directive', $replacements['{{signature}}']);
        $this->assertStringContainsString('my-custom-directive', $replacements['{{description}}']);
    }

    public function test_get_replacements_with_pascal_case_class_name(): void
    {
        $pathInfo = new PathInfo(
            className: 'MyCustomDirective',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertSame('MyCustomDirective', $replacements['{{class}}']);
        // extractSignature enlève le suffixe 'Directive' -> MyCustom -> my-custom
        $this->assertSame('my-custom', $replacements['{{signature}}']);
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
        $this->assertContains('{{signature}}', $keys);
        $this->assertContains('{{class}}', $keys);
        $this->assertContains('{{description}}', $keys);
        $this->assertContains('{{date}}', $keys);
        $this->assertCount(5, $keys);
    }

    public function test_get_replacements_contains_date_placeholder(): void
    {
        $pathInfo = new PathInfo(
            className: 'test',
            subPath: '',
            segments: []
        );

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertArrayHasKey('{{date}}', $replacements);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $replacements['{{date}}']);
    }

    public function test_validate_valid_name(): void
    {
        $result = $this->generator->validate('user-list');
        $this->assertTrue($result);
    }

    public function test_validate_valid_name_with_numbers(): void
    {
        $result = $this->generator->validate('user-v2-list');
        $this->assertTrue($result);
    }

    public function test_validate_invalid_name_with_at_symbol(): void
    {
        $this->interaction->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid directive name'));

        $result = $this->generator->validate('user@list');
        $this->assertFalse($result);
    }

    public function test_validate_invalid_name_with_underscore(): void
    {
        $this->interaction->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid directive name'));

        $result = $this->generator->validate('user_list');
        $this->assertFalse($result);
    }

    public function test_validate_invalid_name_starts_with_number(): void
    {
        $this->interaction->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid directive name'));

        $result = $this->generator->validate('123-user');
        $this->assertFalse($result);
    }

    public function test_validate_invalid_name_ends_with_hyphen(): void
    {
        $this->interaction->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid directive name'));

        $result = $this->generator->validate('user-');
        $this->assertFalse($result);
    }

    public function test_validate_invalid_name_with_double_hyphen(): void
    {
        $this->interaction->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Invalid directive name'));

        $result = $this->generator->validate('user--list');
        $this->assertFalse($result);
    }

    public function test_validate_with_subdirectory_path(): void
    {
        $result = $this->generator->validate('user/domain/hello-directive');
        $this->assertTrue($result);
    }

    public function test_generator_extends_abstract_generator(): void
    {
        $this->assertInstanceOf(\AndyDefer\DirectiveForge\Generators\AbstractGenerator::class, $this->generator);
    }

    public function test_generator_has_correct_type(): void
    {
        $type = $this->generator->getType();

        $this->assertSame('directive', $type->value);
    }

    public function test_generator_has_correct_config(): void
    {
        $config = $this->generator->getType()->getConfig();

        $this->assertSame('directive', $config->type->value);
        $this->assertSame('/app/Directives/', $config->basePath);
        $this->assertSame('App\\Directives', $config->baseNamespace);
        $this->assertStringContainsString('directive.stub', $config->stubPath);
        $this->assertSame('Directive', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
        $this->assertArrayHasKey('{{date}}', $config->extraReplacements);
    }
}
