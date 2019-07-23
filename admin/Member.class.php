<?php
/**
 * Created by PhpStorm.
 * User: lulu
 */
class Member{

    /**
     * 注册会员列表
     */
    public function list(){
        if (empty($_POST)){
            require_once (VIEW.'/admin/member_list.html');
        }else{

        }
    }

}