<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2018/11/20
 * Time: 18:23
 */

namespace Ecjia\App\Theme\ThemeOption;

use Ecjia\App\Theme\ThemeOption\Repositories\TemplateOptionsRepository;
use RC_Hook;

class ThemeOption
{
    protected $repository;

    public function __construct($repository = null)
    {
        if (is_null($repository)) {
            $this->repository = new TemplateOptionsRepository();
        } else {
            $this->repository = $repository;
        }

    }


    /**
     * Retrieves an option value based on an option name.
     *
     * If the option does not exist or does not have a value, then the return value
     * will be false. This is useful to check whether you need to install an option
     * and is commonly used during installation of plugin options and to test
     * whether upgrading is required.
     *
     * If the option was serialized then it will be unserialized when it is returned.
     *
     * Any scalar values will be returned as strings. You may coerce the return type of
     * a given option by registering an {@see 'option_$option'} filter callback.
     *
     * @since 1.5.0
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @param string $option  Name of option to retrieve. Expected to not be SQL-escaped.
     * @param mixed  $default Optional. Default value to return if the option does not exist.
     * @return mixed Value set for the option.
     */
    public function get_option( $option, $default = false )
    {
        $option = trim( $option );
        if ( empty( $option ) )
            return false;

        /**
         * Filters the value of an existing option before it is retrieved.
         *
         * The dynamic portion of the hook name, `$option`, refers to the option name.
         *
         * Passing a truthy value to the filter will short-circuit retrieving
         * the option value, returning the passed value instead.
         *
         * @since 1.5.0
         * @since 4.4.0 The `$option` parameter was added.
         * @since 4.9.0 The `$default` parameter was added.
         *
         *
         * @param bool|mixed $pre_option The value to return instead of the option value. This differs from
         *                               `$default`, which is used as the fallback value in the event the option
         *                               doesn't exist elsewhere in get_option(). Default false (to skip past the
         *                               short-circuit).
         * @param string     $option     Option name.
         * @param mixed      $default    The fallback value to return if the option does not exist.
         *                               Default is false.
         */
        $pre = RC_Hook::apply_filters( "pre_option_{$option}", false, $option, $default );

        if ( false !== $pre )
            return $pre;

        // Distinguish between `false` as a default, and not passing one.
        $passed_default = func_num_args() > 1;

        // prevent non-existent options from triggering multiple queries
        $notoptions = wp_cache_get( 'notoptions', 'options' );
        if ( isset( $notoptions[ $option ] ) ) {
            /**
             * Filters the default value for an option.
             *
             * The dynamic portion of the hook name, `$option`, refers to the option name.
             *
             * @since 3.4.0
             * @since 4.4.0 The `$option` parameter was added.
             * @since 4.7.0 The `$passed_default` parameter was added to distinguish between a `false` value and the default parameter value.
             *
             * @param mixed  $default The default value to return if the option does not exist
             *                        in the database.
             * @param string $option  Option name.
             * @param bool   $passed_default Was `get_option()` passed a default value?
             */
            return RC_Hook::apply_filters( "default_option_{$option}", $default, $option, $passed_default );
        }

        $alloptions = wp_load_alloptions();

        if ( isset( $alloptions[$option] ) ) {
            $value = $alloptions[$option];
        } else {
            $value = wp_cache_get( $option, 'options' );

            if ( false === $value ) {
//                $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );
                $row = $this->repository->getOption($option);

                // Has to be get_row instead of get_var because of funkiness with 0, false, null values
                if ( is_object( $row ) ) {
                    $value = $row->option_value;
                    wp_cache_add( $option, $value, 'options' );
                } else { // option does not exist, so we must cache its non-existence
                    if ( ! is_array( $notoptions ) ) {
                        $notoptions = array();
                    }
                    $notoptions[$option] = true;
                    wp_cache_set( 'notoptions', $notoptions, 'options' );

                    /** This filter is documented in wp-includes/option.php */
                    return RC_Hook::apply_filters( "default_option_{$option}", $default, $option, $passed_default );
                }
            }
        }

        /**
         * Filters the value of an existing option.
         *
         * The dynamic portion of the hook name, `$option`, refers to the option name.
         *
         * @since 1.5.0 As 'option_' . $setting
         * @since 3.0.0
         * @since 4.4.0 The `$option` parameter was added.
         *
         * @param mixed  $value  Value of the option. If stored serialized, it will be
         *                       unserialized prior to being returned.
         * @param string $option Option name.
         */
        return RC_Hook::apply_filters( "option_{$option}", maybe_unserialize( $value ), $option );
    }


    /**
     * Protect WordPress special option from being modified.
     *
     * Will die if $option is in protected list. Protected options are 'alloptions'
     * and 'notoptions' options.
     *
     * @since 2.2.0
     *
     * @param string $option Option name.
     */
    public function protect_special_option( $option )
    {
        if ( 'alloptions' === $option || 'notoptions' === $option )
            wp_die( sprintf( __( '%s is a protected WP option and may not be modified' ), esc_html( $option ) ) );
    }

    /**
     * Print option value after sanitizing for forms.
     *
     * @since 1.5.0
     *
     * @param string $option Option name.
     */
    public function form_option( $option )
    {
        echo esc_attr( $this->get_option( $option ) );
    }


    /**
     * Loads and caches all autoloaded options, if available or all options.
     *
     * @since 2.2.0
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @return array List of all options.
     */
    public function wp_load_alloptions()
    {
        global $wpdb;

        if ( ! wp_installing() || ! is_multisite() ) {
            $alloptions = wp_cache_get( 'alloptions', 'options' );
        } else {
            $alloptions = false;
        }

        if ( ! $alloptions ) {
            $suppress = $wpdb->suppress_errors();
            if ( ! $alloptions_db = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE autoload = 'yes'" ) ) {
                $alloptions_db = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options" );
            }
            $wpdb->suppress_errors( $suppress );

            $alloptions = array();
            foreach ( (array) $alloptions_db as $o ) {
                $alloptions[$o->option_name] = $o->option_value;
            }

            if ( ! wp_installing() || ! is_multisite() ) {
                /**
                 * Filters all options before caching them.
                 *
                 * @since 4.9.0
                 *
                 * @param array $alloptions Array with all options.
                 */
                $alloptions = RC_Hook::apply_filters( 'pre_cache_alloptions', $alloptions );
                wp_cache_add( 'alloptions', $alloptions, 'options' );
            }
        }

        /**
         * Filters all options after retrieving them.
         *
         * @since 4.9.0
         *
         * @param array $alloptions Array with all options.
         */
        return RC_Hook::apply_filters( 'alloptions', $alloptions );
    }


    /**
     * Update the value of an option that was already added.
     *
     * You do not need to serialize values. If the value needs to be serialized, then
     * it will be serialized before it is inserted into the database. Remember,
     * resources can not be serialized or added as an option.
     *
     * If the option does not exist, then the option will be added with the option value,
     * with an `$autoload` value of 'yes'.
     *
     * @since 1.0.0
     * @since 4.2.0 The `$autoload` parameter was added.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @param string      $option   Option name. Expected to not be SQL-escaped.
     * @param mixed       $value    Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
     * @param string|bool $autoload Optional. Whether to load the option when WordPress starts up. For existing options,
     *                              `$autoload` can only be updated using `update_option()` if `$value` is also changed.
     *                              Accepts 'yes'|true to enable or 'no'|false to disable. For non-existent options,
     *                              the default value is 'yes'. Default null.
     * @return bool False if value was not updated and true if value was updated.
     */
    public function update_option( $option, $value, $autoload = null )
    {
        global $wpdb;

        $option = trim($option);
        if ( empty($option) )
            return false;

        wp_protect_special_option( $option );

        if ( is_object( $value ) )
            $value = clone $value;

        $value = sanitize_option( $option, $value );
        $old_value = $this->get_option( $option );

        /**
         * Filters a specific option before its value is (maybe) serialized and updated.
         *
         * The dynamic portion of the hook name, `$option`, refers to the option name.
         *
         * @since 2.6.0
         * @since 4.4.0 The `$option` parameter was added.
         *
         * @param mixed  $value     The new, unserialized option value.
         * @param mixed  $old_value The old option value.
         * @param string $option    Option name.
         */
        $value = RC_Hook::apply_filters( "pre_update_option_{$option}", $value, $old_value, $option );

        /**
         * Filters an option before its value is (maybe) serialized and updated.
         *
         * @since 3.9.0
         *
         * @param mixed  $value     The new, unserialized option value.
         * @param string $option    Name of the option.
         * @param mixed  $old_value The old option value.
         */
        $value = RC_Hook::apply_filters( 'pre_update_option', $value, $option, $old_value );

        /*
         * If the new and old values are the same, no need to update.
         *
         * Unserialized values will be adequate in most cases. If the unserialized
         * data differs, the (maybe) serialized data is checked to avoid
         * unnecessary database calls for otherwise identical object instances.
         *
         * See https://core.trac.wordpress.org/ticket/38903
         */
        if ( $value === $old_value || maybe_serialize( $value ) === maybe_serialize( $old_value ) ) {
            return false;
        }

        /** This filter is documented in wp-includes/option.php */
        if ( RC_Hook::apply_filters( "default_option_{$option}", false, $option, false ) === $old_value ) {
            // Default setting for new options is 'yes'.
            if ( null === $autoload ) {
                $autoload = 'yes';
            }

            return $this->add_option( $option, $value, '', $autoload );
        }

        $serialized_value = maybe_serialize( $value );

        /**
         * Fires immediately before an option value is updated.
         *
         * @since 2.9.0
         *
         * @param string $option    Name of the option to update.
         * @param mixed  $old_value The old option value.
         * @param mixed  $value     The new option value.
         */
        RC_Hook::do_action( 'update_option', $option, $old_value, $value );

        $update_args = array(
            'option_value' => $serialized_value,
        );

        if ( null !== $autoload ) {
            $update_args['autoload'] = ( 'no' === $autoload || false === $autoload ) ? 'no' : 'yes';
        }

        $result = $wpdb->update( $wpdb->options, $update_args, array( 'option_name' => $option ) );
        if ( ! $result )
            return false;

        $notoptions = wp_cache_get( 'notoptions', 'options' );
        if ( is_array( $notoptions ) && isset( $notoptions[$option] ) ) {
            unset( $notoptions[$option] );
            wp_cache_set( 'notoptions', $notoptions, 'options' );
        }

        if ( ! wp_installing() ) {
            $alloptions = wp_load_alloptions();
            if ( isset( $alloptions[$option] ) ) {
                $alloptions[ $option ] = $serialized_value;
                wp_cache_set( 'alloptions', $alloptions, 'options' );
            } else {
                wp_cache_set( $option, $serialized_value, 'options' );
            }
        }

        /**
         * Fires after the value of a specific option has been successfully updated.
         *
         * The dynamic portion of the hook name, `$option`, refers to the option name.
         *
         * @since 2.0.1
         * @since 4.4.0 The `$option` parameter was added.
         *
         * @param mixed  $old_value The old option value.
         * @param mixed  $value     The new option value.
         * @param string $option    Option name.
         */
        RC_Hook::do_action( "update_option_{$option}", $old_value, $value, $option );

        /**
         * Fires after the value of an option has been successfully updated.
         *
         * @since 2.9.0
         *
         * @param string $option    Name of the updated option.
         * @param mixed  $old_value The old option value.
         * @param mixed  $value     The new option value.
         */
        RC_Hook::do_action( 'updated_option', $option, $old_value, $value );
        return true;
    }


    /**
     * Add a new option.
     *
     * You do not need to serialize values. If the value needs to be serialized, then
     * it will be serialized before it is inserted into the database. Remember,
     * resources can not be serialized or added as an option.
     *
     * You can create options without values and then update the values later.
     * Existing options will not be updated and checks are performed to ensure that you
     * aren't adding a protected WordPress option. Care should be taken to not name
     * options the same as the ones which are protected.
     *
     * @since 1.0.0
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @param string         $option      Name of option to add. Expected to not be SQL-escaped.
     * @param mixed          $value       Optional. Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
     * @param string         $deprecated  Optional. Description. Not used anymore.
     * @param string|bool    $autoload    Optional. Whether to load the option when WordPress starts up.
     *                                    Default is enabled. Accepts 'no' to disable for legacy reasons.
     * @return bool False if option was not added and true if option was added.
     */
    public function add_option( $option, $value = '', $deprecated = '', $autoload = 'yes' )
    {
        global $wpdb;

        if ( !empty( $deprecated ) )
            _deprecated_argument( __FUNCTION__, '2.3.0' );

        $option = trim($option);
        if ( empty($option) )
            return false;

        wp_protect_special_option( $option );

        if ( is_object($value) )
            $value = clone $value;

        $value = sanitize_option( $option, $value );

        // Make sure the option doesn't already exist. We can check the 'notoptions' cache before we ask for a db query
        $notoptions = wp_cache_get( 'notoptions', 'options' );
        if ( !is_array( $notoptions ) || !isset( $notoptions[$option] ) )
            /** This filter is documented in wp-includes/option.php */
            if ( RC_Hook::apply_filters( "default_option_{$option}", false, $option, false ) !== $this->get_option( $option ) )
                return false;

        $serialized_value = maybe_serialize( $value );
        $autoload = ( 'no' === $autoload || false === $autoload ) ? 'no' : 'yes';

        /**
         * Fires before an option is added.
         *
         * @since 2.9.0
         *
         * @param string $option Name of the option to add.
         * @param mixed  $value  Value of the option.
         */
        RC_Hook::do_action( 'add_option', $option, $value );

        $result = $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $serialized_value, $autoload ) );
        if ( ! $result )
            return false;

        if ( ! wp_installing() ) {
            if ( 'yes' == $autoload ) {
                $alloptions = wp_load_alloptions();
                $alloptions[ $option ] = $serialized_value;
                wp_cache_set( 'alloptions', $alloptions, 'options' );
            } else {
                wp_cache_set( $option, $serialized_value, 'options' );
            }
        }

        // This option exists now
        $notoptions = wp_cache_get( 'notoptions', 'options' ); // yes, again... we need it to be fresh
        if ( is_array( $notoptions ) && isset( $notoptions[$option] ) ) {
            unset( $notoptions[$option] );
            wp_cache_set( 'notoptions', $notoptions, 'options' );
        }

        /**
         * Fires after a specific option has been added.
         *
         * The dynamic portion of the hook name, `$option`, refers to the option name.
         *
         * @since 2.5.0 As "add_option_{$name}"
         * @since 3.0.0
         *
         * @param string $option Name of the option to add.
         * @param mixed  $value  Value of the option.
         */
        RC_Hook::do_action( "add_option_{$option}", $option, $value );

        /**
         * Fires after an option has been added.
         *
         * @since 2.9.0
         *
         * @param string $option Name of the added option.
         * @param mixed  $value  Value of the option.
         */
        RC_Hook::do_action( 'added_option', $option, $value );
        return true;
    }


    /**
     * Removes option by name. Prevents removal of protected WordPress options.
     *
     * @since 1.2.0
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @param string $option Name of option to remove. Expected to not be SQL-escaped.
     * @return bool True, if option is successfully deleted. False on failure.
     */
    public function delete_option( $option )
    {
        global $wpdb;

        $option = trim( $option );
        if ( empty( $option ) )
            return false;

        wp_protect_special_option( $option );

        // Get the ID, if no ID then return
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s", $option ) );
        if ( is_null( $row ) )
            return false;

        /**
         * Fires immediately before an option is deleted.
         *
         * @since 2.9.0
         *
         * @param string $option Name of the option to delete.
         */
        RC_Hook::do_action( 'delete_option', $option );

        $result = $wpdb->delete( $wpdb->options, array( 'option_name' => $option ) );
        if ( ! wp_installing() ) {
            if ( 'yes' == $row->autoload ) {
                $alloptions = wp_load_alloptions();
                if ( is_array( $alloptions ) && isset( $alloptions[$option] ) ) {
                    unset( $alloptions[$option] );
                    wp_cache_set( 'alloptions', $alloptions, 'options' );
                }
            } else {
                wp_cache_delete( $option, 'options' );
            }
        }
        if ( $result ) {

            /**
             * Fires after a specific option has been deleted.
             *
             * The dynamic portion of the hook name, `$option`, refers to the option name.
             *
             * @since 3.0.0
             *
             * @param string $option Name of the deleted option.
             */
            RC_Hook::do_action( "delete_option_{$option}", $option );

            /**
             * Fires after an option has been deleted.
             *
             * @since 2.9.0
             *
             * @param string $option Name of the deleted option.
             */
            RC_Hook::do_action( 'deleted_option', $option );
            return true;
        }
        return false;
    }


    /**
     * Delete a transient.
     *
     * @since 2.8.0
     *
     * @param string $transient Transient name. Expected to not be SQL-escaped.
     * @return bool true if successful, false otherwise
     */
    public function delete_transient( $transient )
    {

        /**
         * Fires immediately before a specific transient is deleted.
         *
         * The dynamic portion of the hook name, `$transient`, refers to the transient name.
         *
         * @since 3.0.0
         *
         * @param string $transient Transient name.
         */
        RC_Hook::do_action( "delete_transient_{$transient}", $transient );

        if ( wp_using_ext_object_cache() ) {
            $result = wp_cache_delete( $transient, 'transient' );
        } else {
            $option_timeout = '_transient_timeout_' . $transient;
            $option = '_transient_' . $transient;
            $result = $this->delete_option( $option );
            if ( $result )
                $this->delete_option( $option_timeout );
        }

        if ( $result ) {

            /**
             * Fires after a transient is deleted.
             *
             * @since 3.0.0
             *
             * @param string $transient Deleted transient name.
             */
            RC_Hook::do_action( 'deleted_transient', $transient );
        }

        return $result;
    }

    /**
     * Get the value of a transient.
     *
     * If the transient does not exist, does not have a value, or has expired,
     * then the return value will be false.
     *
     * @since 2.8.0
     *
     * @param string $transient Transient name. Expected to not be SQL-escaped.
     * @return mixed Value of transient.
     */
    public function get_transient( $transient )
    {

        /**
         * Filters the value of an existing transient.
         *
         * The dynamic portion of the hook name, `$transient`, refers to the transient name.
         *
         * Passing a truthy value to the filter will effectively short-circuit retrieval
         * of the transient, returning the passed value instead.
         *
         * @since 2.8.0
         * @since 4.4.0 The `$transient` parameter was added
         *
         * @param mixed  $pre_transient The default value to return if the transient does not exist.
         *                              Any value other than false will short-circuit the retrieval
         *                              of the transient, and return the returned value.
         * @param string $transient     Transient name.
         */
        $pre = RC_Hook::apply_filters( "pre_transient_{$transient}", false, $transient );
        if ( false !== $pre )
            return $pre;

        if ( wp_using_ext_object_cache() ) {
            $value = wp_cache_get( $transient, 'transient' );
        } else {
            $transient_option = '_transient_' . $transient;
            if ( ! wp_installing() ) {
                // If option is not in alloptions, it is not autoloaded and thus has a timeout
                $alloptions = wp_load_alloptions();
                if ( !isset( $alloptions[$transient_option] ) ) {
                    $transient_timeout = '_transient_timeout_' . $transient;
                    $timeout = $this->get_option( $transient_timeout );
                    if ( false !== $timeout && $timeout < time() ) {
                        $this->delete_option( $transient_option  );
                        $this->delete_option( $transient_timeout );
                        $value = false;
                    }
                }
            }

            if ( ! isset( $value ) )
                $value = $this->get_option( $transient_option );
        }

        /**
         * Filters an existing transient's value.
         *
         * The dynamic portion of the hook name, `$transient`, refers to the transient name.
         *
         * @since 2.8.0
         * @since 4.4.0 The `$transient` parameter was added
         *
         * @param mixed  $value     Value of transient.
         * @param string $transient Transient name.
         */
        return RC_Hook::apply_filters( "transient_{$transient}", $value, $transient );
    }

    /**
     * Set/update the value of a transient.
     *
     * You do not need to serialize values. If the value needs to be serialized, then
     * it will be serialized before it is set.
     *
     * @since 2.8.0
     *
     * @param string $transient  Transient name. Expected to not be SQL-escaped. Must be
     *                           172 characters or fewer in length.
     * @param mixed  $value      Transient value. Must be serializable if non-scalar.
     *                           Expected to not be SQL-escaped.
     * @param int    $expiration Optional. Time until expiration in seconds. Default 0 (no expiration).
     * @return bool False if value was not set and true if value was set.
     */
    public function set_transient( $transient, $value, $expiration = 0 )
    {

        $expiration = (int) $expiration;

        /**
         * Filters a specific transient before its value is set.
         *
         * The dynamic portion of the hook name, `$transient`, refers to the transient name.
         *
         * @since 3.0.0
         * @since 4.2.0 The `$expiration` parameter was added.
         * @since 4.4.0 The `$transient` parameter was added.
         *
         * @param mixed  $value      New value of transient.
         * @param int    $expiration Time until expiration in seconds.
         * @param string $transient  Transient name.
         */
        $value = RC_Hook::apply_filters( "pre_set_transient_{$transient}", $value, $expiration, $transient );

        /**
         * Filters the expiration for a transient before its value is set.
         *
         * The dynamic portion of the hook name, `$transient`, refers to the transient name.
         *
         * @since 4.4.0
         *
         * @param int    $expiration Time until expiration in seconds. Use 0 for no expiration.
         * @param mixed  $value      New value of transient.
         * @param string $transient  Transient name.
         */
        $expiration = RC_Hook::apply_filters( "expiration_of_transient_{$transient}", $expiration, $value, $transient );

        if ( wp_using_ext_object_cache() ) {
            $result = wp_cache_set( $transient, $value, 'transient', $expiration );
        } else {
            $transient_timeout = '_transient_timeout_' . $transient;
            $transient_option = '_transient_' . $transient;
            if ( false === $this->get_option( $transient_option ) ) {
                $autoload = 'yes';
                if ( $expiration ) {
                    $autoload = 'no';
                    $this->add_option( $transient_timeout, time() + $expiration, '', 'no' );
                }
                $result = $this->add_option( $transient_option, $value, '', $autoload );
            } else {
                // If expiration is requested, but the transient has no timeout option,
                // delete, then re-create transient rather than update.
                $update = true;
                if ( $expiration ) {
                    if ( false === $this->get_option( $transient_timeout ) ) {
                        $this->delete_option( $transient_option );
                        $this->add_option( $transient_timeout, time() + $expiration, '', 'no' );
                        $result = $this->add_option( $transient_option, $value, '', 'no' );
                        $update = false;
                    } else {
                        $this->update_option( $transient_timeout, time() + $expiration );
                    }
                }
                if ( $update ) {
                    $result = $this->update_option( $transient_option, $value );
                }
            }
        }

        if ( $result ) {

            /**
             * Fires after the value for a specific transient has been set.
             *
             * The dynamic portion of the hook name, `$transient`, refers to the transient name.
             *
             * @since 3.0.0
             * @since 3.6.0 The `$value` and `$expiration` parameters were added.
             * @since 4.4.0 The `$transient` parameter was added.
             *
             * @param mixed  $value      Transient value.
             * @param int    $expiration Time until expiration in seconds.
             * @param string $transient  The name of the transient.
             */
            RC_Hook::do_action( "set_transient_{$transient}", $value, $expiration, $transient );

            /**
             * Fires after the value for a transient has been set.
             *
             * @since 3.0.0
             * @since 3.6.0 The `$value` and `$expiration` parameters were added.
             *
             * @param string $transient  The name of the transient.
             * @param mixed  $value      Transient value.
             * @param int    $expiration Time until expiration in seconds.
             */
            RC_Hook::do_action( 'setted_transient', $transient, $value, $expiration );
        }
        return $result;
    }

    /**
     * Deletes all expired transients.
     *
     * The multi-table delete syntax is used to delete the transient record
     * from table a, and the corresponding transient_timeout record from table b.
     *
     * @since 4.9.0
     *
     * @param bool $force_db Optional. Force cleanup to run against the database even when an external object cache is used.
     */
    public function delete_expired_transients( $force_db = false )
    {
        global $wpdb;

        if ( ! $force_db && wp_using_ext_object_cache() ) {
            return;
        }

        $wpdb->query( $wpdb->prepare(
            "DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
			WHERE a.option_name LIKE %s
			AND a.option_name NOT LIKE %s
			AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
			AND b.option_value < %d",
            $wpdb->esc_like( '_transient_' ) . '%',
            $wpdb->esc_like( '_transient_timeout_' ) . '%',
            time()
        ) );

        if ( ! is_multisite() ) {
            // non-Multisite stores site transients in the options table.
            $wpdb->query( $wpdb->prepare(
                "DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
				WHERE a.option_name LIKE %s
				AND a.option_name NOT LIKE %s
				AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
				AND b.option_value < %d",
                $wpdb->esc_like( '_site_transient_' ) . '%',
                $wpdb->esc_like( '_site_transient_timeout_' ) . '%',
                time()
            ) );
        } elseif ( is_multisite() && is_main_site() && is_main_network() ) {
            // Multisite stores site transients in the sitemeta table.
            $wpdb->query( $wpdb->prepare(
                "DELETE a, b FROM {$wpdb->sitemeta} a, {$wpdb->sitemeta} b
				WHERE a.meta_key LIKE %s
				AND a.meta_key NOT LIKE %s
				AND b.meta_key = CONCAT( '_site_transient_timeout_', SUBSTRING( a.meta_key, 17 ) )
				AND b.meta_value < %d",
                $wpdb->esc_like( '_site_transient_' ) . '%',
                $wpdb->esc_like( '_site_transient_timeout_' ) . '%',
                time()
            ) );
        }
    }



}