<?php

declare(strict_types=1);

namespace App\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * @extends AbstractTypedCollection<OrderData>
 */
final class OrderDataCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(OrderData::class);
    }
}