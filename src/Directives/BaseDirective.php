<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Directive\Services\DirectiveInteractionService;
use AndyDefer\Directive\Traits\FileCreator;
use AndyDefer\DirectiveForge\Contracts\GeneratorInterface;
use AndyDefer\DirectiveForge\ValueObjects\PathInfo;

abstract class BaseDirective extends AbstractDirective
{
    use FileCreator;

    protected GeneratorInterface $generator;

    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->initFileCreator();
    }

    public function execute(): ExitCode
    {
        $name = $this->argument('name');

        if ($name === null) {
            $this->error('Name is required');
            return ExitCode::INVALID_ARGUMENT;
        }

        $segments = explode('/', $name);
        $className = array_pop($segments);
        $subPath = !empty($segments) ? implode('\\', array_map('ucfirst', $segments)) : '';

        $pathInfo = new PathInfo(
            className: $className,
            subPath: $subPath,
            segments: $segments,
        );

        $type = $this->option('type');
        $itemType = $this->option('item-type');

        return $this->generator->generate($pathInfo, $type, $itemType);
    }
}
