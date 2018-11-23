<?php

namespace Ecjia\App\Theme\ThemeFramework\Fields\Heading;

use Ecjia\App\Theme\ThemeFramework\Foundation\Options;


/**
 *
 * Field: Heading
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
class Heading extends Options
{

    public function __construct( $field, $value = '', $unique = '' )
    {
        parent::__construct( $field, $value, $unique );
    }

    public function output()
    {

        echo $this->element_before();
        echo $this->field['content'];
        echo $this->element_after();

    }

}
