<?php

namespace Cosmoscript\ScopedVisibility;

use Larangular\Installable\Support\InstallableServiceProvider as ServiceProvider;
use Illuminate\Database\Eloquent\Builder;

class ScopedVisibilityServiceProvider extends ServiceProvider {

    protected $defer = false;

    public function boot(): void {
        
        Builder::macro('applyVisibility', function (array $flags = []) {
            if (! method_exists($this->getModel(), 'scopedFlags')) {
                return $this;
            }
        
            foreach ($flags as $key => $mode) {
                $mode = strtolower($mode);
        
                if (!in_array($mode, ['only', 'with'])) {
                    continue;
                }
        
                $method = $mode === 'only' ? 'onlyScope' : 'withScope';
        
                if (array_key_exists($key, $this->getModel()->scopedFlags())) {
                    $this->$method($key);
                }
            }
        
            return $this;
        });

    }

}
