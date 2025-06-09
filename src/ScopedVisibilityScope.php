<?php

namespace Cosmoscript\ScopedVisibility;

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
