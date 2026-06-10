<?php

declare(strict_types=1);

namespace App\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * @extends AbstractTypedCollection<UserRecord>
 */
final class UserCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(UserRecord::class);
    }
}