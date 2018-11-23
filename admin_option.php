<?php
/**
 * Created by PhpStorm.
 * User: royalwang
 * Date: 2018/11/22
 * Time: 15:03
 */

defined('IN_ECJIA') or exit('No permission resources.');


class admin_option extends ecjia_admin
{

    public function __construct()
    {
        parent::__construct();

    }


    public function init()
    {




        $this->assign('ur_here',   __('主题选项', 'theme'));
        $this->assign('current_code', $this->request->query('section'));


        $this->display('template_option.dwt');
    }


}