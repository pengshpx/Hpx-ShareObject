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
<link  href="/public/js/datepicker2/datepicker.min.css" rel="stylesheet">
<script src="/public/js/vue.js"></script>
<style>
    #month{
        color: #cad2d3;
        cursor: default;
        background-color: #FFFFFF;
        border:1px solid #dce4ec;
    }
    .table-bordered td{
        width: 100px;
    }
</style>
</head>
<body>
<div class="wrap " id="schedule-app">
    <ul class="nav nav-tabs">
        <li class="active"><a href="javascript:;">店铺排班表</a></li>
    </ul>
    <form class="well form-search ">
        店铺：
        <select v-model="search.store_id">
            <?php if(is_array($stores)): $i = 0; $__LIST__ = $stores;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): $mod = ($i % 2 );++$i;?><option <?php if($key == 0): ?>selected<?php endif; ?> value="<?php echo ($item["id"]); ?>"><?php echo ($item["name"]); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>
        </select>
        日历：
        <input type="text" readonly id="month" v-model="search.month" style="width: 120px;" autocomplete="off">

        <input type="button" class="btn btn-primary" @click="searchFun" value="查询">
        <input type="button" class="btn btn-success" @click="export" value="导出">
    </form>
    <div class="container">
        <div class="row" >
            <div class="span12 text-center" v-show="createNew">该月店铺还未排班，<a class="btn btn-small" @click.stop.prevent="create">点击排班</a> </div>
            <div class="span12" v-if="lists.length>0">
                <table class="table table-bordered table-hover">
                    <tr>
                        <th  v-for="title in titles">{{ $index==0?title.month+'月':title.barber_name }}</th>
                    </tr>
                    <tr v-for="item in lists">
                        <td >{{item.work_date}}（{{item.work_date|week}}）</td>
                        <td v-for="title2 in titles" v-if="$index>0" @click="changeWork(item,title2.barber_id)">
                            <i :class="item['barber_'+title2.barber_id]==1?'icon-ok':'icon-remove'" ></i>
                        </td>
                    </tr>
                </table>
                <div class="span12 text-center">
                    <button class="btn btn-primary" @click="save">保存</button>
                </div>
            </div>

        </div>
    </div>


</div>


<script src="/public/js/datepicker2/datepicker.min.js"></script>
<script src="/public/js/datepicker2/datepicker.zh-CN.js"></script>
<script src="/public/js/artDialog/artDialog.js"></script>
<script>
    var loadingArt;
    /*$(document).ajaxStart(function() {
        loadingArt = art.dialog({
            title: '正在排班中,请勿操作...',
            lock:true,
            fixed: true,
            cancel:false
        });
    });
    $(document).ajaxComplete(function() {
        loadingArt.close();
    });*/
    Vue.filter('week', function (date) {
        var str = '周';
        var week = new Date(date).getDay();
        switch (week) {
            case 0 :
                str += "日";
                break;
            case 1 :
                str += "一";
                break;
            case 2 :
                str += "二";
                break;
            case 3 :
                str += "三";
                break;
            case 4 :
                str += "四";
                break;
            case 5 :
                str += "五";
                break;
            case 6 :
                str += "六";
                break;
        }
        return str;
    })
    var app = new Vue({
        el:"#schedule-app",
        data:{
            search:{
                store_id:0,
                month:''
            },
            lists:[],
            titles:[],
            isWordIcon:{
                '':1,
                'icon-remove':0
            },
            createNew:false
        },
        methods:{
            searchFun:function () {
                if(this.search.store_id<1){
                    return;
                }
                $.ajax({
                    url:location.href,
                    data:this.search,
                    type:"POST",
                    success:function (res) {
                        if(res.status==1){
                            app.lists = res.info.lists;
                            app.titles = res.info.titles;
                            if(res.info.lists.length==0){
                                app.createNew=true;
                            }else{
                                app.createNew=false;
                            }
                        }else{

                        }
                    }
                })
            },
            export:function () {
                if(this.search.store_id<1){
                    return;
                }
                sendPost("<?php echo U('exportSchedule');?>",this.search);
            },
            changeWork:function (item,prop) {
                //item["'"+prop+"'"] = item["'"+prop+"'"]==1?0:1;
                item['barber_'+prop] = item['barber_'+prop]==1?0:1;
            },
            save:function () {
                $.ajax({
                    url:"<?php echo U('Store/scheduleEdit');?>",
                    data:{lists:this.lists,titles:this.titles},
                    type:"POST",
                    beforeSend:function () {
                        loadingArt = art.dialog({
                            title: '正在排班中,请勿操作...',
                            lock:true,
                            fixed: true,
                            cancel:false
                        });
                    },
                    success:function (res) {
                        art.dialog({id: 'popup', lock: true, content: res.info,time:3});
                        if(res.status==1){

                        }else{

                        }
                    },
                    complete:function () {
                        loadingArt.close();
                    }
                })
            },
            create:function () {
                $.ajax({
                    url:"<?php echo U('Store/scheduleCreate');?>",
                    data:this.search,
                    type:"POST",
                    success:function (res) {
                        if(res.status==1){
                            app.createNew = false;
                            if(res.info.lists.length>0){
                                app.lists = res.info.lists;
                                app.titles = res.info.titles;
                            }else{
                                app.lists = [];
                                app.titles = [];
                            }
                        }else{

                        }
                    }
                })
            }
        }
    })

    $(function () {
        $("#month").datepicker({
            format:"yyyy-mm",
            language:'zh-CN',
            autohide:true,
            autopick:true
        });
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