<?php



function sp_get_url_route(){

	$apps=sp_scan_dir(SPAPP."*",GLOB_ONLYDIR);

	$host=(is_ssl() ? 'https' : 'http')."://".$_SERVER['HTTP_HOST'];

	$routes=array();

	foreach ($apps as $a){


	

		if(is_dir(SPAPP.$a)){

			if(!(strpos($a, ".") === 0)){

				$navfile=SPAPP.$a."/nav.php";

				$app=$a;

				if(file_exists($navfile)){

					$navgeturls=include $navfile;

					foreach ($navgeturls as $url){

						//echo U("$app/$url");

						$nav= file_get_contents($host.U("$app/$url"));

						$nav=json_decode($nav,true);

						if(!empty($nav) && isset($nav['urlrule'])){

							if(!is_array($nav['urlrule']['param'])){

								$params=$nav['urlrule']['param'];

								$params=explode(",", $params);

							}

							sort($params);

							$param="";

							foreach($params as $p){

								$param.=":$p/";

							}

							

							$routes[strtolower($nav['urlrule']['action'])."/".$param]=$nav['urlrule']['action'];

						}

					}

				}

					

			}

		}

	}

	

	return $routes;

}



/**

 * 导出excel表格

 * @param $expTitle 表格名称

 * @param $expCellName 单元格名

 * @param $expTableData 单元格内容

 */

function exportExcel($expTitle, $expCellName, $expTableData){


	$xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称

	$fileName = $xlsTitle.date("YmdHis");//or $xlsTitle 文件名称可根据自己情况设定

	$cellNum = count($expCellName);

	$dataNum = count($expTableData);

	vendor("PHPExcel.PHPExcel");
    
	$objPHPExcel = new PHPExcel();


	$cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');



	/*$objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格

	$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.'  导出时间:'.date('Y-m-d H:i:s'));*/

	for($i=0;$i<$cellNum;$i++){

		$objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $expCellName[$i][1]);

	}

	// Miscellaneous glyphs, UTF-8

//    echo "<pre/>";
	for($i=0;$i<$dataNum;$i++){
//$expTableData[$i][$expCellName[$j][0]]
		for($j=0;$j<$cellNum;$j++){

            $str=$expTableData[$i][$expCellName[$j][0]];

           
            $str=jsonName($str);


            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+2),"'$str");


		}

	}


//    var_dump($objPHPExcel);
//    exit;

	header('pragma:public');

	header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');

	header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

	$objWriter->save('php://output');
//	$objWriter->save(__DIR__.'a.xls');

	exit;

}



/**
+----------------------------------------------------------
 * 过滤用户昵称里面的特殊字符
+----------------------------------------------------------
 * @param string    $str   待输出的用户昵称
+----------------------------------------------------------
 */
function jsonName($str) {
    if($str){
        $tmpStr = json_encode($str);
        $tmpStr2 = preg_replace("#(\\\ud[0-9a-f]{3})#ie","",$tmpStr);
        $return = json_decode($tmpStr2);
        if(!$return){
            return jsonName($return);
        }
    }else{
        $return = '';
    }
    return $return;
}




