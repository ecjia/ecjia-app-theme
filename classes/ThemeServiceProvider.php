<?php

namespace Ecjia\App\Theme;

use Royalcms\Component\App\AppServiceProvider;

class ThemeServiceProvider extends  AppServiceProvider
{
    
    public function boot()
    {
        $this->package('ecjia/app-theme');
    }
    
    public function register()
    {
        
    }
    
    
    
}