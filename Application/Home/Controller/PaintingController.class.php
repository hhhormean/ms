<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use Think\Upload;
use Think\Page;
/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class PaintingController extends HomeController {

	/*发布作品*/
	public function postPainting(){

		$data['uid'] = check_token();
		$post = I('post.');
		$data['content'] = $post['content'];
		$data['category'] = $post['category'];
		$img = $this->upload();
		foreach($img as $key => $value){
			if(isset($value['url']))
				$imgUrl[] = $value['url'];
		}
		$data['pic_url'] = arr2str($imgUrl,'|');
		$data['date'] = time();
		$data['status'] = 0;

		if(M('painting')->add($data))
			$this->success('发布成功');
		else $this->error('发布失败');

	}

	/*作品点评列表*/
	/*GET：p/2  category/0 分页10条消息*/
	public function paintingList($p = 1, $n = 10, $category = ''){
		/*status代表是否被评价过的*/
		$map['status'] = 1;
		$category ? $map['category'] = $category : '';
		$count = M('painting')->where($map)->count();
		$Page       = new \Think\Page($count,$n);
		$list  = M('painting')
			->field('uid, content, category, date, pic_url, video_url, comment_id')
			->where($map)
			->limit($Page->firstRow.','.$Page->listRows)
			->select();
		if(!$list) $this->success('暂时没有作品');
		foreach($list as $key => $value){
			$list[$key]['author'] = get_username($value['uid']);
			$list[$key]['pic_url'] = str2arr($value['pic_url'], '|');
			$list[$key]['comment_count'] = count(json_decode($value['comment_id']));
			$list[$key]['comment'] = $this->getCommentById(json_decode($value['comment_id']), 2);
			unset($list[$key]['comment_id']);
		}
		$this->success($list);
	}

	/*作品详情*/
	/*Get paintingId */
	public function reviewDetail(){
		if($paintingId = I('get.paintingId')){
			if(!is_numeric($paintingId)) $this->error('找不到信息');

			$map['id'] = $paintingId;
			$paintingData = M('painting')
				->field('uid, content, category, date, pic_url, video_url, comment_id')
				->where($map)
				->find();
			if(!$paintingData)$this->error('没有内容');

			$paintingData['comment'] = $this->getCommentById(json_decode($paintingData['comment_id']));
			$paintingData['pic_url'] = str2arr($paintingData['pic_url'], '|');
			unset($paintingData['comment_id']);
			$this->success($paintingData);
		}
	}

	/*作品评论*/
	/*POST：token，username，comment，paintingId*/
	public function paintingComment(){
		$comment['uid'] 	= check_token();
		$comment['content'] = $_POST['comment'];
		$comment['date'] 	= time();
		$commentId 			= M('painting_comment')->data($comment)->add();

		$paintingId = $_POST['paintingId'];
		if($paintingId){
			if($this->addComment($paintingId, $commentId))
				$this->success('评论成功');
		}else
			$this->success('评论失败');
	}

	/*得到分类信息*/
	public function getCategory(){
		$map['pid'] = 0;
		$cate = M('video_category')->field('id, title')->where($map)->select();
		$this->success($cate);
	}

	/*我的点评*/
	/*POST Username Token Status*/
	/*0,为点评  1,已点评*/
	public function myPainting(){
		if($uid = check_token()){
			$map['status'] = I('post.status');
			$map['uid'] = $uid;

			$list  = M('painting')
				->field('uid, content, category, date, pic_url, video_url, comment_id')
				->where($map)
				->select();
			foreach($list as $key => $value){
				$list[$key]['username'] = get_username($value['uid']);
				$list[$key]['pic_url'] = str2arr($value['pic_url'], '|');
				$list[$key]['comment_count'] = count(json_decode($value['comment_id']));
				$list[$key]['comment'] = $this->getCommentById(json_decode($value['comment_id']), 2);
			}
			$this->success($list);
		}
	}


	/* 上传图片 */
	public function upload(){
		session('upload_error', null);
		/* 上传配置 */
		$setting = C('PAINTING_UPLOAD');

		/* 调用文件上传组件上传文件 */
		$pic_driver = C('PICTURE_UPLOAD_DRIVER');
		$this->uploader = new Upload($setting, C('PICTURE_UPLOAD_DRIVER'),C("UPLOAD_{$pic_driver}_CONFIG"));


		$info = $this->uploader->upload($_FILES);
		if($info){
			$url = C('PAINTING_UPLOAD.rootPath').$info['imgFile']['savepath'].$info['imgFile']['savename'];
			$url = str_replace('./', '/', $url);
			$info['fullpath'] = __ROOT__.$url;
		}
		session('upload_error', $this->uploader->getError());

		return $info;
	}

	/*根据评论id得到评论*/
	private function getCommentById( $cid = '', $limit = ''){
		if(is_array($cid)){
			$map['id'] = array('in', $cid);
		}else{
			$map['id'] = $cid;
		}
		$return = M('painting_comment')->where($map)->limit($limit)->select();
		return $return;
	}

	/*添加评论*/
	private function addComment($paintingId, $commentId ){
		if(!($paintingId && $commentId)){
			return false;
		}
		$map['id'] = $paintingId;
		$data = M('painting')->where($map)->find();
		if(isset($data['comment_id'])){
			$comment_id = json_decode($data['comment_id']);
			$comment_id[] = $commentId;
		}else{
			$comment_id[] = $commentId;
		}

		$save['comment_id'] = json_encode($comment_id);
		$status = M('painting')->where($map)->save($save);

		if( $status )
			return true;
		else
			return false;
	}




}