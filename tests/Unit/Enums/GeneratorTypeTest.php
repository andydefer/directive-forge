<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Tests\Unit\Enums;

use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\Tests\Unit\UnitTestCase;

final class GeneratorTypeTest extends UnitTestCase
{
    public function test_directive_config(): void
    {
        // Arrange: Get the DIRECTIVE enum case
        $generatorType = GeneratorType::DIRECTIVE;

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
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
        // Arrange: Get the ACTION enum case
        $generatorType = GeneratorType::ACTION;

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
        $this->assertSame(GeneratorType::ACTION, $config->type);
        $this->assertSame('/app/Actions/', $config->basePath);
        $this->assertSame('App\\Actions', $config->baseNamespace);
        $this->assertStringContainsString('action.stub', $config->stubPath);
        $this->assertSame('Action', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    public function test_task_config(): void
    {
        // Arrange: Get the TASK enum case
        $generatorType = GeneratorType::TASK;

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
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
        // Arrange: Get the REPOSITORY enum case
        $generatorType = GeneratorType::REPOSITORY;

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
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
        // Arrange: Get the RECORD enum case
        $generatorType = GeneratorType::RECORD;

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
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
        // Arrange: Get the TYPED_COLLECTION enum case
        $generatorType = GeneratorType::TYPED_COLLECTION;

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
        $this->assertSame(GeneratorType::TYPED_COLLECTION, $config->type);
        $this->assertSame('/app/Collections/', $config->basePath);
        $this->assertSame('App\\Collections', $config->baseNamespace);
        $this->assertStringContainsString('typed-collection.stub', $config->stubPath);
        $this->assertSame('Collection', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertTrue($config->requiresType);
    }

    public function test_service_config(): void
    {
        // Arrange: Get the SERVICE enum case
        $generatorType = GeneratorType::SERVICE;

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
        $this->assertSame(GeneratorType::SERVICE, $config->type);
        $this->assertSame('/app/Services/', $config->basePath);
        $this->assertSame('App\\Services', $config->baseNamespace);
        $this->assertStringContainsString('service.stub', $config->stubPath);
        $this->assertSame('Service', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    public function test_request_config(): void
    {
        // Arrange: Get the REQUEST enum case
        $generatorType = GeneratorType::REQUEST;

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
        $this->assertSame(GeneratorType::REQUEST, $config->type);
        $this->assertSame('/app/Http/Requests/', $config->basePath);
        $this->assertSame('App\\Http\\Requests', $config->baseNamespace);
        $this->assertStringContainsString('request.stub', $config->stubPath);
        $this->assertSame('Request', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    public function test_value_object_config(): void
    {
        // Arrange: Get the VALUE_OBJECT enum case
        $generatorType = GeneratorType::VALUE_OBJECT;

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
        $this->assertSame(GeneratorType::VALUE_OBJECT, $config->type);
        $this->assertSame('/app/ValueObjects/', $config->basePath);
        $this->assertSame('App\\ValueObjects', $config->baseNamespace);
        $this->assertStringContainsString('value-object.stub', $config->stubPath);
        $this->assertSame('VO', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    public function test_config_config(): void
    {
        // Arrange: Get the CONFIG enum case
        $generatorType = GeneratorType::CONFIG;

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
        $this->assertSame(GeneratorType::CONFIG, $config->type);
        $this->assertSame('/app/Configs/', $config->basePath);
        $this->assertSame('App\\Configs', $config->baseNamespace);
        $this->assertStringContainsString('config.stub', $config->stubPath);
        $this->assertSame('Config', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    public function test_data_config(): void
    {
        // Arrange: Get the DATA enum case
        $generatorType = GeneratorType::DATA;

        // Act: Get the configuration
        $config = $generatorType->getConfig();

        // Assert: Verify configuration values
        $this->assertSame(GeneratorType::DATA, $config->type);
        $this->assertSame('/app/Data/', $config->basePath);
        $this->assertSame('App\\Data', $config->baseNamespace);
        $this->assertStringContainsString('data.stub', $config->stubPath);
        $this->assertSame('Data', $config->suffix);
        $this->assertFalse($config->supportsType);
        $this->assertFalse($config->requiresType);
    }

    public function test_get_generator_class_for_directive(): void
    {
        // Arrange: Get the DIRECTIVE enum case
        $generatorType = GeneratorType::DIRECTIVE;

        // Act: Get the generator class
        $generatorClass = $generatorType->getGeneratorClass();

        // Assert: Verify generator class contains expected name
        $this->assertStringContainsString('DirectiveGenerator', $generatorClass);
    }

    public function test_get_generator_class_for_action(): void
    {
        // Arrange: Get the ACTION enum case
        $generatorType = GeneratorType::ACTION;

        // Act: Get the generator class
        $generatorClass = $generatorType->getGeneratorClass();

        // Assert: Verify generator class contains expected name
        $this->assertStringContainsString('ActionGenerator', $generatorClass);
    }

    public function test_get_generator_class_for_task(): void
    {
        // Arrange: Get the TASK enum case
        $generatorType = GeneratorType::TASK;

        // Act: Get the generator class
        $generatorClass = $generatorType->getGeneratorClass();

        // Assert: Verify generator class contains expected name
        $this->assertStringContainsString('TaskGenerator', $generatorClass);
    }

    public function test_get_generator_class_for_repository(): void
    {
        // Arrange: Get the REPOSITORY enum case
        $generatorType = GeneratorType::REPOSITORY;

        // Act: Get the generator class
        $generatorClass = $generatorType->getGeneratorClass();

        // Assert: Verify generator class contains expected name
        $this->assertStringContainsString('RepositoryGenerator', $generatorClass);
    }

    public function test_get_generator_class_for_record(): void
    {
        // Arrange: Get the RECORD enum case
        $generatorType = GeneratorType::RECORD;

        // Act: Get the generator class
        $generatorClass = $generatorType->getGeneratorClass();

        // Assert: Verify generator class contains expected name
        $this->assertStringContainsString('RecordGenerator', $generatorClass);
    }

    public function test_get_generator_class_for_typed_collection(): void
    {
        // Arrange: Get the TYPED_COLLECTION enum case
        $generatorType = GeneratorType::TYPED_COLLECTION;

        // Act: Get the generator class
        $generatorClass = $generatorType->getGeneratorClass();

        // Assert: Verify generator class contains expected name
        $this->assertStringContainsString('TypedCollectionGenerator', $generatorClass);
    }

    public function test_get_generator_class_for_service(): void
    {
        // Arrange: Get the SERVICE enum case
        $generatorType = GeneratorType::SERVICE;

        // Act: Get the generator class
        $generatorClass = $generatorType->getGeneratorClass();

        // Assert: Verify generator class contains expected name
        $this->assertStringContainsString('ServiceGenerator', $generatorClass);
    }

    public function test_get_generator_class_for_request(): void
    {
        // Arrange: Get the REQUEST enum case
        $generatorType = GeneratorType::REQUEST;

        // Act: Get the generator class
        $generatorClass = $generatorType->getGeneratorClass();

        // Assert: Verify generator class contains expected name
        $this->assertStringContainsString('RequestGenerator', $generatorClass);
    }

    public function test_get_generator_class_for_value_object(): void
    {
        // Arrange: Get the VALUE_OBJECT enum case
        $generatorType = GeneratorType::VALUE_OBJECT;

        // Act: Get the generator class
        $generatorClass = $generatorType->getGeneratorClass();

        // Assert: Verify generator class contains expected name
        $this->assertStringContainsString('ValueObjectGenerator', $generatorClass);
    }

    public function test_get_generator_class_for_config(): void
    {
        // Arrange: Get the CONFIG enum case
        $generatorType = GeneratorType::CONFIG;

        // Act: Get the generator class
        $generatorClass = $generatorType->getGeneratorClass();

        // Assert: Verify generator class contains expected name
        $this->assertStringContainsString('ConfigGenerator', $generatorClass);
    }

    public function test_get_generator_class_for_data(): void
    {
        // Arrange: Get the DATA enum case
        $generatorType = GeneratorType::DATA;

        // Act: Get the generator class
        $generatorClass = $generatorType->getGeneratorClass();

        // Assert: Verify generator class contains expected name
        $this->assertStringContainsString('DataGenerator', $generatorClass);
    }

    public function test_get_signature_for_directive(): void
    {
        // Arrange: Get the DIRECTIVE enum case
        $generatorType = GeneratorType::DIRECTIVE;

        // Act: Get the signature
        $signature = $generatorType->getSignature();

        // Assert: Verify signature
        $this->assertSame('make-directive', $signature);
    }

    public function test_get_signature_for_action(): void
    {
        // Arrange: Get the ACTION enum case
        $generatorType = GeneratorType::ACTION;

        // Act: Get the signature
        $signature = $generatorType->getSignature();

        // Assert: Verify signature
        $this->assertSame('make-action', $signature);
    }

    public function test_get_signature_for_task(): void
    {
        // Arrange: Get the TASK enum case
        $generatorType = GeneratorType::TASK;

        // Act: Get the signature
        $signature = $generatorType->getSignature();

        // Assert: Verify signature
        $this->assertSame('make-task', $signature);
    }

    public function test_get_signature_for_repository(): void
    {
        // Arrange: Get the REPOSITORY enum case
        $generatorType = GeneratorType::REPOSITORY;

        // Act: Get the signature
        $signature = $generatorType->getSignature();

        // Assert: Verify signature
        $this->assertSame('make-repository', $signature);
    }

    public function test_get_signature_for_record(): void
    {
        // Arrange: Get the RECORD enum case
        $generatorType = GeneratorType::RECORD;

        // Act: Get the signature
        $signature = $generatorType->getSignature();

        // Assert: Verify signature
        $this->assertSame('make-record', $signature);
    }

    public function test_get_signature_for_typed_collection(): void
    {
        // Arrange: Get the TYPED_COLLECTION enum case
        $generatorType = GeneratorType::TYPED_COLLECTION;

        // Act: Get the signature
        $signature = $generatorType->getSignature();

        // Assert: Verify signature
        $this->assertSame('make-typed-collection', $signature);
    }

    public function test_get_signature_for_service(): void
    {
        // Arrange: Get the SERVICE enum case
        $generatorType = GeneratorType::SERVICE;

        // Act: Get the signature
        $signature = $generatorType->getSignature();

        // Assert: Verify signature
        $this->assertSame('make-service', $signature);
    }

    public function test_get_signature_for_request(): void
    {
        // Arrange: Get the REQUEST enum case
        $generatorType = GeneratorType::REQUEST;

        // Act: Get the signature
        $signature = $generatorType->getSignature();

        // Assert: Verify signature
        $this->assertSame('make-request', $signature);
    }

    public function test_get_signature_for_value_object(): void
    {
        // Arrange: Get the VALUE_OBJECT enum case
        $generatorType = GeneratorType::VALUE_OBJECT;

        // Act: Get the signature
        $signature = $generatorType->getSignature();

        // Assert: Verify signature
        $this->assertSame('make-vo', $signature);
    }

    public function test_get_signature_for_config(): void
    {
        // Arrange: Get the CONFIG enum case
        $generatorType = GeneratorType::CONFIG;

        // Act: Get the signature
        $signature = $generatorType->getSignature();

        // Assert: Verify signature
        $this->assertSame('make-config', $signature);
    }

    public function test_get_signature_for_data(): void
    {
        // Arrange: Get the DATA enum case
        $generatorType = GeneratorType::DATA;

        // Act: Get the signature
        $signature = $generatorType->getSignature();

        // Assert: Verify signature
        $this->assertSame('make-data', $signature);
    }

    public function test_get_aliases_for_directive(): void
    {
        // Arrange: Get the DIRECTIVE enum case
        $generatorType = GeneratorType::DIRECTIVE;

        // Act: Get the aliases
        $aliases = $generatorType->getAliases();

        // Assert: Verify aliases
        $this->assertContains('create-directive', $aliases);
        $this->assertContains('make-cmd', $aliases);
    }

    public function test_get_aliases_for_action(): void
    {
        // Arrange: Get the ACTION enum case
        $generatorType = GeneratorType::ACTION;

        // Act: Get the aliases
        $aliases = $generatorType->getAliases();

        // Assert: Verify aliases
        $this->assertContains('create-action', $aliases);
        $this->assertContains('make-act', $aliases);
    }

    public function test_get_aliases_for_task(): void
    {
        // Arrange: Get the TASK enum case
        $generatorType = GeneratorType::TASK;

        // Act: Get the aliases
        $aliases = $generatorType->getAliases();

        // Assert: Verify aliases
        $this->assertContains('create-task', $aliases);
        $this->assertContains('make-job', $aliases);
    }

    public function test_get_aliases_for_repository(): void
    {
        // Arrange: Get the REPOSITORY enum case
        $generatorType = GeneratorType::REPOSITORY;

        // Act: Get the aliases
        $aliases = $generatorType->getAliases();

        // Assert: Verify aliases
        $this->assertContains('create-repository', $aliases);
        $this->assertContains('make-repo', $aliases);
    }

    public function test_get_aliases_for_record(): void
    {
        // Arrange: Get the RECORD enum case
        $generatorType = GeneratorType::RECORD;

        // Act: Get the aliases
        $aliases = $generatorType->getAliases();

        // Assert: Verify aliases
        $this->assertContains('create-record', $aliases);
        $this->assertContains('make-dto', $aliases);
    }

    public function test_get_aliases_for_typed_collection(): void
    {
        // Arrange: Get the TYPED_COLLECTION enum case
        $generatorType = GeneratorType::TYPED_COLLECTION;

        // Act: Get the aliases
        $aliases = $generatorType->getAliases();

        // Assert: Verify aliases
        $this->assertContains('create-collection', $aliases);
        $this->assertContains('make-collection', $aliases);
    }

    public function test_get_aliases_for_service(): void
    {
        // Arrange: Get the SERVICE enum case
        $generatorType = GeneratorType::SERVICE;

        // Act: Get the aliases
        $aliases = $generatorType->getAliases();

        // Assert: Verify aliases
        $this->assertContains('create-service', $aliases);
        $this->assertContains('make-svc', $aliases);
    }

    public function test_get_aliases_for_request(): void
    {
        // Arrange: Get the REQUEST enum case
        $generatorType = GeneratorType::REQUEST;

        // Act: Get the aliases
        $aliases = $generatorType->getAliases();

        // Assert: Verify aliases
        $this->assertContains('create-request', $aliases);
        $this->assertContains('make-req', $aliases);
    }

    public function test_get_aliases_for_value_object(): void
    {
        // Arrange: Get the VALUE_OBJECT enum case
        $generatorType = GeneratorType::VALUE_OBJECT;

        // Act: Get the aliases
        $aliases = $generatorType->getAliases();

        // Assert: Verify aliases
        $this->assertContains('create-vo', $aliases);
        $this->assertContains('make-value-object', $aliases);
    }

    public function test_get_aliases_for_config(): void
    {
        // Arrange: Get the CONFIG enum case
        $generatorType = GeneratorType::CONFIG;

        // Act: Get the aliases
        $aliases = $generatorType->getAliases();

        // Assert: Verify aliases
        $this->assertContains('create-config', $aliases);
        $this->assertContains('make-cfg', $aliases);
    }

    public function test_get_aliases_for_data(): void
    {
        // Arrange: Get the DATA enum case
        $generatorType = GeneratorType::DATA;

        // Act: Get the aliases
        $aliases = $generatorType->getAliases();

        // Assert: Verify aliases
        $this->assertContains('create-data', $aliases);
        $this->assertContains('make-dto', $aliases);
    }
}
