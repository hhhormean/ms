<?php

namespace Home\Controller;
class AppController extends HomeController {
	public function index(){
//		$data = M('app')->where(array('pid'=>1))->select();
//		$index = array();
//		dump($data);
//		foreach($data as $key => $value){
//			$index[$key]['id'] = $value['id'];
//			$index[$key]['type'] = $value['type'];
//			if(isset($value['article_id'])){
//				$art_id = $value['article_id'];
//				$index[$key]['article_id'] = $value['article_id'];
//				$doc = M('document')
//					->field('title,description,view')
//					->where(array('id' => $value['article_id']))
//					->find();
//				$index[$key]['title'] = $doc['title'];
//				$index[$key]['cover'] = 'cover';
//				$index[$key]['view_num'] = $doc['view'];
//				$index[$key]['des'] = $doc['description'];
//				$index[$key]['url'] =$_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'].U("Home/Article/detail?id=$art_id");
//			}
//		}
//		echo $this->success($this->JSON($index));
	}

	/*首页轮播接口*/
	public function getIndexCarousel(){
		$Model = M('app');
		$data = $Model
			->join('RIGHT JOIN __DOCUMENT__ ON __APP__.article_id = __DOCUMENT__.id')
			->where(array('ms_app.pid'=>1,'ms_app.type' => 'carousel'))
			->select();
		foreach($data as $key => $value){
			$data[$key]['cover'] = 'cover!!!';
			$data[$key]['url'] = $index[$key]['url'] =$_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'].U("Home/Article/detail?id=".$value['article_id']);
		}
		$this->success($data);
	}

	/*首页精品视频接口*/
	public function getIndexVideo(){
		$Model = M('app');
		$data = $Model
			->join('RIGHT JOIN __DOCUMENT__ ON __APP__.article_id = __DOCUMENT__.id')
			->where(array('ms_app.pid'=>1,'ms_app.type' => 'video'))
			->select();
		foreach($data as $key => $value){
			$data[$key]['cover'] = 'cover!!!';
			$data[$key]['url'] = $index[$key]['url'] =$_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'].U("Home/Article/detail?id=".$value['article_id']);
		}
		$this->success($data);
	}

	/*首页学习头条接口*/
	public function getIndexNews(){
		$Model = M('app');
		$data = $Model
			->join('RIGHT JOIN __DOCUMENT__ ON __APP__.article_id = __DOCUMENT__.id')
			->where(array('ms_app.pid'=>1,'ms_app.type' => 'news'))
			->select();
		foreach($data as $key => $value){
			$data[$key]['cover'] = 'cover!!!';
			$data[$key]['url'] = $index[$key]['url'] =$_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'].U("Home/Article/detail?id=".$value['article_id']);
		}
		$this->success($data);
	}

}