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
<style type="text/css">
    .mgt-10 {
        margin-bottom: 10px
    }
</style>
<script src="/public/js/vue.js"></script>

</head>
<body>
<div class="wrap" id="order-app">
    <form class="well form-search form-inline">

        <div class="mgt-10">
            所属店铺：
            <select v-model="search.store">
                <option value="0" selected>全部</option>
                <?php if(is_array($stores)): $i = 0; $__LIST__ = $stores;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$store): $mod = ($i % 2 );++$i;?><option value="<?php echo ($store["id"]); ?>"><?php echo ($store["name"]); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
            </select>&nbsp;&nbsp;
            预约理发师：
            <input v-model="search.barber" maxlength="10" type="text" placeholder="理发师姓名">&nbsp;&nbsp;
            预约时间：
            <input type="text" name="start_time" class="js-datetime" v-model="search.start_time" value="" autocomplete="off" placeholder="开始时间">&nbsp;-
            <input type="text" class="js-datetime" name="end_time" v-model="search.end_time" value="" autocomplete="off" placeholder="结束时间"> &nbsp;
            <!--<a class="btn btn-default btn-small" @click="setToday">今天</a><a class="btn btn-default btn-small" @click="setThisWeek">近一周</a><a @click="setThisMonth" class="btn btn-default btn-small">本月</a>-->
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="button" class="btn btn-primary" @click="getData" value="查询">
            <!--<input type="button" class="btn btn-success" @click="export" value="导出">-->
        </div>
    </form>
    <div  class="container">
        <div class="row">
            <div class="span10">
                <table class="table table-hover table-bordered table-list">
                    <thead>
                    <tr>
                        <th></th>
                        <th>已完成</th>
                        <th>已失效</th>
                        <th>未服务</th>
                        <th>已关闭</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>总订单数：</td>
                        <td>{{orderList.amount_2}}</td>
                        <td>{{orderList.amount_3}}</td>
                        <td>{{orderList.amount_1}}</td>
                        <td>{{orderList.amount_0}}</td>
                    </tr>
                    <tr>
                        <td>总金额：</td>
                        <td>{{orderList.sumprice_2}}</td>
                        <td>{{orderList.sumprice_3}}</td>
                        <td>{{orderList.sumprice_1}}</td>
                        <td>{{orderList.sumprice_0}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="/public/js/common.js"></script>
<script>
    Wind.use("artDialog", function () {
    });
    var demo = new Vue({
        el: '#order-app',
        data: {
            titles:{},
            orderList: null,
            search: {store:'',barber:'',start_time: null, end_time: null},
        },
        methods: {
            getData: function (from) {
                $.getJSON("<?php echo U('index');?>", this.search, function (res) {
                    if (res.status == 1) {
                        demo.orderList = res.info;
                        demo.titles = res.info[0];
                    } else {
                        alert(res.info);
                    }
                })
            },
        },
        created: function () {
            this.getData();
        }
    })

    function sendPost(URL, PARAMS) {
        var temp = document.createElement("form");
        temp.target="_blank"
        temp.action = URL;
        temp.method = "post";
        temp.style.display = "none";
        for (var x in PARAMS) {
            var opt = document.createElement("textarea");
            opt.name = x;
            opt.value = PARAMS[x];
            // alert(opt.name)
            temp.appendChild(opt);
        }
        document.body.appendChild(temp);
        temp.submit();
        return temp;
    }
</script>
</body>
</html>