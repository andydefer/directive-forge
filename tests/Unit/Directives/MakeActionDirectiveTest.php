<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Records\DirectiveResponseRecord;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeActionDirective;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class MakeActionDirectiveTest extends UnitTestCase
{
    use InteractsWithDirectives;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initDirectiveTesting();
    }

    protected function tearDown(): void
    {
        $this->destroyDirectiveTesting();
        parent::tearDown();
    }

    private function getDirective(): MakeActionDirective
    {
        return new MakeActionDirective($this->interaction);
    }

    private function registerAndRun(string $signature, array $arguments = []): DirectiveResponseRecord
    {
        $directive = $this->getDirective();
        $this->registerDirective($directive);

        return $this->runDirective($signature, $arguments);
    }

    public function test_get_signature_returns_make_action(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the signature
        $signature = $directive->getSignature();

        // Assert: Verify the signature is correct
        $this->assertSame('make-action {name}', $signature);
    }

    public function test_get_description_returns_description(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the description
        $description = $directive->getDescription();

        // Assert: Verify the description is correct
        $this->assertSame('Create a new action class', $description);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        // Arrange: Get the directive instance
        $directive = $this->getDirective();

        // Act: Get the aliases
        $aliases = $directive->getAliases();

        // Assert: Verify the aliases are correct
        $this->assertTrue($aliases->contains('create-action'));
        $this->assertTrue($aliases->contains('make-act'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        // Arrange: No arguments provided

        // Act: Run the directive without name argument
        $response = $this->registerAndRun('make-action');

        // Assert: Verify invalid argument error
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('Not enough arguments (missing: "name")', $response->output);
    }

    public function test_execute_creates_action_file(): void
    {
        // Arrange: Prepare action name
        $actionName = 'user/show';

        // Act: Run the directive to create the action
        $response = $this->registerAndRun('make-action', [$actionName]);

        $expectedPath = $this->directiveTempDir . '/app/Actions/User/ShowAction.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and file content
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ShowAction', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
    }

    public function test_execute_creates_action_in_subdirectory(): void
    {
        // Arrange: Prepare action with subdirectories
        $actionName = 'api/v1/users/show';

        // Act: Run the directive to create the action
        $response = $this->registerAndRun('make-action', [$actionName]);

        $expectedPath = $this->directiveTempDir . '/app/Actions/Api/V1/Users/ShowAction.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify success and correct namespace
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Actions\\Api\\V1\\Users', $content);
        $this->assertStringContainsString('class ShowAction', $content);
    }

    public function test_execute_adds_action_suffix_automatically(): void
    {
        // Arrange: Prepare action name without suffix
        $actionName = 'user/user-show';

        // Act: Run the directive
        $response = $this->registerAndRun('make-action', [$actionName]);

        $expectedPath = $this->directiveTempDir . '/app/Actions/User/UserShowAction.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix was added automatically
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserShowAction', $content);
    }

    public function test_execute_does_not_double_action_suffix(): void
    {
        // Arrange: Prepare action name that already has suffix
        $actionName = 'ShowUserAction';

        // Act: Run the directive
        $response = $this->registerAndRun('make-action', [$actionName]);

        $expectedPath = $this->directiveTempDir . '/app/Actions/ShowUserAction.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify suffix is not duplicated
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ShowUserAction', $content);
        $this->assertStringNotContainsString('ShowUserActionAction', $content);
    }

    public function test_execute_creates_action_with_kebab_case_name(): void
    {
        // Arrange: Prepare kebab-case action name
        $actionName = 'user-profile-data';

        // Act: Run the directive
        $response = $this->registerAndRun('make-action', [$actionName]);

        $expectedPath = $this->directiveTempDir . '/app/Actions/UserProfileDataAction.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify conversion to PascalCase
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserProfileDataAction', $content);
    }

    public function test_execute_creates_action_with_snake_case_name(): void
    {
        // Arrange: Prepare snake_case action name
        $actionName = 'user_profile_data';

        // Act: Run the directive
        $response = $this->registerAndRun('make-action', [$actionName]);

        $expectedPath = $this->directiveTempDir . '/app/Actions/UserProfileDataAction.php';
        $content = file_get_contents($expectedPath);

        // Assert: Verify conversion to PascalCase
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserProfileDataAction', $content);
    }
}
