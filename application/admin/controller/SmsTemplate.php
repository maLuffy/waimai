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
 * Author: wangqh
 * Date: 2015-09-09
 *  短信平台短信模板管理
 */
namespace app\admin\controller;

use think\Page;

class SmsTemplate extends Base{

    public  $send_scene;
    
    public function _initialize() {
        parent::_initialize();
        
        // 短信使用场景
        $this->send_scene = C('SEND_SCENE');
        $this->assign('send_scene', $this->send_scene);
        
    }
    
    public function index(){        
        $smsTpls = M('sms_template')->select();
	$this->assign('smsTplList',$smsTpls);        
        return $this->fetch("sms_template_list");       
    }
    
    /**
     * 添加修改编辑  短信模板
     */
    public  function addEditSmsTemplate(){
        
        $id = I('tpl_id/d',0);
        $model = M("sms_template");
        
        if(IS_POST)
        {    
            $data = I('post.');
            $data['add_time'] = time();            
            
            if($id){
                $model->update($data);
            }else{
                $id = $model->save($data);
            }
            $this->success("操作成功!!!",U('Admin/SmsTemplate/index'));
            exit;
        } 
        
        
        if($id){
            //进入编辑页面
            $smsTemplate = $model->where(" tpl_id = ".$id)->find(); 
            $this->assign("smsTpl" , $smsTemplate );
            $sceneName = $this->send_scene[$smsTemplate['send_scene']][0];
            $sendscene = $smsTemplate['send_scene'];
            $this->assign("send_name" , $sceneName );
            $this->assign("send_scene_id" , $sendscene );
        }else{
            //进入添加页面
            //查找已经添加了的短信模板
            $scenes = $model->getField("send_scene" , true);
            $filterSendscene = array();
            //过滤已经添加过滤的短信模板
            foreach ($this->send_scene as $key => $value){
                if(!in_array($key, $scenes)){
                    $filterSendscene[$key] = $value;
                }
            }
        }
         
        
        $this->assign("send_scene" , $filterSendscene );
        return $this->fetch("_smsTemplate");
    }
    
    /**
     * 删除订单
     */
   public function delTemplate(){
       $id = I('id');
       $model = M("sms_template");
       $row = $model->where(array('tpl_id' => $id))->delete();
       if ($row){
           $return_arr = array('status' => 1,'msg' => '删除成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
       }else{
           $return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);  
       } 
       $this->ajaxReturn($return_arr,'json');
       
   }


   /**
    * 红包雨设置
    */
   public function signList(){
       $where=array();
       /*$where['end_time']=array('gt',time());
       $where['start_time']=array('lt',time());*/
       if(IS_GET){
           $active_num=I('select_name');
           if($active_num){
               $where['active_num|active_name']=array("like","%$active_num%");
           }
           $start_time=I('start_time',"");
           $end_time=I('end_time',"");
           if($start_time && $end_time){
               $where['start_time']  = array('egt',strtotime($start_time));
               $where['end_time']=array('elt',strtotime($end_time));
               if(strtotime($start_time)>strtotime($end_time)){
                   $this->error("结束时间不能在开始时间之前！！");
               }
           }else{
               if($start_time){
                   $where['start_time']  = array('egt',strtotime($start_time));
               }
               if($end_time){
                   $where['end_time']=array('elt',strtotime($end_time));
               }
           }
       }
       $count = M('red_package s')/*->join('users u','u.user_id = s.user_id','LEFT')*/->where($where)->count();
       $Page  = new Page($count,10);
       //获取订单列表
       $package_list = M('red_package s')->where($where)->order("id desc")->limit($Page->firstRow,$Page->listRows)->select();
       //检查是否还有活动开启状态
       $res_status=M("red_package")->where(array("status"=>"1"))->select();
       if(count($res_status)>0){
           $count_status="1";
       }else{
           $count_status="0";
       }
       //检查活动是否过期    如果过期只能查看
       foreach($package_list as $k=>&$v){
           if($v['end_time']<=time()){
                $v['time_over']="1";
           }
       }
       $this->assign("count_status",$count_status);
       $this->assign('package',$package_list);
       $this->assign('action_name',ACTION_NAME);
       $this->assign("date",time());
       $this->assign("count_list",$count);
       $this->assign("page",$Page->show_page());
       return $this->fetch();
   }


    /**
     * 新增红包雨
     */
    public function red_edit(){
        $id=I('id',0);
        if($id){
            $store_list=array();
            $red_info=M('red_package')->where(array("id"=>$id))->find();
            $store_list=M('red_store')->where(array("red_id"=>$red_info['id']))->select();
            if($red_info['end_time']<time()){
                $red_info['not_save']="1";
            }
            $this->assign("info",$red_info);
            $this->assign("store_list",$store_list);
        }else{
            if(IS_POST){
                $data=I("post.");
                if($data['red_id']){
                    $save_id=$data['red_id'];
                    $data['start_time']=strtotime($data['start_time']);
                    $data['end_time']=strtotime($data['end_time']);
                    if($data['start_time']>$data['end_time']){
                        $this->error("开始时间不能在结束时间前!!");
                    }
                    unset($data['red_id']);
                    $res=M('red_package')->where(array("id"=>$save_id))->save($data);
                }else{
                    //unset($data)
                    $data['start_time']=strtotime($data['start_time']);
                    $data['end_time']=strtotime($data['end_time']);
                    if($data['start_time']>$data['end_time']){
                        $this->error("开始时间不能在结束时间前!!");
                    }
                    $redpageage_model=D('RedPackage');
                    $res=$redpageage_model->create($data);
                }
                if($res){
                    $this->success("操作成功!");
                }else{
                    $this->error("操作失败！！");
                }
            }
        }

        return $this->fetch(); 
    }

    /**
     * 修改红包雨状态
     */
    public function red_status_edit(){
        $status=I('status');
        $id=I('id');
        if($status=='1'){
/*            M('red_package')->where(array("id"=>array("egt",'1')))->save(array("status"=>"1"));*/
            $status='0';
        }else{
            M('red_package')->where(array("id"=>array("egt",'1')))->save(array("status"=>"0"));
            $status='1';
        }
        $res=M('red_package')->where(array("id"=>$id))->save(array("status"=>$status));

        $cache = new \think\Cache;
        if($status=="1"){
            $redRain = M("red_package") -> field("id,total_money,total_money surplus_money,start_time,end_time,status") -> where(array("id"=>$id)) -> find();//获取活动总金额
            $redrain=tpCache('redrain');
            $redRain=array_merge($redRain,$redrain);
            selfCacheWrite("redRecharInfo",$redRain);
        }else{
            selfCacheWrite('redRecharInfo',null);
            selfCacheWrite("redJoinStoreList",NULL);
        }
        if($res!==false){
            $this->ajaxReturn(['status'=>1,'data'=>$status,'msg'=>'修改成功!']);
        }else{
            $this->ajaxReturn(['status'=>0,'msg'=>'修改失败!']);
        }
    }

    /**
     * 删除红包雨
     */
    public function red_del(){
        $id=I('id');
        if($id){
            $res=M('red_package')->where(array("id"=>$id))->delete();
            if($res!==false){
                $this->success("删除成功!");
            }else{
                $this->error("删除失败!");
            }
        }
    }


   /**
    * 红包雨赞助商维护
    */
   public function red_Sponsor(){
       $id=I('id');
       $store_id=I('store_id');
       if($store_id){
           $store_info=M('red_store')->where(array("id"=>$store_id))->find();
           $id=$store_info['red_id'];
           $this->assign('store_info',$store_info);
       }
       $package=M('red_package')->where(array("id"=>$id))->find();
       //判断还可添加多少金额
       $red_store_list=M('red_store')->where(array("red_id"=>$id))->select();
       $store_money=0;
       foreach($red_store_list as $k=>$v){
           $store_money+=$v['contribute_money'];
       }
       $left_over=$package['total_money']-$store_money;
       $this->assign('left_over',$left_over);
       $this->assign('info',$package);
       return $this->fetch();
   }


    /**
     * 添加赞助商
     */
    public function add_redSponsor(){
        $data=I('post.');
        if($data['store_id'] && $data['red_package']){
            if($data['act']=="update"){
                /*$join_arr['red_id']=$data['red_package'];
                $join_arr['store_id']=$data['store_id'];*/
                $join_arr['store_name']=$data['store_name'];
                $join_arr['store_logo']=$data['store_logo'];
                $join_arr['contribute_money']=$data['contribute_money'];
                $join_arr['red_desc']=$data['red_desc'];
                $res=M('red_store')->where(array("red_id"=>$data['red_package'],"store_id"=>$data['store_id']))->save($join_arr);
            }else{
                $join_arr['red_id']=$data['red_package'];
                $join_arr['store_id']=$data['store_id'];
                $join_arr['store_name']=$data['store_name'];
                $join_arr['store_logo']=$data['store_logo'];
                $join_arr['contribute_money']=$data['contribute_money'];
                $join_arr['red_desc']=$data['red_desc'];
                $res=M('red_store')->add($join_arr);
            }
            store_money_pro($data['red_package']);
            if($res!==false){
                // U('Admin/Goods/categoryList')
                $this->success("添加成功",U('SmsTemplate/signList'));
            }else{
                $this->error("添加失败");
            }
        }else{
            $this->error("缺少关键参数店铺id和红包id，操作失败!");
        }
    }

    /**
     * 删除赞助商
     */
    public function red_Sponsor_del(){
        $id=I('id');
        if($id){
           $res=M('red_store')->where(array("id"=>$id))->delete();
            if($res!==false){
                $this->success("操作成功");
            }else{
                $this->error("操作失败");
            }
        }
    }


   /**
    * 获取店铺列表
    */
   public function get_store(){
       $red_id=I('red_id');
       $store_name=I('store_name');
       if($store_name){
           $map['s.store_name']=array("like","%$store_name%");
       }
       $have_store=M('red_store')->where(array("red_id"=>$red_id))->field('id,store_id')->select();
       $where=array();
       $count=M('store s')->join('users u','u.user_id = s.user_id','LEFT')->where($where)->where($map)->count();
       $Page  = new Page($count,10);
       $store=M('store s')->join('users u','u.user_id = s.user_id','LEFT')->where($where)->where($map)->order("store_id desc")->field('store_id,store_name,user_name,seller_name,u.mobile,store_logo')->limit($Page->firstRow,$Page->listRows)->select();
       if(count($have_store)>0){
           foreach ($store as $k=>&$v){
               foreach($have_store as $k1=>$v1){
                   if($v['store_id']==$v1['store_id']){
                       $v['haveing']="1";
                   }
               }
           }
       }
       $this->assign("red_id",$red_id);
       $this->assign("pager",$Page);
       $this->assign("page",$Page->show());
       $this->assign('goodsList',$store);
       return $this->fetch();
   }


   /**
    * 红包雨设置
    */
   public function red_config(){
       if(IS_POST){
           $data=I('post.');
           if($data['red_1'] && $data['red_2']){
               $data['red_drawn_interval']=$data['red_1'].",".$data['red_2'];
               unset($data['red_1']);
               unset($data['red_2']);
           }else{
               $data['red_drawn_interval']=0;
           }
            tpCache('redrain',$data);
            $this->success("操作成功");

       }
       $inc_type =  'redrain';
       $inc_type=tpCache($inc_type);
       $red_drawn_interval=explode(',',$inc_type['red_drawn_interval']);
       $inc_type['red_1']=$red_drawn_interval['0'];
       $inc_type['red_2']=$red_drawn_interval['1'];
       $this->assign('config_redrain',$inc_type);
       $this->assign('action_name',ACTION_NAME);
       return $this->fetch();
   }



   /**
    * 获取中奖用户
    */
   public function get_red_user(){
       $red_id=I('red_id');
       if($red_id){
           $nickname=I('nickname');
           if($nickname){
               $where['u.nickname|u.mobile']=array("like","%$nickname%");
           }
           $start_time=I('start_time');
           if($start_time){
               $where['r.create_time']=array("egt",strtotime($start_time));
               $this->assign("start_time",date("Y-m-d H:i:s",strtotime($start_time)));
           }
           $where['red_id']=$red_id;
           $count=M('red_log r')->join('users u','u.user_id = r.user_id','LEFT')->where($where)->count();
           $Page  = new Page($count,10);
           $red_user_list=M('red_log r')->join('users u','u.user_id = r.user_id','LEFT')->where($where)->order("create_time desc")->field('r.*,u.nickname,u.mobile')->limit($Page->firstRow,$Page->listRows)->select();
           $this->assign("red_id",$red_id);
           $this->assign("pager",$Page);
           $this->assign("page",$Page->show());
           $this->assign('goodsList',$red_user_list);
           return $this->fetch();
       }else{
           $this->error("没有红包雨期号!");
       }
   }




}