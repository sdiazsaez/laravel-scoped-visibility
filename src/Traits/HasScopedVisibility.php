<?php

namespace Cosmoscript\ScopedVisibility\Traits;

use Illuminate\Database\Eloquent\Builder;

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
        return $query->whereIn('id', $this->scopedFlags()[$scopeKey]());
    }

    public function scopeWithoutScope(Builder $query, string $scopeKey): Builder
    {
        $this->scopedVisibilityOverrides[$scopeKey] = true;
        return $query->whereNotIn('id', $this->scopedFlags()[$scopeKey]());
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

    public function scopeApplyVisibility(Builder $query, string|array $flags = []): Builder
    {
        $model = $query->getModel();

        if (!method_exists($model, 'scopedFlags')) {
            return $query;
        }

        $availableFlags = $model->scopedFlags();
        $validModes = ['only', 'with', 'without'];

        $normalized = [];

        // 1. Normalize input into flag => mode
        if (is_string($flags)) {
            if (str_contains($flags, '|')) {
                // single 'flag|mode' string
                [$flag, $mode] = array_map('trim', explode('|', $flags) + [null, null]);
                if ($flag && in_array($mode, $validModes, true)) {
                    $normalized[$flag] = $mode;
                }
            } elseif (in_array($flags, $validModes, true)) {
                // global mode: apply to all
                foreach (array_keys($availableFlags) as $flag) {
                    $normalized[$flag] = $flags;
                }
            }
        } elseif (is_array($flags)) {
            foreach ($flags as $key => $value) {
                // Handle both ['flag' => 'mode'] and ['flag|mode']
                if (is_int($key) && str_contains($value, '|')) {
                    [$flag, $mode] = array_map('trim', explode('|', $value) + [null, null]);
                } else {
                    $flag = trim((string) $key);
                    $mode = trim((string) $value);
                }

                if ($flag && in_array($mode, $validModes, true)) {
                    $normalized[$flag] = $mode;
                }
            }
        }

        // 2. Apply all scoped flags
        foreach ($normalized as $flag => $mode) {
            if (array_key_exists($flag, $availableFlags)) {
                $query->{$mode . 'Scope'}($flag);
            }
        }

        return $query;
    }


    /**
     * Each model using this trait must implement scopedFlags(), returning an array of [key => filterQuery closure].
     *
     * Each closure should return a base subquery that selects the IDs to filter by (e.g., `metable_id`).
     *
     * The trait will apply that subquery using whereIn or whereNotIn on the model's ID.
     *
     * Example:
     *   return [
     *     'imported' => fn() => Metadata::where([...])->select('metable_id')
     *   ];
     */

    abstract public function scopedFlags(): array;
}
