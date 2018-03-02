<?php
namespace RuiTu\SmsSend;
/**
 * 发送短信
 * Class SmsServ
 */
class ChuangLanSmsSender{

    private static $URL = "http://sapi.253.com/msg/HttpBatchSendSM";    //接口地址
    private static $SMS_DATA = [
        "account" => "BG1RTWL_lqw",         //账号
        "pswd" => "910213abcD",             //密码
        "needstatus" => false,              //是否需要状态报告，需要true，不需要false
        "extno" => "002"                    //扩展码
    ];
    private static $REMIND_MSG = [
        '0' => '发送成功',
        '101' => '无此用户',
        '102' => '密码错',
        '103' => '提交过快',
        '104' => '系统忙',
        '105' => '敏感短信',
        '106' => '消息长度错',
        '107' => '错误的手机号码',
        '108' => '手机号码个数错',
        '109' => '无发送额度',
        '110' => '不在发送时间内',
        '111' => '超出该账户当月发送额度限制',
        '112' => '无此产品',
        '113' => 'extno格式错',
        '115' => '自动审核驳回',
        '116' => '签名不合法，未带签名',
        '117' => 'IP地址认证错',
        '118' => '用户没有相应的发送权限',
        '119' => '用户已过期',
        '120' => '内容不是白名单',
    ];

    private static $TEMPLATES = [
        "notice_release" => "有人发活了,发布人：{1},手机号:{2}.详情请登录后台查看.",
    ];


    public function __construct(){
    }

    public static function sendSms($mobile = "", $msg = ""){
        $smsData = self::$SMS_DATA;
        $smsData["mobile"] = $mobile;
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
            $ret['ret'] = $callbackRst[1] == 0 ? 1 : $callbackRst[1];
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