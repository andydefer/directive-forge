<?php

declare(strict_types=1);

namespace App\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * @extends AbstractTypedCollection<ProductRecord>
 */
final class ProductCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(ProductRecord::class);
    }
}