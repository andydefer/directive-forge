<?php

declare(strict_types=1);

namespace App\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;


final class UserList2Directive extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'user-list2';
    }

    public function getDescription(): string
    {
        return 'Description for user-list2';
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