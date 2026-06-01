<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Enums;

use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class GeneratorTypeTest extends UnitTestCase
{
    public function test_directive_config(): void
    {
        $config = GeneratorType::DIRECTIVE->getConfig();

        $this->assertSame(GeneratorType::DIRECTIVE, $config->type);
        $this->assertSame('/app/Directives/', $config->basePath);
        $this->assertSame('App\\Directives', $config->baseNamespace);
        $this->assertStringContainsString('directive.stub', $config->stubPath);
        $this->assertSame('Directive', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    public function test_action_config(): void
    {
        $config = GeneratorType::ACTION->getConfig();

        $this->assertSame(GeneratorType::ACTION, $config->type);
        $this->assertSame('/app/Actions/', $config->basePath);
        $this->assertSame('App\\Actions', $config->baseNamespace);
        $this->assertStringContainsString('action.stub', $config->stubPath);
        $this->assertSame('Action', $config->suffix);
        $this->assertFalse($config->supportsType);  // ✅ Changé : false au lieu de true
        $this->assertFalse($config->requiresType);
    }

    public function test_task_config(): void
    {
        $config = GeneratorType::TASK->getConfig();

        $this->assertSame(GeneratorType::TASK, $config->type);
        $this->assertSame('/app/Tasks/', $config->basePath);
        $this->assertSame('App\\Tasks', $config->baseNamespace);
        $this->assertStringContainsString('task.stub', $config->stubPath);
        $this->assertSame('Task', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    public function test_repository_config(): void
    {
        $config = GeneratorType::REPOSITORY->getConfig();

        $this->assertSame(GeneratorType::REPOSITORY, $config->type);
        $this->assertSame('/app/Repositories/', $config->basePath);
        $this->assertSame('App\\Repositories', $config->baseNamespace);
        $this->assertStringContainsString('repository.stub', $config->stubPath);
        $this->assertSame('Repository', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    public function test_record_config(): void
    {
        $config = GeneratorType::RECORD->getConfig();

        $this->assertSame(GeneratorType::RECORD, $config->type);
        $this->assertSame('/app/Records/', $config->basePath);
        $this->assertSame('App\\Records', $config->baseNamespace);
        $this->assertStringContainsString('record.stub', $config->stubPath);
        $this->assertSame('Record', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    public function test_typed_collection_config(): void
    {
        $config = GeneratorType::TYPED_COLLECTION->getConfig();

        $this->assertSame(GeneratorType::TYPED_COLLECTION, $config->type);
        $this->assertSame('/app/Collections/', $config->basePath);
        $this->assertSame('App\\Collections', $config->baseNamespace);
        $this->assertStringContainsString('typed-collection.stub', $config->stubPath);
        $this->assertSame('Collection', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertTrue($config->requiresType);
    }

    public function test_get_generator_class(): void
    {
        $this->assertStringContainsString('DirectiveGenerator', GeneratorType::DIRECTIVE->getGeneratorClass());
        $this->assertStringContainsString('ActionGenerator', GeneratorType::ACTION->getGeneratorClass());
        $this->assertStringContainsString('TaskGenerator', GeneratorType::TASK->getGeneratorClass());
        $this->assertStringContainsString('RepositoryGenerator', GeneratorType::REPOSITORY->getGeneratorClass());
        $this->assertStringContainsString('RecordGenerator', GeneratorType::RECORD->getGeneratorClass());
        $this->assertStringContainsString('TypedCollectionGenerator', GeneratorType::TYPED_COLLECTION->getGeneratorClass());
    }

    public function test_get_signature(): void
    {
        $this->assertSame('make-directive', GeneratorType::DIRECTIVE->getSignature());
        $this->assertSame('make-action', GeneratorType::ACTION->getSignature());
        $this->assertSame('make-task', GeneratorType::TASK->getSignature());
        $this->assertSame('make-repository', GeneratorType::REPOSITORY->getSignature());
        $this->assertSame('make-record', GeneratorType::RECORD->getSignature());
        $this->assertSame('make-typed-collection', GeneratorType::TYPED_COLLECTION->getSignature());
    }

    public function test_get_aliases(): void
    {
        $directiveAliases = GeneratorType::DIRECTIVE->getAliases();
        $this->assertContains('create-directive', $directiveAliases);
        $this->assertContains('make-cmd', $directiveAliases);

        $actionAliases = GeneratorType::ACTION->getAliases();
        $this->assertContains('create-action', $actionAliases);
        $this->assertContains('make-act', $actionAliases);

        $taskAliases = GeneratorType::TASK->getAliases();
        $this->assertContains('create-task', $taskAliases);
        $this->assertContains('make-job', $taskAliases);

        $repositoryAliases = GeneratorType::REPOSITORY->getAliases();
        $this->assertContains('create-repository', $repositoryAliases);
        $this->assertContains('make-repo', $repositoryAliases);

        $recordAliases = GeneratorType::RECORD->getAliases();
        $this->assertContains('create-record', $recordAliases);
        $this->assertContains('make-dto', $recordAliases);

        $collectionAliases = GeneratorType::TYPED_COLLECTION->getAliases();
        $this->assertContains('create-collection', $collectionAliases);
        $this->assertContains('make-collection', $collectionAliases);
    }
}
