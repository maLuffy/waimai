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
use think\AjaxPage;
use think\Page;
use think\Loader;
use think\Db;
use app\admin\logic\UsersLogic;
class User extends Base {

    public function index(){
        return $this->fetch();
    }

    /**
     * 会员列表
     */
    public function ajaxindex(){
        // 搜索条件
        $condition = array();
        $search_key=I('search_key');
        if($search_key){
            $where['mobile|email'] =array("like","%$search_key%");
        }

        I('first_leader') && ($condition['first_leader'] = I('first_leader')); // 查看一级下线人有哪些
        I('second_leader') && ($condition['second_leader'] = I('second_leader')); // 查看二级下线人有哪些
        I('third_leader') && ($condition['third_leader'] = I('third_leader')); // 查看三级下线人有哪些
        $sort_order = I('order_by').' '.I('sort');
        $count = Db::name('users')->where($condition)->where($where)->count();

        $Page  = new AjaxPage($count,10);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }

        $userList = Db::name('users')->where($condition)->where($where)->order($sort_order)
            ->limit($Page->firstRow.','.$Page->listRows)->select();


        $user_id_arr = get_arr_column($userList, 'user_id');
        if(!empty($user_id_arr))
        {
            $first_leader = DB::query("select first_leader,count(1) as count  from __PREFIX__users where first_leader in(".  implode(',', $user_id_arr).")  group by first_leader");
            $first_leader = convert_arr_key($first_leader,'first_leader');

            $second_leader = DB::query("select second_leader,count(1) as count  from __PREFIX__users where second_leader in(".  implode(',', $user_id_arr).")  group by second_leader");
            $second_leader = convert_arr_key($second_leader, 'second_leader');

            $third_leader = DB::query("select third_leader,count(1) as count  from __PREFIX__users where third_leader in(".  implode(',', $user_id_arr).")  group by third_leader");
            $third_leader = convert_arr_key($third_leader,'third_leader');
        }

        $this->assign('first_leader',$first_leader);
        $this->assign('second_leader',$second_leader);
        $this->assign('third_leader',$third_leader);

        foreach ($userList as $key => &$val)
        {
            $user_store = M("user_store") -> where("user_id = {$val['user_id']}") -> value("user_id");
            $val["is_store"] = $user_store ? "有" : "无";
            $val["total_price"] = $this -> getUserOrderAmount($val["user_id"]);
            $levelId = userRoleName($val['user_id']);
            $levelName = M("user_level")->where("level_id = {$levelId}")->getField("level_name");
            $val['level_name'] = $levelName;
        }
        $show = $Page->show();
        $this->assign('pager',$Page);
        $this->assign('userList',$userList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('relation',M('user_relation')->getField('id,level_name'));
        return $this->fetch();
    }

    public function getUserOrderAmount ($user_id)
    {
        $total_amount = M("order") -> where("user_id = $user_id") -> field("SUM(order_amount) total_amount") -> find();
//        echo ;
        $return_goods = M("return_goods") -> where("user_id = $user_id") -> field("SUM(refund_money) total_refund_money") -> find();

        $total_refund = round($total_amount["total_amount"] - $return_goods["total_refund_money"],2);
        return $total_refund;
    }

    /**
     * 会员详细信息查看
     */
    public function detail()
    {
        if(IS_POST)
        {
            //  会员信息编辑
            $password = I('post.password');
            $password2 = I('post.password2');
            $user_id = I('post.user_id');
            if($password != '' && $password != $password2){
                $this->ajaxReturn(['status'=>0,'msg'=>'两次输入密码不同']);
            }

            if($password == '' && $password2 == ''){
                unset($_POST['password']);
            }else{
                $_POST['password'] = encrypt($_POST['password']);
            }

            if(!empty($_POST['email']))
            {   $email = trim($_POST['email']);
                $c = M('users')->where("user_id != $user_id and email = '$email'")->count();
                $c && $this->ajaxReturn(['status'=>0,'msg'=>'邮箱不得和已有用户重复']);
            }

            if(!empty($_POST['mobile']))
            {
                $mobile = trim($_POST['mobile']);
                $c = M('users') -> where("user_id != $user_id AND mobile = '$mobile'")->count();
                $c && $this->ajaxReturn(['status'=>0,'msg'=>'手机号不得和已有用户重复']);
            }

            $userData["mobile"] = $mobile;

            $is_insert = insertStoreInfo($user_id,$_POST["store"], $userData);

            if( $_POST["level_relation"] && $_POST["relation_level"] )
            {
                $_POST["user_relation"] = $_POST["relation_level"];
                $_POST["level"] = $_POST["level_relation"];
                unset($_POST["level_relation"]);
                unset($_POST["relation_level"]);
            }
            $row = M('users')->where(array('user_id' => $user_id))->save($_POST);
            if($row || $is_insert)
                $this->ajaxReturn(['status'=>1, 'msg'=>'修改成功']);

            $this->ajaxReturn(['status'=>0, 'msg'=>'未作内容修改或修改失败']);
        }

        $uid = I('id/d', 0);
        $user = D('users')->where(array('user_id'=>$uid))->find();
        if(!$user)
            $this->ajaxReturn(['status' => 0,'msg' => '会员不存在']);

        if($user["user_relation"])
        {
            $user_relation = M("user_relation") -> field("id,level_id") -> where("id = {$user["user_relation"]}") -> find();

            $this->assign('user_relation', $user_relation);
        }


        $user['first_lower'] = M('users') -> where("first_leader = {$user['user_id']}")->count();
        $user['second_lower'] = M('users') -> where("second_leader = {$user['user_id']}")->count();
        $user['third_lower'] = M('users') -> where("third_leader = {$user['user_id']}")->count();
        //  获取省份
        $province = M('region') -> where(array('parent_id' => 0, 'level' => 1)) -> select();
        $user_store = M("user_store") -> where("user_id = $uid") -> select();
        $this->assign('province', $province);
        $rank_name = M("user_level") -> getField("level_id,level_name");

        $this->assign("rank_name", $rank_name);
//        print_r($user_store);die;

        $this->assign('user_store', $user_store);
        $this->assign('user', $user);
        return $this->fetch();
    }

    public function getRelation ()
    {
        $id = I("id", 0);
        if(!$id)
            ajaxReturn(['status' => 0,'msg'=>'id不能为空！']);

        $user_relation = M("user_relation") -> where("level_id = $id") -> select();
        ajaxReturn(['status' => 1,'msg'=>'返回成功！',"result" => $user_relation]);
    }

    /**
     * 会员列表
     */
    public function relationList ()
    {
        $pagesize = 10;
        $count = M('user_relation')->count();
        $page = new Page($count,$pagesize ? $pagesize : 10);
        $relation  = M('user_relation') ->limit($page->firstRow.','.$page->listRows)->select();
        foreach($relation as $key => $val)
        {
            $val["level_id"] = $this -> getRelationName($val["level_id"]);
            $relation[$key] = $val;
        }
        $this->assign('page',$page -> show_page());
        $this->assign('list_count',$count);
        $this->assign('lists',$relation);
        return $this -> fetch();
    }

    public function getRelationName ($id)
    {
        return M("user_level") -> where("level_id = $id") -> value("level_name");
    }

    /**
     * 会员关系维护
     */
    public function relation ()
    {
        $id = I("id",0);
        if(IS_POST)
        {
            $postData = I("post.");
            if(!$postData["level_id"])
                ajaxReturn(['status' => 0,'msg'=>'level_id不能为空！']);

            if(!$postData["level_name"])
                ajaxReturn(['status' => 0,'msg'=>'level_name不能为空！']);

            $where = "level_id = '{$postData['level_id']}' AND level_name = '{$postData['level_name']}'";
            if($id)
                $where .= " AND id <> $id";

            $find = M("user_relation") ->where($where) -> find();

            if($find)
                ajaxReturn(['status' => 0, 'msg'=>'该会员等级下的等级名称已经存在了！']);

            if($id)
            {
                db("user_relation") -> where("id = $id") -> update($postData);
                ajaxReturn(['status' => 1,'msg'=>'编辑成功！']);
            }
            if( db("user_relation") -> insert($postData) )
                ajaxReturn(['status' => 1,'msg'=>'添加成功！']);
            else
                ajaxReturn(['status' => 0,'msg'=>'添加失败！']);
        }

        if($id)
        {
            $relation = M("user_relation") -> where("id = $id") -> find();
        }


        $rank_name = M("user_level")->getField("level_id,level_name");
        $this->assign("rank_name", $rank_name);
        $this->assign("relation", $relation);

        return $this->fetch();
    }

    /**
     * 删除等级关系
     */
    public function delRelation ()
    {
        $id = I("id");
        if(!$id)
            ajaxReturn(['status' => 0,'msg'=>'删除失败，参数错误！']);
        $where = "id in ($id)";
        M("user_relation") -> where($where) -> delete();
        ajaxReturn(['status' => 1,'msg'=>'删除成功！']);
    }

    public function add_user()
    {
        // 获取省份
        $province = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
    	if(IS_POST)
    	{
    		$data = I('post.');
    		$user_obj = new UsersLogic();
            $user_obj->addUser($data);
            ajaxReturn(['status' => 1,'msg'=>'添加成功！']);
    	}
        $this->assign('province', $province);
    	return $this->fetch();
    }

    public function export_user(){
    	$strTable ='<table width="500" border="1">';
    	$strTable .= '<tr>';
    	$strTable .= '<td style="text-align:center;font-size:12px;width:120px;">会员ID</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="100">会员昵称</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">会员等级</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">手机号</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">邮箱</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">注册时间</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">最后登陆</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">余额</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">积分</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">累计消费</td>';
    	$strTable .= '</tr>';
    	$count = M('users')->count();
    	$p = ceil($count/5000);
    	for($i=0;$i<$p;$i++){
    		$start = $i*5000;
    		$end = ($i+1)*5000;
    		$userList = M('users')->order('user_id')->limit($start.','.$end)->select();
    		if(is_array($userList)){
    			foreach($userList as $k=>$val){
    				$strTable .= '<tr>';
    				$strTable .= '<td style="text-align:center;font-size:12px;">'.$val['user_id'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['nickname'].' </td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['level'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['mobile'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['email'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H:i',$val['reg_time']).'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H:i',$val['last_login']).'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['user_money'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pay_points'].' </td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['total_amount'].' </td>';
    				$strTable .= '</tr>';
    			}
    			unset($userList);
    		}
    	}
    	$strTable .='</table>';
    	downloadExcel($strTable,'users_'.$i);
    	exit();
    }

    /**
     * 用户收货地址查看
     */
    public function address(){
        $uid = I('get.id');
        $lists = D('user_address')->where(array('user_id'=>$uid))->select();
        $regionList = Db::name('region')->cache(true)->getField('id,name');
        $this->assign('regionList',$regionList);
        $this->assign('lists',$lists);
        return $this->fetch();
    }

    /**
     * 删除会员
     */
    public function delete(){
        $uid = I('get.id');
        $row = M('users')->where(array('user_id'=>$uid))->delete();
        if($row){
            $this->success('成功删除会员');
        }else{
            $this->error('操作失败');
        }
    }
    /**
     * 删除会员
     */
    public function ajax_delete(){
        $uid = I('id');
        if($uid){
            $row = M('users')->where(array('user_id'=>$uid))->delete();
            if($row !== false){
                $this->ajaxReturn(array('status' => 1, 'msg' => '删除成功', 'data' => ''));
            }else{
                $this->ajaxReturn(array('status' => 0, 'msg' => '删除失败', 'data' => ''));
            }
        }else{
            $this->ajaxReturn(array('status' => 0, 'msg' => '参数错误', 'data' => ''));
        }
    }

    /**
     * 账户资金记录
     */
    public function account_log(){
        $user_id = I('get.id');
        //获取类型
        $type = I('get.type');
        //获取记录总数
        $count = M('account_log')->where(array('user_id'=>$user_id))->count();
        $page = new Page($count);
        $lists  = M('account_log')->where(array('user_id'=>$user_id))->order('change_time desc')->limit($page->firstRow.','.$page->listRows)->select();

        $this->assign('user_id',$user_id);
        $this->assign('page',$page->show());
        $this->assign('lists',$lists);
        return $this->fetch();
    }

    /**
     * 账户资金调节
     */
    public function account_edit(){
        $user_id = I('uid/d',0);
        if(!($user_id > 0))
            $this->ajaxReturn(['status'=>0,'msg'=>"参数有误"]);
        $user = M('users')->field('user_id,user_money,frozen_money,pay_points,is_lock')->where('user_id',$user_id)->find();
        if(IS_POST){
            $desc = I('post.desc');
            if(!$desc)
                $this->ajaxReturn(['status'=>0,'msg'=>'请填写操作说明']);
            //加减用户资金
            $m_op_type = I('post.money_act_type');
            $user_money = I('post.user_money/f',0);
            if($m_op_type !=1 and $user_money > $user['user_money'] ){
                $this->ajaxReturn(['status'=>0,'msg'=>'用户剩余资金不足！！']);
            }
            $user_money =  $m_op_type ? $user_money : 0-$user_money;
            //加减用户积分
            $p_op_type = I('post.point_act_type');
            $pay_points = I('post.pay_points/d',0);
            if($p_op_type !=1 and $pay_points > $user['pay_points'] ){
                $this->ajaxReturn(['status'=>0,'msg'=>'用户剩余积分不足！！']);
            }
            $pay_points =  $p_op_type ? $pay_points : 0-$pay_points;
            //加减冻结资金
            $f_op_type = I('post.frozen_act_type');
            $acquire_frozen_money = I('post.frozen_money/f',0);
            $revision_frozen_money = 0;
            if( $acquire_frozen_money != 0){    //有加减冻结资金的时候

                $revision_frozen_money =  $f_op_type ? $acquire_frozen_money : 0-$acquire_frozen_money;
                $frozen_money = $user['frozen_money']+$revision_frozen_money;    //计算用户被冻结的资金

                if($f_op_type==1 and $acquire_frozen_money > $user['user_money']){
                    $this->ajaxReturn(['status'=>0,'msg'=>'用户剩余资金不足！！']);
                }
                if($f_op_type==0 and $acquire_frozen_money > $user['frozen_money']){
                    $this->ajaxReturn(['status'=>0,'msg'=>'冻结的资金不足！！']);
                }
                $user_money = $f_op_type ? 0-$acquire_frozen_money : $acquire_frozen_money ;    //计算用户剩余资金

                M('users')->where('user_id',$user_id)->update(['frozen_money' => $frozen_money]);
            }

            if(accountLog($user_id,$user_money,$pay_points,"资金调节_".$desc,0,0,'',$revision_frozen_money)){
                $this->ajaxReturn(['status'=>1,'msg'=>'操作成功','url'=>U("Admin/User/account_log",array('id'=>$user_id))]);
            }else{
                $this->ajaxReturn(['status'=>0,'msg'=>'操作失败']);
            }
            exit;
        }
        $this->assign('user',$user);
        return $this->fetch();
    }

    public function recharge(){
        $timegap = I('timegap');
    	$nickname = I('nickname');
    	$map = array();
    	if($timegap){
    		$gap = explode(',', $timegap);
    		$begin = $gap[0];
    		$end = $gap[1];
            $this->assign('start_time',$begin);
            $this->assign('end_time',$end);
    		$map['ctime'] = array('between',array(strtotime($begin),strtotime($end)));
    	}
    	if($nickname){
    		$map['u.nickname'] = array('like',"%$nickname%");
    	}
    	$count = M('recharge')->alias('r')->join('__USERS__ u','u.user_id=r.user_id')->where($map)->count();
    	$page = new Page($count,20);
    	$lists  = M('recharge')->alias('r')->join('__USERS__ u','u.user_id=r.user_id')->where($map)->order('ctime desc')->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('pager',$page);
    	$this->assign('page',$page->show());
    	$this->assign('lists',$lists);
    	return $this->fetch();
    }

    public function level(){
    	$act = I('get.act','add');
    	$this->assign('act',$act);
    	$level_id = I('get.level_id');
    	$level_info = array();
    	if($level_id){
    		$level_info = D('user_level')->where('level_id='.$level_id)->find();
    		$this->assign('info',$level_info);
    	}
    	return $this->fetch();
    }

    public function consumeQuery ()
    {
        $pagesize = I("pagesize");
        $query = I("get.");

        if($query["mobile"])
            $whereJoint[] = "mobile = '{$query["mobile"]}'";

        if($query["first_money"])
            $whereJoint[] = "first_money > '{$query["first_money"]}'";

        if($query["total_money"])
            $whereJoint[] = "total_money > '{$query["total_money"]}'";

        if($whereJoint)
             $where = implode(" AND ",$whereJoint);
        else
            $where = "1=1";

        $subsql = db("order")->where("order_status in (2,4)")->field('user_id,order_amount first_money') -> order("add_time asc") -> group('user_id')-> buildSql();
        $subsql2 = db("order")->where("order_status in (2,4)")->field('user_id,SUM(order_amount) total_money') -> group('user_id')-> buildSql();

        $count = db("users")
            -> where($where)
            -> alias('u')
            ->join([$subsql=> 'o'], 'u.user_id = o.user_id',"left")
            ->join([$subsql2=> 't'], 'u.user_id = t.user_id',"left")
            -> count();

        $page = new Page($count,$pagesize ? $pagesize : 10);
        $users  = db("users")
           -> where($where)
            -> field("u.*,o.first_money,t.total_money")
           -> alias('u')
           ->join([$subsql=> 'o'], 'u.user_id = o.user_id',"left")
           ->join([$subsql2=> 't'], 'u.user_id = t.user_id',"left")
            -> limit($page->firstRow.','.$page->listRows)
            ->select();
//print_r($users);die;
//        if( $users )
//        {
//            $this->userBatch($users);
//
//        }
        $this -> assign( "userData", $users );
        $this->assign('page',$page -> show());
        return $this -> fetch();
    }

    /**
     * 会员等级编辑
     */
    public function consumeEdit ()
    {
       $id = I("id",0);
       if(!$id)
           $this -> ajaxReturn(['status'=>0,'msg'=>'id不能为空！']);

        $users = M("users") -> where("user_id = $id") -> find();

        $user_order = $this -> getConsume($id, 1);

        $user_total_order = $this -> getConsume($id, 2);
        $users["first_money"] = $user_order["order_amount"] ? $user_order["order_amount"] : "0.00";
        $users["total_money"] = $user_total_order["total_money"] ? $user_total_order["total_money"] : "0.00";
        $rank_name = M("user_level")->getField("level_id,level_name");
        $this->assign( "rank_name", $rank_name );
        $this -> assign("users", $users);
        return $this -> fetch("consumeInfo");

    }

    /**
     * 编辑会员
     */
    public function consumeEditMember ()
    {
        $post = I("post.");
        if($post["level_id"])
            $data["level"] = $post["level_id"];
        else
            $this -> ajaxReturn(['status'=>0,'msg'=>'会员等级不能为空！']);

        $getId = I("get.id",0);

        if(!$getId)
            $this -> ajaxReturn(['status'=>0,'msg'=>'id不能为空！']);

        if($post["relation_level"])
            $data["user_relation"] = $post["relation_level"];

        M("users") -> where("user_id = $getId") -> save($data);
        $this -> ajaxReturn(['status'=>1,'msg'=>'成功！']);
    }

    public function userBatch (&$users)
    {
        foreach ( $users as $key => &$val )
        {

            $user_order = $this -> getConsume($val["user_id"]);
            $val["first_money"] = $user_order["order_amount"] ? $user_order["order_amount"] : "0.00";//首次消费金额
            $user_total_order = $this -> getConsume($val["user_id"], 2);
            $val["total_money"] = $user_total_order["total_money"] ? $user_total_order["total_money"] : "0.00";
        }
    }

    public function getConsume ($user_id, $type = 1)
    {
        if($type == 1)
        {
            if(!$user_id)
            {
                $user_order["order_amount"] = 0;
                return;
            }
            $user_order =  M("order")
                -> field("order_amount")
                -> where("user_id = $user_id AND order_status = 2")
                -> order("add_time asc") -> find();
            return $user_order;
        }elseif ($type == 2)
        {
            if(!$user_id)
            {
                $user_order["total_money"] = 0;
                return;
            }
            $user_total_order =  M("order")
                -> field("SUM(`order_amount`) total_money")
                -> where("user_id = $user_id AND order_status = 2")
                -> order("add_time asc") -> find();
            return $user_total_order;
        }
    }

    public function levelList(){
    	$Ad =  M('user_level');
        $p = $this->request->param('p');
        $res = $Ad->order('level_id')->page($p.',10')->select();
    	if($res){
    		foreach ($res as $val){
    			$list[] = $val;
    		}
    	}
    	//不允许删除
    	foreach ($list as $k=>&$v){
    	    if(in_array($v['level_id'],array("1","2","3"))){
                $v['not_del']="1";
            }
        }
    	$this->assign('list',$list);
    	$count = $Ad->count();
    	$Page = new Page($count,10);
    	$show = $Page->show();
    	$this->assign('page',$show);
    	return $this->fetch();
    }

    /**
     * 会员等级添加编辑删除
     */
    public function levelHandle()
    {
        $data = I('post.');
        $userLevelValidate = Loader::validate('UserLevel');
        $return = ['status' => 0, 'msg' => '参数错误', 'result' => ''];//初始化返回信息
        if ($data['act'] == 'add') {
            if (!$userLevelValidate -> batch() -> check($data)) {
                $return = ['status' => 0, 'msg' => '添加失败', 'result' => $userLevelValidate->getError()];
            } else {
                $r = D('user_level')->add($data);
                if ($r !== false) {
                    $return = ['status' => 1, 'msg' => '添加成功', 'result' => $userLevelValidate->getError()];
                } else {
                    $return = ['status' => 0, 'msg' => '添加失败，数据库未响应', 'result' => ''];
                }
            }
        }

        if ($data['act'] == 'edit') {
            if (!$userLevelValidate->scene('edit')->batch()->check($data)) {
                $return = ['status' => 0, 'msg' => '编辑失败', 'result' => $userLevelValidate->getError()];
            } else {
                $r = D('user_level')->where('level_id=' . $data['level_id'])->save($data);
                if ($r !== false) {
                    $return = ['status' => 1, 'msg' => '编辑成功', 'result' => $userLevelValidate->getError()];
                } else {
                    $return = ['status' => 0, 'msg' => '编辑失败，数据库未响应', 'result' => ''];
                }
            }
        }
        if ($data['act'] == 'del') {
            $r = D('user_level')->where('level_id=' . $data['level_id'])->delete();
            if ($r !== false) {
                $return = ['status' => 1, 'msg' => '删除成功', 'result' => ''];
            } else {
                $return = ['status' => 0, 'msg' => '删除失败，数据库未响应', 'result' => ''];
            }
        }
        $this->ajaxReturn($return);
    }
    /**
     * 搜索用户名
     */
    public function search_user()
    {
        $search_key = trim(I('search_key'));
        if(strstr($search_key,'@'))
        {
            $list = M('users')->where(" email like '%$search_key%' ")->select();
            foreach($list as $key => $val)
            {
                echo "<option value='{$val['user_id']}'>{$val['email']}</option>";
            }
        }
        else
        {
            $list = M('users')->where(" mobile like '%$search_key%' ")->select();
            foreach($list as $key => $val)
            {
                echo "<option value='{$val['user_id']}'>{$val['mobile']}</option>";
            }
        }
        exit;
    }

    public function integration ()
    {
        $where = "pay_points <> 0";
        $mem_id = I("mem_id", 0);
        if($mem_id)
            $where .= " AND user_id = $mem_id";
        $count = M('account_log')->where($where) -> count();
        $page = new Page($count,10);
        $lists  = M('account_log') -> where($where) -> order('change_time desc') -> limit($page->firstRow.','.$page->listRows)->select();
        foreach ( $lists as $key => &$val )
        {
            if($val["pay_points"] > 0)
                $val["pay_points"] = "+".$val["pay_points"];
        }
//        print_r($lists);die;
        $this->assign("lists",$lists);
        $this->assign("page",$page -> show());
        return $this -> fetch();
    }

    public function recomConsume ()
    {
        $mem_id = I("mem_id") ? I("mem_id",0) : I("user_id",0);
        if($mem_id)
            $whereBatch[] = " user_id=$mem_id";

        $mobile = I("mobile");
        if($mobile)
            $whereBatch[] = " mobile='$mobile'";

        $recom_mobile = I("recom_mobile");
        if($recom_mobile)
            $whereBatch[] = "apply_mobile='$recom_mobile'";
//        $start_time = I("start_time");

//        if($start_time)
//            $whereBatch[] = "reg_time >= '".strtotime($start_time)."'";
//
//        $end_time = I("end_time");
//        if($end_time)
//            $whereBatch[] = "reg_time <= '".strtotime($end_time)."'";

//        print_r($whereBatch);die;
        $where = $whereBatch ? implode(" AND ", $whereBatch) : "";
        $count = M('users') -> where( $where ) -> count();
        $page = new Page($count, 10);

        $lists  = M('users') -> where($where) -> order('user_id desc') -> limit($page->firstRow.','.$page->listRows) -> select();

        $this -> consumeBatch($lists);
//      print_r($lists);die;
        $whereBatch && $currentUrl = implode("&", $whereBatch);

        $is_export = I("is_export");
        if($is_export == 1)
        {
//            print_r($lists);die;
            $this->consumeImport($lists,"exportData");
        }

        if($currentUrl)
            $currentUrl = url("Admin/User/recomConsume")."?".trim($currentUrl);
        else
            $currentUrl = url("Admin/User/recomConsume");

        $this -> assign("currentUrl",$currentUrl);
        $this->assign( "lists", $lists );
        $this->assign("page", $page -> show());
        return $this -> fetch();
    }

    public function consumeImport ($data, $function)
    {
        require_once  VENDOR_PATH."phpoffice/phpexcel/Classes/PHPExcel.php";

        $objPHPExcel = new \PHPExcel();

        $this -> $function( $objPHPExcel, $data );

        $title = "Excel".date("YmdHis");
        $objPHPExcel->getActiveSheet()->setTitle($title);

        $objPHPExcel->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$title.'.xls"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');

        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header ('Cache-Control: cache, must-revalidate');
        header ('Pragma: public');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function exportData ($objPHPExcel,$data)
    {
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '会员ID')
            ->setCellValue('B1', '会员手机号')
            ->setCellValue('C1', '推荐人手机号')
            ->setCellValue('D1', '推荐人会员ID')
            ->setCellValue('E1', '购买总额');

        $export = $objPHPExcel -> setActiveSheetIndex(0);

        foreach($data as $key => $val)
        {
            $grade = $key + 2;
            $export->setCellValue('A'.$grade, $val["user_id"]);
            $export->setCellValue('B'.$grade, $val["mobile"]);
            $export->setCellValue('C'.$grade, $val["recommend_mobile"]);
            $export->setCellValue('D'.$grade, $val["recommend_id"]);
            $export->setCellValue('E'.$grade, $val["total_money"]);
        }
    }


    public function consumeEditImport ( $objPHPExcel, $data )
    {
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '会员ID')
            ->setCellValue('B1', '会员手机号')
            ->setCellValue('C1', '推荐人手机号')
            ->setCellValue('D1', '推荐人会员ID')
            ->setCellValue('E1', '订单编号')
            ->setCellValue('F1', '订单金额')
            ->setCellValue('G1', '订单状态')
            ->setCellValue('H1', '订单时间');

        $export = $objPHPExcel -> setActiveSheetIndex(0);

        foreach($data as $key => $val)
        {
            $grade = $key + 2;
            $export->setCellValue('A'.$grade, $val["user_id"]);
            $export->setCellValue('B'.$grade, $val["mobile"]);
            $export->setCellValue('C'.$grade, $val["recommend_mobile"]);
            $export->setCellValue('D'.$grade, $val["recommend_id"]);
            $export->setCellValue('E'.$grade, $val["order_sn"]);
            $export->setCellValue('F'.$grade, $val["order_amount"]);
            $export->setCellValue('G'.$grade, orderStatus($val));
            $export->setCellValue('H'.$grade, date("Y-m-d H:i:s",$val["add_time"]));
        }
    }

    /**
     * 消费推荐编辑
     */
    public function recomConsumeEdit ()
    {
        $id = I("id");
        if(!$id)
        {
            $this->error(" id不能为空", url("Admin/user/recomConsume"));
            exit;
        }

        $where = "o.user_id = $id AND o.order_status in (2,4)";
        $count = M('order') -> alias("o") -> where( $where ) -> count();
        $page = new Page( $count, 10 );

        $orderInfo = M('order')
            -> alias("o")
            -> field("o.*,u.apply_mobile")
            -> where($where)
            -> join("__USERS__ u","o.user_id = u.user_id","left")
            -> limit($page -> firstRow.','.$page -> listRows)
            -> select();

        foreach ($orderInfo as $key => &$val)
        {
            if($val["user_id"])
            {
                $userInfo = M("users") -> field("mobile") -> where("user_id = {$val["user_id"]}") -> find();
                $val["user_mobile"] = $userInfo["mobile"];
            }
        }

        $is_export = I("is_export");
        if($is_export == 1)
        {
//            print_r($lists);die;
            $this->consumeImport($orderInfo,"consumeEditImport");
        }
        $this->assign("currentUrl", url("Admin/User/recomConsumeEdit",array("id"=>$id)));
        $this -> consumeBatch( $orderInfo);

        $this->assign("orderInfo", $orderInfo);

        return $this -> fetch();
    }

    /**
     * 消费推荐处理，操作用户表
     */
    public function  consumeBatch (&$users,$single = 0)
    {
        if($single)
            $users = [$users];

        foreach ( $users as $key => &$val )
        {
            $orderAmount = M('order') -> field("SUM(`order_amount`) total_money") -> where("order_status in (2,4) AND user_id = {$val["user_id"]}") -> find();
            if($val["apply_mobile"])
            {
                $userInfo = M('users') -> field("mobile, user_id") -> where("mobile = ".$val["apply_mobile"]) -> find();
                $val["recommend_mobile"] = $userInfo["mobile"];
                $val["recommend_id"] = $userInfo["user_id"];
            }
            $val["total_money"] = $orderAmount["total_money"] ? $orderAmount["total_money"] : "0.00";
        }

        if($single)
            $users = $users[0];
    }

    /**
     * 分销树状关系
     */
    public function ajax_distribut_tree()
    {
          $list = M('users')->where("first_leader = 1")->select();
          return $this->fetch();
    }

    /**
     *
     * @time 2016/08/31
     * @author dyr
     * 发送站内信
     */
    public function sendMessage()
    {
        $user_id_array = I('get.user_id_array');
        $users = array();
        if (!empty($user_id_array)) {
            $users = M('users')->field('user_id,nickname')->where(array('user_id' => array('IN', $user_id_array)))->select();
        }
        $this->assign('users', $users);
        return $this->fetch(); 
    }

    /**
     * 发送系统消息
     */
    public function doSendMessage()
    {
        $call_back = I('call_back');//回调方法
        $type = I('post.type', 0);//个体or全体
        $admin_id = session('admin_id');
        $users = I('post.user/a');//个体id
        $category = I('post.category/d', 0); //0系统消息，1物流通知，2优惠促销，3商品提醒，4我的资产，5商城好店

        $raw_data = [
            'title'       => I('post.title', ''),
            'order_id'    => I('post.order_id', 0),
            'discription' => I('post.text', ''), //内容
            'goods_id'    => I('post.goods_id', 0),
            'change_type' => I('post.change_type/d', 0),
            'money'       => I('post.money/d', 0),
            'cover'       => I('post.cover', '')
        ];

        $msg_data = [
            'admin_id' => $admin_id,
            'category' => $category,
            'type' => $type
        ];
        /*dd($users);
        exit;*/

        $msglogic = new \app\common\logic\MessageLogic;
        $res = $msglogic->sendMessage($msg_data, $raw_data, $users);
        $this->ajaxReturn($res);
/*        exit("<script>parent.{$call_back}(1);</script>");*/
    }

    /**
     *
     * @time 2016/09/03
     * @author dyr
     * 发送邮件
     */
    public function sendMail()
    {
        $user_id_array = I('get.user_id_array');
        $users = array();
        if (!empty($user_id_array)) {
            $user_where = array(
                'user_id' => array('IN', $user_id_array),
                'email'=> array('neq','')
            );
            $users = M('users')->field('user_id,nickname,email')->where($user_where)->select();
        }
        $this->assign('smtp',tpCache('smtp'));
        $this->assign('users',$users);
        return $this->fetch();
    }

    /**
     * 发送邮箱
     * @author dyr
     * @time  2016/09/03
     */
    public function doSendMail()
    {
        echo 1111;exit;
        $call_back = I('call_back');//回调方法
        $message = I('post.text');//内容
        $title = I('post.title');//标题
        $users = I('post.user/a');
        if (!empty($users)) {
            $user_id_array = implode(',', $users);
            $users = M('users')->field('email')->where(array('user_id' => array('IN', $user_id_array)))->select();
            $to = array();
            foreach ($users as $user) {
                if (check_email($user['email'])) {
                    $to[] = $user['email'];
                }
            }
            dd($users);
            exit;
            $res = send_email($to, $title, $message);
            echo "<script>parent.{$call_back}('{$res['status']}');</script>";
            exit();
        }
    }
    
     /**
     * 签到列表
     * @date 2017/09/28
     */
    public function signList() {       
        return $this->fetch();
    }
    
    
    /**
     * 会员签到 ajax
     * @date 2017/09/28
     */
    public function ajaxsignList() {
        $model = M('user_sign us');
        //A.查询条件
        ($nickname = I('nickname')) && $map['u.nickname'] = ['like', "%$nickname%"];
        ($mobile = I('mobile')) && $map['u.mobile'] = ['like', "%$mobile%"];
        $prefix = C('prefix');
        $field = [
            'us.*',
            'u.user_id',
            'u.nickname',
            'u.mobile',
        ];
        // B. 开始查询
        $model = $model
                ->field($field)
                ->join($prefix . 'users u  ', 'u.user_id = us.user_id', 'left')
                ->where($map)
                ->order('us.id DESC');
        // B.1 总数
        $tModel = clone $model;
        $count = $tModel->count();
        // B.2 开始分页
        $Page  = new AjaxPage($count,15);
        $show = $Page->show();
        $list = $model->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('page', $show);
        $this->assign('pager', $Page);
        $this->assign('list', $list);
        return $this->fetch();
    }
    
    /**
     * 签到规则设置 
     * @date 2017/09/28
     */
    public function signRule() {
        $inc_type = 'sign';
        if (IS_POST) {
            $param = I('post.');           
            //unset($param['__hash__']);
            unset($param['inc_type']);
            unset($param['form_submit']);
            tpCache($inc_type, $param);
            $this->success("操作成功", U('User/signRule', array('inc_type' => $inc_type)));
        }       
        $this->assign('inc_type',$inc_type);
	$this->assign('config',tpCache($inc_type));//当前配置项       
        return $this->fetch();        
    }




    /**
     * 合伙人审核
     */
    public function shareholder(){
        $map=array();
        $prefix=config('database.prefix');
        $apply_order_sn=I('apply_order_sn');
        if($apply_order_sn){
            $map['s.apply_order_sn']=array("like","%$apply_order_sn%");
        }
        $user_name=I('nickname');
        if($user_name){
            $map['u.nickname']=array("like","%$user_name%");
        }
        $mobile=I('mobile');
        if($mobile){
            $map['u.mobile']=array("like","%$mobile%");
        }
        $status=I('status');
        if($status){
            if($status=='10'){
                $map['s.status']=0;
            }else{
                $map['s.status']=$status;
            }
        }
        $count=M('user_shareholder s')->join($prefix.'users u','s.user_id = u.user_id','LEFT')
            ->where($map)->count();
        $page_rows=C('page_listRows');
        $page = new Page($count,$page_rows);
        $list=M('user_shareholder s')->join($prefix.'users u','s.user_id = u.user_id','LEFT')
            ->where($map)->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('lists',$list);
        $this->assign("page",$page -> show());
        return $this->fetch();
    }

    public function shareholder_info(){
        $prefix=config('database.prefix');
        $id=I('id');
        if($id){
            $entre_info=M('user_shareholder s')->join($prefix.'users u','s.user_id = u.user_id','LEFT')->field('s.*,u.nickname,u.mobile')->where(array("id"=>$id))->find();
            if($entre_info['province'] && $entre_info['city']){
                $province = M('region')->where(array('id' => $entre_info['province']))->find();
                $city = M('region')->where(array('id' => $entre_info['city']))->find();
                $area = M('region')->where(array('id' => $entre_info['district']))->find();
                $this->assign('province', $province);
                $this->assign('city', $city);
                $this->assign('area', $area);
            }
            if($entre_info['check_detail']){
                $entre_info['check_detail']=unserialize($entre_info['check_detail']);
            }
            $this->assign('info',$entre_info);
        }
        return $this->fetch();
    }

    //合伙人审核操作
    public function shareholder_ing(){
        $save_data=array();
        $res="";
        if(IS_GET){
            $post_data=I('get.');

            $check_detail['status']=I('is_code')?I('is_code'):"0";
            $check_detail['time']=time();
            $check_detail['reason']=I('reason')?I('reason'):"";
            $entre_info=M('user_shareholder')->where(array("id"=>$post_data['id']))->find();
            if($entre_info['check_detail']){
                $detail_arr=unserialize($entre_info['check_detail']);
            }else{
                $detail_arr=array();
            }

            if(count($detail_arr)>0){
                array_push($detail_arr,$check_detail);
            }else{
                array_push($detail_arr,$check_detail);
            }
            if($post_data['is_code']=='1'){
                //通过
                $img=I('img',"");
                if($img){
                    $check_detail['empowerment']=$img;
                }else{
                    $this->error("请上传股份合同相关照片");
                }



                $user_id=M('user_shareholder')->where(array("id"=>$post_data['id']))->getField("user_id");
                //改变会员等级和角色
                M("users")->where(array("user_id"=>$user_id))->save(array("level"=>3,"user_relation"=>26));
                $res=M('user_shareholder')->where(array("id"=>$post_data['id']))->save(array("status"=>"1",'check_detail'=>serialize($detail_arr),"empowerment"=>$img));
            }else{
                //拒绝
                $res=M('user_shareholder')->where(array("id"=>$post_data['id']))->save(array("status"=>"2",'check_detail'=>serialize($detail_arr)));
            }
            if($res!==false){
                $this->success("操作成功");
            }else{
                $this->error("操作失败");
            }
        }
    }


    public function shareholder_del(){
        $id=I('id');
        $table=I('table');
        $res=M($table)->where(array("id"=>$id))->delete();
        if($res!==false){
            $this->success("操作成功");
        }else{
            $this->error("操作失败");
        }
    }

    /*
     * 股东
     */
    public function startup(){
        $map=array();
        $prefix=config('database.prefix');
        $apply_order_sn=I('apply_order_sn');
        if($apply_order_sn){
            $map['s.apply_order_sn']=array("like","%$apply_order_sn%");
        }
        $user_name=I('nickname');
        if($user_name){
            $map['u.nickname']=array("like","%$user_name%");
        }
        $mobile=I('mobile');
        if($mobile){
            $map['u.mobile']=array("like","%$mobile%");
        }
        $status=I('status');
        if($status){
            if($status=='10'){
                $map['s.status']=0;
            }else{
                $map['s.status']=$status;
            }
        }
        $count=M('user_startup s')->join($prefix.'users u','s.user_id = u.user_id','LEFT')
            ->where($map)->count();
        $page_rows=C('page_listRows');
        $page = new Page($count,$page_rows);
        $list=M('user_startup s')->join($prefix.'users u','s.user_id = u.user_id','LEFT')
            ->where($map)->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('lists',$list);
        $this->assign("page",$page -> show());
        return $this->fetch();
    }

    //股东审核页面
    public function startup_info(){
        $prefix=config('database.prefix');
        $id=I('id');
        if($id){
            $entre_info=M('user_startup s')->join($prefix.'users u','s.user_id = u.user_id','LEFT')->field('s.*,u.nickname,u.mobile')->where(array("id"=>$id))->find();
            if($entre_info['province'] && $entre_info['city']){
                $province = M('region')->where(array('id' => $entre_info['province']))->find();
                $city = M('region')->where(array('id' => $entre_info['city']))->find();
                $area = M('region')->where(array('id' => $entre_info['district']))->find();
                $this->assign('province', $province);
                $this->assign('city', $city);
                $this->assign('area', $area);
            }
            if($entre_info['check_detail']){
                $entre_info['check_detail']=unserialize($entre_info['check_detail']);
            }
            $this->assign('info',$entre_info);
        }
        return $this->fetch();
    }
    //股东审核操作
    public function startup_ing(){
        $save_data=array();
        $res="";
        if(IS_GET){
            $post_data=I('get.');
            $check_detail['status']=I('is_code')?I('is_code'):"0";
            $check_detail['time']=time();
            $check_detail['reason']=I('reason')?I('reason'):"";
            $entre_info=M('user_startup')->where(array("id"=>$post_data['id']))->find();
            if($entre_info['check_detail']){
                $detail_arr=unserialize($entre_info['check_detail']);
            }else{
                $detail_arr=array();
            }
            if(count($detail_arr)>0){
                array_push($detail_arr,$check_detail);
            }else{
                array_push($detail_arr,$check_detail);
            }
            if($post_data['is_code']=='1'){
                //通过
                $user_id=M('user_startup')->where(array("id"=>$post_data['id']))->getField("user_id");
                M("users")->where(array("user_id"=>$user_id))->save(array("level"=>2,"user_relation"=>25));
                $res=M('user_startup')->where(array("id"=>$post_data['id']))->save(array("status"=>"1",'check_detail'=>serialize($detail_arr)));
            }else{
                //拒绝
                $res=M('user_startup')->where(array("id"=>$post_data['id']))->save(array("status"=>"2",'check_detail'=>serialize($detail_arr)));
            }
            if($res!==false){
                $this->success("操作成功");
            }else{
                $this->success("操作失败");
            }
        }
    }



    //创业者
    public function entre(){
        $map=array();
        $prefix=config('database.prefix');
        $order_sn=I('order_sn');
        if($order_sn){
            $map['s.order_sn']=array("like","%$order_sn%");
        }
        $user_name=I('nickname');
        if($user_name){
            $map['u.nickname']=array("like","%$user_name%");
        }
        $mobile=I('mobile');
        if($mobile){
            $map['u.mobile']=array("like","%$mobile%");
        }
        $status=I('status');
        if($status){
            if($status=='10'){
                $map['s.status']=0;
            }else{
                $map['s.status']=$status;
            }
        }
        $count=M('user_entre s')->join($prefix.'users u','s.user_id = u.user_id','LEFT')
            ->where($map)->count();
        $page_rows=C('page_listRows');
        $page = new Page($count,$page_rows);
        $list=M('user_entre s')->join($prefix.'users u','s.user_id = u.user_id','LEFT')
            ->where($map)->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('lists',$list);
        $this->assign("page",$page -> show());
        return $this->fetch();
    }

    //创业者审核页面
    public function entre_info(){
        $prefix=config('database.prefix');
        $id=I('id');
        if($id){
            $entre_info=M('user_entre s')->join($prefix.'users u','s.user_id = u.user_id','LEFT')->where(array("id"=>$id))->find();
            if($entre_info['province'] && $entre_info['city']){
                $province = M('region')->where(array('id' => $entre_info['province']))->find();
                $city = M('region')->where(array('id' => $entre_info['city']))->find();
                $area = M('region')->where(array('id' => $entre_info['district']))->find();
                $this->assign('province', $province);
                $this->assign('city', $city);
                $this->assign('area', $area);
            }
            $entre_info['check_detail']=unserialize($entre_info['check_detail']);
            $this->assign('info',$entre_info);
        }
        return $this->fetch();
    }
    //创业者审核操作
    public function entre_ing(){
        $save_data=array();
        $res="";
        if(IS_GET){
            $post_data=I('get.');
            $check_detail['status']=I('is_code')?I('is_code'):"0";
            $check_detail['time']=time();
            $check_detail['reason']=I('reason')?I('reason'):"";
            $post_money=I('post_money')?I('post_money'):"";
            if(intval($post_money)<"3000"){
                //1000元创业者
                $user_relation="23";
            }else{
                //3000元创业者
                $user_relation="24";
            }
            $entre_info=M('user_entre')->where(array("id"=>$post_data['id']))->find();
            if($entre_info['check_detail']){
                $detail_arr=unserialize($entre_info['check_detail']);
            }else{
                $detail_arr=array();
            }
            if($post_data['is_code']=='1'){
                if(count($detail_arr)>0){
                    array_push($detail_arr,$check_detail);
                }else{
                    array_push($detail_arr,$check_detail);
                }
                $user_id=M('user_entre')->where(array("id"=>$post_data['id']))->getField("user_id");
                M("users")->where(array("user_id"=>$user_id))->save(array("level"=>2,"user_relation"=>$user_relation));
                //通过
                $res=M('user_entre')->where(array("id"=>$post_data['id']))->save(array("status"=>"1",'check_detail'=>serialize($detail_arr)));
            }else{
                //拒绝
                if(count($detail_arr)>0){
                    array_push($detail_arr,$check_detail);
                }else{
                    array_push($detail_arr,$check_detail);
                }
                $res=M('user_entre')->where(array("id"=>$post_data['id']))->save(array("status"=>"2",'check_detail'=>serialize($detail_arr)));
            }
            if($res!==false){
                $this->success("操作成功");
            }else{
                $this->success("操作失败");
            }
        }
    }
}