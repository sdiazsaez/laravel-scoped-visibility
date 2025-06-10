<?php

namespace Cosmoscript\ScopedVisibility;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ScopedVisibilityScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (!method_exists($model, 'scopedFlags')) {
            return;
        }

        foreach ($model->scopedFlags() as $key => $filterCallback) {
            $identifier = $model->scopeIdentifier($key);

            if (!$model->isScopeOverridden($key)) {
                $builder->withGlobalScope($identifier, function ($builder) use ($filterCallback) {
                    $builder->addNestedWhereQuery($filterCallback()->getQuery(), 'and');
                });
            }
        }
    }
}
