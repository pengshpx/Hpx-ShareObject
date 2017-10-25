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
    #detailModal{width: 900px;margin-left: -480px;}
</style>
</head>
<body>
<div class="wrap" id="app">
    <ul class="nav nav-tabs">
        <li ><a href="<?php echo U('Store/index');?>">店铺列表</a></li>
        <?php if(ACTION_NAME == 'add'): ?><li class="active"><a href="javascript:;">新增店铺</a></li>
            <?php else: ?>
            <li><a href="<?php echo U('Store/add');?>">新增店铺</a></li>
            <li class="active"><a href="javascript:;">编辑店铺</a></li><?php endif; ?>

    </ul>

    <form class="form-horizontal" method="post" action="">
        <input type="hidden" name="id" value="<?php echo ($store["id"]); ?>">
        <div class="control-group">
            <label class="control-label" for="name">店铺名称*：</label>
            <div class="controls">
                <input type="text" name="name" id="name" maxlength="30" value="<?php echo ($store["name"]); ?>" required>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="tel">电话*：</label>
            <div class="controls">
                <input type="text" name="tel" id="tel" maxlength="15" value="<?php echo ($store["tel"]); ?>" required>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="address">详细地址*：</label>
            <div class="controls">
                <input type="text" name="address" id="address" maxlength="50" value="<?php echo ($store["address"]); ?>" required>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="city">所属城市(地图选点)*：</label>
            <div class="controls">
                <input type="text" name="city" id="city" readonly value="<?php echo ($store["city"]); ?>" required>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">经纬度(地图选点)*：</label>
            <div class="controls">
                <input type="text" name="lng" id="lng" readonly value="<?php echo ($store["lng"]); ?>" required>
                <input type="text" name="lat" id="lat" readonly value="<?php echo ($store["lat"]); ?>" required>
            </div>
        </div>
        <div class="control-group">
            <div class="controls">
                <div id="map" style="width: 350px;height: 350px"></div>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">营业时间*：</label>
            <div class="controls">
                <input type="time" class="input-small" oninvalid="validateItTime(this)" oninput="validateItTime(this)" pattern="^(([0-1]\d)|(2[0-4])):[0-5]\d$" name="open_time" value="<?php echo ((isset($store["open_time"]) && ($store["open_time"] !== ""))?($store["open_time"]):'09:00'); ?>" required> -
                <input type="time" class="input-small" oninvalid="validateItTime(this)" oninput="validateItTime(this)" pattern="^(([0-1]\d)|(2[0-4])):[0-5]\d$" name="close_time" value="<?php echo ((isset($store["close_time"]) && ($store["close_time"] !== ""))?($store["close_time"]):'20:00'); ?>" required>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">店铺实景图(640*360)：</label>
            <div class="controls">
                <input type="hidden" name="pic" id="thumb" value="<?php echo ($store["pic"]); ?>">
                <a href="javascript:void(0);" onclick="flashupload('thumb_images', '附件上传','thumb',thumb_images,'1,jpg|jpeg|gif|png|bmp,1,,,1','','','');return false;">
                    <img src='<?php echo ($store[pic]?$store[pic]:"/admin/themes/simplebootx/Public/assets/images/default-thumbnail.png"); ?>' id="thumb_preview" width="135" style="cursor: hand"/>
                </a>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">详情图片列表(560*460)：</label>
            <div class="controls">
                <fieldset>
                    <ul id="photos" class="pic-list unstyled">
                        <?php if(is_array($store["pic_list"])): $i = 0; $__LIST__ = $store["pic_list"];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$item): $mod = ($i % 2 );++$i;?><li id="savedimage<?php echo ($key); ?>">
                                <input type="text" id="image<?php echo ($key); ?>" readonly name="photos_url[]" value="<?php echo sp_get_asset_upload_path($item);?>" title="双击查看" style="width: 310px;" ondblclick="image_priview(this.value);" class="input image-url-input">
                                <a href="javascript:flashupload('replace_albums_images', '图片替换','image<?php echo ($key); ?>',replace_image2,'1,gif|jpg|jpeg|png|bmp,0','','','')">替换</a>
                                <a href="javascript:remove_div('savedimage<?php echo ($key); ?>')">移除</a>
                            </li><?php endforeach; endif; else: echo "" ;endif; ?>

                    </ul>
                </fieldset>
                <a href="javascript:flashupload('albums_images', '图片上传','photos',change_images,'10,gif|jpg|jpeg|png|bmp,0','','','')" id="photos_upload_btn" class="btn btn-small">选择图片</a>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">店铺详情：</label>
            <div class="controls">
                <script type="text/plain" name="details" id="content"><?php echo (htmlspecialchars_decode($store["details"])); ?></script>
            </div>
        </div>

        <div class="control-group">
            <div class="controls">
                <input type="submit" class="btn btn-primary" value="保存"/>
            </div>
        </div>
    </form>

</div>
<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=tz8lw5xTEmwZOYAcfDnEIh3r"></script>
<script src="/public/js/common.js"></script>
<script type="text/javascript" src="/public/js/content_addtop.js"></script>
<script type="text/javascript" src="/public/js/ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="/public/js/ueditor/ueditor.all.min.js"></script>
<script>
    $(function () {
        //编辑器
        editorcontent = new baidu.editor.ui.Editor();
        editorcontent.render('content');
        try {
            editorcontent.sync();
        } catch (err) {

        }
    })


    function G(id) {
        return document.getElementById(id);
    }
    var geoc = new BMap.Geocoder();
    var map = new BMap.Map("map");
    map.enableScrollWheelZoom();   //启用滚轮放大缩小，默认禁用
    map.enableContinuousZoom();    //启用地图惯性拖拽，默认禁用


    map.enableInertialDragging();

    var lng = $("#lng").val();
    var lat = $("#lat").val();
    if(lng!=''&&lat!=''){
        var pp = new BMap.Point(lng,lat);
        map.centerAndZoom(pp,18);
        var marker = new BMap.Marker(pp);
        map.addOverlay(marker);
    }else{
        map.centerAndZoom("成都市天府广场", 13);
    }

    //地图点击事件
    map.addEventListener("click",function(e){
        map.clearOverlays();    //清除地图上所有覆盖物

        map.addOverlay(new BMap.Marker(e.point));
        setLngLatInput(e.point);
    });
    var size = new BMap.Size(10, 20);
    map.addControl(new BMap.CityListControl({
        anchor: BMAP_ANCHOR_TOP_LEFT,
        offset: size,
        // 切换城市之间事件
        // onChangeBefore: function(){
        //    alert('before');
        // },
        // 切换城市之后事件
        // onChangeAfter:function(){
        //   alert('after');
        // }
    }));

    function setPlace(myValue) {
        map.clearOverlays();    //清除地图上所有覆盖物
        function myFun() {
            var aaa = local.getResults().getPoi(0);
            if(aaa==undefined){
                alert("未搜索到该地址，请手动在地图点选");
                return;
            }
            var pp = local.getResults().getPoi(0).point;    //获取第一个智能搜索的结果
            map.centerAndZoom(pp, 18);
            map.addOverlay(new BMap.Marker(pp));    //添加标注
            setLngLatInput(pp);
        }

        var local = new BMap.LocalSearch(map, { //智能搜索
            onSearchComplete: myFun
        });
        local.search(myValue);
    }

    function setLngLatInput(point) {
        G("lng").value = point.lng;
        G("lat").value = point.lat;
        geoc.getLocation(point,function (rs) {
            if(rs!=undefined){

                G("city").value = rs.addressComponents.city;

            }
        })
    }
    function validateItTime(inputelement){
        if(inputelement.validity.patternMismatch){
            inputelement.setCustomValidity('请输入正确的24小时制时间');
        }else{
            inputelement.setCustomValidity(''); //输入内容符合验证条件
        }

        return true;
    }
</script>
</body>
</html>