<?php

namespace Ecjia\App\Theme\ThemeFramework\Fields\Subheading;

use Ecjia\App\Theme\ThemeFramework\Foundation\Options;

/**
 *
 * Field: Sub Heading
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
class Subheading extends Options
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
