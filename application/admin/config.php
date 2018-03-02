<?php
$home_config = [
    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------
	//默认错误跳转对应的模板文件
	'dispatch_error_tmpl' => 'public:dispatch_jump',
	//默认成功跳转对应的模板文件
	'dispatch_success_tmpl' => 'public:dispatch_jump',
    'dispatch_top_tmpl' => 'public:top_jump',
        // URL伪静态后缀
        'url_html_suffix'        => '',
        //会员等级名称
       'user_rank_name' => [0 => "普通会员",1 => "星级会员", 2 => "钻石会员"]
];

$html_config = include_once 'html.php';
return array_merge($home_config,$html_config);
?>