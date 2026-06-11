<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeActionDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;

final class MakeActionDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DirectiveTestingService($this->app);
    }

    protected function tearDown(): void
    {
        $this->service->destroy();
        parent::tearDown();
    }

    public function test_execute_with_fully_option_creates_action_request_record_and_data(): void
    {
        $actionName = 'user/profile';

        $response = $this->service->run(MakeActionDirective::class, [$actionName, '--fully']);

        $tempDir = $this->service->getContext()->getTempDir();

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Fully created', $response->output);
        $this->assertFileExists($tempDir . '/app/Actions/User/ProfileAction.php');
        $this->assertFileExists($tempDir . '/app/Http/Requests/User/ProfileRequest.php');
        $this->assertFileExists($tempDir . '/app/Records/User/ProfileRecord.php');
        $this->assertFileExists($tempDir . '/app/Data/User/ProfileData.php');
    }

    public function test_execute_with_fully_option_in_subdirectory(): void
    {
        $actionName = 'api/v1/users/show';

        $response = $this->service->run(MakeActionDirective::class, [$actionName, '--fully']);

        $tempDir = $this->service->getContext()->getTempDir();

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Fully created', $response->output);
        $this->assertFileExists($tempDir . '/app/Actions/Api/V1/Users/ShowAction.php');
        $this->assertFileExists($tempDir . '/app/Http/Requests/Api/V1/Users/ShowRequest.php');
        $this->assertFileExists($tempDir . '/app/Records/Api/V1/Users/ShowRecord.php');
        $this->assertFileExists($tempDir . '/app/Data/Api/V1/Users/ShowData.php');
    }

    public function test_execute_with_fully_option_preserves_naming_consistency(): void
    {
        $actionName = 'user/update-profile';

        $response = $this->service->run(MakeActionDirective::class, [$actionName, '--fully']);

        $tempDir = $this->service->getContext()->getTempDir();
        $actionContent = file_get_contents($tempDir . '/app/Actions/User/UpdateProfileAction.php');
        $requestContent = file_get_contents($tempDir . '/app/Http/Requests/User/UpdateProfileRequest.php');
        $recordContent = file_get_contents($tempDir . '/app/Records/User/UpdateProfileRecord.php');
        $dataContent = file_get_contents($tempDir . '/app/Data/User/UpdateProfileData.php');

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('UpdateProfileAction', $actionContent);
        $this->assertStringContainsString('UpdateProfileRequest', $requestContent);
        $this->assertStringContainsString('UpdateProfileRecord', $recordContent);
        $this->assertStringContainsString('UpdateProfileData', $dataContent);
    }

    public function test_execute_without_fully_option_does_not_create_request_record_and_data(): void
    {
        $actionName = 'user/profile';

        $response = $this->service->run(MakeActionDirective::class, [$actionName]);

        $tempDir = $this->service->getContext()->getTempDir();

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertFileExists($tempDir . '/app/Actions/User/ProfileAction.php');
        $this->assertFileDoesNotExist($tempDir . '/app/Http/Requests/User/ProfileRequest.php');
        $this->assertFileDoesNotExist($tempDir . '/app/Records/User/ProfileRecord.php');
        $this->assertFileDoesNotExist($tempDir . '/app/Data/User/ProfileData.php');
    }
}
