<?php

declare(strict_types=1);

namespace AndyDefer\DirectiveForge\Configs;

use AndyDefer\DirectiveForge\Contracts\Configs\ForgeConfigInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class ForgeConfig implements ForgeConfigInterface
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly string $packageBasePath = __DIR__.'/../..'
    ) {}

    public function getMode(): string
    {
        $mode = $this->config->get('directive-forge.mode');

        if ($mode !== null && in_array($mode, ['app', 'library'])) {
            return $mode;
        }

        $mode = getenv('DIRECTIVE_MODE');

        if ($mode !== false && in_array($mode, ['app', 'library'])) {
            return $mode;
        }

        $cwd = getcwd();
        $hasApp = is_dir($cwd.'/app');
        $hasSrc = is_dir($cwd.'/src');

        if ($hasApp && $hasSrc) {
            throw new \RuntimeException(
                'Both "app" and "src" directories found. Set directive-forge.mode in config/directive-forge.php or DIRECTIVE_MODE in .env'
            );
        }

        return $hasApp ? 'app' : 'library';
    }

    public function getNamespace(): string
    {
        $configNamespace = $this->config->get('directive-forge.namespace');

        if ($configNamespace !== null) {
            return $configNamespace;
        }

        return $this->getPackageNamespace();
    }

    public function getExtension(): string
    {
        return $this->config->get('directive-forge.extension', 'php');
    }

    public function getDirectoryPermission(): int
    {
        return (int) ($this->config->get('directive-forge.directory_permission') ?: 0755);
    }

    public function getStubPath(string $name): string
    {
        $customPath = $this->config->get('directive-forge.stubs_path');

        if ($customPath !== null) {
            $path = $customPath.'/'.$name.'.stub';
            if (file_exists($path)) {
                return $path;
            }
        }

        return $this->packageBasePath.'/stubs/'.$name.'.stub';
    }

    public function getAvailableStubs(): array
    {
        $paths = [];

        $packageStubs = $this->packageBasePath.'/stubs';
        if (is_dir($packageStubs)) {
            foreach (glob($packageStubs.'/*.stub') as $file) {
                $paths[] = str_replace('.stub', '', basename($file));
            }
        }

        $customPath = $this->config->get('directive-forge.stubs_path');
        if ($customPath !== null && is_dir($customPath)) {
            foreach (glob($customPath.'/*.stub') as $file) {
                $paths[] = str_replace('.stub', '', basename($file));
            }
        }

        return array_unique($paths);
    }

    private function getPackageNamespace(): string
    {
        $composerPath = getcwd().'/composer.json';

        if (! file_exists($composerPath)) {
            return 'App';
        }

        $content = file_get_contents($composerPath);
        $data = json_decode($content, true);

        if (! isset($data['autoload']['psr-4'])) {
            return 'App';
        }

        $namespaces = array_keys($data['autoload']['psr-4']);

        if (empty($namespaces)) {
            return 'App';
        }

        // Prendre le premier namespace PSR-4 du composer.json
        return rtrim($namespaces[0], '\\');
    }
}
