<?php
/**
SMS短信发送函数
@author		sms.cn
@link		http://www.sms.cn
*/

/**
* 短信发送
*
* @param string $uid 短信账号
* @param string $pwd MD5接口密码
* @param string $mobile 手机号码
* @param string $content 短信内容
* @param string $template 短信模板ID
* @return array
*/
function sendSMS($uid,$pwd,$mobile,$content,$template='')
{
	$apiUrl = 'http://api.sms.cn/sms/';		//短信接口地址
	$data = array(
		'ac' =>		'send',
		'uid'=>		$uid,					//用户账号
		'pwd'=>		md5($pwd.$uid),					//MD5位32密码,密码和用户名拼接字符
		'mobile'=>	$mobile,				//号码
		'content'=>	$content,				//内容
		'template'=>$template,				//变量模板ID 全文模板不用填写
		'format' => 'json',					//接口返回信息格式 json\xml\txt
		);
	
	$result = postSMS($apiUrl,$data);			//POST方式提交
	$re = json_to_array($result);			    //JSON数据转为数组
	//$re = getSMS($apiUrl,$data);				//GET方式提交
	
	return $re;
	/*
	if( $re['stat']=='100' )
	{
		return "发送成功!";
	}
	else if( $re['stat']=='101')
	{
		return "验证失败! 状态：".$re;
	}
	else 
	{
		return "发送失败! 状态：".$re;
	}
	*/
}

/*
//密码直接写的函数里

function sendSMS($mobile,$content,$template='')
{
	$uid = 'test';
	$pwd = 'testpass';
	$apiUrl = 'http://api.sms.cn/sms/';		//短信接口地址
	$data = array(
		'ac' =>		'send',
		'uid'=>		$uid,					//用户账号
		'pwd'=>		md5($pwd.$uid),					//MD5位32密码,密码和用户名拼接字符
		'mobile'=>	$mobile,				//号码
		'content'=>	$content,				//内容
		'template'=>$template,				//变量模板ID 全文模板不用填写
		'format' => 'json',					//接口返回信息格式 json\xml\txt
		);
	
	$result = postSMS($apiUrl,$data);			//POST方式提交
	$re = json_to_array($result);			    //JSON数据转为数组
	
	if( $re['stat']=='100' )
	{
		return "发送成功";
	}
	else if( $re['stat']=='101')
	{
		return "验证失败! 状态：".$re;
	}
	else 
	{
		return "发送失败! 状态：".$re;
	}
}
*/

/**
* POST方式HTTP请求
*
* @param string $url URL地址
* @param array $data POST参数
* @return string
*/
function postSMS($url,$data='')
{
	$row = parse_url($url);
	$host = $row['host'];
	$port = $row['port'] ? $row['port']:80;
	$file = $row['path'];
	while (list($k,$v) = each($data)) 
	{
		$post .= rawurlencode($k)."=".rawurlencode($v)."&";	//转URL标准码
	}
	$post = substr( $post , 0 , -1 );
	$len = strlen($post);
	$fp = @fsockopen( $host ,$port, $errno, $errstr, 10);
	if (!$fp) {
		return "$errstr ($errno)\n";
	} else {
		$receive = '';
		$out = "POST $file HTTP/1.1\r\n";
		$out .= "Host: $host\r\n";
		$out .= "Content-type: application/x-www-form-urlencoded\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "Content-Length: $len\r\n\r\n";
		$out .= $post;		
		fwrite($fp, $out);
		while (!feof($fp)) {
			$receive .= fgets($fp, 128);
		}
		fclose($fp);
		$receive = explode("\r\n\r\n",$receive);
		unset($receive[0]);
		return implode("",$receive);
	}
}
/**
* GET方式HTTP请求
*
* @param string $url URL地址
* @param array $data POST参数
* @return string
*/
function getSMS($url,$data='')
{
	$get='';
	while (list($k,$v) = each($data)) 
	{
		$get .= $k."=".urlencode($v)."&";	//转URL标准码
	}
	return file_get_contents($url.'?'.$get);
}
//数字随机码
function randNumber($len = 6)
{
	$chars = str_repeat('0123456789', 10);
	$chars = str_shuffle($chars);
	$str   = substr($chars, 0, $len);
	return $str;
}
//把数组转json字符串
function array_to_json($p)
{
	return urldecode(json_encode(json_urlencode($p)));
}
//url转码
function json_urlencode($p)
{
	if( is_array($p) )
	{
		foreach( $p as $key => $value )$p[$key] = json_urlencode($value);
	}
	else
	{
		$p = urlencode($p);
	}
	return $p;
}

//把json字符串转数组
function json_to_array($p)
{
	if( mb_detect_encoding($p,array('ASCII','UTF-8','GB2312','GBK')) != 'UTF-8' )
	{
		$p = iconv('GBK','UTF-8',$p);
	}
	return json_decode($p, true);
}


?>