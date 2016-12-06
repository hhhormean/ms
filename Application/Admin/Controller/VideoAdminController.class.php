<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2016/7/12 0012
 * Time: 16:33
 */
namespace Admin\Controller;
use Think\Upload;

class VideoAdminController extends AdminController{
    public function admin(){
        $this->meta_title = '专辑管理';
        $list       =   M("video_album")->field(true)->order('id asc')->select();
        $this->assign('list',$list);
        $this->display();
    }



    public function detail($id){
        $map['album_id'] = $id;
        $data['section_detail'] = M('video')->where($map)->select();

        $this->assign('data',$data);
        $this->display();
    }

    public function del(){
        $id = array_unique((array)I('id',0));

        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }

        $map = array('id' => array('in', $id) );
        if(M('video_album')->where($map)->delete()){
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }


    public function video_del(){
        $id = array_unique((array)I('id',0));

        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }

        $map = array('id' => array('in', $id) );
        if(M('video')->where($map)->delete()){
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }
}