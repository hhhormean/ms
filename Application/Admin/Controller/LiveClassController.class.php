<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;


class LiveClassController extends AdminController {

    public function _initialize() {

        parent::_initialize();
        date_default_timezone_set('Asia/Shanghai');

        $this->base = 'http://api.open.letvcloud.com/live/execute';
        $this->ver = '3.1';
        $this->user_id = C('letv_uid');
        $this->app_id =  C('letv_user_unique');
        $this->app_key =  C('letv_secretkey');
        $this->activityCategory = '012';
        $this->time = $this->get_microtime();
        static $v_types = array(
            '001' => '发布会',
            '002' => '婚礼',
            '003' => '年会',
            '004' => '体育',
            '005' => '游戏',
            '006' => '旅游&amp;户外',
            '007' => '财经',
            '008' => '演唱会',
            '009' => '烹饪',
            '010' => '宠物&amp;动物',
            '011' => '访谈',
            '012' => '教育',
            '013' => '竞技',
            '014' => '剧场',
            '015' => '晚会',
            '016' => '电视节目',
            '017' => '秀场',
            '999' => '其它',

        );

        /*
            创建或更新时使用
        */
        $this->default_args = array(
            'activityName' => 'TEST_TITLE_' . (string) date('Y-m-d H:i:s'),
            'startTime' => self::get_start_time(),
            'endTime' => self::get_end_time(self::get_start_time()),
            'coverImgUrl' => '',
            'description' => 'TEST_DESC_' . (string) date('Y-m-d H:i:s'),
            'liveNum' => 1,
            'codeRateTypes' => '13,16,19,22,25',
            'needRecord' => 1,
            'needTimeShift' => 0,
            'needFullView' => 0,
            'activityCategory' => $this->activityCategory,
            'playMode' => 0,
        );

        /*
            应为全局设置: 参数待确定
        */
        $this->security_args = array(
            'activityId' => '',
            'neededPushAuth' => 1,
            'pushUrlValidTime' => 7200,
            'liveKey' => 'gfh5tuj4tij746kmtre54jm5y',
            'needIpWhiteList' => 0,
            'pushIpWhiteList' => 0,
            'needPlayerDomainWhiteList' => 1,
            /*
                域名白名单，多个时逗号分隔。最大长度为512，最多为10个
            */
            'playerDomainWhiteList' => 'lecloud-live.com',
        );
    }

    /**
     * 据给定的参数数组创建直播活动
     * @param  array           $args   创建直播活动的数组
     * @return string|false            json字符串或false
     */
    public function create($args = array()) {

        $method = 'lecloud.cloudlive.activity.create';

        $args['startTime'] = date('YmdHis',strtotime($args['startTime']));
        $args['endTime'] = date('YmdHis',strtotime($args['endTime']));

        $args = self::parse_args($args, $this->default_args);
        $base_args = $this->get_general_args($method);
        $base_args = array_merge($base_args, $args);
        $base_args['sign'] = $this->get_sign($base_args);
        $r = self::curl_post($this->base, $base_args);

        if (!stripos($r, 'activityId')) {
            return false;
        }

        $api_res_data = json_decode($r);


        $event_id = $api_res_data->activityId;
        return $event_id;

    }

    /*

    activityStatus
    0 未开始
    1 直播中
    2 已中断
    3 已结束

     */
    /**
     * 据给定的直播活动ID获取直播活动的信息
     * @param  string $event_id       直播活动的id
     * @return string|false     json字符串或false
     */
    public function retrieve($event_id) {

        $method = 'lecloud.cloudlive.vrs.activity.vrsinfo.search';
        $args = $this->get_general_args($method);
        $args['activityId'] = $event_id;
        $args['sign'] = $this->get_sign($args);
        /*
        parse_str：将一个url ?后面的参数转换成一个数组，array parse_str(url,arr)。
        parse_url：将一个完整的url解析成数组，array parse_url(string url)。
        http_build_query：再简要解释下，将一个数组转换成url ?后面的参数字符串，会自动进行urlencode处理
         */
        $url = $this->base . '?' . http_build_query($args);
        $r = self::curl_get($url);

        return $r;
    }


    /**
     * @param string $status 状态 0：未开始 1：已开始 3：已结束
     * @param string $offSet 第几条开始查询 默认0
     * @param string $fetchSize 一次查询多少条 默认10 不超过100
     * @return false|string
     */
    public function search($status= '',$offSet = '', $fetchSize = '') {

        $method = 'lecloud.cloudlive.vrs.activity.vrsinfo.search';
        $args = $this->get_general_args($method);
        ($status  || $status == 0) ? $args['activityStatus'] = $status : '';
        $offSet  ? $args['offSet'] = $offSet : '';
        $fetchSize  ? $args['fetchSize'] = $fetchSize : '';
        $args['sign'] = $this->get_sign($args);

        $url = $this->base . '?' . http_build_query($args);
        $r = self::curl_get($url);

        return $r;
    }

    /*

    $args = array(
    'activityId'=>'',
    'activityName'=>'',
    'startTime'=>'',
    'endTime'=>'',
    'coverImgUrl'=>'',
    'description'=>'',
    );

     */
    /**
     * 更新直播活动
     * @param  [type] $id [description]
     * @param  [type] $args [description]
     * @return [type]     [description]
     */
    public function update($args = array()) {
        /*
            A2016012201455
        */
        extract($args);
        $method = 'letv.cloudlive.activity.modify';

        $args = self::parse_args($args, $this->default_args);

        if (isset($args['activityCategory'])) {
            unset($args['activityCategory']);
        }

        $base_args = $this->get_general_args($method);

        $base_args = array_merge($base_args, $args);

        $base_args['sign'] = $this->get_sign($base_args);

        $r = self::curl_post($this->base, $base_args);
        return $r;
    }


    /**
     * 尚未开放删除接口。
     * @param  [type] $event_id [description]
     * @return [type]           [description]
     */
    public function delete($event_id) {

        $method = 'letv.cloudlive.activity.stop';
        $args = array('activityId' => $event_id);
        $base_args = $this->get_general_args($method);
        $base_args = array_merge($base_args, $args);
        $base_args['sign'] = $this->get_sign($base_args);
        $r = self::curl_post($this->base, $base_args, true);
        return $r;

    }


    /**
     * 直播活动安全设置: 参数待确定
     * @param  array  $args 参见 $this->security_args
     * @return [type]       [description]
     */
    public function security($args = array()) {

        $method = 'letv.cloudlive.activity.sercurity.config';
        $args = self::parse_args($args, $this->security_args);
        $base_args = $this->get_general_args($method);
        $base_args = array_merge($base_args, $args);
        $base_args['sign'] = $this->get_sign($base_args);
        $r = self::curl_post($this->base, $base_args);
        return $r;
    }

    /**
     * 上传活动封面(修改直播活动封面,直接上传一个图片，支持格式jpg、png、gif): 返回coverImgUrl
     * @param  [type] $args [description]
     * @return [type]       [description]
     */
    public function upload_cover($args) {

        $method = 'lecloud.cloudlive.activity.modifyCoverImg';
        /*

            $args = array(
            'activityId'=>'',
            //File 是 要上传的封面图片
            'file'=>''
            );

            https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest/Using_XMLHttpRequest
        */
        $base_args = $this->get_general_args($method);
        $base_args = array_merge($base_args, $args);
        $base_args['sign'] = $this->get_sign($base_args);


        $r = self::curl_post($this->base, $base_args, true);
        return $r;

    }

    public function upload_cover4($args) {

    }

    public function is_event_editable($event_id, $return_time = false) {

        $event = $this->retrieve($event_id);
        if (!$event) {
            // return 'false1';
            return false;
        }
        $arr = json_decode($event);
        if (!isset($arr[0]) || !property_exists($arr[0], 'endTime')) {
            //return 'false2';
            return false;
        }
        $now = date('YmdHis');
        $event_endtime = $arr[0]->endTime;
        if ($return_time) {
            return $event_endtime;
        }
        $editable = ($now > $event_endtime) ? false : true;
        return $editable;

    }
    /**
     * [get_event_status description]
     * @param  [type] $event_id [description]
     * @return [type]           [description]
     */
    public function get_event_status($event_id, $return_start_time = false) {

        $event = $this->retrieve($event_id);
        if (!$event) {
            return false;
        }
        $arr = json_decode($event);
        if (!isset($arr[0]) || !property_exists($arr[0], 'activityStatus')) {
            return false;
        }
        if ($return_start_time) {

            return array(
                'status' => $arr[0]->activityStatus,
                'startTime' => $arr[0]->startTime,
                'endTime' => $arr[0]->endTime,
            );
        }
        return $arr[0]->activityStatus;

    }

    /**
     * 据给定的代表状态的activityStatus(整数)，以布尔值或文本方式友好地返回直播状态
     * @param  int $status_int    0-3,包括0和3之间的一个整数
     * @return string             返回直播状态
     */
    public function parse_status($status_int, $return_bool = true) {

        $int = abs($status_int);
        if ($int > 3) {

            if ($return_bool) {
                return false;
            }
            return '已结束';
        }

        if ($return_bool) {

            return $int ? false : true;

        } else {

            $arr = array('未开始', '直播中', '已中断', '已结束');
            return $arr[$int];

        }

    }
    /**
     * 获取在线播放URL.
     * @param  string $event_id      直播活动的$event_id
     * @return string|false     直播地址或者false
     */
    public function get_play_url($event_id) {
        $method = 'lecloud.cloudlive.activity.playerpage.getUrl';
        $args = $this->get_general_args($method);
        $args['activityId'] = $event_id;
        $args['sign'] = $this->get_sign($args);
        $url = $this->base . '?' . http_build_query($args);
        $r = $this->curl_get($url);
        if(false===stripos($r,'playPageUrl')){
            return false;
        }
        $r = json_decode($r);
        $r = $r->playPageUrl;
        return $r;

    }

    /**
     * 功能说明：获取推流token，用于调用乐视云上传SDK使用。
     * @param  string $event_id      直播活动的$event_id
     * @return string|false      json字符串或false
     */
    public function get_push_stream_token($event_id) {

        $method = 'letv.cloudlive.activity.getPushToken';
        $args = $this->get_general_args($method);
        $args['activityId'] = $event_id;
        $args['sign'] = $this->get_sign($args);
        $url = $this->base . '?' . http_build_query($args);
        $r = $this->curl_get($url);
        return $r;
    }


    /**
     * 获取推流URL以及推流ID
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function get_push_stream_url($event_id) {

        $method = 'letv.cloudlive.activity.getPushUrl';
        $args = $this->get_general_args($method);
        $args['activityId'] = $event_id;
        $args['sign'] = $this->get_sign($args);
        $url = $this->base . '?' . http_build_query($args);
        $r = $this->curl_get($url);
        return $r;

    }

    /**
     * 返回通用参数数组
     * @param  string $method 乐视云-直播-方法
     * @return array         通用参数数组
     */
    public function get_general_args($method) {
        $arr = array(
            'method' => $method,
            'ver' => $this->ver,
            'userid' => $this->user_id,
            'timestamp' => $this->time,
        );
        return $arr;
    }


    /**
     * 解析参数数组
     * @param  array $args     实际传入的参数数组
     * @param  array $defaults 作为兜底的默认参数数组
     * @return array           供实际使用的参数数组
     */
    static public function parse_args($args, $defaults) {

        return self::wp_parse_args($args, $defaults);
    }

    public function get_microtime() {
        list($t1, $t2) = explode(' ', microtime());
        return (float) sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    /**
     * 获取签名
     * @param  array $arr 要签名的参数
     * @return string     签名
     */
    public function get_sign($arr) {
        ksort($arr);
        $keyStr = '';
        foreach ($arr as $key => $value) {
            $keyStr .= $key . $value;
        }
        $keyStr .= $this->app_key;
        $key = md5($keyStr);
        return $key;
    }

    /**
     * CURL GET
     * @param  string $url       GET的URL
     * @return string|false      [description]
     */
    static public function curl_get($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;

    }
    /**
     * CURL POST
     * @param  [type] $url  [description]
     * @param  [type] $args [description]
     * @return [type]       [description]
     */
    static public function curl_post($url, $args, $return_status_code = false) {


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, self::get_header());
        if(strlen($url) > 5 && strtolower(substr($url,0,5)) == "https" ) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        if (is_array($args) && 0 < count($args)){
            $postBodyString = "";
            $postMultipart = false;
            foreach ($args as $k => $v){
                if("@" != substr($v, 0, 1)){
                    $postBodyString .= "$k=" . urlencode($v) . "&";
                }else{
                    //文件上传用multipart/form-data，否则用www-form-urlencoded
                    $postMultipart = true;
                }
            }
            unset($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart){
                curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
            }else{
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString,0,-1));
            }
        }

        $reponse = curl_exec($ch);

        if (curl_errno($ch)){
//            throw new Exception(curl_error($ch),0);
//            E(curl_error($ch),0);
        }else{
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode){
//                throw new Exception($reponse,$httpStatusCode);
//                E($reponse,$httpStatusCode);
            }
        }
        curl_close($ch);
        list($header, $body) = explode("\r\n\r\n", $reponse, 2);
        return $body;
    }


    /*=============Helper===============*/
    /*
          2016-01-25 03:35
          to
          20160125033500
    */
    public function parse_datetime_for_lecloud($str) {

        $r = (string) str_replace(array('-', ':', ' '), '', $str);
        $r .= '00';
        return $r;

    }
    /*
        20160125033500
        to
        2016-01-25 03:35
    */
    public function parse_datetime_for_local($str = '20160123004104', $return_arr = false) {

        if (strlen($str) != 14) {
            return false;
        }
        $y = substr($str, 0, 4);
        $m = substr($str, 4, 2);
        $d = substr($str, 6, 2);
        $h = substr($str, 8, 2);
        $i = substr($str, 10, 2);
        if (true === $return_arr) {
            $r = array(
                'd' => $y . '-' . $m . '-' . $d,
                't' => $h . ':' . $i,
            );
            return $r;
        } else {
            return $y . '-' . $m . '-' . $d . ' ' . $h . ':' . $i;
        }
    }

    /**
     * 为 CURL POST 准备 header.
     * @return array header的数组.
     */
    public  static  function get_header() {

        $header = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
            'charset' => 'utf-8',
        );

        return $header;
    }

    /**
     * For TEST
     * @return [type] [description]
     */
    public  static function get_start_time() {

        $time = date('YmdHis');
        return $time;
    }
    /**
     * For TEST
     * @return [type] [description]
     */
    public  static function get_end_time($starttime) {

        $time = strtotime($starttime);

        $r = date('YmdHis', $time + 2 * 3600);

        return $r;

    }


    public static function wp_parse_args( $args, $defaults = '' ) {
        if ( is_object( $args ) )
            $r = get_object_vars( $args );
        elseif ( is_array( $args ) )
            $r =& $args;
        else
            self::wp_parse_str( $args, $r );

        if ( is_array( $defaults ) )
            return array_merge( $defaults, $r );
        return $r;
    }


    public static function wp_parse_str( $string, &$array ) {
        parse_str( $string, $array );
        if ( get_magic_quotes_gpc() )
            $array = self::stripslashes_deep( $array );
    }

    public static function stripslashes_deep( $value ) {
        return self::map_deep( $value, array(__CLASS,'stripslashes_from_strings_only' ));
    }

    public static function stripslashes_from_strings_only( $value ) {
        return is_string( $value ) ? stripslashes( $value ) : $value;
    }

    public static function map_deep( $value, $callback ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $index => $item ) {
                $value[ $index ] = self::map_deep( $item, $callback );
            }
        } elseif ( is_object( $value ) ) {
            $object_vars = get_object_vars( $value );
            foreach ( $object_vars as $property_name => $property_value ) {
                $value->$property_name = self::map_deep( $property_value, $callback );
            }
        } else {
            $value = call_user_func( $callback, $value );
        }

        return $value;
    }


}
