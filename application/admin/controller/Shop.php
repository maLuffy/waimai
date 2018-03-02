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
 * Date: 2016-05-27
 */

namespace app\admin\controller;
use app\admin\logic\StoreLogic;
use think\Page;
use think\Loader;
use think\Db;

class Shop extends Base{
	//线下店铺行业设置
    public function data_dic_index(){
        $where=array();
        if(IS_GET){
            $industry_name=I('industry_name');
            if($industry_name){
                $where['industry_name']=array("like","%$industry_name%");
            }
        }
        $DataDic=new \app\admin\model\DataDic();
        $list=$DataDic->getDatadic($where);
        $this->assign('page',$list['page']);
        $this->assign('list',$list['list']);
        return $this->fetch();
    }


    //修改是否显示
    public function DataDic_show(){
        $id=I('id');
        $is_show=I('is_show');
        if($is_show=='1'){
            $is_show='0';
        }else{
            $is_show='1';
        }
        $res=M('shop_industry')->where(array("industry_id"=>$id))->save(array("is_show"=>$is_show));
        if($res!==false){
            $this->ajaxReturn(['status'=>1,'data'=>$is_show,'msg'=>'修改成功!']);
        }else{
            $this->ajaxReturn(['status'=>0,'msg'=>'修改失败!']);
        }
    }

    //删除数据
    public function DataDic_del(){
        $id=I('id');
        $res=M('shop_industry')->where(array("industry_id"=>$id))->delete();
        if($res){
            $this->success("操作成功!!");
        }else{
            $this->error("操作失败!!");
        }
    }


    //行业新增
    public function data_dic_add(){
        /*$DataDic=new \app\admin\model\DataDic();
        $parent_main=$DataDic->getDatadic();*/

        $data=I('post.');
        $id=I('industry_id');
        if(count($data)){
            $save_data['industry_name']=$data['industry_name'];
            $save_data['is_show'] =$data['is_show'];
            if(empty($save_data['industry_name'])){
                $this->error("行业名称不能为空!");
            }
            if($id){
                $res=M('shop_industry')->where(array("industry_id"=>$id))->save($save_data);
                if($res!==false){
                    $this->success("操作成功!!",U('Shop/data_dic_index'));
                }
            }else{
                $res=M('shop_industry')->add($save_data);
                if($res){
                    $this->success("操作成功!!",U('Shop/data_dic_index'));
                }
            }
        }

        if($id){
            $info=M('shop_industry')->where(array("industry_id"=>$id))->find();
            $this->assign('info',$info);
        }
        return $this->fetch();
    }


	//店铺等级
	public function store_grade(){
		$model =  M('store_grade');
		$count = $model->count();
		$Page = new Page($count,10);
		$list = $model->order('sg_id')->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('list',$list);
		$show = $Page->show();
		$this->assign('pager',$Page);
		$this->assign('page',$show);
		return $this->fetch();
	}
	
	public function grade_info(){
		$sg_id = I('sg_id');
		if($sg_id){
			$info = M('store_grade')->where("sg_id=$sg_id")->find();
			$this->assign('info',$info);
		}
		return $this->fetch();
	}
	
	public function grade_info_save(){
		$data = I('post.');
		if($data['sg_id'] > 0 || $data['act']=='del'){
			if($data['act'] == 'del'){
				if(M('store')->where(array('grade_id'=>$data['del_id']))->count()>0){
                    $this->ajaxReturn(['status'=>0,'msg'=>'该等级下有开通店铺，不得删除']);
				}else{
					$r = M('store_grade')->where("sg_id=".$data['del_id'])->delete();
                    $this->ajaxReturn(['status'=>1,'msg'=>'删除成功']);
				}
			}else{
				$r = M('store_grade')->where("sg_id=".$data['sg_id'])->save($data);
			}
		}else{
			$r = M('store_grade')->add($data);
		}
		if($r){
			$this->success('编辑成功',U('Store/store_grade'));
		}else{
			$this->error('提交失败');
		}
	}
	
	public function store_class(){
		$model =  M('store_class');
		$count = $model->count();
		$Page = new Page($count,10);
		$list = $model->order('sc_id')->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('list',$list);
		$show = $Page->show();
		$this->assign('pager',$Page);
		$this->assign('page',$show);
		return $this->fetch();
	}
	
	//店铺分类
	public function class_info(){
		$sc_id = I('sc_id');
		if($sc_id){
			$info = M('store_class')->where("sc_id=$sc_id")->find();
			$this->assign('info',$info);
		}
		return $this->fetch();
	}
	
	public function class_info_save(){
		$data = I('post.');
		$storeClassValidate = Loader::validate('StoreClass');
		if($data['sc_id'] > 0){
			if($data['act']== 'del'){
				$storeClassCount = Db::name('store')->where('sc_id',$data['sc_id'])->count();
				if($storeClassCount > 0){
					$this->ajaxReturn(['status' => 0, 'msg' => '该分类下有开通店铺，不得删除', 'result' => '']);
				}else{
					$r = Db::name('store_class')->where("sc_id", $data['sc_id'])->delete();
					if($r !== false){
						$this->ajaxReturn(['status' => 1, 'msg' => '删除成功', 'result' => '']);
					}else{
						$this->ajaxReturn(['status' => 0, 'msg' => '删除失败', 'result' => '']);
					}
				}
			}else{
				if (!$storeClassValidate->batch()->check($data)) {
					$this->ajaxReturn(['status' => 0, 'msg' => '编辑失败', 'result' => $storeClassValidate->getError()]);
				}else{
					$r = Db::name('store_class')->where("sc_id", $data['sc_id'])->save($data);
					if($r !== false){
						$this->ajaxReturn(['status' => 1, 'msg' => '编辑成功', 'result' => '']);
					}else{
						$this->ajaxReturn(['status' => 0, 'msg' => '编辑失败', 'result' => '']);
					}
				}
			}
		}else{
			if (!$storeClassValidate->batch()->check($data)) {
				$this->ajaxReturn(['status' => 0, 'msg' => '添加失败', 'result' => $storeClassValidate->getError()]);
			}else{
				$r = Db::name('store_class')->add($data);
				if($r !== false){
					$this->ajaxReturn(['status' => 1, 'msg' => '添加成功', 'result' => '']);
				}else{
					$this->ajaxReturn(['status' => 0, 'msg' => '添加失败', 'result' => '']);
				}
			}
		}
	}
	
	//线下店铺
	public function shop_list(){
		$model =  M('Shop');
		$map['status'] = 1 ;
		$telephone = I("telephone");
		if($telephone) $map['telephone'] = array("like","%$telephone%");
        $shop_name = I("shop_name");
        if($shop_name) $map['shop_name'] = array("like","%$shop_name%");

		$count = $model->where($map)->count();
		$Page = new Page($count,10);
		$list = $model->where($map)->order('shop_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($list as $k=>&$v){
		    $v['store_info']=M('store')->where(array("store_id"=>$v['store_id']))->find();
		    $v['industry']=M('shop_industry')->where(array("industry_id"=>$v['industry']))->find();
        }
        /*dd($list);
		exit;*/

		$this->assign('list',$list);
		$show = $Page->show();
		$this->assign('page',$show);
		$this->assign('pager',$Page);
		$store_grade = M('store_grade')->getField('sg_id,sg_name');
		$this->assign('store_grade',$store_grade);
		$this->assign('store_class',M('store_class')->getField('sc_id,sc_name',true));
		return $this->fetch();
	}
	
	/*添加店铺*/
	public function store_add(){
        $is_own_shop = I('is_own_shop',1);
		if(IS_POST){
			$store_name = trim(I('store_name'));
			$user_name = trim(I('user_name'));
			$seller_name = trim(I('seller_name'));
            $password = trim(I('password'));
			if(M('store')->where("store_name='$store_name'")->count()>0){
				$this->ajaxReturn(['status'=>0,'msg'=>'店铺名称已存在']);
			}
			if(M('seller')->where("seller_name='$seller_name'")->count()>0){
                $this->ajaxReturn(['status'=>0,'msg'=>'此会员已被占用']);
			}
			$user_id = M('users')->where("email='$user_name' or mobile='$user_name'")->getField('user_id');
			if($user_id){
				if(M('store')->where(array('user_id'=>$user_id))->count()>0){
                    $this->ajaxReturn(['status'=>0,'msg'=>'该会员已经申请开通过店铺']);
				}
			}else{   //没有这个用户就创建一个
                if(check_email($user_name)){
                    $user_data['email'] = $user_name;
                }else{
                    $user_data['mobile'] = $user_name;
                };
                $user_data['password'] = $password;
                $user_obj = new \app\admin\logic\UsersLogic();
                $add_user_res = $user_obj->addUser($user_data);
                if($add_user_res['status'] !=1) $this->ajaxReturn($add_user_res);
                $user_id = $add_user_res['user_id'];
            }
			$store = array('store_name'=>$store_name,
				'user_id'       =>  $user_id,	
                'user_name'     =>  $user_name,
                'store_state'   =>  1,
				'seller_name'   =>  $seller_name,
                'password'      =>  I('password'),
				'store_time'    =>  time(),
                'is_own_shop'   =>  $is_own_shop
			);
			$storeLogic = new StoreLogic();
			if($storeLogic->addStore($store)){
				if($is_own_shop == 1){
                    $this->ajaxReturn(['status'=>1,'msg'=>'店铺添加成功','url'=>U('Store/store_own_list')]);
				}else{
                    $this->ajaxReturn(['status'=>1,'msg'=>'店铺添加成功','url'=>U('Store/store_list')]);
				}
				exit;
			}else{
                $this->ajaxReturn(['status'=>0,'msg'=>'店铺添加失败']);
			}
		}
		$this->assign('is_own_shop',$is_own_shop);
		return $this->fetch();
	}
	
	/*验证店铺名称，店铺登陆账号*/
	public function store_check(){
		$store_name = I('store_name');
		$seller_name = I('seller_name');
		$user_name = I('user_name');
		$res = array('status'=>1);
		if($store_name && M('store')->where("store_name='$store_name'")->count()>0){
			$res = array('status'=>-1,'msg'=>'店铺名称已存在');
		}
		
		if(!empty($user_name)){
			if(!check_email($user_name) && !check_mobile($user_name)){
				$res = array('status'=>-1,'msg'=>'店主账号格式有误');
			}
			if(M('users')->where("email='$user_name' or mobile='$user_name'")->count()>0){
				$res = array('status'=>-1,'msg'=>'会员名称已被占用');
			}
		}

		if($seller_name && M('seller')->where("seller_name='$seller_name'")->count()>0){
			$res = array('status'=>-1,'msg'=>'此账号名称已被占用');
		}
		respose($res);
	}
	
	/*编辑自营店铺*/
	public function store_edit(){
		if(IS_POST){
			$data = I('post.');
			if(M('store')->where("store_id=".$data['store_id'])->save($data)){
				$this->success('编辑店铺成功',U('Store/store_own_list'));
				exit;
			}else{
				$this->error('编辑失败');
			}
		}
		$store_id = I('store_id',0);
		$store = M('store')->where("store_id=$store_id")->find();
		$this->assign('store',$store);
		return $this->fetch();
	}
	
	//编辑外驻店铺
	public function store_info_edit(){
		if(IS_POST){
			$map = I('post.');
			$store = $map['store'];
            $store['deposit'] = $map['store']['deposit'];
			unset($map['store']);
			$store['province_id'] = $map['company_province']; //省
			$store['city_id'] = $map['company_city']; //市
			$store['district'] = $map['company_district'];  //区
			$store['company_name'] = $map['company_name'];
			$store['store_phone'] = $map['store_person_mobile'];
			$store['store_address'] = $map['company_address'];
			$store['store_address'] = $map['company_address'];
			$store['store_end_time'] = strtotime($map['store_end_time']);
			$store['store_qq'] = $map['store_person_qq'];
//            if($store['company_province']<1 || $store['district']<1){
//                $this->error('请填写完整的公司所在地');
//            }
			$save_store = M('store')->where(array('store_id'=>$map['store_id']))->save($store);
            $save_apply = M('store_apply')->where(array('user_id'=>$store['user_id']))->save($map);
			if(!$save_store && !$save_apply){
                $this->error('编辑失败');
			}else{
                if($store['store_state'] == 0){
                    M('goods')->where(array('store_id'=>$map['store_id']))->save(array('is_on_sale'=>0));//关闭店铺，同时下架店铺所有商品
                }
                $backuUrl = I('get.is_own_shop') == 1 ? U('Store/store_own_list') : U('Store/store_list');  //自营店铺页
                    $this->success('编辑店铺成功',$backuUrl);
                exit;
			}
		}
		$store_id = I('store_id');
		if($store_id>0){
			$store = M('store')->where("store_id=$store_id")->find();
			$this->assign('store',$store);
			$apply = M('store_apply')->where('user_id='.$store['user_id'])->find();
			$bank_province_name = M('region')->where(array('id'=>$apply['bank_province']))->getField('name');
			$bank_city_name = M('region')->where(array('id'=>$apply['bank_city']))->getField('name');
			$this->assign('bank_province_name',$bank_province_name);
			$this->assign('bank_city_name',$bank_city_name);
			$this->assign('apply',$apply);
            $city = M('region')->where(array('parent_id'=>$apply['company_province'],'level'=>2))->select();
            $area = M('region')->where(array('parent_id'=>$apply['company_city'],'level'=>3))->select();
            $this->assign('city',$city);
            $this->assign('area',$area);
		}
		$company_type = config('company_type');
		$rate_list = config('rate_list');
		$this->assign('rate_list', $rate_list);
		$this->assign('company_type', $company_type);
		$this->assign('store_grade',M('store_grade')->getField('sg_id,sg_name'));
		$this->assign('store_class',M('store_class')->getField('sc_id,sc_name'));
		$province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
		$this->assign('province',$province);
		return $this->fetch();
	}
	
	/*删除店铺*/
	public function shop_del(){
		$shop_id = I('del_id');

		if($shop_id > 1){
			$shop = M('shop')->where("shop_id", $shop_id)->find();

			$storeGoodsCount = M('shop_goods')->where("shop_id", $shop['shop_id'])->count();
			if ($storeGoodsCount > 0) {
				$this->ajaxReturn(['status' => 0, 'msg' => '该店铺有发布商品，不得删除', 'result' => '']);
			}else{
				M('shop')->where("shop_id", $shop_id)->delete();
				M('shop_goods')->where("shop_id", $shop_id)->delete();
				//M('users')->where("user_id", $store['user_id'])->delete();
				adminLog("删除店铺".$shop['shop_name']);
				$this->ajaxReturn(['status' => 1, 'msg' => '删除线下店铺'.$shop['shop_name'].'成功', 'result' => '']);
			}
		}else{
			$this->ajaxReturn(['status' => 0, 'msg' => '基础自营店，不得删除', 'result' => '']);
		}
	}
	
	//店铺信息
	public function shop_info(){
		$shop_id = I('shop_id');
		$shop_info = M('shop')->where("shop_id=".$shop_id)->find();

		/*$this->assign('store',$store);
		$store_grade = M('store_grade')->where(array('sg_id'=>$store['grade_id']))->find();
		$this->assign('store_grade',$store_grade);
		$apply = M('store_apply')->where("user_id=".$store['user_id'])->find();
		$province_name = M('region')->where(array('id'=>$apply['company_province']))->getField('name');
		$city_name = M('region')->where(array('id'=>$apply['company_city']))->getField('name');
		$district_name = M('region')->where(array('id'=>$apply['company_district']))->getField('name');
		$bank_province_name = M('region')->where(array('id'=>$apply['bank_province']))->getField('name');
		$bank_city_name = M('region')->where(array('id'=>$apply['bank_city']))->getField('name');
		$this->assign('bank_province_name',$bank_province_name);
		$this->assign('bank_city_name',$bank_city_name);
		$this->assign('province_name',$province_name);
		$this->assign('city_name',$city_name);
		$this->assign('district_name',$district_name);
		$this->assign('apply',$apply);
		$bind_class_list = M('store_bind_class')->where("store_id=".$store_id)->select();
		$goods_class = M('goods_category')->getField('id,name');
		for($i = 0, $j = count($bind_class_list); $i < $j; $i++) {
			$bind_class_list[$i]['class_1_name'] = $goods_class[$bind_class_list[$i]['class_1']];
			$bind_class_list[$i]['class_2_name'] = $goods_class[$bind_class_list[$i]['class_2']];
			$bind_class_list[$i]['class_3_name'] = $goods_class[$bind_class_list[$i]['class_3']];
		}
		$this->assign('bind_class_list',$bind_class_list);*/
		return $this->fetch();
	}
	
	//自营店铺列表
	public function store_own_list(){

		$model =  M('store');
		$map['is_own_shop'] = 1 ;
		$grade_id = I("grade_id");
		if($grade_id>0) $map['grade_id'] = $grade_id;
		$sc_id =I('sc_id');
		if($sc_id>0) $map['sc_id'] = $sc_id;
		$store_state = I("store_state");
        switch ($store_state){
            case '': '';break;
            case 3:
                $map['store_end_time'] = ['between',[time(),strtotime('+1 month')]];
                break;  //即将过期（1个月）
            case 4:
                $map['store_end_time'] = ['between',1,time()];
                break;  //已过期
            default : $map['store_state'] = $store_state;break;
        }
		$seller_name = I('seller_name');
		if($seller_name) $map['seller_name'] = array('like',"%$seller_name%");
		$store_name = I('store_name');
		if($store_name) $map['store_name'] = array('like',"%$store_name%");
		$count = $model->where($map)->count();
		$Page = new Page($count,10);
		$list = $model->where($map)->order('store_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('list',$list);	
		$show = $Page->show();
		$this->assign('page',$show);
		$this->assign('pager',$Page);
		$store_grade = M('store_grade')->getField('sg_id,sg_name');
		$this->assign('store_grade',$store_grade);
		$this->assign('store_class',M('store_class')->getField('sc_id,sc_name'));
		return $this->fetch();
	}
	
	//线下店铺申请列表
	public function apply_list(){
		$model =  M('shop');
		$map['status'] = array('in','0,2,3,4');
		$map['add_time'] = array('gt',0); //填写完成的才显示
        $telephone = I("telephone");
        if($telephone) $map['telephone'] = array("like","%$telephone%");
        $shop_name = I("shop_name");
        if($shop_name) $map['shop_name'] = array("like","%$shop_name%");
		$count = $model->where($map)->count();
		$Page = new Page($count,10);
		$list = $model->where($map)->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		/*dd($list);
		exit;*/
		foreach($list as $k=>&$v){
		    $v['store_info']=M('store')->where(array("store_id"=>$v["store_id"]))->find();
        }
        /*dd($list);
		exit;*/
		$this->assign('list',$list);
		$show = $Page->show();
		$this->assign('pager',$Page);
		$this->assign('page',$show);
		/*$this->assign('store_grade',M('store_grade')->getField('sg_id,sg_name'));
		$this->assign('store_class',M('store_class')->getField('sc_id,sc_name'));*/
		return $this->fetch();
	}
	
	public function apply_del(){
		$id = I('del_id');
		if($id && M('shop')->where(array('shop_id'=>$id))->delete()){
//			$this->success('操作成功',U('Store/apply_list'));
            $this->ajaxReturn(['status'=>1,'操作成功']);
		}else{
//			$this->error('操作失败');
            $this->ajaxReturn(['status'=>0,'操作失败']);
		}
	}
	//经营类目申请列表
	public function apply_class_list(){
		$state = I('state'); //审核状态
        $store_name = I('store_name');  ///店铺名称
        if($store_name) {
            $store_where['store_name'] = $store_name;  ///店铺名称
            $bind_class_where['store_id'] = Db::name('store')->where($store_where)->getField('store_id');
        }
        $state>=0 && $bind_class_where['state'] = $state;
        $count = Db::name('store_bind_class')->where($bind_class_where)->count();
        $Page = new Page($count,20);
        $bind_class = Db::name('store_bind_class')
            ->where($bind_class_where)
            ->order('bid desc')
            ->limit($Page->firstRow,$Page->listRows)
            ->select();
		$goods_class = Db::name('goods_category')->cache(true)->getField('id,name');
		for($i = 0, $j = count($bind_class); $i < $j; $i++) {
			$bind_class[$i]['class_1_name'] = $goods_class[$bind_class[$i]['class_1']];
			$bind_class[$i]['class_2_name'] = $goods_class[$bind_class[$i]['class_2']];
			$bind_class[$i]['class_3_name'] = $goods_class[$bind_class[$i]['class_3']];
			$store = Db::name('store')->where("store_id=".$bind_class[$i]['store_id'])->find();
			$bind_class[$i]['store_name'] = $store['store_name'];
			$bind_class[$i]['seller_name'] = $store['seller_name'];
		}
		$this->assign('bind_class',$bind_class);
		$this->assign('page',$Page);
		return $this->fetch();
	}
	
	//查看-添加店铺经营类目
	public function store_class_info(){
		$store_id = I('store_id');
		$store = M('store')->where(array('store_id'=>$store_id))->find();
		$this->assign('store',$store);
		if(IS_POST){
			$data = I('post.');
			$data['state'] = 1;
			$where = 'class_3 ='.$data['class_3'].' and store_id='.$data['store_id'];
			if(M('store_bind_class')->where($where)->count()>0){
				$this->ajaxReturn(['status'=>-1,'msg'=>'该店铺已申请过此类目']);
			}
            if($data['class_1']==0 || $data['class_3']==0 || $data['class_3']==0){
                $this->ajaxReturn(['status'=>-1,'msg'=>'必须选择第三级类目']);
            }
			$data['commis_rate'] = M('goods_category')->where(array('id'=>$data['class_3']))->getField('commission');
			if(M('store_bind_class')->add($data)){
				adminLog('添加店铺经营类目，类目编号:'.$data['class_3'].',店铺编号:'.$data['store_id']);
				$this->ajaxReturn(['status'=>1,'msg'=>'添加经营类目成功']);exit;
			}else{
				$this->ajaxReturn(['status'=>-1,'msg'=>'操作失败']);
			}
		}
		$bind_class_list = M('store_bind_class')->where('store_id='.$store_id)->select();
		$goods_class = M('goods_category')->getField('id,name');
		for($i = 0, $j = count($bind_class_list); $i < $j; $i++) {
			$bind_class_list[$i]['class_1_name'] = $goods_class[$bind_class_list[$i]['class_1']];
			$bind_class_list[$i]['class_2_name'] = $goods_class[$bind_class_list[$i]['class_2']];
			$bind_class_list[$i]['class_3_name'] = $goods_class[$bind_class_list[$i]['class_3']];
		}
		$this->assign('bind_class_list',$bind_class_list);
		$cat_list = M('goods_category')->where("parent_id = 0")->select();
		$this->assign('cat_list',$cat_list);
		return $this->fetch();
	}
	
	
	public function apply_class_save(){
		$data = I('post.');
		if($data['act']== 'del'){
			$r = M('store_bind_class')->where("bid=".$data['del_id'])->delete();
			respose(1);
		}else{
			$data = I('get.');
			$r = M('store_bind_class')->where("bid=".$data['bid'])->save(array('state'=>$data['state']));
		}
		if($r){
			$this->success('操作成功',U('Store/apply_class_list'));
		}else{
			$this->error('提交失败');
		}
	}
	
	//店铺申请信息详情
	public function apply_info(){
		$id = I('id');
		$shop_info = M('shop')->where("shop_id=$id")->find();
        $industry=M('shop_industry')->where("industry_id=".$shop_info['industry'])->find();
		$province_name = M('region')->where(array('id'=>$shop_info['province']))->getField('name');
		$city_name = M('region')->where(array('id'=>$shop_info['city']))->getField('name');
		$district_name = M('region')->where(array('id'=>$shop_info['district']))->getField('name');
		//获取线下店铺产品
        $where['shop_id']=$shop_info['shop_id'];
        $count = Db::name('shop_goods')->where($where)->count();
        $Page = new Page($count,10);
        $shop_good_list = Db::name('shop_goods')
            ->where($where)
            ->order('shop_goods_id desc')
            ->limit($Page->firstRow,$Page->listRows)
            ->select();
        $this->assign('page',$Page);
        $this->assign('shop_good_list',$shop_good_list);
		$this->assign('province_name',$province_name);
		$this->assign('city_name',$city_name);
		$this->assign('district_name',$district_name);
		$this->assign('industry',$industry);
		/*$bank_province_name = M('region')->where(array('id'=>$apply['bank_province']))->getField('name');
		$bank_city_name = M('region')->where(array('id'=>$apply['bank_city']))->getField('name');
		$this->assign('bank_province_name',$bank_province_name);
		$this->assign('bank_city_name',$bank_city_name);
		$goods_cates = M('goods_category')->getField('id,name,commission');

		if(!empty($apply['store_class_ids'])){
			$store_class_ids = unserialize($apply['store_class_ids']);
			foreach ($store_class_ids as $val){
				$cat = explode(',', $val);
				$bind_class_list[] = array(
						'class_1'=>$goods_cates[$cat[0]]['name'],
						'class_2'=>$goods_cates[$cat[1]]['name'],
						'class_3'=>$goods_cates[$cat[2]]['name'],
						'commis_rate'=>$goods_cates[$cat[2]]['commission'],
						'value'=>$val,
				);
			}
			$this->assign('bind_class_list',$bind_class_list);
		}
		*/
		$company_type = config('company_type');
        $store_info=M('store')->where(array("store_id"=>$shop_info['store_id']))->find();
        $this->assign('store',$store_info);
		$this->assign('shop',$shop_info);

		/*$apply_log = M('admin_log')->where(array('log_type'=>1))->select();
		$this->assign('apply_log',$apply_log);
		$this->assign('company_type', $company_type);
		$this->assign('store_grade',M('store_grade')->select());*/
		return $this->fetch();
	}
	
	//审核线下店铺开通申请
	public function review(){
		$data = I('post.');
		if($data['id']){
		    $shop_info=M('shop')->where(array("shop_id"=>$data['id']))->find();
		    $store_info=M('store')->where(array("store_id"=>$shop_info['store_id']))->find();
		    $res=M('shop')->where(array("shop_id"=>$data['id']))->save($data);
		    if($res!==false){
                switch ($data["status"])
                {
                    case 0 :
                        $status_text = "未审核";
                        break;
                    case 1 :
                        $status_text = "审核通过";
                        break;
                    case 2 :
                        $status_text = "未通过";
                        break;
                    case 3 :
                        $status_text = "冻结";
                        break;
                    case 4 :
                        $status_text = "关闭";
                        break;
                }
                adminLog($store_info['store_name'].'线下店铺申请'.$status_text,1);
                $this->success('操作成功',U('Shop/shop_list'));
            }else{
                $this->error('提交失败',U('Shop/apply_list'));
            }
		}
	}


	public function reopen_list()
	{
		$list = M('store_reopen')->where('')->select();
		$this->assign('list', $list);
		return $this->fetch();
	}
	
	public function domain_list(){
		$model =  M('store');
		$map['store_state'] = 1;
		$store_domian = I("store_domain");
		if($store_domian) {
			$map['store_domian'] = array('like',"%$store_domian%");
		}else{
			$map['store_domain'] = array('neq',"");
		}
		$seller_name = I('seller_name');
		if($seller_name) $map['seller_name'] = array('like',"%$seller_name%");
		$store_name = I('store_name');
		if($store_name) $map['store_name'] = array('like',"%$store_name%");
		$count = $model->where($map)->count();
		$Page = new Page($count,20);
		$list = $model->where($map)->order('store_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('list',$list);
		
		$show = $Page->show();
		$this->assign('page',$show);
		$this->assign('pager',$Page);
		return $this->fetch();
	}
	
        /**
        *  店家满意度 
        *  2017-9-22 14：45
        */
	public function satisfaction(){ 
            $model =  M('order_comment oc');
            //A.查询条件
            ($user_name  = I('user_name'))  && $map['u.nickname']    = ['like',"%$user_name%"];            
            ($store_name = I('store_name')) && $map['s.store_name']  = ['like',"%$store_name%"];         
            $prefix      = C('prefix');
            $field       = [
                'oc.*',
                'u.user_id',
                'u.nickname',
                'o.order_id',
                'o.order_sn',
                's.store_id',
                's.store_name',
            ];
            // B. 开始查询
            $model = $model
                    ->field($field)
                    ->join($prefix . 'users u  ','u.user_id = oc.user_id','left')
                    ->join($prefix . 'store s  ','s.store_id = oc.store_id','left')
                    ->join($prefix . 'order o  ','o.order_id = oc.user_id','left')
                    ->where($map)
                    ->order('order_commemt_id DESC');
            // B.1 总数
            $tModel = clone $model;
            $count = $tModel->count();
            // B.2 开始分页
            $Page = new Page($count,20);
            $show = $Page->show();
            $list = $model->limit($Page->firstRow.','.$Page->listRows)->select();
            
            $this->assign('page',$show);
            $this->assign('pager',$Page);
            $this->assign('list',$list);        
            return $this->fetch();
	}
	
        /**
        *  店铺评分
        *  2017-9-22 15：05
        */
	public function score(){     
            $model =  M('order_comment oc');
            $store_name = I('store_name');
            if($store_name) $map['store_name'] = array('like',"%$store_name%");
             $field=[
                'store_id',
                'COUNT(*) AS number',
                'AVG(describe_score) AS describe_score',
                'AVG(seller_score) AS seller_score',
                'AVG(logistics_score) AS logistics_score', 
            ];
            $count = $model->field($field)->where($map)->group("store_id")->count();
            $Page = new Page($count,20);           
            $list = $model->field($field)
                        ->where($map)
                        ->order('order_commemt_id DESC')
                        ->limit($Page->firstRow.','.$Page->listRows)
                        ->group("store_id")
                        ->select();
            if($list){    
                foreach($list as $k => $v){                                  
                    $v['store_name'] = M('store')->cache(true)
                            ->where('store_id = '.$v['store_id'])
                            ->getfield('store_name');               
                    $order_comment_list[] = $v;
                }
            }             
            $show = $Page->show();
            $this->assign('page',$show);
            $this->assign('pager',$Page);
            $this->assign('list',$order_comment_list);
            return $this->fetch();
	}


	/**
     * 删除线下店铺商品
     */
	function good_del(){
	    $good_id=I('id');
	    if($good_id){
            $res=M('shop_goods')->where(array("shop_goods_id"=>$good_id))->delete();
            if($res!==false){
                $this->success("删除成功");
            }else{
                $this->error("删除失败");
            }
        }

    }
}