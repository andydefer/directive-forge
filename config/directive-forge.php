<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Mode d'exécution
    |--------------------------------------------------------------------------
    |
    | Détermine si le package fonctionne en mode application (app) ou
    | en mode bibliothèque (library).
    |
    | - 'app' : Les directives sont générées dans app/Directives
    | - 'library' : Les directives sont générées dans src/Directives
    |
    | Si non défini, le mode est détecté automatiquement :
    | - Si le dossier app/ existe → mode 'app'
    | - Si le dossier src/ existe → mode 'library'
    | - Si les deux existent → une exception est levée (spécifiez le mode)
    */
    'mode' => env('DIRECTIVE_MODE'),

    /*
    |--------------------------------------------------------------------------
    | Namespace de l'application
    |--------------------------------------------------------------------------
    |
    | Le namespace de base de l'application pour le mode 'app'.
    | Par défaut 'App', mais peut être personnalisé.
    |
    | Exemple: 'App' → 'App\Directives'
    |          'Hello' → 'Hello\Directives'
    |          'Company\MyApp' → 'Company\MyApp\Directives'
    */
    'namespace' => env('DIRECTIVE_NAMESPACE', 'App'),

    /*
    |--------------------------------------------------------------------------
    | Chemin personnalisé des stubs
    |--------------------------------------------------------------------------
    |
    | Chemin vers un dossier contenant des stubs personnalisés.
    | Si défini, les stubs sont chargés depuis ce dossier en priorité.
    |
    | Si non défini, les stubs du package sont utilisés.
    */
    'stubs_path' => env('DIRECTIVE_STUBS_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Extension par défaut
    |--------------------------------------------------------------------------
    |
    | Extension par défaut des fichiers générés.
    | Par défaut: 'php'
    */
    'extension' => env('DIRECTIVE_EXTENSION', 'php'),

    /*
    |--------------------------------------------------------------------------
    | Permission des répertoires
    |--------------------------------------------------------------------------
    |
    | Permission par défaut pour les répertoires créés.
    | Par défaut: 0755
    */
    'directory_permission' => (int) env('DIRECTIVE_DIRECTORY_PERMISSION', 0755),
];
