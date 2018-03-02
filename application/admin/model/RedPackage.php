<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: IT宇宙人
 * Date: 2015-09-09
 */

namespace app\admin\model;
use think\Model;
use think\Page;
class RedPackage extends Model {
    protected $tableName = 'ruit_red_package';

    //自动验证 示例
    // array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
     //手机验证:必填、格式需正确、唯一性
        array('active_num','require','标题必须填写！'),
        array('active_name','require','标题必须填写'),
            //array('name','','类别名称已存在！',0,'unique',1),
        array('active_num','','活动期号已经存在！',0,'unique',1),
        array('active_name','','活动名称已经存在！',0,'unique',1),
    );


    public function createUser($data){
        echo '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>成功调用！';
    }


}
