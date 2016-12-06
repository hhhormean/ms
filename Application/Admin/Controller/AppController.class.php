<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2016/7/12 0012
 * Time: 16:33
 */
namespace Admin\Controller;

class AppController extends AdminController{
    public function app_index(){
        $this->meta_title = 'App首页';
        $data = M('app')->where(array('pid'=>1))->select();
        $index = array();
        foreach($data as $key => $value){
            $index[$key]['id'] = $value['id'];
            $index[$key]['type'] = $value['type'];
            if(isset($value['article_id'])){
                $art_id = $value['article_id'];
                $index[$key]['article_id'] = $value['article_id'];
                $doc = M('document')
                        ->field('title,description,view')
                        ->where(array('id' => $value['article_id']))
                        ->find();
                $index[$key]['title'] = $doc['title'];
                $index[$key]['cover'] = 'cover';
                $index[$key]['view_num'] = $doc['view'];
                $index[$key]['des'] = $doc['description'];
                $index[$key]['url'] =$_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'].U("Home/Article/detail?id=$art_id");
            }
        }
        $this->assign('data',$index);
        $this->display();
    }

    public function app_review(){

    }
}