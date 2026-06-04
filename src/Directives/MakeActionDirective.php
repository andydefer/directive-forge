<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\ActionGenerator;
use AndyDefer\DirectiveForge\Generators\RecordGenerator;
use AndyDefer\DirectiveForge\Generators\RequestGenerator;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

final class MakeActionDirective extends BaseDirective
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->generator = new ActionGenerator($interaction);
    }

    public function getSignature(): string
    {
        return 'make-action {name} {--fully}';
    }

    public function getDescription(): string
    {
        return 'Create a new action class (with --fully option to also create Request and Record)';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection();
        $aliases->add('create-action', 'make-act');

        return $aliases;
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');

        if ($name === null) {
            $this->error('Action name is required');
            return ExitCode::INVALID_ARGUMENT;
        }

        // Sauvegarder le nom original avant que parent::execute() ne le modifie
        $originalName = $name;

        // Créer l'Action
        $actionResult = parent::execute();

        if ($actionResult !== ExitCode::SUCCESS) {
            return $actionResult;
        }

        // Si l'option --fully est présente, créer également la Request et le Record
        if ($this->option('fully')) {
            $this->createRequestAndRecord($originalName);
        }

        return ExitCode::SUCCESS;
    }

    /**
     * Crée la Request et le Record associés à l'Action.
     *
     * @param string $actionName Le nom de l'Action (ex: 'user/show')
     */
    private function createRequestAndRecord(string $actionName): void
    {
        // Extraire le chemin et le nom de base
        $segments = explode('/', $actionName);
        $rawClassName = array_pop($segments);
        $subPath = !empty($segments) ? implode('/', $segments) : '';

        // Normaliser le nom de base (kebab-case -> PascalCase) en utilisant la méthode parente
        $normalizedBaseName = $this->toPascalCase($rawClassName);

        // Noms des classes (sans suffixe Action)
        $baseClassName = str_replace('Action', '', $normalizedBaseName);
        $baseClassName = str_replace('Record', '', $baseClassName);
        $baseClassName = str_replace('Request', '', $baseClassName);

        $requestClassName = $baseClassName . 'Request';
        $recordClassName = $baseClassName . 'Record';

        // Chemins complets
        $requestPath = !empty($subPath) ? $subPath . '/' . $requestClassName : $requestClassName;
        $recordPath = !empty($subPath) ? $subPath . '/' . $recordClassName : $recordClassName;

        // Créer la Request via son générateur
        $this->createRequest($requestPath);

        // Créer le Record via son générateur
        $this->createRecord($recordPath);

        // Afficher un message récapitulatif
        $this->interaction->line('');
        $this->interaction->info('🎉 Fully created:');
        $this->interaction->line("   Action:  {$actionName}");
        $this->interaction->line("   Request: {$requestPath}");
        $this->interaction->line("   Record:  {$recordPath}");
    }

    /**
     * Crée une Request.
     *
     * @param string $path Le chemin de la Request
     */
    private function createRequest(string $path): void
    {
        $requestGenerator = new RequestGenerator($this->interaction);
        // Utiliser la méthode extractPathInfo du parent (BaseDirective)
        $pathInfo = $this->extractPathInfo($path);
        $requestGenerator->generate($pathInfo);
    }

    /**
     * Crée un Record.
     *
     * @param string $path Le chemin du Record
     */
    private function createRecord(string $path): void
    {
        $recordGenerator = new RecordGenerator($this->interaction);
        // Utiliser la méthode extractPathInfo du parent (BaseDirective)
        $pathInfo = $this->extractPathInfo($path);
        $recordGenerator->generate($pathInfo);
    }
}
