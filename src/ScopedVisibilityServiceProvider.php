<?php

namespace Cosmoscript\ScopedVisibility;

use Larangular\Installable\Support\InstallableServiceProvider as ServiceProvider;
use Illuminate\Database\Eloquent\Builder;

class ScopedVisibilityServiceProvider extends ServiceProvider {

    protected $defer = false;

    public function boot(): void {
    }

}
