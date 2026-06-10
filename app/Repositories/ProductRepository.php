<?php

declare(strict_types=1);

namespace App\Repositories;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\Repository\AbstractRepository;
use Illuminate\Database\Eloquent\Builder;

final class ProductRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(
            modelClass: $this->getModelClass(),
            recordClass: $this->getRecordClass(),
            filterRecordClass: $this->getFilterRecordClass()
        );
    }

    protected function getModelClass(): string
    {
        // TODO: Return the model class name
        // Example: return User::class;
        return 'App\\Models\\' . str_replace('Repository', '', class_basename($this));
    }

    protected function getRecordClass(): string
    {
        // TODO: Return the record class name
        // Example: return UserRecord::class;
        $baseName = str_replace('Repository', '', class_basename($this));
        return "App\\Records\\{$baseName}Record";
    }

    protected function getFilterRecordClass(): string
    {
        // TODO: Return the filter record class name
        // Example: return UserFilterRecord::class;
        $baseName = str_replace('Repository', '', class_basename($this));
        return "App\\Records\\{$baseName}FilterRecord";
    }

    protected function applyFilters(Builder $query, AbstractRecord $filters): void
    {
        $filterClass = $this->getFilterRecordClass();
        
        if (!$filters instanceof $filterClass) {
            return;
        }

        // Apply your filters here
       
    }
}