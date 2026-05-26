<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Testing\InteractsWithDirectives;
use AndyDefer\DirectiveForge\DirectiveForgeServiceProvider;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;

final class DirectiveForgeIntegrationTest extends IntegrationTestCase
{
    use InteractsWithDirectives;

    protected function getPackageProviders($app): array
    {
        return [
            DirectiveForgeServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->initDirectiveTesting();
        $this->createDirectories();
    }

    protected function tearDown(): void
    {
        $this->destroyDirectiveTesting();
        parent::tearDown();
    }

    private function createDirectories(): void
    {
        $directories = [
            $this->directiveTempDir . '/app/Directives',
            $this->directiveTempDir . '/app/Actions',
            $this->directiveTempDir . '/app/Tasks',
            $this->directiveTempDir . '/app/Repositories',
            $this->directiveTempDir . '/app/Records',
            $this->directiveTempDir . '/app/Collections',
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    public function test_make_directive_with_simple_name(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);

        $response = $this->runDirective('make-directive', ['user-list']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Directives/UserListDirective.php';
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('directive created successfully!', strtolower($response->getOutput()));
    }

    public function test_make_directive_with_subdirectory(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);

        $response = $this->runDirective('make-directive', ['user/domain/hello-directive']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Directives/User/Domain/HelloDirective.php';
        $this->assertFileExists($fullPath);
    }

    public function test_make_action_with_api_type(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['user/show', '--type=api']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Actions/User/ShowAction.php';
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('action created successfully!', strtolower($response->getOutput()));
    }

    public function test_make_action_with_web_type(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['admin/dashboard', '--type=web']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Actions/Admin/DashboardAction.php';
        $this->assertFileExists($fullPath);
    }

    public function test_make_task(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTaskDirective::class);

        $response = $this->runDirective('make-task', ['send-welcome-email']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Tasks/SendWelcomeEmailTask.php';
        $this->assertFileExists($fullPath);
    }

    public function test_make_task_with_subdirectory(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTaskDirective::class);

        $response = $this->runDirective('make-task', ['user/send-welcome-email']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Tasks/User/SendWelcomeEmailTask.php';
        $this->assertFileExists($fullPath);
    }

    public function test_make_repository(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective::class);

        $response = $this->runDirective('make-repository', ['user']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Repositories/UserRepository.php';
        $this->assertFileExists($fullPath);
    }

    public function test_make_record(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRecordDirective::class);

        $response = $this->runDirective('make-record', ['user-data']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Records/UserDataRecord.php';
        $this->assertFileExists($fullPath);
    }

    public function test_make_typed_collection(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective::class);

        $response = $this->runDirective('make-typed-collection', [
            'user-collection',
            '--item-type=UserRecord'
        ]);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';
        $this->assertFileExists($fullPath);
        $this->assertStringContainsString('UserRecord', file_get_contents($fullPath));
    }

    public function test_make_typed_collection_requires_item_type(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective::class);

        $response = $this->runDirective('make-typed-collection', ['user-collection']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->getExitCode());
        $this->assertStringContainsString('Item type is required', $response->getOutput());

        $fullPath = $this->directiveTempDir . '/app/Collections/UserCollection.php';
        $this->assertFileDoesNotExist($fullPath);
    }

    public function test_prevent_duplicate_file_creation(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);

        $firstResponse = $this->runDirective('make-directive', ['user-list']);
        $firstResponse->assertSuccess();

        $secondResponse = $this->runDirective('make-directive', ['user-list']);

        $this->assertSame(ExitCode::FAILURE, $secondResponse->getExitCode());
        $this->assertStringContainsString('File already exists', $secondResponse->getOutput());
    }

    public function test_invalid_type_for_action(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);

        $response = $this->runDirective('make-action', ['test', '--type=invalid']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->getExitCode());
        $this->assertStringContainsString('Invalid type', $response->getOutput());
    }

    public function test_invalid_directive_name(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);

        $response = $this->runDirective('make-directive', ['user@list']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->getExitCode());
        $this->assertStringContainsString('Invalid directive name', $response->getOutput());
    }

    public function test_help_command_shows_system_commands(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTaskDirective::class);
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective::class);
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRecordDirective::class);
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective::class);

        $response = $this->runDirective('--help');

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('--list', $response->getOutput());
        $this->assertStringContainsString('--help', $response->getOutput());
        $this->assertStringContainsString('--version', $response->getOutput());
    }

    public function test_list_command_shows_all_directives(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTaskDirective::class);
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRepositoryDirective::class);
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeRecordDirective::class);
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeTypedCollectionDirective::class);

        $response = $this->runDirective('--list');

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());

        $this->assertStringContainsString('make-directive', $response->getOutput());
        $this->assertStringContainsString('create-directive', $response->getOutput());
        $this->assertStringContainsString('make-cmd', $response->getOutput());

        $this->assertStringContainsString('make-action', $response->getOutput());
        $this->assertStringContainsString('create-action', $response->getOutput());
        $this->assertStringContainsString('make-act', $response->getOutput());

        $this->assertStringContainsString('make-task', $response->getOutput());
        $this->assertStringContainsString('create-task', $response->getOutput());
        $this->assertStringContainsString('make-job', $response->getOutput());

        $this->assertStringContainsString('make-repository', $response->getOutput());
        $this->assertStringContainsString('create-repository', $response->getOutput());
        $this->assertStringContainsString('make-repo', $response->getOutput());

        $this->assertStringContainsString('make-record', $response->getOutput());
        $this->assertStringContainsString('create-record', $response->getOutput());
        $this->assertStringContainsString('make-dto', $response->getOutput());

        $this->assertStringContainsString('make-typed-collection', $response->getOutput());
        $this->assertStringContainsString('create-collection', $response->getOutput());
        $this->assertStringContainsString('make-collection', $response->getOutput());
    }

    public function test_using_alias_create_directive(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeDirective::class);

        $response = $this->runDirective('create-directive', ['cache-clear']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Directives/CacheClearDirective.php';
        $this->assertFileExists($fullPath);
    }

    public function test_using_alias_make_act(): void
    {
        $this->registerDirectiveClass(\AndyDefer\DirectiveForge\Directives\MakeActionDirective::class);

        $response = $this->runDirective('make-act', ['user/profile', '--type=api']);

        $response->assertSuccess();

        $fullPath = $this->directiveTempDir . '/app/Actions/User/ProfileAction.php';
        $this->assertFileExists($fullPath);
    }

    public function test_version_command_shows_version(): void
    {
        $response = $this->runDirective('--version');

        $this->assertSame(ExitCode::SUCCESS, $response->getExitCode());
        $this->assertStringContainsString('Laravel Directive', $response->getOutput());
        $this->assertStringContainsString('Version:', $response->getOutput());
    }
}
