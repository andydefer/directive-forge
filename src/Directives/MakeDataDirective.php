<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\DataGenerator;
use AndyDefer\DirectiveForge\Generators\RecordGenerator;
use AndyDefer\DirectiveForge\Generators\TypedCollectionGenerator;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

final class MakeDataDirective extends BaseDirective
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->generator = new DataGenerator($interaction);
    }

    public function getSignature(): string
    {
        return 'make-data {name} {--fully}';
    }

    public function getDescription(): string
    {
        return 'Create a new data DTO class (with --fully option to also create Record and TypedCollection)';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection();
        $aliases->add('create-data', 'make-dto');

        return $aliases;
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');

        if ($name === null) {
            $this->error('Data name is required');
            return ExitCode::INVALID_ARGUMENT;
        }

        $originalName = $name;

        $dataResult = parent::execute();

        if ($dataResult !== ExitCode::SUCCESS) {
            return $dataResult;
        }

        if ($this->option('fully')) {
            $this->createRecordAndCollection($originalName);
        }

        return ExitCode::SUCCESS;
    }

    /**
     * Crée le Record et la TypedCollection associés à la Data.
     *
     * @param string $dataName Le nom de la Data (ex: 'user')
     */
    private function createRecordAndCollection(string $dataName): void
    {
        $segments = explode('/', $dataName);
        $rawClassName = array_pop($segments);
        $subPath = !empty($segments) ? implode('/', $segments) : '';

        $normalizedBaseName = $this->toPascalCase($rawClassName);

        $baseClassName = str_replace('Data', '', $normalizedBaseName);
        $baseClassName = str_replace('Record', '', $baseClassName);
        $baseClassName = str_replace('Collection', '', $baseClassName);

        $recordClassName = $baseClassName . 'Record';
        $collectionClassName = $baseClassName . 'DataCollection';

        $recordPath = !empty($subPath) ? $subPath . '/' . $recordClassName : $recordClassName;
        $collectionPath = !empty($subPath) ? $subPath . '/' . $collectionClassName : $collectionClassName;

        $this->createRecord($recordPath);
        $this->createTypedCollection($collectionPath, $normalizedBaseName . 'Data');

        $this->interaction->line('');
        $this->interaction->info('🎉 Fully created:');
        $this->interaction->line("   Data:       {$dataName}");
        $this->interaction->line("   Record:     {$recordPath}");
        $this->interaction->line("   Collection: {$collectionPath}");
    }

    private function createRecord(string $path): void
    {
        $recordGenerator = new RecordGenerator($this->interaction);
        $pathInfo = $this->extractPathInfo($path);
        $recordGenerator->generate($pathInfo);
    }

    private function createTypedCollection(string $path, string $itemType): void
    {
        $collectionGenerator = new TypedCollectionGenerator($this->interaction);
        $pathInfo = $this->extractPathInfo($path);
        $collectionGenerator->generate($pathInfo, null, $itemType);
    }
}
