<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
use Think\Upload;

class LiveController extends LiveClassController {
    public function create($args = array())
    {
        if($_POST){
            /*上传图片*/
            $img = $this->upload();
            $pic_driver = C('PICTURE_UPLOAD_DRIVER');
            if (strtolower($pic_driver) == 'local') {
                $imgurl = $img['coverImgUrl']['path'];
            } else {
                $imgurl = $img['coverImgUrl']['url'];
            }

            /*创建直播*/
            if($live_id = parent::create($_POST)){

                $data = $_POST;
                $data['uid'] = is_login();
                $data['activityId'] = $live_id;
                $data['coverImgUrl'] = $imgurl;
                $data['startTime'] = strtotime($_POST['startTime']);
                $data['endTime'] = strtotime($_POST['endTime']);
                M('live')->add($data);
                $this->success('提交成功',U('Live/index'),false);
            } else {
                $this->error('提交失败',U('Live/create'),false);
            }

        }else{
            $this->display();
        }

    }


    /* 上传图片 */
    public function upload(){
        session('upload_error', null);
        /* 上传配置 */
        $setting = C('EDITOR_UPLOAD');

        /* 调用文件上传组件上传文件 */
        $pic_driver = C('PICTURE_UPLOAD_DRIVER');
        $uploader = new Upload($setting, C('PICTURE_UPLOAD_DRIVER'),C("UPLOAD_{$pic_driver}_CONFIG"));


        $info = $uploader->upload($_FILES);
        if($info){
            $url = C('EDITOR_UPLOAD.rootPath').$info['imgFile']['savepath'].$info['imgFile']['savename'];
            $url = str_replace('./', '/', $url);
            $info['fullpath'] = __ROOT__.$url;
        }
        session('upload_error', $uploader->getError());

        return $info;
    }



    public function index($status = ''){
        $lists = json_decode(parent::search($status)) ; // TODO: Change the autogenerated stub
        $count = $lists->total;
        $Page       = new \Think\Page($count,10);
        $show       = $Page->show();//

        $lists = json_decode(parent::search($status, $Page->firstRow, $Page->listRows)) ;
        $lists = $lists->rows;

        $this->assign('_list',$lists);
        $this->assign('_page',$show);// 赋值分页输出
        $this->display('index');
    }

    public function retrieve( $status = '')
    {
        $lists = json_decode(parent::search($status)) ; // TODO: Change the autogenerated stub
        $count = $lists->total;
        $Page       = new \Think\Page($count,10);
        $show       = $Page->show();//

        $lists = json_decode(parent::search($status, $Page->firstRow, $Page->listRows)) ;
        $lists = $lists->rows;
        $this->assign('_list',$lists);
        $this->assign('_page',$show);// 赋值分页输出
        $this->display('index');
    }

    public function detail($activityId){
        $data = json_decode(parent::retrieve($activityId));
        $this->assign('data', $data);
        $this->display();
    }

    public function update($args = array())
    {
        if($_POST){
            return parent::update($args); // TODO: Change the autogenerated stub
        }elseif($_GET){
            $info = json_decode(parent::retrieve(I('get.activityId')))->rows;
            $this->assign('info', $info[0]);
            $this->display();
        }

    }

    public function delete($activityId)
    {
        return parent::delete($activityId); // TODO: Change the autogenerated stub
    }

    public function get_play_url($activityId)
    {
        $url = parent::get_play_url($activityId); // TODO: Change the autogenerated stub
//        echo $url;
        redirect($url);
    }

    public function myLive(){
        $map['uid'] = is_login();
        $lists = M('live')->where($map)->select() ;
        $count = count($lists);
        $Page       = new \Think\Page($count,10);
        $show       = $Page->show();//

        $lists = M('live')->where($map)->limit($Page->firstRow, $Page->listRows)->select() ;

        $this->assign('_list',$lists);
        $this->assign('_page',$show);// 赋值分页输出
        $this->display();

    }
}