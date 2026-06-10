<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Generators;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Configs\EnvDirectiveNamingConfig;
use AndyDefer\Directive\Configs\EnvSignatureValidationConfig;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Services\DirectiveNamingService;
use AndyDefer\Directive\Services\SignatureValidationService;
use AndyDefer\DirectiveForge\Enums\GeneratorType;
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

        // Correction : SignatureValidationService attend une config
        $signatureConfig = new EnvSignatureValidationConfig();
        $this->signatureValidator = new SignatureValidationService($signatureConfig);

        // Correction : DirectiveNamingService attend une config
        $namingConfig = new EnvDirectiveNamingConfig();
        $this->namingService = new DirectiveNamingService($namingConfig);

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
        $pathInfo = $this->createPathInfo('user-list', '', []);

        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

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
        $pathInfo = $this->createPathInfo('hello-directive', 'User\\Domain', ['User', 'Domain']);

        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        $this->assertSame('App\\Directives\\User\\Domain', $array['{{namespace}}']);
        $this->assertSame('hello-directive', $array['{{signature}}']);
        $this->assertSame('hello-directive', $array['{{class}}']);
    }

    public function test_get_replacements_with_class_already_has_suffix(): void
    {
        $pathInfo = $this->createPathInfo('UserListDirective', '', []);

        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        $this->assertSame('App\\Directives', $array['{{namespace}}']);
        $this->assertSame('user-list', $array['{{signature}}']);
        $this->assertSame('UserListDirective', $array['{{class}}']);
    }

    public function test_get_replacements_with_kebab_case_class_name(): void
    {
        $pathInfo = $this->createPathInfo('my-custom-directive', '', []);

        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        $this->assertSame('my-custom-directive', $array['{{class}}']);
        $this->assertSame('my-custom-directive', $array['{{signature}}']);
        $this->assertStringContainsString('my-custom-directive', $array['{{description}}']);
    }

    public function test_get_replacements_with_pascal_case_class_name(): void
    {
        $pathInfo = $this->createPathInfo('MyCustomDirective', '', []);

        $replacements = $this->generator->getReplacements($pathInfo);
        $array = $replacements->toAssociativeArray();

        $this->assertSame('MyCustomDirective', $array['{{class}}']);
        $this->assertSame('my-custom', $array['{{signature}}']);
    }

    public function test_get_replacements_returns_collection_with_correct_keys(): void
    {
        $pathInfo = $this->createPathInfo('test', '', []);

        $replacements = $this->generator->getReplacements($pathInfo);
        $keys = array_keys($replacements->toAssociativeArray());

        $this->assertContains('{{namespace}}', $keys);
        $this->assertContains('{{signature}}', $keys);
        $this->assertContains('{{class}}', $keys);
        $this->assertContains('{{description}}', $keys);
        $this->assertContains('{{date}}', $keys);
        $this->assertCount(5, $keys);
    }

    public function test_get_replacements_contains_date_placeholder(): void
    {
        $pathInfo = $this->createPathInfo('test', '', []);

        $replacements = $this->generator->getReplacements($pathInfo);

        $this->assertReplacementHasKeyMatching($replacements, '{{date}}', '/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/');
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
        $this->assertInstanceOf(AbstractGenerator::class, $this->generator);
    }

    public function test_generator_has_correct_type(): void
    {
        $type = $this->generator->getType();
        $this->assertSame('directive', $type->value);
    }

    public function test_generator_has_correct_config(): void
    {
        $generatorType = GeneratorType::DIRECTIVE;
        $config = $generatorType->getConfig();

        $this->assertSame('directive', $config->type->value);
        $this->assertSame('/app/Directives/', $config->basePath);
        $this->assertSame('App\\Directives', $config->baseNamespace);
        $this->assertStringContainsString('directive.stub', $config->stubPath);
        $this->assertSame('Directive', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
        $this->assertInstanceOf(ReplacementCollection::class, $config->extraReplacements);
    }
}
