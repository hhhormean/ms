<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
use User\Api\UserApi as UserApi;

/**
 * 后台首页控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class LicenseController extends AdminController {

    /**
     * 后台首页
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function index(){
        if(UID){
            $this->meta_title = '注册码';
            $licenses = M('license')->select();
//            dump($licenses);
            $this->assign('licenses',$licenses);
            $this->display();
        } else {
            $this->redirect('Public/login');
        }
    }

    public function create_license(){
        $license[] = array();
        for($i=0;$i<10;$i++){
//            $license[$i]['license'] = $this->getRandChar(10);
//            $license[$i]['license_status'] = 0;
//            $license[$i]['create_time']=time();
            $license[$i] = array('license'=>$this->getRandChar(10),'license_status'=> 0, 'create_time'=>time());
        }
        dump($license);
        echo M('license')->addAll($license);
    }

    /**
     * 生成随机字符串
     * @param $length
     * @return null|string
     */
    function getRandChar($length){
        $str = null;
        $strPol = "0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;

        for($i=0;$i<$length;$i++){
            $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        return $str;
    }

}
