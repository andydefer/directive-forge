<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\Directives\MakeActionDirective;
use AndyDefer\DirectiveForge\Directives\MakeDataDirective;
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
        $this->registerDirective(new MakeDataDirective($this->interaction));
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
            $this->directiveTempDir . '/app/Data',
            $this->directiveTempDir . '/app/Data/User',
            $this->directiveTempDir . '/app/Data/Api/V1/Users',
            $this->directiveTempDir . '/app/Data/UserProfile',
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    // ... autres tests (get_signature, get_description, get_aliases, etc.) ...

    public function test_execute_with_fully_option_creates_action_request_record_and_data(): void
    {
        // Arrange
        $actionName = 'user/show';

        // Act
        $response = $this->runMakeAction([$actionName, '--fully']);

        $actionPath = $this->directiveTempDir . '/app/Actions/User/ShowAction.php';
        $requestPath = $this->directiveTempDir . '/app/Http/Requests/User/ShowRequest.php';
        $recordPath = $this->directiveTempDir . '/app/Records/User/ShowRecord.php';
        $dataPath = $this->directiveTempDir . '/app/Data/User/ShowData.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($actionPath);
        $this->assertFileExists($requestPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($dataPath);

        $actionContent = file_get_contents($actionPath);
        $requestContent = file_get_contents($requestPath);
        $recordContent = file_get_contents($recordPath);
        $dataContent = file_get_contents($dataPath);

        $this->assertStringContainsString('class ShowAction', $actionContent);
        $this->assertStringContainsString('class ShowRequest', $requestContent);
        $this->assertStringContainsString('extends AbstractRequest', $requestContent);
        $this->assertStringContainsString('class ShowRecord', $recordContent);
        $this->assertStringContainsString('extends AbstractRecord', $recordContent);
        $this->assertStringContainsString('class ShowData', $dataContent);
        $this->assertStringContainsString('extends AbstractData', $dataContent);

        $this->assertStringContainsString('Fully created', $response->output);
        $this->assertStringContainsString('Action:', $response->output);
        $this->assertStringContainsString('Request:', $response->output);
        $this->assertStringContainsString('Record:', $response->output);
        $this->assertStringContainsString('Data:', $response->output);
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
        $dataPath = $this->directiveTempDir . '/app/Data/Api/V1/Users/ShowData.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($actionPath);
        $this->assertFileExists($requestPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($dataPath);

        $actionContent = file_get_contents($actionPath);
        $requestContent = file_get_contents($requestPath);
        $recordContent = file_get_contents($recordPath);
        $dataContent = file_get_contents($dataPath);

        $this->assertStringContainsString('namespace App\\Actions\\Api\\V1\\Users', $actionContent);
        $this->assertStringContainsString('class ShowAction', $actionContent);
        $this->assertStringContainsString('namespace App\\Http\\Requests\\Api\\V1\\Users', $requestContent);
        $this->assertStringContainsString('class ShowRequest', $requestContent);
        $this->assertStringContainsString('namespace App\\Records\\Api\\V1\\Users', $recordContent);
        $this->assertStringContainsString('class ShowRecord', $recordContent);
        $this->assertStringContainsString('namespace App\\Data\\Api\\V1\\Users', $dataContent);
        $this->assertStringContainsString('class ShowData', $dataContent);
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
        $dataPath = $this->directiveTempDir . '/app/Data/UserProfile/UpdateEmailData.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($actionPath);
        $this->assertFileExists($requestPath);
        $this->assertFileExists($recordPath);
        $this->assertFileExists($dataPath);

        $this->assertStringContainsString('UpdateEmailAction', file_get_contents($actionPath));
        $this->assertStringContainsString('UpdateEmailRequest', file_get_contents($requestPath));
        $this->assertStringContainsString('UpdateEmailRecord', file_get_contents($recordPath));
        $this->assertStringContainsString('UpdateEmailData', file_get_contents($dataPath));
    }

    public function test_execute_without_fully_option_does_not_create_request_record_and_data(): void
    {
        // Arrange
        $actionName = 'user/show';

        // Act
        $response = $this->runMakeAction([$actionName]);

        $requestPath = $this->directiveTempDir . '/app/Http/Requests/User/ShowRequest.php';
        $recordPath = $this->directiveTempDir . '/app/Records/User/ShowRecord.php';
        $dataPath = $this->directiveTempDir . '/app/Data/User/ShowData.php';

        // Assert
        $this->assertSame(ExitCode::SUCCESS, $response->exitCode);

        $this->assertFileExists($this->directiveTempDir . '/app/Actions/User/ShowAction.php');
        $this->assertFileDoesNotExist($requestPath);
        $this->assertFileDoesNotExist($recordPath);
        $this->assertFileDoesNotExist($dataPath);
    }
}
