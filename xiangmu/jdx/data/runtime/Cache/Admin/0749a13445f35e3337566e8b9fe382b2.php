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
<div class="wrap" id="app">
    <ul class="nav nav-tabs">
        <li class="active"><a href="<?php echo U('index');?>">会员列表</a></li>
    </ul>
    <form class="well form-search" id="search_form" method="post">
        昵称：
        <input type="text" class="input" v-model="searchCon.nickname" placeholder="昵称">
        手机号：
        <input type="number" v-model="searchCon.mobile" class="input" placeholder="手机号">
        状态：
        <select v-model="searchCon.status">
            <option value="" selected>请选择</option>
            <option v-for="(key,val) in status" :value="key">{{val}}</option>
        </select>
        <input type="button" class="btn btn-primary" @click="mySearch(true)" value="搜索">
        <input type="button" class="btn btn-success" @click="export" value="导出">
    </form>
    <table class="table table-hover table-bordered">
        <thead>
        <tr>
            <th>昵称</th>
            <th>注册时间</th>
            <th>手机号</th>
            <th>余额</th>
            <th>状态</th>
            <th align="center">操作</th>
        </tr>
        </thead>
        <tbody>
        <tr v-cloak v-for="item in list">
            <td>{{item.nickname}}</td>
            <td>{{item.create_time}}</td>
            <td>{{item.mobile}}</td>
            <td>{{item.money}}</td>
            <td>{{status[item.status]}}</td>
            <td align="center">
                <a v-if="item.status==0" @click="openUser(item.member_id)" class="btn btn-small btn-primary">启用用户</a>
                <a v-else @click="disableUser(item.member_id)" class="btn btn-small btn-danger">禁用用户</a>
                <a class="btn btn-small btn-info" href="<?php echo U('balance');?>/member_id/{{item.member_id}}">会员卡明细</a>
            </td>
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
            status: {0:"禁用",1:"正常"},
            list:[],
            searchCon:{nickname:'',status:'',mobile:'',p:1},
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
            },
            openUser: function (member_id) {
                $.dialog({id: 'popup', lock: true,icon:"question", content: "确认开启吗？",cancel: true, ok: function () {
                    $.getJSON("<?php echo U('open_user');?>",{member_id:member_id},function (data) {
                        $.dialog({id: 'popup', lock: true, content: data.info, time: 2});
                        if(data.status == 1){
                            app.mySearch(false);
                        }
                    })
                }})
            },
            disableUser: function (member_id) {
                $.dialog({id: 'popup', lock: true,icon:"question", content: "确认禁用吗？",cancel: true, ok: function () {
                    $.getJSON("<?php echo U('disable_user');?>",{member_id:member_id},function (data) {
                        $.dialog({id: 'popup', lock: true, content: data.info, time: 2});
                        if(data.status == 1){
                            app.mySearch(false);
                        }
                    })
                }})
            },
            export: function () {
                sendPost("<?php echo U('exportMember');?>",this.searchCon);
            }
        },
        created: function () {
            this.mySearch(true);
        }
    });
    function sendPost(URL, PARAMS) {
        var temp = document.createElement("form");
        temp.target="_blank";
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