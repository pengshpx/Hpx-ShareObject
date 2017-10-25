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

<style>

    .add-on {

        padding: 8px 5px !important;

    }



    .bottom5 {

        margin-bottom: 5px;

    }

    .hidden_div{

        position: absolute ;z-index:-1

    }

    .hidden_div input{

        border:0;

        background-color: transparent;

    }

</style>

<script src="/public/js/vue.js"></script>

</head>

<body>

<div class="wrap" id="barber-app">

    <ul class="nav nav-tabs">

        <li><a href="<?php echo U('Barber/index');?>">理发师列表</a></li>

        <?php if(ACTION_NAME == 'add'): ?><li class="active"><a href="javascript:;">添加理发师</a></li>

            <?php else: ?>

            <li><a href="<?php echo U('Barber/add');?>">添加理发师</a></li>

            <li class="active"><a href="javascript:;">编辑理发师</a></li><?php endif; ?>



    </ul>



    <form class="form-horizontal" method="post" @submit.prevent="onSubmit" >

        <input type="hidden" name="id" value="<?php echo ($store["id"]); ?>">

        <div class="control-group">

            <label class="control-label" for="name">理发师姓名*：</label>

            <div class="controls">

                <input type="text" maxlength="10" name="name" v-model="barber.name" id="name"  required>

            </div>

        </div>

        <div class="control-group">

            <label class="control-label" for="tel">电话*：</label>

            <div class="controls">

                <input type="tel" name="tel" autocomplete="off"  maxlength="11" v-model="barber.tel" id="tel"  required>

            </div>

        </div>

        <div class="hidden_div">

            <input type="text" readonly>

        </div>

        <div class="control-group">

            <label class="control-label" for="pwd">密码*：</label>

            <div class="controls">

                <input type="password" name="pwd" id="pwd" autocomplete="off" pattern="^[\w\W]{6,20}$" titile="密码长度6-20位" v-model="barber.pwd" id="pwd" <?php if(ACTION_NAME == 'add'): ?>required<?php endif; ?>>

            </div>

        </div>

        <div class="control-group">

            <label class="control-label" for="year">从业年数*：</label>

            <div class="controls">

                <input type="number" name="year" max="99" min="0" v-model="barber.year" id="year"  required>

            </div>

        </div>

        <div class="control-group">

            <label class="control-label" for="description">个人简介(500字内)*：</label>

            <div class="controls">

                <textarea cols="10" maxlength="500" rows="5" name="description" v-model="barber.description" id="description"></textarea>

            </div>

        </div>



        <div v-for="belong in barber.belongStores">

            <div class="control-group">

                <label class="control-label">所属店铺*：</label>

                <div class="controls">

                    <select required v-model="belong.store_id" @change="checkBelong(belong,$index)">

                        <option value="0">请选择</option>

                        <?php if(is_array($stores)): $i = 0; $__LIST__ = $stores;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): $mod = ($i % 2 );++$i;?><option value="<?php echo ($item["id"]); ?>"><?php echo ($item["name"]); ?></option><?php endforeach; endif; else: echo "" ;endif; ?>

                    </select>

                    <a class="btn btn-small" v-show="barber.belongStores.length>1" @click="removeBelong(belong)" href="javascript:;"><i class="icon-remove"></i></a>

                    <a class="btn btn-small" v-show="$index==barber.belongStores.length-1" @click="addBelong" href="javascript:;"><i class="icon-plus"></i></a>

                </div>

            </div>

            <div class="control-group">

                <label class="control-label">服务类型*：</label>

                <div class="controls">

                    <div class="bottom5" v-for="service in belong.services">

                        <div class="input-prepend">

                            <span class="add-on">服务名称</span>

                            <input class="input" type="text" maxlength="20" v-model="service.name" required>

                        </div>

                        <div class="input-prepend">

                            <span class="add-on">价格</span>

                            <input class="input" type="number" max="9999999.99" min="0.01" step="0.01" v-model="service.price" required>

                        </div>

                        <a class="btn btn-small" v-if="$index>0 || belong.services.length>1" @click="removeService(service,belong)" href="javascript:;"><i class="icon-remove"></i></a>

                        <a class="btn btn-small" v-if="$index==belong.services.length-1" @click="addService(belong)" href="javascript:;"><i class="icon-plus"></i></a>

                    </div>

                </div>

            </div>

        </div>



        <div class="control-group">

            <label class="control-label">作品管理(170*200)：</label>

            <div class="controls">

                <fieldset>

                    <ul id="photos" class="pic-list unstyled">

                        <li v-for="photo in barber.photos">

                            <input readonly title='双击查看' type="text" :value="photo" name="photos" style="width:310px;" ondblclick="image_priview('{{photo}}');" class="input image-url-input">

                            <a href="javascript:;" @click="remove_image(photo)">移除</a>

                        </li>

                    </ul>

                </fieldset>

                <a href="javascript:;" id="photos_upload_btn" class="btn btn-small">选择图片</a>

            </div>

        </div>

        <div class="control-group">

            <div class="controls">

                <input type="submit" class="btn btn-primary" value="保存"/>

            </div>

        </div>

    </form>

</div>



<script src="/public/js/common.js"></script>

<script src="/public/js/content_addtop.js"></script>

<script src="/public/js/artDialog/artDialog.js"></script>

<script>
    var app = new Vue({

        el:"#barber-app",

        data:{

            barber:{

                name:'',

                tel:'',

                pwd:'',

                year:'',

                description:'',

                photos:[],

                belongStores:[

                    {

                        store_id:0,

                        services:[

                            {name:'',price:0}

                        ]

                    }

                ]

            }

        },

        created:function () {

            <?php if(isset($barber)): ?>this.barber = <?php echo ($barber); endif; ?>

        },

        methods:{

            addBelong:function () {

                this.barber.belongStores.push({

                    store_id:0,

                    services:[

                        {name:'',price:0}

                    ]

                })

            },

            checkBelong:function (belong,index) {

                if(this.barber.belongStores.length<=1 || belong.store_id==0){

                    return;

                }

                for(var i=0;i<this.barber.belongStores.length;i++){

                    if(belong.store_id == this.barber.belongStores[i].store_id && i!=index){

                        art.dialog({

                            id: 'popup',

                            icon: 'error',

                            lock: true,

                            content: '请勿选择重复的所属店铺',

                            time: 2,

                            cancel:false,

                            close:function () {

                                app.barber.belongStores[index].store_id = 0;

                            }

                        });

                        break;

                    }

                }

            },

            removeBelong:function (belong) {

                if(belong.store_barber_id!=undefined && belong.store_barber_id>0){

                    art.dialog({

                        content: '你确定要删除这个所属的店铺吗？（注意操作不可逆！）',

                        ok: function () {

                            $.getJSON("<?php echo U('Barber/deleteBelong');?>",{belongId:belong.store_barber_id},function (res) {

                                if(res.status==1){

                                    app.barber.belongStores.$remove(belong);

                                }else{

                                    art.dialog({id: 'popup', lock: true, content: res.info, time: 2});

                                }

                            })

                        },

                        cancelVal: '取消',

                        cancel: true //为true等价于function(){}

                    });



                }else{

                    this.barber.belongStores.$remove(belong);

                }

            },

            addService:function (belong) {

                belong.services.push({name:'',price:0})

            },

            removeService:function (service,belong) {

                if(service.id != undefined && service.id>0){

                    art.dialog({

                        content: '你确定要删除这个服务类型吗？（注意操作不可逆！）',

                        ok: function () {

                            $.getJSON("<?php echo U('Barber/deleteService');?>",{serviceId:service.id},function (res) {

                                if(res.status==1){

                                    belong.services.$remove(service);

                                }else{

                                    art.dialog({id: 'popup', lock: true, content: res.info, time: 2});

                                }

                            })

                        },

                        cancelVal: '取消',

                        cancel: true //为true等价于function(){}

                    });

                }else{

                    belong.services.$remove(service);

                }

            },

            remove_image:function (photo) {

                this.barber.photos.$remove(photo);

            },

            onSubmit:function () {

                $.ajax({

                    url:location.href,

                    data:this.barber,

                    type:"POST",

                    dataType:"json",

                    success:function (res) {

                        if(res.status==1){

                            art.dialog({

                                id: 'popup',

                                icon: 'succeed',

                                lock: true,

                                content: res.info,

                                time: 2,

                                cancel:false,

                                close:function () {

                                    location.href="<?php echo U('Barber/index');?>"

                                }});

                        }else{

                            art.dialog({id: 'popup', lock: true, content: res.info, time: 2});

                        }

                    }

                })

            }

        }

    })

    pwd.oninput=function(){

        pwd.setCustomValidity("");

    };

    pwd.oninvalid=function(){

        pwd.setCustomValidity("密码长度6-20位");

    };



    $(function () {

        $("#photos_upload_btn").click(function () {

            var args =  "10,gif|jpg|jpeg|png|bmp,0";

            flashupload('albums_images', '图片上传', 'photos', change_images2, args, '', '', '')

        })



    })

    function change_images2(uploadid, returnid) {

        var d = uploadid.iframe.contentWindow;

        var in_content = d.$("#att-status").html().substring(1);

        var in_filename = d.$("#att-name").html().substring(1);

        var str = $('#' + returnid).html();

        var contents = in_content.split('|');

        var filenames = in_filename.split('|');

        $('#' + returnid + '_tips').css('display', 'none');

        if (contents == '') return true;

        for(i in contents){

            app.barber.photos.splice(app.barber.photos.length,0,contents[i]);
        }
    


        //$('#' + returnid).html(str);

    }

</script>

</body>

</html>