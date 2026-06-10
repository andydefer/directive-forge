# Directive Forge

**Directives de génération de code pour Laravel - forgez des directives, actions, tâches, repositories, records, collections typées, objets de valeur et classes de configuration.**

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x%20%7C%2013.x%20%7C%2014.x%20%7C%2015.x-blue)](https://laravel.com)
[![License](https://img.shields.io/badge/Licence-MIT-green)](LICENSE)

---

## Installation

```bash
composer require andydefer/directive-forge --dev
```

### Prérequis

- PHP 8.2 ou supérieur
- Laravel 12.x, 13.x, 14.x ou 15.x
- `andydefer/laravel-directive` ^2.1 (installé automatiquement)

### Service Provider (auto-découverte)

Le package utilise l'auto-découverte de Laravel. Le service provider sera automatiquement enregistré.

---

## Vue d'ensemble

Directive Forge fournit un ensemble de commandes CLI pour générer diverses classes PHP suivant les principes d'architecture propre. Il étend le package [Laravel Directive](https://github.com/andydefer/laravel-directive) pour offrir un échafaudage de code pour :

| Type | Description | Dossier | Suffixe |
|------|-------------|---------|---------|
| **Directive** | Commandes CLI pour votre application | `app/Directives/` | `Directive` |
| **Action** | Logique métier à responsabilité unique | `app/Actions/` | `Action` |
| **Requête (Request)** | Validation des données entrantes | `app/Http/Requests/` | `Request` |
| **Tâche (Task)** | Jobs en arrière-plan et tâches planifiées | `app/Tasks/` | `Task` |
| **Repository** | Couche d'accès aux données | `app/Repositories/` | `Repository` |
| **Record** | Objets de transfert de données typés | `app/Records/` | `Record` |
| **Collection Typée** | Collections type-safe | `app/Collections/` | `Collection` |
| **Objet de valeur (VO)** | Objets de valeur immutables du domaine | `app/ValueObjects/` | `VO` |
| **Configuration (Config)** | Classes de configuration | `app/Configs/` | `Config` |

---

## Utilisation

### Générer une Directive

```bash
./vendor/bin/directive make-directive user-list
```

Généré : `app/Directives/UserListDirective.php`

```php
<?php

declare(strict_types=1);

namespace App\Directives;

use AndyDefer\Directive\AbstractDirective;
use AndyDefer\Directive\Enums\ExitCode;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

final class UserListDirective extends AbstractDirective
{
    public function getSignature(): string
    {
        return 'user-list';
    }

    public function getDescription(): string
    {
        return 'Description pour user-list';
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
        $this->info('Directive exécutée avec succès !');
        
        return ExitCode::SUCCESS;
    }
}
```

### Générer une Action

```bash
# Créer uniquement l'Action
./vendor/bin/directive make-action user/show

# Créer l'Action, la Requête et le Record ensemble
./vendor/bin/directive make-action user/show --fully
```

**Sans `--fully`** - crée uniquement : `app/Actions/User/ShowAction.php`

**Avec `--fully`** - crée les trois fichiers :
- `app/Actions/User/ShowAction.php`
- `app/Http/Requests/User/ShowRequest.php`
- `app/Records/User/ShowRecord.php`

Action générée (`app/Actions/User/ShowAction.php`) :

```php
<?php

declare(strict_types=1);

namespace App\Actions\User;

use AndyDefer\Actions\Actions\AbstractAction;
use AndyDefer\Actions\Http\ResponseFactory;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class ShowAction extends AbstractAction
{
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        // TODO: Implémentez votre logique métier ici
        
        return ResponseFactory::json(['message' => 'Action exécutée avec succès']);
    }
}
```

### Générer une Requête (Request)

```bash
# Créer uniquement la Requête
./vendor/bin/directive make-request StoreUserRequest

# Créer la Requête et le Record ensemble
./vendor/bin/directive make-request StoreUserRequest --fully
```

**Sans `--fully`** - crée uniquement : `app/Http/Requests/StoreUserRequest.php`

**Avec `--fully`** - crée les deux fichiers :
- `app/Http/Requests/StoreUserRequest.php`
- `app/Records/StoreUserRecord.php`

Requête générée (`app/Http/Requests/StoreUserRequest.php`) :

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use AndyDefer\Actions\Http\Requests\AbstractRequest;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\EmptyRecord;

final class StoreUserRequest extends AbstractRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function getRecord(): AbstractRecord
    {
        return new EmptyRecord();
    }
}
```

### Générer une Tâche (Task)

```bash
./vendor/bin/directive make-task send-welcome-email
```

Généré : `app/Tasks/SendWelcomeEmailTask.php`

### Générer un Repository

```bash
./vendor/bin/directive make-repository user
```

Généré : `app/Repositories/UserRepository.php`

### Générer un Record

```bash
./vendor/bin/directive make-record user-data
```

Généré : `app/Records/UserDataRecord.php`

### Générer un Objet de valeur (Value Object)

```bash
./vendor/bin/directive make-vo EmailAddress
```

Généré : `app/ValueObjects/EmailAddressVO.php`

```php
<?php

declare(strict_types=1);

namespace App\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;

final class EmailAddressVO extends AbstractValueObject
{
    public function __construct(
        // TODO: Ajoutez les propriétés avec le mot-clé readonly
    ) {
        // TODO: Ajoutez la logique de validation
    }
}
```

### Générer une Classe de Configuration

```bash
./vendor/bin/directive make-config Database
```

Généré : `app/Configs/DatabaseConfig.php`

```php
<?php

declare(strict_types=1);

namespace App\Configs;

use AndyDefer\DomainStructures\Abstracts\AbstractConfig;

final class DatabaseConfig extends AbstractConfig
{
    // TODO: Ajoutez les méthodes de configuration
}
```

### Générer une Collection Typée

```bash
# Avec un type string
./vendor/bin/directive make-typed-collection user-collection --item-type=string

# Avec un type personnalisé (Record)
./vendor/bin/directive make-typed-collection user-collection --item-type=UserRecord
```

Généré : `app/Collections/UserCollection.php`

---

## Structure avec sous-dossiers

Vous pouvez organiser vos classes générées en utilisant des sous-dossiers :

```bash
# Directive avec sous-dossier
./vendor/bin/directive make-directive user/domain/hello-directive
# → app/Directives/User/Domain/HelloDirective.php
# → namespace App\Directives\User\Domain

# Action avec sous-dossier
./vendor/bin/directive make-action api/v1/users/show --fully
# → app/Actions/Api/V1/Users/ShowAction.php
# → app/Http/Requests/Api/V1/Users/ShowRequest.php
# → app/Records/Api/V1/Users/ShowRecord.php

# Tâche avec sous-dossier
./vendor/bin/directive make-task user/send-welcome-email
# → app/Tasks/User/SendWelcomeEmailTask.php

# Requête avec sous-dossier
./vendor/bin/directive make-request api/v1/StoreUserRequest --fully
# → app/Http/Requests/Api/V1/StoreUserRequest.php
# → app/Records/Api/V1/StoreUserRecord.php

# Objet de valeur avec sous-dossier
./vendor/bin/directive make-vo User/EmailAddress
# → app/ValueObjects/User/EmailAddressVO.php

# Configuration avec sous-dossier
./vendor/bin/directive make-config Database/Mysql
# → app/Configs/Database/MysqlConfig.php
```

---

## Référence des commandes

| Commande | Alias | Description |
|----------|-------|-------------|
| `make-directive {name}` | `create-directive`, `make-cmd` | Crée une nouvelle classe de directive |
| `make-action {name} {--fully}` | `create-action`, `make-act` | Crée une nouvelle classe d'action (avec `--fully` pour Requête + Record) |
| `make-request {name} {--fully}` | `create-request`, `make-req` | Crée une nouvelle classe de requête (avec `--fully` pour Record) |
| `make-task {name}` | `create-task`, `make-job` | Crée une nouvelle classe de tâche |
| `make-repository {name}` | `create-repository`, `make-repo` | Crée une nouvelle classe de repository |
| `make-record {name}` | `create-record`, `make-dto` | Crée une nouvelle classe de record |
| `make-vo {name}` | `create-vo`, `make-value-object` | Crée un nouvel objet de valeur (VO) |
| `make-config {name}` | `create-config`, `make-cfg` | Crée une nouvelle classe de configuration |
| `make-typed-collection {name} {--item-type}` | `create-collection`, `make-collection` | Crée une nouvelle collection typée |

### Options globales

| Option | Description |
|--------|-------------|
| `--list`, `-l` | Liste toutes les directives disponibles |
| `--help`, `-h` | Affiche l'aide |
| `--version`, `-v` | Affiche la version |

### Options spécifiques aux Actions

| Option | Description |
|--------|-------------|
| `--fully` | Crée également les classes Requête et Record associées |

### Options spécifiques aux Requêtes

| Option | Description |
|--------|-------------|
| `--fully` | Crée également la classe Record associée |

### Options pour les Collections typées

| Option | Description |
|--------|-------------|
| `--item-type` | Requis. Le type des éléments de la collection (ex: `string`, `UserRecord`) |

---

## Convention de nommage intelligente

Directive Forge gère intelligemment les conversions de noms :

| Entrée | Nom de classe | Signature |
|--------|---------------|-----------|
| `user-list` | `UserListDirective` | `user-list` |
| `hello` | `HelloDirective` | `hello` |
| `hello-directive` | `HelloDirective` | `hello` |
| `ShowUserAction` | `ShowUserAction` | (préservé) |
| `EmailAddress` | `EmailAddressVO` | (préservé) |
| `Database` | `DatabaseConfig` | (préservé) |

### Traitement des chemins

- Les slashes (`/`) créent des sous-dossiers
- Le dernier segment devient le nom de la classe
- Les segments sont convertis en PascalCase pour les sous-dossiers (ex: `user-profile` → `UserProfile`)

```bash
# Entrée : user-profile/update-email
# Segments : ['user-profile']
# Nom de classe : 'update-email'
# Sous-dossier : 'UserProfile'
# Classe finale : 'UpdateEmailAction'
```

---

## Tests

### Tests unitaires

```bash
./vendor/bin/phpunit --filter ActionGeneratorTest
./vendor/bin/phpunit --filter DirectiveGeneratorTest
./vendor/bin/phpunit --filter RecordGeneratorTest
./vendor/bin/phpunit --filter RepositoryGeneratorTest
./vendor/bin/phpunit --filter TaskGeneratorTest
./vendor/bin/phpunit --filter TypedCollectionGeneratorTest
./vendor/bin/phpunit --filter ValueObjectGeneratorTest
./vendor/bin/phpunit --filter ConfigGeneratorTest
```

### Tests d'intégration

```bash
./vendor/bin/phpunit --filter FileCreationIntegrationTest
./vendor/bin/phpunit --filter DirectiveForgeIntegrationTest
```

### Exécuter tous les tests

```bash
./vendor/bin/phpunit
```
---

### Composants clés

| Composant | Description |
|-----------|-------------|
| `BaseDirective` | Directive abstraite avec parsing et normalisation des chemins |
| `AbstractGenerator` | Générateur de base pour la création de fichiers |
| `GeneratorType` | Enum définissant toutes les configurations des générateurs |
| `PathInfo` | Objet de valeur pour la gestion des chemins et namespaces |
| `GeneratorConfig` | Configuration immuable pour chaque type de générateur |

---

## Configuration

### Stubs personnalisés

Vous pouvez publier et personnaliser les stubs :

```bash
php artisan vendor:publish --tag=directive-forge-stubs
```

Les stubs seront copiés dans `stubs/directive-forge/` à la racine de votre projet.

### Chemin de base personnalisé

Vous pouvez modifier les dossiers par défaut en modifiant la configuration du générateur :

```php
// Dans votre AppServiceProvider
use AndyDefer\DirectiveForge\Enums\GeneratorType;

// Surcharge de la configuration
GeneratorType::DIRECTIVE->getConfig()->basePath = '/app/Custom/Directives/';
```

---

## Exemples

### Flux de travail complet

```bash
# 1. Créer une directive pour importer des utilisateurs
./vendor/bin/directive make-directive user/import

# 2. Créer une action avec sa Requête et son Record
./vendor/bin/directive make-action user/process-import --fully

# 3. Créer une requête avec son Record
./vendor/bin/directive make-request api/v1/StoreUserRequest --fully

# 4. Créer une tâche pour le traitement par lots
./vendor/bin/directive make-task user/send-notifications

# 5. Créer un repository pour l'accès aux données
./vendor/bin/directive make-repository user

# 6. Créer un record pour le transfert de données
./vendor/bin/directive make-record user-data

# 7. Créer un objet de valeur
./vendor/bin/directive make-vo EmailAddress

# 8. Créer une classe de configuration
./vendor/bin/directive make-config Database

# 9. Créer une collection typée
./vendor/bin/directive make-typed-collection user-collection --item-type=UserDataRecord
```

### Structure résultante

```
app/
├── Directives/
│   └── User/
│       └── ImportDirective.php
├── Actions/
│   └── User/
│       └── ProcessImportAction.php
├── Http/
│   └── Requests/
│       ├── User/
│       │   └── ProcessImportRequest.php
│       └── Api/
│           └── V1/
│               └── StoreUserRequest.php
├── Tasks/
│   └── User/
│       └── SendNotificationsTask.php
├── Repositories/
│   └── UserRepository.php
├── Records/
│   ├── User/
│   │   └── ProcessImportRecord.php
│   ├── Api/
│   │   └── V1/
│   │       └── StoreUserRecord.php
│   └── UserDataRecord.php
├── ValueObjects/
│   └── EmailAddressVO.php
├── Configs/
│   └── DatabaseConfig.php
└── Collections/
    └── UserCollection.php
```

---

## Licence

MIT © [Andy Defer](https://github.com/andydefer)
