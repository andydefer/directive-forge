# Directive Forge

**Code generation directives for Laravel - forge directives, actions, tasks, repositories, records and typed collections.**

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x%20%7C%2013.x%20%7C%2014.x%20%7C%2015.x-blue)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## Installation

```bash
composer require andydefer/directive-forge --dev
```

### Requirements

- PHP 8.1 or higher
- Laravel 12.x, 13.x, 14.x, or 15.x
- `andydefer/laravel-directive` ^2.1 (automatically installed)

### Service Provider (auto-discovered)

The package uses Laravel's auto-discovery. The service provider will be automatically registered.

---

## Overview

Directive Forge provides a set of CLI commands to generate various PHP classes following clean architecture principles. It extends the [Laravel Directive](https://github.com/andydefer/laravel-directive) package to offer code scaffolding for:

| Type | Description | Directory | Suffix |
|------|-------------|-----------|--------|
| **Directive** | CLI commands for your application | `app/Directives/` | `Directive` |
| **Action** | Single-responsibility business logic | `app/Actions/` | `Action` |
| **Task** | Background jobs and scheduled tasks | `app/Tasks/` | `Task` |
| **Repository** | Data access layer | `app/Repositories/` | `Repository` |
| **Record** | Type-safe data transfer objects | `app/Records/` | `Record` |
| **TypedCollection** | Type-safe collections | `app/Collections/` | `Collection` |

---

## Usage

### Generate a Directive

```bash
./vendor/bin/directive make-directive user-list
```

Generated: `app/Directives/UserListDirective.php`

```php
<?php

declare(strict_types=1);

namespace App\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\Records\Collections\Utility\StringTypedCollection;

final class UserListDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'user-list';
    }

    public function getDescription(): string
    {
        return 'Description for user-list';
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
```

### Generate an Action

```bash
# API action (default)
./vendor/bin/directive make-action user/show --type=api

# Web action (with Inertia)
./vendor/bin/directive make-action admin/dashboard --type=web
```

Generated: `app/Actions/User/ShowAction.php` (API) or `app/Actions/Admin/DashboardAction.php` (Web)

### Generate a Task

```bash
./vendor/bin/directive make-task send-welcome-email
```

Generated: `app/Tasks/SendWelcomeEmailTask.php`

### Generate a Repository

```bash
./vendor/bin/directive make-repository user
```

Generated: `app/Repositories/UserRepository.php`

### Generate a Record

```bash
./vendor/bin/directive make-record user-data
```

Generated: `app/Records/UserDataRecord.php`

### Generate a Typed Collection

```bash
# With string type
./vendor/bin/directive make-typed-collection user-collection --item-type=string

# With custom record type
./vendor/bin/directive make-typed-collection user-collection --item-type=UserRecord
```

Generated: `app/Collections/UserCollection.php`

---

## Directory Structure with Subdirectories

You can organize your generated classes using subdirectories:

```bash
# Directive with subdirectory
./vendor/bin/directive make-directive user/domain/hello-directive
# → app/Directives/User/Domain/HelloDirective.php
# → namespace App\Directives\User\Domain

# Action with subdirectory
./vendor/bin/directive make-action api/v1/users/show --type=api
# → app/Actions/Api/V1/Users/ShowAction.php
# → namespace App\Actions\Api\V1\Users

# Task with subdirectory
./vendor/bin/directive make-task user/send-welcome-email
# → app/Tasks/User/SendWelcomeEmailTask.php
```

---

## Commands Reference

| Command | Aliases | Description |
|---------|---------|-------------|
| `make-directive {name}` | `create-directive`, `make-cmd` | Create a new directive class |
| `make-action {name} {--type=api}` | `create-action`, `make-act` | Create a new action class (api/web) |
| `make-task {name}` | `create-task`, `make-job` | Create a new task class |
| `make-repository {name}` | `create-repository`, `make-repo` | Create a new repository class |
| `make-record {name}` | `create-record`, `make-dto` | Create a new record class |
| `make-typed-collection {name} {--item-type}` | `create-collection`, `make-collection` | Create a new typed collection class |

### Global Options

| Option | Description |
|--------|-------------|
| `--list`, `-l` | List all available directives |
| `--help`, `-h` | Show help message |
| `--version`, `-v` | Show version information |

---

## Action Types

Actions support two types with different stub templates:

### API Type (default)

```php
<?php

namespace App\Actions\User;

use AndyDefer\Actions\AbstractAction;
use Illuminate\Http\JsonResponse;

final class ShowAction extends AbstractAction
{
    public function execute(): JsonResponse
    {
        return $this->json([
            'message' => 'Action executed successfully',
            'data' => null,
        ]);
    }
}
```

### Web Type (Inertia)

```php
<?php

namespace App\Actions\Admin;

use App\Http\Actions\AbstractAction;
use AndyDefer\Records\Contracts\Recordable;
use Inertia\Response as InertiaResponse;

final class DashboardAction extends AbstractAction
{
    protected function handle(Recordable $request): InertiaResponse
    {
        return $this->inertia('Dashboard', [
            'user' => auth()->user(),
        ]);
    }
}
```

---

## Smart Naming Convention

Directive Forge intelligently handles naming conversions:

| Input | Class Name | Signature |
|-------|------------|-----------|
| `user-list` | `UserListDirective` | `user-list` |
| `hello` | `HelloDirective` | `hello` |
| `hello-directive` | `HelloDirective` | `hello` |
| `ShowUserAction` | `ShowUserAction` | (preserved) |

### Path Processing

- Slashes (`/`) create subdirectories
- The last segment becomes the class name
- Segments are converted to PascalCase for subdirectories

```bash
# Input: user/domain/hello-directive
# Segments: ['user', 'domain']
# Class name: 'hello-directive'
# SubPath: 'User\Domain'
# Final class: 'HelloDirective'
```

---

## Testing

### Unit Tests

```bash
./vendor/bin/phpunit --filter ActionGeneratorTest
./vendor/bin/phpunit --filter DirectiveGeneratorTest
./vendor/bin/phpunit --filter RecordGeneratorTest
./vendor/bin/phpunit --filter RepositoryGeneratorTest
./vendor/bin/phpunit --filter TaskGeneratorTest
./vendor/bin/phpunit --filter TypedCollectionGeneratorTest
```

### Integration Tests

```bash
./vendor/bin/phpunit --filter FileCreationIntegrationTest
./vendor/bin/phpunt --filter DirectiveForgeIntegrationTest
```

### Run All Tests

```bash
./vendor/bin/phpunit
```

---

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    DIRECTIVE FORGE                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                   DIRECTIVES                         │   │
│  │  - MakeDirective                                     │   │
│  │  - MakeActionDirective                               │   │
│  │  - MakeTaskDirective                                 │   │
│  │  - MakeRepositoryDirective                           │   │
│  │  - MakeRecordDirective                               │   │
│  │  - MakeTypedCollectionDirective                      │   │
│  └─────────────────────────────────────────────────────┘   │
│                           │                               │
│                           ▼                               │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                   GENERATORS                         │   │
│  │  - AbstractGenerator (normalization, file creation) │   │
│  │  - DirectiveGenerator                               │   │
│  │  - ActionGenerator                                  │   │
│  │  - TaskGenerator                                    │   │
│  │  - RepositoryGenerator                              │   │
│  │  - RecordGenerator                                  │   │
│  │  - TypedCollectionGenerator                         │   │
│  └─────────────────────────────────────────────────────┘   │
│                           │                               │
│                           ▼                               │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                    STUBS                            │   │
│  │  - directive.stub                                   │   │
│  │  - action.stub / action.api.stub / action.web.stub │   │
│  │  - task.stub                                        │   │
│  │  - repository.stub                                  │   │
│  │  - record.stub                                      │   │
│  │  - typed-collection.stub                            │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### Key Components

| Component | Description |
|-----------|-------------|
| `BaseDirective` | Abstract directive with path parsing logic |
| `AbstractGenerator` | Base generator with file creation and name normalization |
| `GeneratorType` | Enum defining all generator configurations |
| `PathInfo` | Value object for path and namespace handling |
| `GeneratorConfig` | Immutable configuration for each generator type |

---

## Configuration

### Custom Stubs

You can publish and customize the stubs:

```bash
php artisan vendor:publish --tag=directive-forge-stubs
```

Stubs will be copied to `stubs/directive-forge/` in your project root.

### Custom Base Path

You can modify the default directories by extending the generator configuration in your service provider:

```php
// In your AppServiceProvider
use AndyDefer\DirectiveForge\Enums\GeneratorType;
use AndyDefer\DirectiveForge\ValueObjects\GeneratorConfig;

// Override configuration
GeneratorType::DIRECTIVE->getConfig()->basePath = '/app/Custom/Directives/';
```

---

## Examples

### Complete Workflow Example

```bash
# 1. Create a directive to import users
./vendor/bin/directive make-directive user/import

# 2. Create an action to process the import
./vendor/bin/directive make-action user/process-import --type=api

# 3. Create a task for batch processing
./vendor/bin/directive make-task user/send-notifications

# 4. Create a repository for user data access
./vendor/bin/directive make-repository user

# 5. Create a record for user data transfer
./vendor/bin/directive make-record user-data

# 6. Create a typed collection for user records
./vendor/bin/directive make-typed-collection user-collection --item-type=UserDataRecord
```

### Resulting Structure

```
app/
├── Directives/
│   └── User/
│       └── ImportDirective.php
├── Actions/
│   └── User/
│       └── ProcessImportAction.php
├── Tasks/
│   └── User/
│       └── SendNotificationsTask.php
├── Repositories/
│   └── UserRepository.php
├── Records/
│   └── UserDataRecord.php
└── Collections/
    └── UserCollection.php
```

---

## Extending Directive Forge

### Adding a New Generator Type

1. Add a new case to `GeneratorType` enum:

```php
case MY_TYPE = 'my-type';

public function getConfig(): GeneratorConfig
{
    return match ($this) {
        self::MY_TYPE => new GeneratorConfig(
            type: $this,
            basePath: '/app/MyTypes/',
            baseNamespace: 'App\\MyTypes',
            stubPath: __DIR__ . '/../../stubs/my-type.stub',
            suffix: 'MyType',
        ),
        // ...
    };
}
```

2. Create a generator class extending `AbstractGenerator`:

```php
final class MyTypeGenerator extends AbstractGenerator
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->type = GeneratorType::MY_TYPE;
    }

    public function getReplacements(PathInfo $pathInfo, ?string $type = null, ?string $itemType = null): array
    {
        return [
            '{{namespace}}' => $pathInfo->getNamespace($this->type->getConfig()->baseNamespace),
            '{{class}}' => $pathInfo->className,
        ];
    }
}
```

3. Create a directive class extending `BaseDirective`:

```php
final class MakeMyTypeDirective extends BaseDirective
{
    public function __construct(DirectiveInteractionService $interaction)
    {
        parent::__construct($interaction);
        $this->generator = new MyTypeGenerator($interaction);
    }

    public function getSignature(): string
    {
        return 'make-my-type {name}';
    }

    // ...
}
```

4. Register the directive in `DirectiveForgeServiceProvider`.

---

## License

MIT © [Andy Defer](https://github.com/andydefer)
```