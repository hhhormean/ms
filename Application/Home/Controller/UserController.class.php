<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use User\Api\UserApi;
use Think\Upload;

/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class UserController extends HomeController {

	/* 用户中心首页 */
	public function index(){
		
	}

	/* 注册页面 */
	public function register($username = '', $password = '', $repassword = '', $email = '', $verify = '', $license=''){
        if(!C('USER_ALLOW_REGISTER')){
            $this->error('注册已关闭');
        }
		if(IS_POST){ //注册用户
			/* 检测验证码 */
			if(!check_phone_verify($username,$verify)){
				$this->error('验证码错误！');
			}

			/* 检测密码 */
			if($password != $repassword){
				$this->error('密码和重复密码不一致！');
			}

			/* 调用注册接口注册用户 */
            $User = new UserApi;
			$uid = $User->register($username, $password, $email, $username, $license);
			if(0 < $uid){ //注册成功
				//TODO: 发送验证邮件
				$this->success('注册成功！');
			} else { //注册失败，显示错误信息
				$this->error($this->showRegError($uid));
			}

		}
	}

	/* 登录页面 */
	public function login($username = '', $password = '', $verify = ''){
		if(IS_POST){ //登录验证
			/* 检测验证码 */
//			if(!check_verify($verify)){
//				$this->error('验证码输入错误！');
//			}

			/* 调用UC登录接口登录 */
			$user = new UserApi;
			$uid = $user->login($username, $password);
			if(0 < $uid){ //UC登录成功
				/* 登录用户 */
				$Member = D('Member');
				if($Member->login($uid)){ //登录用户
                    $return["info"] = "登录成功！";
                    $return["status"] = "1";
                    $token = create_token($username);
                    $return['token'] = $token['access_token'];
                    $this->ajaxReturn($return);
				} else {
                    $this->ajaxReturn($Member->getError());
				}

			} else { //登录失败
				switch($uid) {
					case -1: $error = '用户不存在或被禁用！'; break; //系统级别禁用
					case -2: $error = '密码错误！'; break;
					default: $error = '未知错误！'; break; // 0-接口参数错误（调试阶段使用）
				}
                $this->error($error);
//				$this->ajaxReturn($error);
			}

		} else { //显示登录表单
			$this->display();
		}
	}

	/* 退出登录 */
	public function logout(){
		if(is_login()){
			D('Member')->logout();
			$this->success('退出成功！', U('User/login'));
		} else {
			$this->redirect('User/login');
		}
	}

	/* 验证码，用于登录和注册 */
	public function verify(){
		$verify = new \Think\Verify();
		$verify->entry(1);
	}

	/**
	 * 获取用户注册错误信息
	 * @param  integer $code 错误编码
	 * @return string        错误信息
	 */
	private function showRegError($code = 0){
		switch ($code) {
			case -1:  $error = '用户名长度必须在16个字符以内！'; break;
			case -2:  $error = '用户名被禁止注册！'; break;
			case -3:  $error = '用户名被占用！'; break;
			case -4:  $error = '密码长度必须在6-30个字符之间！'; break;
			case -5:  $error = '邮箱格式不正确！'; break;
			case -6:  $error = '邮箱长度必须在1-32个字符之间！'; break;
			case -7:  $error = '邮箱被禁止注册！'; break;
			case -8:  $error = '邮箱被占用！'; break;
			case -9:  $error = '手机格式不正确！'; break;
			case -10: $error = '手机被禁止注册！'; break;
			case -11: $error = '手机号被占用！'; break;
			case -12: $error = '注册码无效！'; break;
			default:  $error = '未知错误';
		}
		return $error;
	}


    /**
     * 修改密码提交
     * @author huajie <banhuajie@163.com>
     */
    public function profile(){
		if ( !check_token()) {
			$this->error( '您还没有登陆' );
		}
        if ( IS_POST ) {
            //获取参数
            $uid        =  check_token();
            $password   =   I('post.old');
            $repassword = I('post.repassword');
            $data['password'] = I('post.password');

            if(empty($password)) $this->error('请输入原密码');
            if(empty($data['password'])) $this->error('请输入新密码');
            if(empty($repassword)) $this->error('请输入确认密码');

            if($data['password'] !== $repassword){
                $this->error('您输入的新密码与确认密码不一致');
            }

            $Api = new UserApi();
            $res = $Api->updateInfo($uid, $password, $data);
            if($res['status']){
                $this->success('修改密码成功！');
            }else{
                $this->error($this->showRegError($res['info']));
            }
        }
    }

    public function modifyUserInfo(){
		if(!check_token()){
			$this->error ("未登录");
		}

        //获取参数
        $data['username'] = I('post.username');
        $data['nickname'] = I('post.nickname');
        $data['sex'] 	  = I('post.sex');
        $data['location'] = I('post.location');
        $data['school']	  = I('post.school');
        $data['grade']	  = I('post.grade');

        if(empty($data['nickname'])) unset($data['nickname']);
        if(empty($data['sex'])) 	 unset($data['sex']);
        if(empty($data['location'])) unset($data['location']);
        if(empty($data['school'])) 	 unset($data['school']);
        if(empty($data['grade']))	 unset($data['grade']);


        $status = M('member')->where(array('username' =>  $data['username']))->save($data);
        if($status){
            $this->success('修改成功！');
        }else{
            $this->error('修改失败！');
        }

	}

	public function getUserInfo(){
        if(check_token()){
            $user = I('post.username');
            $username = $user['username'];
			$info = M('member')->field('head_pic,nickname,sex,status,location,school,grade')->where(array('username' => $username))->find();
			$this->success($this->JSON($info));
		}else{
			$this->error ("请重新登录");
		}
	}

    /**
     * 发送短信按钮
     */
	public function sendMessage(){
		if($_POST){
			$phone = I('post.phone',null);
			if(strlen($phone) == "11")
			{
				/*正则验证手机号码*/
				if(preg_match_all("/1[3578]{1}\d{8}/",$phone)){
					$code = getRandNumber();
                    $msg = 'msg_'.$phone;
					$result = sendTemplateSMS($phone,array($code),C('TempId'));
					if($result == null){
						$this->error("短信发送失败");
					}elseif($result->statusCode!=0) {
						$this->error("超过每天短信发送量，请明天再试");
					}else{
                        session(array("name"=>$msg,'expire'=>300));
                        session($msg,$code);
						$this->success('发送成功');
					}
				}else{
                    $this->error("手机格式不对");
                }
			}else{
				$this->error("手机号码应该为11位");
			}
		}
	}


	public function uploadHeadPic(){

        if(!($uid = check_token())){
            $this->error ("未登录");
        }

        session('upload_error', null);
        /* 上传配置 */
        $setting = C('EDITOR_UPLOAD');
        $pic_driver = C('PICTURE_UPLOAD_DRIVER');

        $uploader = new Upload($setting, C('PICTURE_UPLOAD_DRIVER'),C("UPLOAD_{$pic_driver}_CONFIG"));

        $info = $uploader->upload($_FILES);
        if($info){
            $data['head_pic'] =$info['photo']['url'];
            $status = M('member')->where(array('uid' => $uid))->save($data);
            if($status) $this->success('更换头像成功');
        }
        session('upload_error', $uploader->getError());
        $this->error('更换头像失败');
    }

    public function forgetPwd($username = '', $verify = '', $password = '', $repassword = ''){

        if(IS_POST){
            /* 检测验证码 */
            if(!check_phone_verify($username,$verify)){
                $this->error('验证码错误！');
            }

            /* 检测密码 */
            if($password != $repassword){
                $this->error('密码和重复密码不一致！');
            }

            $data['password'] = $password;

            /* 调用注册接口注册用户 */
            $User = new UserApi;
            $uid = $User->updatePwd($username, $data);
            if(0 < $uid){
                $this->success('密码更新成功！');
            } else { //注册失败，显示错误信息
                $this->error($this->showRegError($uid));
            }

        }
    }

}
