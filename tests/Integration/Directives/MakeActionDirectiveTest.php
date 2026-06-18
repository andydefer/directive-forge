<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Integration\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveTestingService;
use AndyDefer\DirectiveForge\Directives\MakeActionDirective;
use AndyDefer\DirectiveForge\Directives\MakeRecordDirective;
use AndyDefer\DirectiveForge\Directives\MakeRequestDirective;
use AndyDefer\DirectiveForge\Tests\IntegrationTestCase;
use AndyDefer\PhpServices\Services\FileSystemService;

final class MakeActionDirectiveTest extends IntegrationTestCase
{
    private DirectiveTestingService $service;

    private string $tempDir;

    private FileSystemService $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DirectiveTestingService($this->app);

        // Enregistrer les directives nécessaires pour les appels en cascade
        $this->service->registerDirective(MakeRecordDirective::class);
        $this->service->registerDirective(MakeRequestDirective::class);

        $this->tempDir = $this->service->getTempDir();

        $this->app['config']->set('directive-forge.mode', 'app');
        $this->app['config']->set('directive-forge.namespace', 'App');
        $this->app['config']->set('directive-forge.extension', 'php');
        $this->app['config']->set('directive-forge.directory_permission', 0755);

        $this->filesystem = new FileSystemService;
    }

    protected function tearDown(): void
    {
        $this->service->destroy();

        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$file;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    public function test_creates_action_successfully(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'create-user',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
        $this->assertStringContainsString('Action created successfully', $response->output);

        $expectedPath = $this->tempDir.'/app/Actions/CreateUserAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateUserAction', $content);
        $this->assertStringContainsString('namespace App\\Actions', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
    }

    public function test_creates_action_with_subdirectories(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'admin.update-user',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Actions/Admin/UpdateUserAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class UpdateUserAction', $content);
        $this->assertStringContainsString('namespace App\\Actions\\Admin', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
    }

    public function test_creates_action_with_deep_subdirectories(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'api.v1.user.create',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Actions/Api/V1/User/CreateAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateAction', $content);
        $this->assertStringContainsString('namespace App\\Actions\\Api\\V1\\User', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
    }

    public function test_creates_action_with_suffix_already_present(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'create-user-action',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Actions/CreateUserAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateUserAction', $content);
        $this->assertStringContainsString('namespace App\\Actions', $content);
    }

    public function test_creates_action_with_suffix_in_subdirectory(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'admin.create-user-action',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Actions/Admin/CreateUserAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CreateUserAction', $content);
        $this->assertStringContainsString('namespace App\\Actions\\Admin', $content);
    }

    public function test_creates_action_with_supfile_r(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'create-user',
            '--supfile=r',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Vérifier que l'action a été créée
        $actionPath = $this->tempDir.'/app/Actions/CreateUserAction.php';
        $this->assertFileExists($actionPath);

        // Vérifier que la request a été créée (sans record)
        $requestPath = $this->tempDir.'/app/Requests/CreateUserRequest.php';
        $this->assertFileExists($requestPath);

        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('class CreateUserRequest', $requestContent);
        $this->assertStringContainsString('namespace App\\Requests', $requestContent);

        // Vérifier que le record N'EXISTE PAS (request sans record)
        $recordPath = $this->tempDir.'/app/Records/CreateUserRecord.php';
        $this->assertFileDoesNotExist($recordPath);
    }

    public function test_creates_action_with_supfile_a(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'create-user',
            '--supfile=a',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Vérifier que l'action a été créée
        $actionPath = $this->tempDir.'/app/Actions/CreateUserAction.php';
        $this->assertFileExists($actionPath);

        // Vérifier que la request a été créée
        $requestPath = $this->tempDir.'/app/Requests/CreateUserRequest.php';
        $this->assertFileExists($requestPath);

        // Vérifier que le record a été créé (via l'option -r de make-request)
        $recordPath = $this->tempDir.'/app/Records/CreateUserRecord.php';
        $this->assertFileExists($recordPath);

        $recordContent = file_get_contents($recordPath);
        $this->assertStringContainsString('class CreateUserRecord', $recordContent);
        $this->assertStringContainsString('namespace App\\Records', $recordContent);

        // Vérifier que la request importe bien le record
        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('use App\\Records\\CreateUserRecord;', $requestContent);
        $this->assertStringContainsString('return CreateUserRecord::from([ // TODO: Map request data to record properties ])', $requestContent);
    }

    public function test_creates_action_with_supfile_a_and_subdirectories(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'admin.create-user',
            '--supfile=a',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Vérifier l'action
        $actionPath = $this->tempDir.'/app/Actions/Admin/CreateUserAction.php';
        $this->assertFileExists($actionPath);

        // Vérifier la request
        $requestPath = $this->tempDir.'/app/Requests/Admin/CreateUserRequest.php';
        $this->assertFileExists($requestPath);

        // Vérifier le record
        $recordPath = $this->tempDir.'/app/Records/Admin/CreateUserRecord.php';
        $this->assertFileExists($recordPath);

        // Vérifier que la request importe bien le record
        $requestContent = file_get_contents($requestPath);
        $this->assertStringContainsString('use App\\Records\\Admin\\CreateUserRecord;', $requestContent);
    }

    public function test_creates_action_with_supfile_r_and_subdirectories(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'admin.create-user',
            '--supfile=r',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        // Vérifier l'action
        $actionPath = $this->tempDir.'/app/Actions/Admin/CreateUserAction.php';
        $this->assertFileExists($actionPath);

        // Vérifier la request
        $requestPath = $this->tempDir.'/app/Requests/Admin/CreateUserRequest.php';
        $this->assertFileExists($requestPath);

        // Vérifier que le record N'EXISTE PAS
        $recordPath = $this->tempDir.'/app/Records/Admin/CreateUserRecord.php';
        $this->assertFileDoesNotExist($recordPath);
    }

    public function test_returns_error_when_name_missing(): void
    {
        $response = $this->service->run(MakeActionDirective::class, []);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Not enough arguments', $response->output);
    }

    public function test_returns_error_when_name_is_empty(): void
    {
        $response = $this->service->run(MakeActionDirective::class, ['']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Action name is required', $response->output);
    }

    public function test_returns_error_when_action_already_exists(): void
    {
        $this->service->run(MakeActionDirective::class, ['existing']);

        $response = $this->service->run(MakeActionDirective::class, ['existing']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('Action already exists', $response->output);
    }

    public function test_returns_error_when_name_has_invalid_characters(): void
    {
        $response = $this->service->run(MakeActionDirective::class, ['invalid@name']);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_uses_custom_namespace_from_config(): void
    {
        $this->app['config']->set('directive-forge.namespace', 'Custom');

        $response = $this->service->run(MakeActionDirective::class, [
            'custom-action',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Actions/CustomAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class CustomAction', $content);
        $this->assertStringContainsString('namespace Custom\\Actions', $content);
    }

    public function test_handles_kebab_case_name(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'my-custom-action',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Actions/MyCustomAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomAction', $content);
    }

    public function test_handles_camel_case_name(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'myCustomAction',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Actions/MyCustomAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class MyCustomAction', $content);
    }

    public function test_handles_snake_case_name(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'my_custom_action',
        ]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
        $this->assertStringContainsString('FilePath contains invalid characters', $response->output);
    }

    public function test_creates_action_in_src_when_mode_library(): void
    {
        $this->app['config']->set('directive-forge.mode', 'library');

        mkdir($this->tempDir.'/src', 0777, true);
        mkdir($this->tempDir.'/src/Actions', 0777, true);

        $response = $this->service->run(MakeActionDirective::class, [
            'lib-action',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/src/Actions/LibAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);
        $this->assertStringContainsString('class LibAction', $content);
        $this->assertStringContainsString('namespace App\\Actions', $content);
        $this->assertStringContainsString('extends AbstractAction', $content);
    }

    public function test_generates_correct_stub_content(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'test.content',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Actions/Test/ContentAction.php';
        $this->assertFileExists($expectedPath);

        $content = file_get_contents($expectedPath);

        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString('namespace App\\Actions\\Test;', $content);
        $this->assertStringContainsString('class ContentAction extends AbstractAction', $content);
        $this->assertStringContainsString('use AndyDefer\\Actions\\Actions\\AbstractAction;', $content);
        $this->assertStringContainsString('use AndyDefer\\Actions\\Http\\ResponseFactory;', $content);
        $this->assertStringContainsString('use AndyDefer\\DomainStructures\\Abstracts\\AbstractRecord;', $content);
        $this->assertStringContainsString('protected function handle(AbstractRecord $request): ResponseFactory', $content);
        $this->assertStringContainsString('return ResponseFactory::noContent();', $content);
    }

    public function test_works_with_create_action_alias(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'alias-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Actions/AliasTestAction.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_works_with_make_act_alias(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'act-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);

        $expectedPath = $this->tempDir.'/app/Actions/ActTestAction.php';
        $this->assertFileExists($expectedPath);
    }

    public function test_returns_success_code_on_success(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'success-test',
        ]);

        $this->assertSame(ExitCode::SUCCESS, $response->exit_code);
    }

    public function test_returns_failure_code_on_error(): void
    {
        $response = $this->service->run(MakeActionDirective::class, [
            'invalid..name',
        ]);

        $this->assertSame(ExitCode::INVALID_ARGUMENT, $response->exit_code);
    }
}
