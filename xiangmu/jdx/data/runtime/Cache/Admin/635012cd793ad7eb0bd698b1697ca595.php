<?php if (!defined('THINK_PATH')) exit();?><!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<!-- Set render engine for 360 browser -->
	<meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- HTML5 shim for IE8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <![endif]-->

	<link href="/public/simpleboot/themes/<?php echo C('SP_ADMIN_STYLE');?>/theme.min.css" rel="stylesheet">
    <link href="/public/simpleboot/css/simplebootadmin.css" rel="stylesheet">
    <link href="/public/js/artDialog/skins/default.css" rel="stylesheet" />
    <link href="/public/simpleboot/font-awesome/4.4.0/css/font-awesome.min.css"  rel="stylesheet" type="text/css">
    <style>
		.length_3{width: 180px;}
		form .input-order{margin-bottom: 0px;padding:3px;width:40px;}
		.table-actions{margin-top: 5px; margin-bottom: 5px;padding:0px;}
		.table-list{margin-bottom: 0px;}
		[v-cloak] {  display: none;  }
	</style>
	<!--[if IE 7]>
	<link rel="stylesheet" href="/public/simpleboot/font-awesome/4.4.0/css/font-awesome-ie7.min.css">
	<![endif]-->
    <script type="text/javascript">
    //全局变量
    var GV = {
        DIMAUB: "/",
        JS_ROOT: "public/js/",
        TOKEN: ""
    };
    </script>
    <script src="/public/js/jquery.js"></script>
    <script src="/public/js/wind.js"></script>
    <script src="/public/simpleboot/bootstrap/js/bootstrap.min.js"></script>
<?php if(APP_DEBUG): ?><style>
		#think_page_trace_open{
			z-index:9999;
		}
	</style><?php endif; ?>
	<style type="text/css">

		/*移除HTML5 input在type="number"时的上下小箭头*/

		/*在chrome下：*/
		input::-webkit-outer-spin-button,
		input::-webkit-inner-spin-button{
			-webkit-appearance: none !important;
			margin: 0;
		}
		/*Firefox下：*/
		input[type="number"]{
			-moz-appearance:textfield;
		}
	</style>
<script src="/public/js/vue.js"></script>
<style type="text/css">
    #detailModal {
        width: 900px;
        margin-left: -480px;
    }
</style>
</head>
<body>
<div class="wrap" id="app">
    <ul class="nav nav-tabs">
        <li class="active"><a href="<?php echo U('Barber/index');?>">理发师列表</a></li>
        <li><a href="<?php echo U('Barber/add');?>">添加理发师</a></li>
    </ul>
    <table class="table table-hover table-bordered table-list">
        <thead>
        <tr>
            <th>理发师姓名</th>
            <th>电话</th>
            <th>所属店铺</th>
            <th>从业年数</th>
            <th>单数</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if(is_array($lists)): $i = 0; $__LIST__ = $lists;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): $mod = ($i % 2 );++$i;?><tr>
                <td><?php echo ($item["name"]); ?></td>
                <td><?php echo ($item["tel"]); ?></td>
                <td><?php echo ($item["store_name"]); ?></td>
                <td><?php echo ($item["year"]); ?></td>
                <td><?php echo ($item["service_num"]); ?></td>
                <td>
                    <a href="javascript:parent.openapp('<?php echo U('Order/index?barber='.$item[name]);?>','order','预约订单')" class="btn btn-small btn-default">预约订单</a>
                    <?php if($item["closed"] == 0): ?><a href="<?php echo U('Barber/edit?id='.$item[id]);?>" class="btn btn-small btn-primary">编辑</a>

                        <a href="<?php echo U('Barber/delete?id='.$item[id]);?>" data-msg="确定冻结此理发师吗？" class="btn btn-small btn-danger js-ajax-delete">冻结</a>
                        <?php else: ?>
                        <a href="<?php echo U('Barber/recover?id='.$item[id]);?>" data-msg="确定恢复此理发师吗？" class="btn btn-small btn-success js-ajax-delete">恢复</a><?php endif; ?>

                </td>

            </tr><?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>
    <div class="pagination pagination-centered"><ul><?php echo ($Page); ?></ul></div>
</div>

<script src="/public/js/common.js"></script>

</body>
</html>