<?php

declare(strict_types=1);

namespace App\Collections;

use AndyDefer\Records\Collections\TypedCollection;

/**
 * @extends TypedCollection<string>
 */
final class TestCollection extends TypedCollection
{
    public function __construct()
    {
        parent::__construct(string::class);
    }
}