<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">

<title>CUTMAN男士理发</title>

<meta name="keywords" content="">

<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">

<!--分享使用-->

<meta itemprop="name" content="" />

<meta itemprop="description" name="description" content="" />

<meta itemprop="image" content="img_url" />

<meta name="format-detection" content="telephone=no" />

<link rel="stylesheet" type="text/css" href="/themes/jiandaoxia//Public/css/global.css">

<link rel="stylesheet" type="text/css" href="/themes/jiandaoxia//Public/css/font-awesome.min.css" />

<script src="http://apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js"></script>

    <link rel="stylesheet" href="/themes/jiandaoxia//Public/css/my_css.6.21.css" />
    <link rel="stylesheet" type="text/css" href="/themes/jiandaoxia//Public/css/style.css" />
    <style type="text/css">
        .system-message{ padding: 1.35rem 48px; color: #FFF !important;}
        .system-message p{ color: #FFF !important;}
        .system-message h1{ font-size: 20px; font-weight: normal; line-height: 120px; margin-bottom: 12px; color: #FFF !important;}
        .system-message .jump{ padding-top: 10px}
        .system-message .jump a{ color: #FFF !important;}
        .system-message .success,.system-message .error{ line-height: 1.8em; font-size: 20px }
        .system-message .detail{ font-size: 12px; line-height: 20px; margin-top: 12px; display:none}
    </style>
</head>
<body  class="back-black">
    <div class="q-header q-header-color1">
        <a href="<?php echo U('Index/index');?>" class="q-pull-left"><i class="fa fa-angle-left"></i></a>
        <span class="q-title">提示</span>
    </div>
    <div class="system-message">
        <?php if(isset($message)) {?>
        <h1>:)</h1>
        <p class="success"><?php echo($message); ?></p>
        <?php }else{?>
        <h1>:(</h1>
        <p class="error"><?php echo($error); ?></p>
        <?php }?>
        <p class="detail"></p>
        <p class="jump" id="jump">
            页面自动 <a id="href" href="<?php echo($jumpUrl); ?>">跳转</a> 等待时间： <b id="wait"><?php echo($waitSecond); ?></b>
        </p>
    </div>
    <script type="text/javascript">
        (function(){
            if(history.length<=2){
                $("#jump").hide();
            }
            var wait = document.getElementById('wait'),href = document.getElementById('href').href;
            var interval = setInterval(function(){
                var time = --wait.innerHTML;
                if(time <= 0) {
                    location.href = href;
                    clearInterval(interval);
                };
            }, 1000);
        })();
    </script>
</body>
</html>