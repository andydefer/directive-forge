<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\Contexts\DirectiveContext;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Services\FileCreatorService;
use AndyDefer\DirectiveForge\Generators\RecordGenerator;
use AndyDefer\DirectiveForge\Generators\RepositoryGenerator;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

final class MakeRepositoryDirective extends BaseDirective
{
    public function __construct(
        DirectiveContext $context,
        DirectiveInteractionService $interaction,
        FileCreatorService $fileCreator
    ) {
        parent::__construct($context, $interaction, $fileCreator, new RepositoryGenerator($interaction, $fileCreator));
    }

    public function getSignature(): string
    {
        return 'make-repository {name} {--fully}';
    }

    public function getDescription(): string
    {
        return 'Create a new repository class (with --fully option to also create Record and FilterRecord)';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-repository');
        $aliases->add('make-repo');

        return $aliases;
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');

        if ($name === null) {
            $this->error('Repository name is required');

            return ExitCode::INVALID_ARGUMENT;
        }

        // Sauvegarder le nom original
        $originalName = $name;

        // Créer le Repository
        $repositoryResult = parent::execute();

        if ($repositoryResult !== ExitCode::SUCCESS) {
            return $repositoryResult;
        }

        // Si l'option --fully est présente, créer également les Records
        if ($this->option('fully')) {
            $this->createRecords($originalName);
        }

        return ExitCode::SUCCESS;
    }

    /**
     * Crée les Records associés au Repository.
     *
     * @param  string  $repositoryName  Le nom du Repository (ex: 'user')
     */
    private function createRecords(string $repositoryName): void
    {
        // Extraire le chemin et le nom de base
        $segments = explode('/', $repositoryName);
        $rawClassName = array_pop($segments);
        $subPath = ! empty($segments) ? implode('/', $segments) : '';

        // Normaliser le nom de base (kebab-case -> PascalCase)
        $normalizedBaseName = $this->toPascalCase($rawClassName);

        // Nom de base sans le suffixe Repository
        $baseClassName = str_replace('Repository', '', $normalizedBaseName);
        $baseClassName = str_replace('Record', '', $baseClassName);
        $baseClassName = str_replace('Filter', '', $baseClassName);

        // Noms des Records
        $recordClassName = $baseClassName.'Record';
        $filterRecordClassName = $baseClassName.'FilterRecord';

        // Chemins complets
        $recordPath = ! empty($subPath) ? $subPath.'/'.$recordClassName : $recordClassName;
        $filterRecordPath = ! empty($subPath) ? $subPath.'/'.$filterRecordClassName : $filterRecordClassName;

        // Créer le Record principal
        $this->createRecord($recordPath);

        // Créer le FilterRecord
        $this->createRecord($filterRecordPath);

        // Afficher un message récapitulatif
        $this->newLine();
        $this->info('🎉 Fully created:');
        $this->line("   Repository:   {$repositoryName}");
        $this->line("   Record:       {$recordPath}");
        $this->line("   FilterRecord: {$filterRecordPath}");
    }

    /**
     * Crée un Record.
     *
     * @param  string  $path  Le chemin du Record
     */
    private function createRecord(string $path): void
    {
        $recordGenerator = new RecordGenerator($this->interaction);
        $pathInfo = $this->extractPathInfo($path);
        $recordGenerator->generate($pathInfo);
    }
}
