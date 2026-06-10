<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Users;


use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\EmptyData;


final class ShowAction extends AbstractAction
{
    protected function handle(AbstractRecord $recordRequest): ResponseFactory
    {
        // TODO: Implement your business logic here
        
        return ResponseFactory::json(EmptyData::from([]));
    }
}

