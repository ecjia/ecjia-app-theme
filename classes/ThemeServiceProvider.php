<?php

namespace Ecjia\App\Theme;

use Ecjia\App\Theme\ThemeOption\ThemeOption;
use Ecjia\App\Theme\ThemeFramework\ThemeFramework;
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
     * Register the theme option
     * @return \Ecjia\App\Theme\ThemeOption\ThemeOption
     */
    public function registerThemeOption()
    {
        $this->royalcms->bindShared('ecjia.theme.option', function($royalcms){
            return new ThemeOption();
        });
    }


    /**
     * Register the theme framework
     * @return \Ecjia\App\Theme\ThemeFramework\ThemeFramework
     */
    public function registerThemeFramework()
    {
        $this->royalcms->bindShared('ecjia.theme.framework', function($royalcms){
            return new ThemeFramework();
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
            $loader->alias('ecjia_theme_framework', 'Ecjia\App\Theme\Facades\EcjiaThemeFramework');
        });
    }
    
}