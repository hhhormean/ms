<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class VideoController extends HomeController {


	/**
	 * App视频课程的首页信息整合
	 */
	public function index(){
		$data = M('app')->where(array('pid'=>16))->select();
		$carousel = array();
		foreach($data as $key => $value){
			$carousel[$key]['id'] = $value['id'];
			$carousel[$key]['type'] = $value['type'];
			if(isset($value['article_id'])){
				$art_id = $value['article_id'];
				$carousel[$key]['article_id'] = $value['article_id'];
				$doc = M('document')
					->field('title,description,view')
					->where(array('id' => $value['article_id']))
					->find();
				$carousel[$key]['title'] = $doc['title'];
				$carousel[$key]['cover'] = 'cover';
				$carousel[$key]['view_num'] = $doc['view'];
				$carousel[$key]['des'] = $doc['description'];
				$carousel[$key]['url'] =$_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'].U("Home/Article/detail?id=$art_id");
			}
		}

		$category = M('video_category')->field('title,sort,id')->where(array('pid' => 0))->select();
		$map['pid'] = array('neq',0);
		$recent_update = M('video')->where($map)->field('title,url,view')->order('update_time desc')->limit(6)->select();
		$return['carousel'] = $carousel;
		$return['category'] = $category;
		$return['recent_update'] = $recent_update;
		$this->success($this->JSON($return));
	}

	/**
	 * 获取视频首页轮播
	 * 查询条件待修改优化
	 */
	public function getCarousel(){
		$data = M('app')->where(array('pid'=>16))->select();
		$carousel = array();
		foreach($data as $key => $value){
			$carousel[$key]['id'] = $value['id'];
			$carousel[$key]['type'] = $value['type'];
			if(isset($value['article_id'])){
				$art_id = $value['article_id'];
				$carousel[$key]['article_id'] = $value['article_id'];
				$doc = M('document')
					->field('title,description,view')
					->where(array('id' => $value['article_id']))
					->find();
				$carousel[$key]['title'] = $doc['title'];
				$carousel[$key]['cover'] = 'cover';
				$carousel[$key]['view_num'] = $doc['view'];
				$carousel[$key]['des'] = $doc['description'];
				$carousel[$key]['url'] =$_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'].U("Home/Article/detail?id=$art_id");
			}
		}

		$this->success($carousel);
	}

	/**
	 * 根据父节点pid获得分类信息
	 * v1.1
	 */
	public function getCategoryByPid( $pid = 0 ){
			$map['pid'] = $pid;
			$category = M('video_category')->where($map)->select();
			$this->success($category);
			return $category;
	}


	/**
	 * 根据分类id获得视频列表信息
	 * v1.1
	 */
	public function getVideoListByCateId( $cid = 0){


		/*后期记得加上limit*/
		/*整合相关视频ID*/
		$cate_map['id'] = $cid;
		$cate_id1 = M('video_category')->field('id')->where($cate_map)->select();
		$cate_id2 = M('video_category')->field('id')->where(array('pid'=>$cate_id1[0]['id']))->select();
		$cate_id = empty($cate_id2) ? $cate_id1 : array_merge_recursive($cate_id1,$cate_id2);

		foreach($cate_id as $value){
			$video_map[] = $value['id'];
		}
		$video_map = arr2str($video_map,',');
		$map['category'] = array('in',$video_map);
		$video_id = M('video_album')->field('id')->where($map)->select();

		foreach($video_id as $value){
			$video[] = $value['id'];
		}


		$video = arr2str($video,',');
		$video_map2['album_id'] = array('in',$video);
		$list = M('video')->field('id,title,album_id,category_id,update_time,view')->where($video_map2)->select();
//		var_dump($list);

		if($list) $this->success($list);
		else $this->error('暂时没有视频');
	}

	/**
	 * 根据视频id获取视频相关具体信息
	 * v1.1
	 */
	public function getVideoContent(){
		if(!check_token()) $this->error('请登录！');

		$user = I('post.');
		if(!is_numeric($user['vid'])) $this->error('参数错误');

		/*查找视频内容*/
		$data['main'] = $this->getMainVideoContent($user['vid']);
		if(is_null($data['main'])) $this->error('暂时没有该视频');

		/*查找视频相关列表*/
		$data['relate'] = $this->getRelateVideoContent($user['vid']);

		$this->success($data);
	}

	/**
	 * @param $vid
	 * @return null
	 * 得到视频主要内容
	 * v1.1
	 */
	private function getMainVideoContent($vid){

		/*查找视频内容*/
		if(is_array($vid)){
			$vid = arr2str($vid,',');
			$map['id'] = array('in',$vid);
		}else{
			$map['id'] = $vid;
		}

		$data = M('video')->field(true)->where($map)->find();

		return $data;
	}

	/**
	 * @param $vid
	 * @return null
	 * 得到视频相关内容
	 * v1.1
	 */
	private function getRelateVideoContent($vid){

		/*查找视频相关列表*/
		if($vid){
			$map['id'] = $vid;
			$album_map = M('video')->field('album_id')->where($map)->find();
			$data = M('video')->field('id,title,album_id,category_id,view,create_time,update_time')->where($album_map)->select();
		}else{
			$data= null;
		}

		return $data;
	}


	/**
	 * 根据视频id获取相关评论
	 * v1.1
	 */
	public function getVideoComment($vid)
	{
//		$video_id = $video_id ? $video_id : I('get.video_id');
		$map['video_id']= $vid;
		$comment = M('video_comment')
			->field('id, content, nickname, date, head_pic')
			->join('__MEMBER__ on __VIDEO_COMMENT__ . uid = __MEMBER__ . uid AND __VIDEO_COMMENT__ . video_id = ' . $vid)
			->select();
		$this->success($comment);

	}

	/**
	 * 存入评论
	 */
	public function putVideoComment()
	{
		if(!($uid = check_token())) $this->error('请登录！');

		$data = I('post.');
		if(is_null($data['content']) || !is_string($data['content'])) $this->error('内容错误');
		$data['date'] = time();
		$data['uid'] = $uid;
		$data['comment_id'] = 0;
		dump($data);
		$data['main'] = M('video_comment')->add($data);
		if(!$data['main']) $this->error('评论失败');

		$this->success('评论成功');

	}

	/**
	 * 搜索视频
	 * v1.1
	 */
	public function searchVideo()
	{
		if($_GET){
			$word = I('get.key');
			$map['title'] = array('like',"%$word%");
			$data = M('video')->field('id, title')->where($map)->select();
//			dump($data);
			$this->success($data);
		}

	}


	/*上传照片，作品欣赏*/
    public function video_pic(){

        $count =  M('video_pic')->count();
        $page  =  new \Think\Page($count, 10);
        $data  =  M('video_pic')->order('id desc')->limit($page->firstRow.','.$page->listRows)->select();
        $this->success($data);
    }



}