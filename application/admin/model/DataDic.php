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
class DataDic extends Model {
    protected $tableName = 'ruit_shop_industry';

    //自动验证 示例
    // array(验证字段,验证规则,错误提示,[验证条件,附加规则,验证时间])
    protected $_validate = array(
    // 手机验证:必填、格式需正确、唯一性
    //        array('title','require','标题必须填写！'),
    //        array('content','require','标题必须填写'),
            //array('name','','类别名称已存在！',0,'unique',1),
            array('industry_name','','帐号名称已经存在！',0,'unique',1),
    );


    // 自动完成
    // array(填充字段,填充内容,[填充条件,附加规则])
    protected $_auto=array(
        // 增加和修改时,将密码填充为123456,并使用MD5加密
        //array('manager_password','defaultPassword',3,'callback')
    );

    /**
     * 作用:在添加和修改时,密码填充为123456的md5加密形式
     * @return string
     */
    /*public function defaultPassword(){
        $strPassword=md5('123456');
        return $strPassword;
    }*/

    /*protected $_validate = array(
    // 新增时验证标题唯一
            array('title','','标题已经存在！',0,'unique',1),
    };*/



    public function createUser($data){
        echo '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>成功调用！';
    }



    /**
     * 获取所有数据
     */
    public function getDatadic($where){
        $count=M('shop_industry')->where($where)->count();
        $Page=new Page($count,C('page_listRows'));
        $list=M('shop_industry')->where($where)->order("industry_id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $data['list']=$list;
        $data['list_count']=count($list);
        $data['page']=$Page->show_page();
        return $data;
    }

    public function getData_all($num){
        if($num){
            $where['parent_id']=array('eq',$num);
        }else{
            $where['parent_id']=array('gt',0);
        }
        $count=M('data_dic')->where($where)->order("order_by asc,id desc")->count();
        $Page=new Page($count,C('page_listRows'));
        $data=M('data_dic')->where($where)->order("order_by asc,parent_id asc,id asc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $return['data']=$data;
        $return['page']=$Page->show();
        return $return;
    }

    /*
     * 获取分类信息
     */
    public function getDatadic_type($parent_id){
        $data_dic_p=M('data_dic')->where(array("id"=>$parent_id))->find();
        return $data_dic_p;
    }


    //显示所有分类数据
    public function get_Children($name=null){
        if($name){
            $map['name']=array("eq","$name");
        }
        $data_dic=M('data_dic')->where(array("parent_id"=>0))->where($map)->select();
        foreach ($data_dic as $k=>&$v){
            $v['children']=M('data_dic')->where(array("parent_id"=>$v['id'],"is_show"=>"1"))->order("order_by asc")->select();
        }
        return $data_dic;
    }
}
