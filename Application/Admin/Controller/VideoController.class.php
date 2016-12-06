<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2016/7/12 0012
 * Time: 16:33
 */
namespace Admin\Controller;
use Think\Upload;

class VideoController extends AdminController{
    public function index(){
        $this->meta_title = '视频管理';
        $pid  = I('get.pid',0);
        $title      =   trim(I('get.title'));
        if($pid){
            $data = M('video')->where("id={$pid}")->field(true)->find();
            $this->assign('data',$data);
        }
        $map['pid'] =   $pid;
        $map['title'] = array('like',"%{$title}%");

        $list       =   M("video")->where($map)->field(true)->order('id asc')->select();
        if($list) {
            foreach($list as $key => $value){
                $list[$key]['category'] = getVideoCateById($value['category_id']);
            }
            $this->assign('list',$list);
        }
        Cookie('__forward__',$_SERVER['REQUEST_URI']);
        $this->display();
    }

    public function add( $album_id = 0){
        cookie('ms_album_id',$album_id);
        if(IS_POST){
            $Video = M('video');
            $data = $_POST;
            if(cookie('ms_video_url')){
                $data['url'] = cookie('ms_video_url');
                $data['create_time'] = time();
                $data['update_time'] = time();
//                cookie('ms_video_url',null);
            }else{
                $this->error('必须上传视频内容','',false);
            }
            dump($data);
//            $status = $Video->add($data);
//
//
//            $album['update_time'] = time();
//            $status = M('video_album')->where(array('id'=>$data['album_id']))->save($album);
//
//            if($status){
//                    $this->success('新增成功', U('VideoAlbum/detail',array('id'=>$data['album_id'])), false);
//            } else {
//                $this->error('新增失败', '', false);
//            }
        } else {
            $this->assign('info',array('pid'=>I('pid')));
            $this->meta_title = '新增视频';
            $this->display('video_edit');
        }
    }


    /**
     * 编辑配置
     * @author yangweijie <yangweijiester@gmail.com>
     */
    public function edit($id = 0){
        if(IS_POST){
            $Menu = D('video');
            $data = $Menu->create();
            if($data){
                if($Menu->save()!== false){
                    // S('DB_CONFIG_DATA',null);
                    //记录行为
//                    action_log('update_menu', 'Menu', $data['id'], UID);
                    $this->success('更新成功', Cookie('__forward__'));
                } else {
                    $this->error('更新失败');
                }
            } else {
                $this->error($Menu->getError());
            }
        } else {
            $info = array();
            /* 获取数据 */
            $info = M('video')->field(true)->find($id);
            $menus = M('video')->where(array('pid' => 0))->field(true)->select();
            $menus = D('Common/Tree')->toFormatTree($menus);
            $menus = array_merge(array(0=>array('id'=>0,'title_show'=>'顶级单元')), $menus);
            $this->assign('Menus', $menus);
            if(false === $info){
                $this->error('获取视频信息错误');
            }
            $this->assign('info', $info);
            $this->meta_title = '编辑视频信息';
            $this->display();
        }
    }

    public function del(){
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




    /**
     * 编辑配置
     * @author yangweijie <yangweijiester@gmail.com>
     */
    public function video_edit($id = 0){
        if(IS_POST){
            $Menu = D('video');
            $data = $Menu->create();
            if($data){
                if($Menu->save()!== false){
                    action_log('update_video', 'Video', $data['id'], UID);
                    $this->success('更新成功', Cookie('__forward__'));
                } else {
                    $this->error('更新失败');
                }
            } else {
                $this->error($Menu->getError());
            }
        } else {
            $info = array();
            /* 获取数据 */
            $info = M('video')->field(true)->find($id);
            cookie('ms_video_id',null);
            cookie('ms_video_id',$id);

            if(false === $info){
                $this->error('获取视频分类信息错误');
            }
            $this->assign('info', $info);
            $this->meta_title = '编辑视频分类';
            $this->display();
        }
    }




    public function video_upload(){

        $video_info = $_REQUEST;

        $file_size = isset($video_info['file_size']) ? intval($video_info['file_size']) : 0 ;
        $uploadtype = isset($video_info['uploadtype']) ? intval($video_info['uploadtype']) : 0 ;
        $uc1 = isset($video_info['uc1']) ? intval($video_info['uc1']) : 0 ;
        $uc2 = isset($video_info['uc2']) ? intval($video_info['uc2']) : 0 ;
        $nodeid = isset($video_info['nodeid']) ? intval($video_info['nodeid']) : 0 ;
        // $client_ip = $_SERVER['REMOTE_ADDR'];
        $client_ip = '121.8.210.12';

        // 若为上传初始化，视频名称为必须值
        if (isset($video_info['videoname']) && !empty($video_info['videoname'])) {
            echo $result = $this->video_init($video_info['videoname'], $file_size, $client_ip, $uploadtype, $uc1, $uc2, $nodeid);
        }

        // 若有token，则为断点续传，分发给断点续传接口
        if (isset($video_info['token']) && !empty($video_info['token'])) {
            echo $result = $this->resume($video_info['token'], $client_ip, $uploadtype);
        }

        if(!is_null($result)){
            $json = json_decode($result);
            if($json->code == 0 && $json->data->video_unique){
                $video['url'] = 'http://yuntv.letv.com/bcloud.html?uu='.C('letv_user_unique').'&vu='.$json->data->video_unique.'&auto_play=1&gpcflag=1&width=640&height=360';
//                M('video')->where(array('id' => cookie('ms_video_id')))->save($video);
                cookie('ms_video_url',$video['url']);
            }
        }
    }

    public function video_init($video_name, $file_size, $client_ip, $uploadtype = 0, $uc1 = 0, $uc2 = 0, $nodeid = 0) {
        $api = 'video.upload.init';

        $params['video_name'] = $video_name;
        $params['file_size'] = $file_size;
        $params['uploadtype'] = $uploadtype;
        $params['api'] = $api;
        $params['client_ip'] = $client_ip;
        $params['uc1'] = $uc1;
        $params['uc2'] = $uc2;
        $params['nodeid'] = $nodeid;
        $final_url = $this->_handleParam($params, $api);
        return file_get_contents($final_url);

    }


    public function resume($token, $client_ip, $uploadtype = 0) {
        $api = 'video.upload.resume';

        $params['token'] = $token;
        $params['uploadtype'] = $uploadtype;
        $params['api'] = $api;
        $params['client_ip'] = $client_ip;

        $final_url = $this->_handleParam($params, $api);

        return file_get_contents($final_url);
    }


    private function _handleParam($params) {

        $params['user_unique'] = C('letv_user_unique');
        $params['timestamp'] = time();
        $params['format'] = C('letv_format');
        $params['ver'] = C('letv_version');


        // 对所有参数按key排序
        ksort($params);
        $url_param = '';
        $keyStr = '';    // 用于生成验证码的字符串由参数的键值和用户密钥拼接而成

        foreach($params as $key=>$param) {
            $url_param .= (empty($url_param) ? '?' : '&') . $key . '=' . urlencode($param);
            $keyStr .= $key . $param;
        }

        $keyStr .= C('letv_secretkey');

        $sign = md5($keyStr);  // 计算sign参数
        $url_param .= '&sign=' . $sign;
        $final_url = C('letv_api_url') . $url_param;
        return $final_url;
    }

    public function speed(){
        $api_getTestNodes = 'http://dispatcher.cloud.letv.com/api/getTestNodes';

        $client_ip = $_SERVER['REMOTE_ADDR'];

        $final_url = $api_getTestNodes . '?client_ip=' . $client_ip;

        //print htmlspecialchars($final_url);
        echo file_get_contents($final_url); //htmlspecialchars($final_url);
    }





















































    /*上传照片，作品欣赏*/
    public function video_pic(){
        $this->meta_title = '作品欣赏';
        M('video_pic')->count();

        $list       =   M('video_pic')->order('id desc')->select();
        $request    =   (array)I('request.');
        $total      =   M('video_pic')->count();
        $listRows   =   10;
        $page       =   new \Think\Page($total, $listRows, $request);
        $voList     =   array_slice($list, $page->firstRow, $page->listRows);
        $p          =   $page->show();
        $this->assign('list', $voList);
        $this->assign('_page', $p? $p: '');
        $this->display();
    }

    public $uploader = null;

    /* 上传图片 */
    public function upload(){
        session('upload_error', null);
        /* 上传配置 */
        $setting = C('EDITOR_UPLOAD');

        /* 调用文件上传组件上传文件 */
        $pic_driver = C('PICTURE_UPLOAD_DRIVER');
        $this->uploader = new Upload($setting, C('PICTURE_UPLOAD_DRIVER'),C("UPLOAD_{$pic_driver}_CONFIG"));


        $info = $this->uploader->upload($_FILES);
        if($info){
            $url = C('EDITOR_UPLOAD.rootPath').$info['imgFile']['savepath'].$info['imgFile']['savename'];
            $url = str_replace('./', '/', $url);
            $info['fullpath'] = __ROOT__.$url;
        }
        session('upload_error', $this->uploader->getError());

        return $info;
    }


    public function upload_img(){
        /* 返回标准数据 */
        $return  = array('error' => 0, 'info' => '上传成功', 'data' => '');
        $img = $this->upload();

        $pic_driver = C('PICTURE_UPLOAD_DRIVER');
        if (strtolower($pic_driver) == 'local') {
            $imgurl = $img['imgFile']['path'];
        } else {
            $imgurl = $img['imgFile']['url'];
        }


        /* 记录附件信息 */
        if($img){
            $return['url'] = $imgurl; //修改这里
            unset($return['info'], $return['data']);
        } else {
            $return['error'] = 1;
            $return['message']   = session('upload_error');
        }

        /* 返回数据 */

        if($return['error'] == 0){
            $data['pic_url'] = $return['url'];
            $data['date'] = time();
            $status = M('video_pic')->add($data);
            $status ? $this->success('上传成功','',false) : $this->success('上传失败','',false);
        }else{
            $this->success($return['message'],'',false);
        }
    }

    /*删除照片*/
    public function pic_del(){
        $id = array_unique((array)I('id',0));

        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }

        $map = array('id' => array('in', $id) );
        if(M('video_pic')->where($map)->delete()){
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
        }
    }


}