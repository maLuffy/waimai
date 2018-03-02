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

use think\Page;
use app\admin\logic\ArticleCatLogic;
use think\Loader;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class Article extends Base {

    public function categoryList(){
        $ArticleCat = new ArticleCatLogic(); 
        $cat_list = $ArticleCat->article_cat_list(0, 0, false);
        $this->assign('cat_list',$cat_list);
        return $this->fetch('categoryList');
    }

    public function category()
    {
        $ArticleCat = new ArticleCatLogic();
        $act = I('get.act', 'add');
        $cat_id = I('get.cat_id/d');
        $parent_id = I('get.parent_id/d');
        if ($cat_id) {
            $cat_info = M('article_cat')->where('cat_id=' . $cat_id)->find();
            $parent_id = $cat_info['parent_id'];
            $this->assign('cat_info', $cat_info);
        }
        $cats = $ArticleCat->article_cat_list(0, $parent_id, true);
        $this->assign('act', $act);
        $this->assign('cat_select', $cats);
        return $this->fetch();
    }
    
    public function articleList(){
        $Article =  M('Article'); 
        $res = $list = array();
        $p = empty($_REQUEST['p']) ? 1 : $_REQUEST['p'];
        $size = empty($_REQUEST['size']) ? 20 : $_REQUEST['size'];
        
        $where = " 1 = 1 ";
        $keywords = trim(I('keywords'));
        $keywords && $where.=" and title like '%$keywords%' ";
        $cat_id = I('cat_id','34');
        if($cat_id=="-1"){
            //$where .="";
        }else{
            $cat_id && $where.=" and cat_id = $cat_id ";
        }

        $res = $Article->where($where)->order('article_id desc')->page("$p,$size")->select();
        $count = $Article->where($where)->count();// 查询满足要求的总记录数
        $pager = new Page($count,$size);// 实例化分页类 传入总记录数和每页显示的记录数
        //$page = $pager->show();//分页显示输出
        
        $ArticleCat = new ArticleCatLogic();
        $cats = $ArticleCat->article_cat_list(0,0,false);
        if($res){
        	foreach ($res as $val){
        		$val['category'] = $cats[$val['cat_id']]['cat_name'];
        		$val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);        		
        		$list[] = $val;
        	}
        }
        $this->assign('cats',$cats);
        $this->assign('cat_id',$cat_id);
        $this->assign('keywords',$keywords);
        $this->assign('list',$list);// 赋值数据集
        $this->assign('pager',$pager);// 赋值分页输出        
		return $this->fetch('articleList');
    }
    
    public function article(){
        $ArticleCat = new ArticleCatLogic();
 		$act = I('GET.act','add');
        $info = array();
        $info['publish_time'] = time()+3600*24;
        if(I('GET.article_id')){
           $article_id = I('GET.article_id');
           $info = M('article')->where('article_id='.$article_id)->find();
        }
        $cats = $ArticleCat->article_cat_list(0,$info['cat_id']);
        $this->assign('cat_select',$cats);
        $this->assign('act',$act);
        $this->assign('info',$info);
        return $this->fetch();
    }
    
    
    public function categoryHandle()
    {
    	$data = I('post.');

        $result = $this->validate($data, 'ArticleCategory.'.$data['act'], [], true);
        if ($result !== true) {
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误', 'result' => $result]);
        }
        
        if ($data['act'] == 'add') {
            $r = M('article_cat')->add($data);
        } elseif ($data['act'] == 'edit') {
        	$cat_info = M('article_cat')->where("cat_id",$data['cat_id'])->find();
        	if($cat_info['cat_type'] == 1 && $data['parent_id'] > 1){
        		$this->ajaxReturn(['status' => -1, 'msg' => '可更改系统预定义分类的上级分类']);
        	}
        	$r = M('article_cat')->where("cat_id",$data['cat_id'])->save($data);
        } elseif ($data['act'] == 'del') {
        	if($data['cat_id']<9){
        		$this->ajaxReturn(['status' => -1, 'msg' => '系统默认分类不得删除']);
        	}
        	if (M('article_cat')->where('parent_id', $data['cat_id'])->count()>0)
        	{
        		$this->ajaxReturn(['status' => -1, 'msg' => '还有子分类，不能删除']);
        	}
        	if (M('article')->where('cat_id', $data['cat_id'])->count()>0)
        	{
        		$this->ajaxReturn(['status' => -1, 'msg' => '该分类下有文章，不允许删除，请先删除该分类下的文章']);
        	}
        	$r = M('article_cat')->where('cat_id', $data['cat_id'])->delete();
        }
        
        if (!$r) {
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
        } 
        $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
    }
    
    public function aticleHandle()
    {
        $data = I('post.');
        $data['publish_time'] = strtotime($data['publish_time']);
        //$referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Admin/Article/articleList');
        
        $result = $this->validate($data, 'Article.'.$data['act'], [], true);
        if ($result !== true) {
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误', 'result' => $result]);
        }
        
        if ($data['act'] == 'add') {
            $data['click'] = mt_rand(1000,1300);
        	$data['add_time'] = time(); 
            $r = M('article')->add($data);
        } elseif ($data['act'] == 'edit') {
            $r = M('article')->where('article_id='.$data['article_id'])->save($data);
        } elseif ($data['act'] == 'del') {
        	$r = M('article')->where('article_id='.$data['article_id'])->delete(); 	
        }
        
        if (!$r) {
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
        }
            
        $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
    }
    
    
    public function link(){
    	$act = I('GET.act','add');
    	$this->assign('act',$act);
    	$link_id = I('GET.link_id');
    	$link_info = array();
    	if($link_id){
    		$link_info = M('friend_link')->where('link_id='.$link_id)->find();
    		$this->assign('info',$link_info);
    	}
    	return $this->fetch();
    }
    
    public function linkList(){
    	$Ad =  M('friend_link');
        $p = $this->request->param('p');
    	$res = $Ad->order('orderby')->page($p.',10')->select();
    	if($res){
    		foreach ($res as $val){
    			$val['target'] = $val['target']>0 ? '开启' : '关闭';
    			$list[] = $val;
    		}
    	}
    	$this->assign('list',$list);// 赋值数据集
    	$count = $Ad->count();// 查询满足要求的总记录数
    	$Page = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
    	$show = $Page->show();// 分页显示输出
        $this->assign('pager',$Page);
    	$this->assign('page',$show);// 赋值分页输出
    	return $this->fetch();
    }
    
    public function linkHandle(){
        $data = I('post.');
    	if($data['act'] == 'del'){
    		$r = M('friend_link')->where('link_id='.$data['link_id'])->delete();
    		if($r) exit(json_encode(1));
    	}
        $result = $this->validate($data,'FriendLink.'.$data['act'], [], true);
        if(true !== $result){
            // 验证失败 输出错误信息
            $validate_error = '';
            foreach ($result as $key =>$value){
                $validate_error .=$value.',';
            }
            $this->error($validate_error);
        }
        if($data['act'] == 'add'){
            $r = M('friend_link')->insert($data);
        }
        if($data['act'] == 'edit'){
            $r = M('friend_link')->where('link_id='.$data['link_id'])->save($data);
        }
    	if($r){
    		$this->error("操作成功",U('Admin/Article/linkList'));
    	}else{
            $this->error("操作失败");
    	}
    }
    
    public function helpList(){
        $ArticleCat = new ArticleCatLogic();
    	$Article =  M('help');
    	$res = $list = array();
    	$p = empty($_REQUEST['p']) ? 1 : $_REQUEST['p'];
    	$size = empty($_REQUEST['size']) ? 20 : $_REQUEST['size'];
    	$where = " 1 = 1 ";
    	$keywords = trim(I('keywords'));
    	$keywords && $where.=" and help_title like '%$keywords%' ";
    	$type_id = I('type_id',0);
    	$type_id && $where.=" and type_id = $type_id ";
    	$res = $Article->where($where)->order('help_id desc')->page("$p,$size")->select();
    	$count = $Article->where($where)->count();
    	$pager = new Page($count,$size);
    	$all_type = M('help_type')->where(array('help_show'=>1))->getField('type_id,type_name,pid');
    	if(!empty($all_type)){
			$type_tree = $ArticleCat->getCatTree($all_type);
			$cat_select = $ArticleCat->exportTree($type_tree);
			$this->assign('cat_select',$cat_select);
			$this->assign('all_type',$all_type);
    	}
    	$this->assign('type_id',$type_id);
    	$this->assign('list',$res);
    	$this->assign('pager',$pager);
    	return $this->fetch();
    }
    
    public function helpInfo(){
    	$act = I('GET.act','add');
    	$help_id = I('help_id/d');
    	if($help_id>0){
    		$info = M('help')->where('help_id='.$help_id)->find();
    		$this->assign('info',$info);
    	}
        $ArticleCat = new ArticleCatLogic();
    	$all_type = M('help_type')->where(array('help_show'=>1))->getField('type_id,type_name,pid');
    	if(!empty($all_type)){
    		$all_type = $ArticleCat->getCatTree($all_type);
    		$select_id = !empty($info) ? $info['type_id'] : 0 ;
    		$cat_select = $ArticleCat->exportTree($all_type,0,$select_id);
    		$this->assign('cat_select',$cat_select);
    	}
    	$this->assign('act',$act);
    	return $this->fetch();
    }
    
    public function helpHandle()
    {
    	$data = I('post.');
    	if(empty($data['help_title']) && $data['act'] != 'del'){
    		$this->ajaxReturn(['status' => -1, 'msg' => '标题不能为空']);
    	}
        $validate = loader::Validate('Help');
        if (!$validate->scene($data['act'])->check($data)) {
            $error = $validate->getError();
            $this->ajaxReturn(['status' => -1,'msg' => $error]);
        }
    	if ($data['act'] == 'add') {
    		$data['add_time'] = time();
    		$r = M('help')->add($data);
    	} elseif ($data['act'] == 'edit') {
    		$r = M('help')->where('help_id='.$data['help_id'])->save($data);
    	} elseif ($data['act'] == 'del') {
            if($data['help_id']<11){
                $this->ajaxReturn(['status' => -1, 'msg' => '系统默认分类不得删除']);
            }
    		$r = M('help')->where('help_id='.$data['help_id'])->delete();
    	}
    	if (!$r) {
    		$this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
    	}
    	$this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
    }
    
    public function helpTypeList(){
        $all_type = M('help_type')->where(array('help_show'=>1))->order('type_id')->getField('type_id,type_name,pid,sort_order,is_show,level');
        $all_type2 = getSortCatArray($all_type,'type_id');
    	$this->assign('cat_list',$all_type2);
        return $this->fetch();
    }
    
    
    public function helpTypeInfo(){
       	$all_type = M('help_type')->where(array('help_show'=>1))->getField('type_id,type_name,pid');
        $ArticleCat = new ArticleCatLogic();
    	if(!empty($all_type)){
			$all_type = $ArticleCat->getCatTree($all_type);
			$cat_select = $ArticleCat->exportTree($all_type);
			$this->assign('cat_select',$cat_select);
    	}
    	$type_id = I('type_id/d');
    	if($type_id>0){
    		$type_info = M('help_type')->where(array('type_id'=>$type_id))->find();
    		$this->assign('cat_info',$type_info);
    	}
    	$act = I('get.act', 'add');
    	$this->assign('act', $act);
    	return $this->fetch();
    }
    
    public function helpTypeHandle(){
    	$data = I('post.');
    	if($data['pid']>0){
    		$data['level'] = M('help_type')->where(array('type_id'=>$data['pid']))->getField('level')+1;
    	}
        $validate = loader::Validate('HelpType');
        if (!$validate->scene($data['act'])->check($data)) {
            $error = $validate->getError();
            $this->ajaxReturn(['status' => -1,'msg' => $error]);
        }
    	if ($data['act'] == 'add') {
    		$r = M('help_type')->add($data);
    	} elseif ($data['act'] == 'edit') {
    		$r = M('help_type')->where("type_id",$data['type_id'])->save($data);
    	} elseif ($data['act'] == 'del') {
    		$r = M('help_type')->where('type_id', $data['type_id'])->delete();
    	}
    	if (!$r) {
    		$this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
    	}
    	$this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
    }

    public function uploadify(){
        $targetFolder = ROOT_PATH.'/uploads'; // Relative to the root
        if(!$_FILES['Filedata']['tmp_name']){
            $return_data['status'] = "0";
            $return_data['info'] = "请联系管理员修改php.ini上传设置";
            $this->ajaxReturn($return_data,'json');
        }
        if (!empty($_FILES)) {
            $tempFile = $_FILES['Filedata']['tmp_name'];
            $targetPath = $targetFolder;
            $fileTypes = array('avi','mp4'); // File extensions

            $fileParts = pathinfo($_FILES['Filedata']['name']);
            /*  52428800
             * //上传骑牛云
             * */
            $fileParts = pathinfo($_FILES['Filedata']['name']);
            $file_size=$_FILES['Filedata']['size'];
            if($file_size > 52428800){
                $return_data['status'] = "0";
                $return_data['info'] = "请检查文件大小";
                $this->ajaxReturn($return_data,'json');
            }

            $ext = $fileParts['extension'];  //后缀
            //转小写    mp4/avi/wmv
            $ext_lower=strtolower($ext);
            $video_type=array("mp4","avi","wmv");
            if(!in_array($ext_lower,$video_type)){
                $return_data['status'] = "0";
                $return_data['info'] = "请检查文件格式";
                $this->ajaxReturn($return_data,'json');
            }
            if (in_array($ext,$fileTypes)) {
                $key =substr(md5($fileParts['filename']) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;
                //上传  CDN
                require_once APP_PATH . '../vendor/qiniu/php-sdk/autoload.php';
                // 需要填写你的 Access Key 和 Secret Key
                $accessKey = config('ACCESSKEY');
                $secretKey = config('SECRETKEY');
                // 构建鉴权对象
                $auth = new Auth($accessKey, $secretKey);
                // 要上传的空间
                $bucket = config('BUCKET');
                $domain = config('DOMAINImage');
                $token = $auth->uploadToken($bucket);
                // 初始化 UploadManager 对象并进行文件的上传
                $uploadMgr = new UploadManager();
                // 调用 UploadManager 的 putFile 方法进行文件的上传
                list($ret, $err) = $uploadMgr->putFile($token, $key, $tempFile);

                if ($err !== null) {
                    $return_data['status'] = "0";
                    $return_data['original'] = ''; // 这里好像没啥用 暂时注释起来
                    $return_data['info'] = "系统错误";
                    //$return_data['path'] = $path;
                    //$return_data['url'] = $cnd_url;
                    $this->ajaxReturn($return_data,'json');
                } else {
                    //返回图片的完整URL
                    $cnd_url = $domain.$ret['key'];
                    $return_data['status'] = "1";
                    $return_data['original'] = ''; // 这里好像没啥用 暂时注释起来
                    $return_data['info'] = "上传成功";
                    //$return_data['path'] = $path;
                    $return_data['url'] = $cnd_url;
                    $this->ajaxReturn($return_data,'json');
                }

            } else {
                $data['status']="0";
                $data['info']="上传失败!请确认文件类型";
                //$data['save_path']=$targetFile;
                return json_encode($data);
                //echo 'Invalid file type.';
            }
        }
    }



}