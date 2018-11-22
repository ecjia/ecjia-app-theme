<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2018/11/20
 * Time: 14:49
 */

namespace Ecjia\App\Theme\ThemeFramework;

use RC_Hook;
use RC_Format;
use ecjia_theme_option;

class ThemeFramework
{

    protected $customize_options_key;


    public function __construct()
    {
        $this->customize_options_key = '_cs_customize_options';



    }

    /**
     *
     * Framework path finder
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public function cs_get_path_locate()
    {

        $dirname        = RC_Format::normalize_path( dirname( __FILE__ ) );
        $plugin_dir     = RC_Format::normalize_path( WP_PLUGIN_DIR );
        $located_plugin = ( preg_match( '#'. $plugin_dir .'#', $dirname ) ) ? true : false;
        $directory      = ( $located_plugin ) ? $plugin_dir : get_template_directory();
        $directory_uri  = ( $located_plugin ) ? WP_PLUGIN_URL : get_template_directory_uri();
        $basename       = str_replace( RC_Format::normalize_path( $directory ), '', $dirname );
        $dir            = $directory . $basename;
        $uri            = $directory_uri . $basename;

        return RC_Hook::apply_filters( 'cs_get_path_locate', array(
            'basename' => RC_Format::normalize_path( $basename ),
            'dir'      => RC_Format::normalize_path( $dir ),
            'uri'      => $uri
        ) );

    }


    /**
     *
     * Framework locate template and override files
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public function cs_locate_template( $template_name )
    {

        $located      = '';
        $override     = RC_Hook::apply_filters( 'cs_framework_override', 'cs-framework-override' );
        $dir_plugin   = WP_PLUGIN_DIR;
        $dir_theme    = get_template_directory();
        $dir_child    = get_stylesheet_directory();
        $dir_override = '/'. $override .'/'. $template_name;
        $dir_template = CS_BASENAME .'/'. $template_name;

        // child theme override
        $child_force_overide    = $dir_child . $dir_override;
        $child_normal_override  = $dir_child . $dir_template;

        // theme override paths
        $theme_force_override   = $dir_theme . $dir_override;
        $theme_normal_override  = $dir_theme . $dir_template;

        // plugin override
        $plugin_force_override  = $dir_plugin . $dir_override;
        $plugin_normal_override = $dir_plugin . $dir_template;

        if ( file_exists( $child_force_overide ) ) {

            $located = $child_force_overide;

        } else if ( file_exists( $child_normal_override ) ) {

            $located = $child_normal_override;

        } else if ( file_exists( $theme_force_override ) ) {

            $located = $theme_force_override;

        } else if ( file_exists( $theme_normal_override ) ) {

            $located = $theme_normal_override;

        } else if ( file_exists( $plugin_force_override ) ) {

            $located =  $plugin_force_override;

        } else if ( file_exists( $plugin_normal_override ) ) {

            $located =  $plugin_normal_override;
        }

        $located = RC_Hook::apply_filters( 'cs_locate_template', $located, $template_name );

        if ( ! empty( $located ) ) {
            load_template( $located, true );
        }

        return $located;

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
    public function cs_get_multilang_value( $value, $default = null )
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

}