<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeActionDirective;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Directives\MakeRequestDirective;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class MakeActionDirectiveTest extends UnitTestCase
{
    use InteractsWithDirectives;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initDirectiveTesting(bootLaravel: false);

        // Créer les répertoires nécessaires
        $this->createDirectories();

        // Enregistrer les directives nécessaires
        $this->registerDirective(new MakeRequestDirective($this->interaction));
        $this->registerDirective(new MakeRecordDirective($this->interaction));
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

    private function runMakeAction(array $arguments = []): \AndyDefer\Directive\Records\DirectiveResponseRecord
    {
        $directive = $this->getDirective();
        $this->registerDirective($directive);

        // Le premier paramètre doit être le FQCN, pas la signature
        return $this->runDirective(MakeActionDirective::class, $arguments);
    }

    private function createDirectories(): void
    {
        $directories = [
            $this->directiveTempDir . '/app/Actions',
            $this->directiveTempDir . '/app/Actions/User',
            $this->directiveTempDir . '/app/Actions/Api/V1/Users',
            $this->directiveTempDir . '/app/Actions/UserProfile',
            $this->directiveTempDir . '/app/Http/Requests',
            $this->directiveTempDir . '/app/Http/Requests/User',
            $this->directiveTempDir . '/app/Http/Requests/Api/V1/Users',
            $this->directiveTempDir . '/app/Http/Requests/UserProfile',
            $this->directiveTempDir . '/app/Records',
            $this->directiveTempDir . '/app/Records/User',
            $this->directiveTempDir . '/app/Records/Api/V1/Users',
            $this->directiveTempDir . '/app/Records/UserProfile',
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    public function test_get_signature_returns_make_action(): void
    {
        // Arrange
        $directive = $this->getDirective();

        // Act
        $signature = $directive->getSignature();

        // Assert
        $this->assertSame('make-action {name} {--fully}', $signature);
    }

    public function test_get_description_returns_description(): void
    {
        // Arrange
        $directive = $this->getDirective();

        // Act
        $description = $directive->getDescription();

        // Assert
        $this->assertSame('Create a new action class (with --fully option to also create Request and Record)', $description);
    }

    public function test_get_aliases_returns_aliases(): void
    {
        // Arrange
        $directive = $this->getDirective();

        // Act
        $aliases = $directive->getAliases();

        // Assert
        $this->assertTrue($aliases->contains('create-action'));
        $this->assertTrue($aliases->contains('make-act'));
        $this->assertSame(2, $aliases->count());
    }

    public function test_execute_returns_error_when_name_missing(): void
    {
        // Arrange: No arguments provided (just the directive name, no 'name' argument)

        // Act
        $response = $this->runMakeAction([]);

        // Assert
        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exitCode);
        $this->assertStringContainsString('name', $response->output);
    }

    public function test_execute_creates_action_file(): void
    {
        // Arrange
        $actionName = 'user/show';

        // Act
        $response = $this->runMakeAction([$actionName]);

        $expectedPath = $this->directiveTempDir . '/app/Actions/User/ShowAction.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ShowAction', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
    }

    public function test_execute_creates_action_in_subdirectory(): void
    {
        // Arrange
        $actionName = 'api/v1/users/show';

        // Act
        $response = $this->runMakeAction([$actionName]);

        $expectedPath = $this->directiveTempDir . '/app/Actions/Api/V1/Users/ShowAction.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('namespace App\\Actions\\Api\\V1\\Users', $content);
        $this->assertStringContainsString('class ShowAction', $content);
    }

    public function test_execute_adds_action_suffix_automatically(): void
    {
        // Arrange
        $actionName = 'user/user-show';

        // Act
        $response = $this->runMakeAction([$actionName]);

        $expectedPath = $this->directiveTempDir . '/app/Actions/User/UserShowAction.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserShowAction', $content);
    }

    public function test_execute_does_not_double_action_suffix(): void
    {
        // Arrange
        $actionName = 'ShowUserAction';

        // Act
        $response = $this->runMakeAction([$actionName]);

        $expectedPath = $this->directiveTempDir . '/app/Actions/ShowUserAction.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class ShowUserAction', $content);
        $this->assertStringNotContainsString('ShowUserActionAction', $content);
    }

    public function test_execute_creates_action_with_kebab_case_name(): void
    {
        // Arrange
        $actionName = 'user-profile-data';

        // Act
        $response = $this->runMakeAction([$actionName]);

        $expectedPath = $this->directiveTempDir . '/app/Actions/UserProfileDataAction.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserProfileDataAction', $content);
    }

    public function test_execute_creates_action_with_snake_case_name(): void
    {
        // Arrange
        $actionName = 'user_profile_data';

        // Act
        $response = $this->runMakeAction([$actionName]);

        $expectedPath = $this->directiveTempDir . '/app/Actions/UserProfileDataAction.php';
        $content = file_get_contents($expectedPath);

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);
        $this->assertStringContainsString('action created successfully!', strtolower($response->output));
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('class UserProfileDataAction', $content);
    }

    // ==================== Tests avec option --fully ====================

    public function test_execute_with_fully_option_creates_action_request_and_record(): void
    {
        // Arrange
        $actionName = 'user/show';

        // Act: L'option --fully se passe comme argument avec -- devant
        $response = $this->runMakeAction([$actionName, '--fully']);

        $actionPath = $this->directiveTempDir . '/app/Actions/User/ShowAction.php';
        $requestPath = $this->directiveTempDir . '/app/Http/Requests/User/ShowRequest.php';
        $recordPath = $this->directiveTempDir . '/app/Records/User/ShowRecord.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($actionPath);
        $this->assertFileExists($requestPath);
        $this->assertFileExists($recordPath);

        $actionContent = file_get_contents($actionPath);
        $requestContent = file_get_contents($requestPath);
        $recordContent = file_get_contents($recordPath);

        $this->assertStringContainsString('class ShowAction', $actionContent);
        $this->assertStringContainsString('class ShowRequest', $requestContent);
        $this->assertStringContainsString('extends AbstractRequest', $requestContent);
        $this->assertStringContainsString('class ShowRecord', $recordContent);
        $this->assertStringContainsString('extends AbstractRecord', $recordContent);

        $this->assertStringContainsString('Fully created', $response->output);
    }

    public function test_execute_with_fully_option_in_subdirectory(): void
    {
        // Arrange
        $actionName = 'api/v1/users/show';

        // Act
        $response = $this->runMakeAction([$actionName, '--fully']);

        $actionPath = $this->directiveTempDir . '/app/Actions/Api/V1/Users/ShowAction.php';
        $requestPath = $this->directiveTempDir . '/app/Http/Requests/Api/V1/Users/ShowRequest.php';
        $recordPath = $this->directiveTempDir . '/app/Records/Api/V1/Users/ShowRecord.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($actionPath);
        $this->assertFileExists($requestPath);
        $this->assertFileExists($recordPath);

        $actionContent = file_get_contents($actionPath);
        $requestContent = file_get_contents($requestPath);
        $recordContent = file_get_contents($recordPath);

        $this->assertStringContainsString('namespace App\\Actions\\Api\\V1\\Users', $actionContent);
        $this->assertStringContainsString('class ShowAction', $actionContent);
        $this->assertStringContainsString('namespace App\\Http\\Requests\\Api\\V1\\Users', $requestContent);
        $this->assertStringContainsString('class ShowRequest', $requestContent);
        $this->assertStringContainsString('namespace App\\Records\\Api\\V1\\Users', $recordContent);
        $this->assertStringContainsString('class ShowRecord', $recordContent);
    }

    public function test_execute_with_fully_option_preserves_naming_consistency(): void
    {
        // Arrange
        $actionName = 'user-profile/update-email';

        // Act
        $response = $this->runMakeAction([$actionName, '--fully']);

        $actionPath = $this->directiveTempDir . '/app/Actions/UserProfile/UpdateEmailAction.php';
        $requestPath = $this->directiveTempDir . '/app/Http/Requests/UserProfile/UpdateEmailRequest.php';
        $recordPath = $this->directiveTempDir . '/app/Records/UserProfile/UpdateEmailRecord.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($actionPath);
        $this->assertFileExists($requestPath);
        $this->assertFileExists($recordPath);

        $this->assertStringContainsString('UpdateEmailAction', file_get_contents($actionPath));
        $this->assertStringContainsString('UpdateEmailRequest', file_get_contents($requestPath));
        $this->assertStringContainsString('UpdateEmailRecord', file_get_contents($recordPath));
    }

    public function test_execute_with_fully_option_does_not_create_duplicate_files_on_second_run(): void
    {
        // Arrange
        $actionName = 'test/duplicate';

        // Act: First run
        $firstResponse = $this->runMakeAction([$actionName, '--fully']);

        // Assert: First creation succeeded
        $this->assertSame(ExitCode::SUCCESS, $firstResponse->exitCode);

        // Act: Second run
        $secondResponse = $this->runMakeAction([$actionName, '--fully']);

        // Assert: Second run fails because files already exist
        $this->assertSame(ExitCode::FAILURE, $secondResponse->exitCode);
        $this->assertStringContainsString('File already exists', $secondResponse->output);
    }

    public function test_execute_without_fully_option_does_not_create_request_and_record(): void
    {
        // Arrange
        $actionName = 'user/show';

        // Act: Sans l'option --fully
        $response = $this->runMakeAction([$actionName]);

        $requestPath = $this->directiveTempDir . '/app/Http/Requests/User/ShowRequest.php';
        $recordPath = $this->directiveTempDir . '/app/Records/User/ShowRecord.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($this->directiveTempDir . '/app/Actions/User/ShowAction.php');
        $this->assertFileDoesNotExist($requestPath);
        $this->assertFileDoesNotExist($recordPath);
    }
}
