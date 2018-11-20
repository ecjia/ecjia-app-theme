<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2018/11/20
 * Time: 09:36
 */

namespace Ecjia\App\Theme\ThemeFramework;

use RC_Hook;

/**
 *
 * Abstract Class
 * A helper class for action and filter hooks
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
abstract class ThemeFrameworkAbstract
{

    public function addAction( $hook, $function_to_add, $priority = 30, $accepted_args = 1 )
    {
        RC_Hook::add_action( $hook, array( &$this, $function_to_add), $priority, $accepted_args );
    }

    public function addFilter( $tag, $function_to_add, $priority = 30, $accepted_args = 1 )
    {
        RC_Hook::add_action( $tag, array( &$this, $function_to_add), $priority, $accepted_args );
    }

}