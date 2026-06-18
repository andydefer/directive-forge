<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\ValueObjects;

use AndyDefer\Directive\Collections\ReplacementCollection;
use AndyDefer\Directive\Records\ReplacementRecord;
use AndyDefer\DirectiveForge\ValueObjects\StubVO;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use PHPUnit\Framework\TestCase;

final class StubVOTest extends TestCase
{
    // ==================== TESTS CONSTRUCTEUR ====================

    public function test_constructor_creates_valid_instance(): void
    {
        $stub = new StubVO('Hello {{name}}!');

        $this->assertInstanceOf(StubVO::class, $stub);
        $this->assertInstanceOf(AbstractValueObject::class, $stub);
        $this->assertSame('Hello {{name}}!', $stub->getValue());
    }

    public function test_constructor_with_empty_content(): void
    {
        $stub = new StubVO('');

        $this->assertInstanceOf(StubVO::class, $stub);
        $this->assertSame('', $stub->getValue());
    }

    public function test_constructor_with_complex_content(): void
    {
        $content = <<<'STUB'
<?php

namespace {{namespace}};

final class {{class}}
{
    {{content}}
}
STUB;

        $stub = new StubVO($content);

        $this->assertSame($content, $stub->getValue());
    }

    // ==================== TESTS REPLACE ====================

    public function test_replace_single(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));

        $this->assertSame('Hello John!', $stub->getValue());
    }

    public function test_replace_multiple_times(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));
        $stub->replace(new ReplacementRecord('name', 'Jane'));

        $this->assertSame('Hello Jane!', $stub->getValue());
    }

    public function test_replace_with_spaces_placeholder(): void
    {
        $stub = new StubVO('Hello {{ name }}!');
        $stub->replace(new ReplacementRecord('name', 'John'));

        $this->assertSame('Hello John!', $stub->getValue());
    }

    public function test_replace_with_mixed_placeholder_format(): void
    {
        $stub = new StubVO('Hello {{name}}, welcome {{ site }}!');
        $stub->replace(new ReplacementRecord('name', 'John'));
        $stub->replace(new ReplacementRecord('site', 'Example'));

        $this->assertSame('Hello John, welcome Example!', $stub->getValue());
    }

    public function test_replace_nonexistent_placeholder(): void
    {
        $stub = new StubVO('Hello World!');
        $stub->replace(new ReplacementRecord('name', 'John'));

        $this->assertSame('Hello World!', $stub->getValue());
    }

    // ==================== TESTS REPLACE_MANY ====================

    public function test_replace_many(): void
    {
        $stub = new StubVO('Hello {{name}}, welcome to {{site}}!');

        $replacements = new ReplacementCollection;
        $replacements->add(new ReplacementRecord('name', 'John'));
        $replacements->add(new ReplacementRecord('site', 'Example'));

        $stub->replaceMany($replacements);

        $this->assertSame('Hello John, welcome to Example!', $stub->getValue());
    }

    public function test_replace_many_overwrites_existing(): void
    {
        $stub = new StubVO('Hello {{name}}!');

        $replacements1 = new ReplacementCollection;
        $replacements1->add(new ReplacementRecord('name', 'John'));

        $replacements2 = new ReplacementCollection;
        $replacements2->add(new ReplacementRecord('name', 'Jane'));

        $stub->replaceMany($replacements1);
        $stub->replaceMany($replacements2);

        $this->assertSame('Hello Jane!', $stub->getValue());
    }

    public function test_replace_many_with_empty_collection(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replaceMany(new ReplacementCollection);

        $this->assertSame('Hello {{name}}!', $stub->getValue());
    }

    public function test_replace_many_chained(): void
    {
        $stub = new StubVO('{{a}} {{b}} {{c}}');

        $replacements = new ReplacementCollection;
        $replacements->add(new ReplacementRecord('a', '1'));
        $replacements->add(new ReplacementRecord('b', '2'));
        $replacements->add(new ReplacementRecord('c', '3'));

        $stub->replaceMany($replacements);

        $this->assertSame('1 2 3', $stub->getValue());
    }

    // ==================== TESTS REMOVE ====================

    public function test_remove_replacement(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));
        $stub->remove('name');

        $this->assertSame('Hello {{name}}!', $stub->getValue());
        $this->assertFalse($stub->has('name'));
    }

    public function test_remove_nonexistent_replacement(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));
        $stub->remove('unknown');

        $this->assertSame('Hello John!', $stub->getValue());
        $this->assertTrue($stub->has('name'));
    }

    public function test_remove_multiple(): void
    {
        $stub = new StubVO('{{a}} {{b}} {{c}}');
        $stub->replace(new ReplacementRecord('a', '1'));
        $stub->replace(new ReplacementRecord('b', '2'));
        $stub->replace(new ReplacementRecord('c', '3'));
        $stub->remove('b');

        $this->assertSame('1 {{b}} 3', $stub->getValue());
        $this->assertFalse($stub->has('b'));
        $this->assertTrue($stub->has('a'));
        $this->assertTrue($stub->has('c'));
    }

    // ==================== TESTS HAS ====================

    public function test_has_replacement(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));

        $this->assertTrue($stub->has('name'));
        $this->assertFalse($stub->has('unknown'));
    }

    public function test_has_after_remove(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));
        $stub->remove('name');

        $this->assertFalse($stub->has('name'));
    }

    // ==================== TESTS GET ====================

    public function test_get_replacement(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));

        $this->assertSame('John', $stub->get('name'));
        $this->assertNull($stub->get('unknown'));
    }

    public function test_get_after_overwrite(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));
        $stub->replace(new ReplacementRecord('name', 'Jane'));

        $this->assertSame('Jane', $stub->get('name'));
    }

    // ==================== TESTS GET_REPLACEMENTS ====================

    public function test_get_replacements(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));
        $stub->replace(new ReplacementRecord('site', 'Example'));

        $replacements = $stub->getReplacements();

        $this->assertInstanceOf(ReplacementCollection::class, $replacements);
        $this->assertCount(2, $replacements);
        $this->assertTrue($replacements->hasPlaceholder('name'));
        $this->assertTrue($replacements->hasPlaceholder('site'));
    }

    public function test_get_replacements_empty(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $replacements = $stub->getReplacements();

        $this->assertInstanceOf(ReplacementCollection::class, $replacements);
        $this->assertCount(0, $replacements);
    }

    // ==================== TESTS FIND_PLACEHOLDERS ====================

    public function test_find_placeholders(): void
    {
        $stub = new StubVO('Hello {{name}}, welcome to {{site}}!');
        $placeholders = $stub->findPlaceholders();

        $this->assertInstanceOf(StringTypedCollection::class, $placeholders);
        $this->assertCount(2, $placeholders);
        $this->assertTrue($placeholders->contains('name'));
        $this->assertTrue($placeholders->contains('site'));
    }

    public function test_find_placeholders_with_spaces(): void
    {
        $stub = new StubVO('Hello {{ name }}, welcome to {{ site }}!');
        $placeholders = $stub->findPlaceholders();

        $this->assertCount(2, $placeholders);
        $this->assertTrue($placeholders->contains('name'));
        $this->assertTrue($placeholders->contains('site'));
    }

    public function test_find_placeholders_mixed_format(): void
    {
        $stub = new StubVO('{{a}} {{ b }} {{c}} {{ d }}');
        $placeholders = $stub->findPlaceholders();

        $this->assertCount(4, $placeholders);
        $this->assertTrue($placeholders->contains('a'));
        $this->assertTrue($placeholders->contains('b'));
        $this->assertTrue($placeholders->contains('c'));
        $this->assertTrue($placeholders->contains('d'));
    }

    public function test_find_placeholders_no_placeholders(): void
    {
        $stub = new StubVO('Hello World!');
        $placeholders = $stub->findPlaceholders();

        $this->assertCount(0, $placeholders);
    }

    public function test_find_placeholders_empty_content(): void
    {
        $stub = new StubVO('');
        $placeholders = $stub->findPlaceholders();

        $this->assertCount(0, $placeholders);
    }

    // ==================== TESTS HAS_PLACEHOLDER ====================

    public function test_has_placeholder(): void
    {
        $stub = new StubVO('Hello {{name}}!');

        $this->assertTrue($stub->hasPlaceholder('name'));
        $this->assertFalse($stub->hasPlaceholder('unknown'));
    }

    public function test_has_placeholder_with_spaces(): void
    {
        $stub = new StubVO('Hello {{ name }}!');

        $this->assertTrue($stub->hasPlaceholder('name'));
        $this->assertFalse($stub->hasPlaceholder('unknown'));
    }

    public function test_has_placeholder_mixed_format(): void
    {
        $stub = new StubVO('Hello {{name}}, welcome {{ site }}!');

        $this->assertTrue($stub->hasPlaceholder('name'));
        $this->assertTrue($stub->hasPlaceholder('site'));
        $this->assertFalse($stub->hasPlaceholder('unknown'));
    }

    // ==================== TESTS VALEUR ====================

    public function test_get_value_returns_rendered_content(): void
    {
        $stub = new StubVO('Hello {{name}}!');
        $stub->replace(new ReplacementRecord('name', 'John'));

        $this->assertSame('Hello John!', $stub->getValue());
    }

    public function test_get_value_without_replacements(): void
    {
        $stub = new StubVO('Hello {{name}}!');

        $this->assertSame('Hello {{name}}!', $stub->getValue());
    }

    public function test_get_value_with_complex_template(): void
    {
        $content = <<<'STUB'
<?php

namespace {{namespace}};

final class {{class}}
{
    {{content}}
}
STUB;

        $stub = new StubVO($content);
        $stub->replace(new ReplacementRecord('namespace', 'App\\Data'));
        $stub->replace(new ReplacementRecord('class', 'UserProfile'));
        $stub->replace(new ReplacementRecord('content', '// User logic'));

        $expected = <<<'EXPECTED'
<?php

namespace App\Data;

final class UserProfile
{
    // User logic
}
EXPECTED;

        $this->assertSame($expected, $stub->getValue());
    }

    // ==================== TESTS ÉGALITÉ ====================

    public function test_equals_same_content(): void
    {
        $stub1 = new StubVO('Hello {{name}}!');
        $stub2 = new StubVO('Hello {{name}}!');

        $this->assertTrue($stub1->equals($stub2));
    }

    public function test_equals_different_content(): void
    {
        $stub1 = new StubVO('Hello {{name}}!');
        $stub2 = new StubVO('Goodbye {{name}}!');

        $this->assertFalse($stub1->equals($stub2));
    }

    public function test_equals_after_replacements(): void
    {

        $stub1 = new StubVO('Hello {{name}}!');
        $stub1->replace(new ReplacementRecord('name', 'John'));

        $stub2 = new StubVO('Hello John!');

        $this->assertTrue($stub1->equals($stub2));
    }

    public function test_equals_after_multiple_replacements(): void
    {
        $stub1 = new StubVO('Hello {{name}}, welcome to {{site}}!');
        $stub1->replace(new ReplacementRecord('name', 'John'));
        $stub1->replace(new ReplacementRecord('site', 'Example'));

        $stub2 = new StubVO('Hello John, welcome to Example!');

        $this->assertTrue($stub1->equals($stub2));
    }

    public function test_equals_different_values(): void
    {
        $stub1 = new StubVO('Hello {{name}}!');
        $stub1->replace(new ReplacementRecord('name', 'John'));

        $stub2 = new StubVO('Hello {{name}}!');
        $stub2->replace(new ReplacementRecord('name', 'Jane'));

        $this->assertFalse($stub1->equals($stub2));
    }

    // ==================== TESTS SCÉNARIOS RÉELS ====================

    public function test_real_scenario_php_class_generation(): void
    {
        $stub = new StubVO(<<<'STUB'
<?php

declare(strict_types=1);

namespace {{namespace}};

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class {{class}} extends AbstractRecord
{
    public function __construct(
        {{properties}}
    ) {}
}
STUB
        );

        $stub->replace(new ReplacementRecord('namespace', 'App\\Data'));
        $stub->replace(new ReplacementRecord('class', 'UserProfile'));
        $stub->replace(new ReplacementRecord('properties', 'public readonly string $name, public readonly string $email'));

        $expected = <<<'EXPECTED'
<?php

declare(strict_types=1);

namespace App\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class UserProfile extends AbstractRecord
{
    public function __construct(
        public readonly string $name, public readonly string $email
    ) {}
}
EXPECTED;

        $this->assertSame($expected, $stub->getValue());
    }

    public function test_real_scenario_config_file(): void
    {
        $stub = new StubVO(<<<'STUB'
return [
    'app_name' => '{{app_name}}',
    'environment' => '{{environment}}',
    'debug' => {{debug}},
];
STUB
        );

        $stub->replace(new ReplacementRecord('app_name', 'MyApp'));
        $stub->replace(new ReplacementRecord('environment', 'production'));
        $stub->replace(new ReplacementRecord('debug', 'false'));

        $expected = <<<'EXPECTED'
return [
    'app_name' => 'MyApp',
    'environment' => 'production',
    'debug' => false,
];
EXPECTED;

        $this->assertSame($expected, $stub->getValue());
    }
}
