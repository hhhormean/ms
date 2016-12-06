<?php
/*--------------------------------
功能:		HTTP接口 发送短信类
修改日期:	2015-02-26
说明:		http://api.sms.cn/mt/?uid=用户账号&pwd=MD5位32密码&mobile=号码&mobileids=号码编号&content=内容
状态:
	100 发送成功
	101 验证失败
	102 短信不足
	103 操作失败
	104 非法字符
	105 内容过多
	106 号码过多
	107 频率过快
	108 号码内容空
	109 账号冻结
	110 禁止频繁单条发送
	112 号码不正确
	120 系统升级
--------------------------------*/
class Send
{
	function sendSMS($http,$uid,$pwd,$mobile,$content,$mobileids,$time='',$mid='')
	{

		$data = array
			(
			'uid'=>$uid,					//用户账号
			'pwd'=>md5($pwd.$uid),			//MD5位32密码,密码和用户名拼接字符
			'mobile'=>$mobile,				//号码
			'content'=>$content,			//内容
			'mobileids'=>$mobileids,
			'time'=>$time,					//定时发送
			'encode'=>'utf8',				//编码格式，看自己的项目需求了 我的是utf8的	
			);
		$re= $this->postSMS($http,$data);			//POST方式提交
		return $re;
	}
	function postSMS($url,$data='')
	{
		$port="";
		$post="";
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
}
?>