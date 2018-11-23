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

        ecjia_screen::get_current_screen()->add_nav_here(new admin_nav_here(__('主题选项')));


        $this->assign('current_code', $this->request->query('section'));


        $this->display('template_option.dwt');
    }


}