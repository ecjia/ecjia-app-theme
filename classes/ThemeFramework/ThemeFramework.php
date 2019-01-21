<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2018/11/20
 * Time: 14:49
 */

namespace Ecjia\App\Theme\ThemeFramework;

use Ecjia\App\Theme\ThemeFramework\Foundation\AdminPanel;
use Ecjia\App\Theme\ThemeFramework\Foundation\Metabox;
use Ecjia\App\Theme\ThemeFramework\Foundation\ShortcodeManager;
use Ecjia\App\Theme\ThemeFramework\Foundation\Taxonomy;
use RC_Hook;
use ecjia_theme_option;

class ThemeFramework
{

    protected $customize_options_key;

    protected $option_field;

    protected $app_dir;
    protected $statics_dir;

    /**
     * 主题框架配置项集合
     * @var \Royalcms\Component\Support\Collection
     */
    protected $config;

    public function __construct()
    {
        // active modules
        defined( 'CS_ACTIVE_FRAMEWORK' )  or  define( 'CS_ACTIVE_FRAMEWORK',  true );
        defined( 'CS_ACTIVE_METABOX'   )  or  define( 'CS_ACTIVE_METABOX',    true );
        defined( 'CS_ACTIVE_TAXONOMY'   ) or  define( 'CS_ACTIVE_TAXONOMY',   true );
        defined( 'CS_ACTIVE_SHORTCODE' )  or  define( 'CS_ACTIVE_SHORTCODE',  true );
        defined( 'CS_ACTIVE_CUSTOMIZE' )  or  define( 'CS_ACTIVE_CUSTOMIZE',  true );

        $this->app_dir = dirname(dirname(dirname(__FILE__)));
        $this->statics_dir = dirname(dirname(dirname(__FILE__))) . '/statics';

        $this->customize_options_key = '_cs_customize_options';

        $this->option_field = new OptionField($this);

    }

    public function getOptionField()
    {
        return $this->option_field;
    }

    public function getAppDir()
    {
        return $this->app_dir;
    }

    public function getStaticsDir()
    {
        return $this->statics_dir;
    }

    public function getStaticsUrl()
    {
        return \RC_App::apps_url('', $this->statics_dir) . '/statics';
    }

    /**
     *
     * Get option
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public function get_option( $option_name, $default = null )
    {
        return ecjia_theme_option::get_option($option_name, $default);
    }

    /**
     *
     * Set option
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public function set_option( $option_name, $new_value )
    {
        return ecjia_theme_option::update_option($option_name, $new_value);
    }

    /**
     *
     * Get all option
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public function get_all_option()
    {
        return ecjia_theme_option::load_alloptions();
    }


    /**
     *
     * Get custom option
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public function get_customize_option( $option_name, $default = null )
    {

        $options = RC_Hook::apply_filters( 'cs_get_customize_option', ecjia_theme_option::get_option( $this->customize_options_key ), $option_name, $default );

        if ( ! empty( $option_name ) && ! empty( $options[$option_name] ) ) {
            return $options[$option_name];
        } else {
            return ( ! empty( $default ) ) ? $default : null;
        }

    }

    /**
     *
     * Set custom option
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public function set_customize_option( $option_name, $new_value )
    {

        $options = RC_Hook::apply_filters( 'cs_set_customize_option', ecjia_theme_option::get_option( $this->customize_options_key ), $option_name, $new_value );

        if ( ! empty( $option_name ) ) {
            $options[$option_name] = $new_value;
            ecjia_theme_option::update_option( $this->customize_options_key, $options );
        }

    }

    /**
     *
     * Get all custom option
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public function get_all_customize_option()
    {
        return ecjia_theme_option::get_option( $this->customize_options_key );
    }


    /**
     *
     * Get language defaults
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public function language_defaults()
    {

        $multilang = array();

        $multilang = RC_Hook::apply_filters( 'cs_language_defaults', $multilang );

        return ( ! empty( $multilang ) ) ? $multilang : false;

    }

    /**
     *
     * Multi language option
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public function get_multilang_option( $option_name, $default = null )
    {

        $value     = $this->get_option( $option_name, $default );
        $languages = $this->language_defaults();
        $default   = $languages['default'];
        $current   = $languages['current'];

        if ( is_array( $value ) && is_array( $languages ) && isset( $value[$current] ) ) {
            return  $value[$current];
        } else if ( $default != $current ) {
            return  '';
        }

        return $value;

    }

    /**
     *
     * Multi language value
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public function get_multilang_value( $value, $default = null )
    {

        $languages = $this->language_defaults();
        $default   = $languages['default'];
        $current   = $languages['current'];

        if ( is_array( $value ) && is_array( $languages ) && isset( $value[$current] ) ) {
            return  $value[$current];
        } else if ( $default != $current ) {
            return  '';
        }

        return $value;

    }


    /**
     *
     * Get locate for load textdomain
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public function get_locale()
    {

        $db_locale = config('system.locale');

        if ( $db_locale !== false ) {
            $locale = $db_locale;
        }

        if ( empty( $locale ) ) {
            $locale = 'zh_CN';
        }

        return RC_Hook::apply_filters( 'locale', $locale );

    }

    public function createAdminPanelInstance(array $settings = array(), array $options = array())
    {
        if (empty($settings)) {
            $settings = config('app-theme::settings');
        }

        if (empty($options)) {
            $options = config('app-theme::framework');
        }

        $instance = AdminPanel::instance($this, $settings, $options);
        return $instance;
    }


    public function createMetaboxInstance($options = array())
    {
        if (empty($options)) {
            $options = config('app-theme::metabox');
        }

        $instance = Metabox::instance($this, $options);
        return $instance;
    }

    public function createShortcodeManagerInstance($options = array())
    {
        if (empty($options)) {
            $options = config('app-theme::shortcode');
        }

        $instance = ShortcodeManager::instance($this, $options);
        return $instance;
    }

    public function createTaxonomyInstance($options = array())
    {

        if (empty($options)) {
            $options = config('app-theme::taxonomy');
        }

        $instance = Taxonomy::instance($this, $options);
        return $instance;
    }

}