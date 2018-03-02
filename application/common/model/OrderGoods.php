<?php
/**
 * rtshop
 * ============================================================================
 * 版权所有 2015-2027 苏州睿途网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ruitukeji.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: IT宇宙人
 * Date: 2015-09-09
 */
namespace app\common\model;
use think\Model;
class OrderGoods extends Model {

    protected $table='';

    //自定义初始化
    protected function initialize()
    {
        parent::initialize();
        $select_year = select_year(); // 查询 三个月,今年内,2016年等....订单
        $prefix = C('database.prefix');  //获取表前缀
        $this->table = $prefix.'order_goods'.$select_year;
    }

    public function goods()
    {
        return $this->hasOne('goods','goods_id','goods_id');
    }
}
