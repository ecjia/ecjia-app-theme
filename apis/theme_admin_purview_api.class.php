<?php

defined('IN_ECJIA') or exit('No permission resources.');

/**
 * 主题权限
 */
class theme_admin_purview_api extends Component_Event_Api {
    public function call(&$options) {
        $purviews = array(
            array('action_name' => '主题管理', 'action_code' => 'theme_manage', 'relevance' => ''),
        	array('action_name' => '更换主题', 'action_code' => 'backup_setting', 'relevance' => ''),
        );
        return $purviews;
    }
}

// end