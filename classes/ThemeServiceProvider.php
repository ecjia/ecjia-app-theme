<?php

namespace Ecjia\App\Theme;

use Ecjia\App\Theme\ThemeOption\ThemeOption;
use Royalcms\Component\App\AppParentServiceProvider;

class ThemeServiceProvider extends  AppParentServiceProvider
{
    
    public function boot()
    {
        $this->package('ecjia/app-theme');
    }
    
    public function register()
    {
        $this->registerThemeOption();

        $this->loadAlias();
    }

    /**
     * Register the region
     * @return \Ecjia\App\Theme\ThemeOption\ThemeOption
     */
    public function registerThemeOption()
    {
        $this->royalcms->bindShared('ecjia.theme.option', function($royalcms){
            return new ThemeOption();
        });
    }

    /**
     * Load the alias = One less install step for the user
     */
    protected function loadAlias()
    {
        $this->royalcms->booting(function()
        {
            $loader = \Royalcms\Component\Foundation\AliasLoader::getInstance();
            $loader->alias('ecjia_theme_option', 'Ecjia\App\Theme\Facades\EcjiaThemeOption');
        });
    }
    
}