<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;

/**
 * Record containing path information extracted from a directive name.
 *
 * This record stores the parsed components of a directive path, including
 * the class name, subdirectory path, and individual path segments.
 *
 * @example
 * // Input: 'admin/user/create-user'
 * // Result: className = 'CreateUser'
 * //         subPath = 'admin/user'
 * //         segments = ['admin', 'user']
 */
final class PathInfoRecord extends AbstractRecord
{
    public function __construct(
        /** The directive class name (e.g., 'CreateUser') */
        public readonly string $className,

        /** The subdirectory path (e.g., 'Admin/User' or empty string) */
        public readonly string $subPath,

        /** Collection of individual path segments (e.g., ['admin', 'user']) */
        public readonly ScalarTypedCollection $segments,
    ) {}
}
