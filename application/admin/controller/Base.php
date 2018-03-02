<?php

/**
 * rtshop
 * ============================================================================
 * 版权所有 2015-2027 苏州睿途网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ruitukeji.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 当燃
 * Date: 2015-09-09
 */
namespace app\admin\controller;
use app\admin\logic\UpgradeLogic;
use think\Controller;
 
use think\Session;
class Base extends Controller {

    /**
     * 析构函数
     */
    function __construct() 
    {
	    Session::start();
        header("Cache-control: private");  // history.back返回后输入框值丢失问题 参考文章 http://www.ruitukeji.cn/article_id_1465.html  http://blog.csdn.net/qinchaoguang123456/article/details/29852881
        parent::__construct();		
        $upgradeLogic = new UpgradeLogic();
        $upgradeMsg = $upgradeLogic->checkVersion(); //升级包消息        
        $this->assign('upgradeMsg',$upgradeMsg);
        tpversion();
   }    
    
    /*
     * 初始化操作
     */
    public function _initialize() 
    {
        $this->assign('action',ACTION_NAME);
        //过滤不需要登陆的行为
        if(in_array(ACTION_NAME,array('login','logout','vertify','forget_pwd'))){
        }else{
        	if(session('admin_id') > 0 ){
                cache('admin_id_'.session('admin_id'),"wkf","1400");
        		$this->check_priv();//检查管理员菜单操作权限
        	}else{
        	    $this->topHref("请先登录!!",U('Admin/Admin/login'));
                //exit("<script>alert('请先登录');top.location.href='" . $login_url . "'</script>");
        	}
        }
        $this->public_assign();
    }
    
    /**
     * 保存公告变量到 smarty中 比如 导航 
     */
    public function public_assign()
    {
       $rtshop_config = array();
       $tp_config = M('config')->select();       
       foreach($tp_config as $k => $v)
       {
          $rtshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       }
       $this->assign('rtshop_config', $rtshop_config);
    }
    
    public function check_priv()
    {
    	$ctl = CONTROLLER_NAME;
    	$act = ACTION_NAME;
		$act_list = session('act_list');
		//无需验证的操作
		$uneed_check = array('login','logout','vertifyHandle','vertify','imageUp','upload','login_task','forget_pwd');
    	if($ctl == 'Index' || $act_list == 'all'){
    		//后台首页控制器无需验证,超级管理员无需验证
    		return true;
    	}elseif(request()->isAjax() || strpos($act,'ajax')!== false || in_array($act,$uneed_check)){
    		//所有ajax请求不需要验证权限
    		return true;
    	}else{
    		$right = M('system_menu')->where("id in ($act_list)")->cache(true)->getField('right',true);
    		foreach ($right as $val){
    			$role_right .= $val.',';
    		}
    		$role_right = explode(',', $role_right);
    		//检查是否拥有此操作权限
    		if(!in_array($ctl.'@'.$act, $role_right)){
    			$this->error('您没有操作权限'.($ctl.'@'.$act).',请联系超级管理员分配权限',U('Admin/Index/welcome'));
    		}
    	}
    }
	
    public function ajaxReturn($data,$type = 'json'){                        
        exit(json_encode($data, JSON_UNESCAPED_UNICODE));
    }    	
}