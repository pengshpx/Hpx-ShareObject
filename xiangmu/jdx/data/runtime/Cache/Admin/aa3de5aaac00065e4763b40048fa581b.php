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
</head>
<body>
<div class="wrap js-check-wrap" id="app">
    <ul class="nav nav-tabs">
        <li class="active"><a href="<?php echo U('index');?>">日志列表</a></li>
    </ul>
    <form class="well form-search" id="search_form" method="post">
        操作人：
        <input type="text" class="input" v-model="searchCon.user_login" style="width: 200px;" placeholder="操作人">
        操作时间：
        <input type="text" v-model="searchCon.st_time" class="input js-datetime date" autocomplete="off" placeholder="开始时间">-
        <input type="text" v-model="searchCon.end_time" class="input js-datetime date" autocomplete="off" placeholder="结束时间">
        <input type="button" class="btn btn-primary" @click="mySearch(true)" value="搜索">
    </form>
    <table class="table table-hover table-bordered table-list">
        <thead>
        <tr>
            <th width="10%">ID</th>
            <th width="15%">操作人</th>
            <th width="50%">内容</th>
            <th width="25%">操作时间</th>
        </tr>
        </thead>
        <tbody>
        <tr v-cloak v-for="item in list">
            <td>{{item.id}}</td>
            <td>{{item.user_login}}</td>
            <td>{{item.remark}}</td>
            <td>{{item.create_time}}</td>
        </tr>
        </tbody>
    </table>
    <vue-pager :conf.sync="pagerConf"></vue-pager>
</div>
<script src="/public/js/common.js"></script>
<script src="/public/js/vue.js"></script>
<script src="/public/js/vueComponent/pager.js"></script>
<script type="text/javascript">
    Wind.use("artDialog", function () {});
    var app = new Vue({
        el:"#app",
        data:{
            list:[],
            searchCon:{user_login:'',st_time:'',end_time:'',p:1},
            pagerConf:{
                totalPage : 0,
                currPage : 1,
                prevShow : 3,
                nextShow : 3,
                pageRange:[]
            }
        },
        watch:{
            'pagerConf.currPage':function () {
                this.mySearch(false);
            }
        },
        methods:{
            mySearch: function (search) {
                var data = this.searchCon;
                if (data.st_time!='' && data.end_time!='') {
                    if (data.st_time > data.end_time) {
                        $.dialog({id: 'popup', lock: true,icon:"warning", content: "开始时间不能大于结束时间", time: 2});
                        return;
                    }
                }
                data.p = search == true ? 1 : this.pagerConf.currPage;
                $.ajax({
                    url:"<?php echo U('index');?>",
                    data:data,
                    type:"POST",
                    dataType:"json",
                    success: function (res) {
                        if (res.status == 1) {
                            app.list = res.list;
                            app.pagerConf.totalPage = res.totalPage;
                        }
                    },
                    error: function () {
                        $.dialog({id: 'popup', lock: true,icon:"warning", content: "请求失败,请重试", time: 2});
                    }
                })
            }
        },
        created: function () {
            this.mySearch(true);
        }
    });
</script>
</body>
</html>