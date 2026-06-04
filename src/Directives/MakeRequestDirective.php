<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\DirectiveForge\Generators\RecordGenerator;
use AndyDefer\DirectiveForge\Generators\RequestGenerator;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

/**
 * Directive to create a new Form Request class.
 *
 * @author Andy Defer
 */
final class MakeRequestDirective extends BaseDirective
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->generator = new RequestGenerator($interaction);
    }

    public function getSignature(): string
    {
        return 'make-request {name} {--fully}';
    }

    public function getDescription(): string
    {
        return 'Create a new form request class (with --fully option to also create Record)';
    }

    public function getAliases(): StringTypedCollection
    {
        $aliases = new StringTypedCollection;
        $aliases->add('create-request', 'make-req');

        return $aliases;
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');

        if ($name === null) {
            $this->error('Request name is required');
            return ExitCode::INVALID_ARGUMENT;
        }

        // Sauvegarder le nom original
        $originalName = $name;

        // Créer la Request
        $requestResult = parent::execute();

        if ($requestResult !== ExitCode::SUCCESS) {
            return $requestResult;
        }

        // Si l'option --fully est présente, créer également le Record
        if ($this->option('fully')) {
            $this->createRecord($originalName);
        }

        return ExitCode::SUCCESS;
    }

    /**
     * Crée le Record associé à la Request.
     *
     * @param string $requestName Le nom de la Request (ex: 'user/StoreUserRequest')
     */
    private function createRecord(string $requestName): void
    {
        // Extraire le chemin et le nom de base
        $segments = explode('/', $requestName);
        $rawClassName = array_pop($segments);
        $subPath = !empty($segments) ? implode('/', $segments) : '';

        // Normaliser le nom de base
        $normalizedBaseName = $this->toPascalCase($rawClassName);

        // Supprimer le suffixe 'Request' pour obtenir le nom de base
        $baseClassName = str_replace('Request', '', $normalizedBaseName);

        // Nom du Record
        $recordClassName = $baseClassName . 'Record';

        // Chemin complet du Record
        $recordPath = !empty($subPath) ? $subPath . '/' . $recordClassName : $recordClassName;

        // Créer le Record via son générateur
        $recordGenerator = new RecordGenerator($this->interaction);
        $pathInfo = $this->extractPathInfo($recordPath);
        $recordGenerator->generate($pathInfo);

        // Afficher un message récapitulatif
        $this->interaction->line('');
        $this->interaction->info('🎉 Fully created:');
        $this->interaction->line("   Request: {$requestName}");
        $this->interaction->line("   Record:  {$recordPath}");
    }
}
