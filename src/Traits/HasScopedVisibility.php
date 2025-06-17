<?php

namespace Cosmoscript\ScopedVisibility\Traits;

use Illuminate\Database\Eloquent\Builder;
use Cosmoscript\ScopedVisibility\ScopedVisibilityScope;

trait HasScopedVisibility {
    protected array $scopedVisibilityOverrides = [];

    public function isScopeOverridden(string $scopeKey): bool
    {
        return $this->scopedVisibilityOverrides[$scopeKey] ?? false;
    }

    public function scopeWithScope(Builder $query, string $scopeKey): Builder
    {
        $this->scopedVisibilityOverrides[$scopeKey] = true;

        return $query->withoutGlobalScope($this->scopeIdentifier($scopeKey));
    }

    public function scopeOnlyScope(Builder $query, string $scopeKey): Builder
    {
        $this->scopedVisibilityOverrides[$scopeKey] = true;

        return $query->withoutGlobalScope($this->scopeIdentifier($scopeKey))
            ->addNestedWhereQuery($this->scopedFlags()[$scopeKey]()->getQuery(), 'and');
    }

    public function scopeWithoutScopes(Builder $query): Builder
    {
        foreach (array_keys($this->scopedFlags()) as $key) {
            $query->withoutGlobalScope($this->scopeIdentifier($key));
        }

        return $query;
    }

    public function scopeIdentifier(string $key): string
    {
        return 'scoped_flag:' . $key;
    }

    
    public function scopeApplyVisibility(Builder $query, array $flags = []): Builder
    {
        $model = $query->getModel();
    
        if (!method_exists($model, 'scopedFlags')) {
            return $query;
        }
    
        $availableFlags = $model->scopedFlags();
        $validModes = ['only', 'with'];
    
        foreach ($flags as $key => $mode) {
            $mode = strtolower(trim($mode));
    
            if (!in_array($mode, $validModes, true)) {
                continue;
            }
    
            if (!array_key_exists($key, $availableFlags)) {
                continue;
            }
    
            $method = $mode . 'Scope';
    
            if (method_exists($query, $method)) {
                $query->{$method}($key);
            }
        }
    
        return $query;
    }



    /**
     * Each model using this trait must implement scopedFlags(), returning an array of [key => filterQuery closure].
     *
     * Example:
     *   return [
     *     'imported' => fn() => $this->query()->whereNotIn('id', ...)
     *   ];
     */
    abstract public function scopedFlags(): array;
}
