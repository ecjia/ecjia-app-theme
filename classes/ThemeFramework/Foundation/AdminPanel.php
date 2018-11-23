<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2018/11/20
 * Time: 09:41
 */

namespace Ecjia\App\Theme\ThemeFramework\Foundation;

use Ecjia\App\Theme\ThemeFramework\Support\Helpers;
use Ecjia\App\Theme\ThemeFramework\ThemeFrameworkAbstract;
use RC_Hook;
use RC_Uri;
use ecjia_theme_option;
use ecjia_theme_setting;

/**
 *
 * Framework Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
class AdminPanel extends ThemeFrameworkAbstract
{

    /**
     *
     * option database/data name
     * @access public
     * @var string
     *
     */
    public $unique = '_cs_options';

    /**
     *
     * settings
     * @access public
     * @var array
     *
     */
    public $settings = array();

    /**
     *
     * options tab
     * @access public
     * @var array
     *
     */
    public $options = array();

    /**
     *
     * options section
     * @access public
     * @var array
     *
     */
    public $sections = array();

    /**
     *
     * options store
     * @access public
     * @var array
*
*/
    public $theme_options = array();

    /**
     *
     * instance
     * @access private
     * @var class
     *
     */
    private static $instance = null;

    // instance
    public static function instance( $settings = array(), $options = array() )
    {
        if ( is_null( self::$instance ) && CS_ACTIVE_FRAMEWORK ) {
            self::$instance = new self( $settings, $options );
        }
        return self::$instance;
    }


    // run framework construct
    public function __construct( $settings, $options )
    {

        $this->settings = RC_Hook::apply_filters( 'cs_framework_settings', $settings );
        $this->options  = RC_Hook::apply_filters( 'cs_framework_options', $options );


        if( ! empty( $this->options ) ) {

            $this->sections   = $this->getSections();
            $this->theme_options = ecjia_theme_option::load_alloptions();

            $this->addAction('admin_theme_option_nav', 'display_setting_menus');
            $this->addAction('admin_theme_option_page', 'display_theme_option_page');
        }

//        $this->settings_api();

//        dd($this->sections);
//        dd(ecjia_theme_setting::get_registered_settings());
    }


    /**
     * get sections
     *
     * @return array
     */
    public function getSections()
    {

        $sections = array();

        foreach ( $this->options as $key => $value ) {

            if ( isset( $value['sections'] ) ) {

                foreach ( $value['sections'] as $section ) {

                    if ( isset( $section['fields'] ) ) {
                        $sections[$section['name']] = $section;
                    }

                }

            } else {

                if ( isset( $value['fields'] ) ) {
                    $sections[$value['name']] = $value;
                }

            }

        }

        return $sections;
    }

    /**
     * 获取某个section下的字段信息
     *
     * @param $name
     * @return mixed
     */
    public function getSection($name)
    {
        return array_get($this->sections, $name, []);
    }

    /**
     * 渲染主题选项菜单
     *
     * @param $name
     */
    public function display_setting_menus($name)
    {
        echo '<div class="setting-group">'.PHP_EOL;
        echo '<span class="setting-group-title"><i class="fontello-icon-cog"></i>'.$this->settings['menu_title'].'</span>'.PHP_EOL;
        echo '<ul class="nav nav-list m_t10">'.PHP_EOL;

        foreach ($this->sections as $section) {
            echo '<li><a class="data-pjax setting-group-item'; //data-pjax

            if ($name == $section['name']) {
                echo ' llv-active';
            }

            $url = RC_Uri::url('theme/admin_option/init', ['section' => $section['name']]);
            echo '" href="'.$url.'">' . $section['title'] . '</a></li>'.PHP_EOL;
        }


        echo '</ul>'.PHP_EOL;
        echo '</div>'.PHP_EOL;
    }

    /**
     * 渲染主题选项配置页面
     *
     * @param $name
     */
    public function display_theme_option_page($name)
    {
        $section = $this->getSection($name);

        $this->setSettingsFields($section);

        echo '<form method="post" class="form-horizontal" action="{$form_action}" name="theForm" >'.PHP_EOL;

        echo '<fieldset>'.PHP_EOL;

            echo '<div>'.PHP_EOL;
                echo '<h3 class="heading">';
                echo $section['title'];
                echo '</h3>'.PHP_EOL;
            echo '</div>'.PHP_EOL;

            $this->doSettingsFields($section);

            echo '<div class="control-group">'.PHP_EOL;
                echo '<div class="controls">'.PHP_EOL;
                    echo '<input type="submit" value="确定" class="btn btn-gebo" />'.PHP_EOL;
                echo '</div>'.PHP_EOL;
            echo '</div>'.PHP_EOL;

        echo '</fieldset>'.PHP_EOL;

        echo '</form>'.PHP_EOL;

    }


    protected function doSettingsFields(array $section)
    {
        $page = $section['name'] .'_section_group';

        ecjia_theme_setting::do_settings_sections($page);
    }

    /**
     * settings api
     */
    protected function setSettingsFields(array $section)
    {

        $defaults = array();

        ecjia_theme_setting::register_setting( $this->unique .'_group', $this->unique, array( &$this,'validate_save' ) );

        if ( isset( $section['fields'] ) ) {

            ecjia_theme_setting::add_settings_section( $section['name'] .'_section', $section['title'], '', $section['name'] .'_section_group' );

            foreach ( $section['fields'] as $field_key => $field ) {

                ecjia_theme_setting::add_settings_field( $field_key .'_field', '', array( &$this, 'fieldCallback' ), $section['name'] .'_section_group', $section['name'] .'_section', $field );

                // set default option if isset
                if ( isset( $field['default'] ) ) {
                    $defaults[$field['id']] = $field['default'];
                    if ( ! empty( $this->theme_options ) && ! isset( $this->theme_options[$field['id']] ) ) {
                        $this->theme_options[$field['id']] = $field['default'];
                    }
                }

            }
        }

        // set default variable if empty options and not empty defaults
        if( empty( $this->theme_options )  && ! empty( $defaults ) ) {
            $this->theme_options = $defaults;
        }

    }

    // section fields validate in save
    public function validate_save( $request )
    {

        $add_errors = array();
        $section_id = cs_get_var( 'cs_section_id' );

        // ignore nonce requests
        if ( isset( $request['_nonce'] ) ) {
            unset( $request['_nonce'] );
        }

        // import
        if ( isset( $request['import'] ) && ! empty( $request['import'] ) ) {
            $decode_string = cs_decode_string( $request['import'] );
            if ( is_array( $decode_string ) ) {
                return $decode_string;
            }
            $add_errors[] = $this->add_settings_error( __( 'Success. Imported backup options.', 'cs-framework' ), 'updated' );
        }

        // reset all options
        if ( isset( $request['resetall'] ) ) {
            $add_errors[] = $this->add_settings_error( __( 'Default options restored.', 'cs-framework' ), 'updated' );
            return;
        }

        // reset only section
        if ( isset( $request['reset'] ) && ! empty( $section_id ) ) {
            foreach ( $this->sections as $value ) {
                if ( $value['name'] == $section_id ) {
                    foreach ( $value['fields'] as $field ) {
                        if ( isset( $field['id'] ) ) {
                            if ( isset( $field['default'] ) ) {
                                $request[$field['id']] = $field['default'];
                            } else {
                                unset( $request[$field['id']] );
                            }
                        }
                    }
                }
            }
            $add_errors[] = $this->add_settings_error( __( 'Default options restored for only this section.', 'cs-framework' ), 'updated' );
        }

        // option sanitize and validate
        foreach ( $this->sections as $section ) {
            if ( isset( $section['fields'] ) ) {
                foreach( $section['fields'] as $field ) {

                    // ignore santize and validate if element multilangual
                    if ( isset( $field['type'] ) && ! isset( $field['multilang'] ) && isset( $field['id'] ) ) {

                        // sanitize options
                        $request_value = isset( $request[$field['id']] ) ? $request[$field['id']] : '';
                        $sanitize_type = $field['type'];

                        if ( isset( $field['sanitize'] ) ) {
                            $sanitize_type = ( $field['sanitize'] !== false ) ? $field['sanitize'] : false;
                        }

                        if ( $sanitize_type !== false && RC_Hook::has_filter( 'cs_sanitize_'. $sanitize_type ) ) {
                            $request[$field['id']] = RC_Hook::apply_filters( 'cs_sanitize_' . $sanitize_type, $request_value, $field, $section['fields'] );
                        }

                        // validate options
                        if ( isset( $field['validate'] ) && RC_Hook::has_filter( 'cs_validate_'. $field['validate'] ) ) {

                            $validate = RC_Hook::apply_filters( 'cs_validate_' . $field['validate'], $request_value, $field, $section['fields'] );

                            if ( ! empty( $validate ) ) {
                                $add_errors[] = $this->add_settings_error( $validate, 'error', $field['id'] );
                                $request[$field['id']] = ( isset( $this->get_option[$field['id']] ) ) ? $this->get_option[$field['id']] : '';
                            }

                        }

                    }

                    if ( ! isset( $field['id'] ) || empty( $request[$field['id']] ) ) {
                        continue;
                    }

                }
            }
        }

        $request = RC_Hook::apply_filters( 'cs_validate_save', $request );

        RC_Hook::do_action( 'cs_validate_save', $request );

        // set transient
        $transient_time = ( cs_language_defaults() !== false ) ? 30 : 10;
        set_transient( 'cs-framework-transient', array( 'errors' => $add_errors, 'section_id' => $section_id ), $transient_time );

        return $request;
    }

    /**
     * field callback classes
     *
     * @param $field
     */
    public function fieldCallback( $field )
    {
        $value = ( isset( $field['id'] ) && isset( $this->theme_options[$field['id']] ) ) ? $this->theme_options[$field['id']] : '';
        echo royalcms('ecjia.theme.framework')->getOptionField()->addElement( $field, $value, $this->unique );
    }

    public function add_settings_error( $message, $type = 'error', $id = 'global' )
    {
        return array( 'setting' => 'cs-errors', 'code' => $id, 'message' => $message, 'type' => $type );
    }



}