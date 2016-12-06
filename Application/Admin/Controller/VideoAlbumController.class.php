<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2016/7/12 0012
 * Time: 16:33
 */
namespace Admin\Controller;
use Think\Upload;

class VideoAlbumController extends AdminController{
    public function index(){
        $this->meta_title = '我的专辑';

        $map['uid'] =   is_login();
        $list       =   M("video_album")->where($map)->field(true)->order('id asc')->select();
        $this->assign('list',$list);
        $this->display();
    }

    /*新增专辑*/
    public function add(){
        if(IS_POST){
            $data = $_POST;
            if($data['title'] && $data['description'] && $data['category'] ){
                if($data['id']) unset($data['id']);
                $data['cover'] = $this->upload_img();
                $data['create_time'] = time();
                $data['update_time'] = time();
                $data['uid'] = is_login();

                $id = M('video_album')->add($data);
                if($id){
                    $this->success('新增成功',U('VideoAlbum/index'),false);
                } else {
                    $this->error('新增失败','',false);
                }
            }else{
                $this->error('新增失败','',false);
            }
        } else {
            $this->assign('info',array('pid'=>I('pid')));
            $menus = M('videoCategory')->field(true)->select();
            $menus = D('Common/Tree')->toFormatTree($menus);
            $this->assign('Menus', $menus);
            $this->meta_title = '新增专辑';
            $this->display('edit');
        }
    }






    /**
     * 编辑配置
     * @author yangweijie <yangweijiester@gmail.com>
     */
    public function edit($id = 0){
        if(IS_POST){
            $data = $_POST;
            if($upload_url = $this->upload_img())
                $data['cover'] = $upload_url;
            $data['update_time'] = time();
            $map['id'] = $_POST['id'];
            $status = M('video_album')->save($data);
            if($status){
                $this->success('更新成功', U('VideoAlbum/index'), false);
            } else {
                $this->error('更新失败','', false);
            }
        } else {
            $info = array();
            /* 获取数据 */
            $info = M('video_album')->field(true)->find($id);

            $menus = M('videoCategory')->field(true)->select();
            $menus = D('Common/Tree')->toFormatTree($menus);
            $this->assign('Menus', $menus);
//dump($info);

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
        if(M('video_album')->where($map)->delete()){
            $this->success('删除成功');
        } else {
            $this->error('删除失败！');
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
                $video['url'] = 'http://yuntv.letv.com/bcloud.html?uu=xvo3fmiwd2&vu='.$json->data->video_unique.'&auto_play=1&gpcflag=1&width=640&height=360';
                M('video')->where(array('id' => cookie('ms_video_id')))->save($video);
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
            return $return['url'];
        }else{
            return null;
        }
    }

    /**
     * 删除视频
     */
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

    /**
     * 视频详情
     * @param $id
     */
    public function detail($id){
        $map['album_id'] = $id;
        $data['section_detail'] = M('video')->where($map)->select();

        $this->assign('data',$data);
        $this->display();
    }

}