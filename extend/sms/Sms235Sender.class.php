<?php
namespace RuiTu\SmsSend;
/**
 * 发送短信
 * https://zz.253.com/
 * Class SmsServ
 */
class Sms235Sender{

    private static $URL = "https://sms.253.com/msg/send";    //接口地址
    private static $SMS_DATA = [
        "un" => "N8593141",         //账号
        "pw" => "0jEQS6t8e",        //密码
        "rd" => false,              //是否需要状态报告，需要true，不需要false
        "ex" => ""                  //扩展码 选填
    ];
    private static $REMIND_MSG = [
        '0' => '提交成功',
        '101' => '无此用户',
        '102' => '密码错',
        '103' => '提交过快（提交速度超过流速限制）',
        '104' => '系统忙（因平台侧原因，暂时无法处理提交的短信）',
        '105' => '敏感短信（短信内容包含敏感词）',
        '106' => '消息长度错（>536或<=0）',
        '107' => '包含错误的手机号码',
        '108' => '手机号码个数错（群发>50000或<=0）',
        '109' => '无发送额度（该用户可用短信数已使用完）',
        '110' => '不在发送时间内',
        '113' => 'extno格式错（非数字或者长度不对）',
        '116' => '签名不合法或未带签名（用户必须带签名的前提下）',
        '117' => 'IP地址认证错,请求调用的IP地址不是系统登记的IP地址',
        '118' => '用户没有相应的发送权限（账号被禁止发送）',
        '119' => '用户已过期',
        '120' => '违反放盗用策略(日发限制) --自定义添加',
        '121' => '必填参数。是否需要状态报告，取值true或false',
        '122' => '5分钟内相同账号提交相同消息内容过多',
        '123' => '发送类型错误',
        '124' => '白模板匹配错误',
        '125' => '匹配驳回模板，提交失败',
        '126' => '审核通过模板匹配错误',
        '128' => '内容编码失败',
    ];

    private static $TEMPLATES = [
        "notice_release" => "有人发活了,发布人：{1},手机号:{2}.详情请登录后台查看.",
    ];


    public function __construct(){
    }

    public static function sendSms($mobile = "", $msg = ""){
        $smsData = self::$SMS_DATA;
        $smsData["phone"] = $mobile;
        $smsData["msg"] = $msg;
        $smsData = http_build_query($smsData);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $smsData);
        $callback = curl_exec($ch);
        curl_close($ch);
        $callbackRst = self::execResult($callback);
        if(isset($callbackRst[1])){
            $ret['ret'] = $callbackRst[1];
            $ret['msg'] = self::$REMIND_MSG[$callbackRst[1]];
        }else{
            $ret['ret'] = $callbackRst[1];
            $ret['msg'] = "发送失败";
        }
        return $ret;
    }


    /**
     * 处理返回值
     *
     */
    public static function execResult($result){
        $result = preg_split("/[,\r\n]/", $result);
        return $result;
    }


}