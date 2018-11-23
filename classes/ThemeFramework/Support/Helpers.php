<?php

namespace Ecjia\App\Theme\ThemeFramework\Support;

use RC_Hook;

class Helpers
{

    /**
     *
     * Add framework element
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public static function cs_add_element( $field = array(), $value = '', $unique = '' )
    {
        return royalcms('ecjia.theme.framework')->getOptionField()->addElement( $field, $value, $unique );
    }

    /**
     *
     * Encode string for backup options
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public static function cs_encode_string( $string )
    {
        return rtrim( strtr( call_user_func( 'base'. '64' .'_encode', addslashes( gzcompress( serialize( $string ), 9 ) ) ), '+/', '-_' ), '=' );
    }

    /**
     *
     * Decode string for backup options
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public static function cs_decode_string( $string )
    {
        return unserialize( gzuncompress( stripslashes( call_user_func( 'base'. '64' .'_decode', rtrim( strtr( $string, '-_', '+/' ), '=' ) ) ) ) );
    }


    /**
     *
     * Get google font from json file
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public static function cs_get_google_fonts()
    {

        global $cs_google_fonts;

        if( ! empty( $cs_google_fonts ) ) {

            return $cs_google_fonts;

        } else {

            ob_start();
            cs_locate_template( 'fields/typography/google-fonts.json' );
            $json = ob_get_clean();

            $cs_google_fonts = json_decode( $json );

            return $cs_google_fonts;
        }

    }

    /**
     *
     * Get icon fonts from json file
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public static function cs_get_icon_fonts( $file )
    {

        ob_start();
        cs_locate_template( $file );
        $json = ob_get_clean();

        return json_decode( $json );

    }

    /**
     *
     * Array search key & value
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public static function cs_array_search( $array, $key, $value )
    {

        $results = array();

        if ( is_array( $array ) ) {
            if ( isset( $array[$key] ) && $array[$key] == $value ) {
                $results[] = $array;
            }

            foreach ( $array as $sub_array ) {
                $results = array_merge( $results, cs_array_search( $sub_array, $key, $value ) );
            }

        }

        return $results;

    }


    /**
     *
     * Getting POST Var
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public static function cs_get_var( $var, $default = '' )
    {

        if( isset( $_POST[$var] ) ) {
            return $_POST[$var];
        }

        if( isset( $_GET[$var] ) ) {
            return $_GET[$var];
        }

        return $default;

    }


    /**
     *
     * Getting POST Vars
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public static function cs_get_vars( $var, $depth, $default = '' )
    {

        if( isset( $_POST[$var][$depth] ) ) {
            return $_POST[$var][$depth];
        }

        if( isset( $_GET[$var][$depth] ) ) {
            return $_GET[$var][$depth];
        }

        return $default;

    }

    /**
     *
     * Load options fields
     *
     * @since 1.0.0
     * @version 1.0.0
     *
     */
    public static function cs_load_option_fields()
    {

        $located_fields = array();

        foreach ( glob( CS_DIR .'/fields/*/*.php' ) as $cs_field ) {
            $located_fields[] = basename( $cs_field );
            cs_locate_template( str_replace(  CS_DIR, '', $cs_field ) );
        }

        $override_name = apply_filters( 'cs_framework_override', 'cs-framework-override' );
        $override_dir  = get_template_directory() .'/'. $override_name .'/fields';

        if( is_dir( $override_dir ) ) {

            foreach ( glob( $override_dir .'/*/*.php' ) as $override_field ) {

                if( ! in_array( basename( $override_field ), $located_fields ) ) {

                    cs_locate_template( str_replace( $override_dir, '/fields', $override_field ) );

                }

            }

        }

        RC_Hook::do_action( 'cs_load_option_fields' );

    }

}
