<?php

declare(strict_types=1);

namespace App\Directives\User\Domain;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;


final class HelloDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'hello';
    }

    public function getDescription(): string
    {
        return 'Description for hello';
    }

    public function getAliases(): StringTypedCollection
    {
        return new StringTypedCollection();
    }

    public function shouldBootLaravel(): bool
    {
        return false;
    }

    public function execute(): ExitCode
    {
        $this->info('Directive executed successfully!');
        
        return ExitCode::SUCCESS;
    }
}