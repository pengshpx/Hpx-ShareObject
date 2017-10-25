<?php

namespace Common\Controller;



class MobilebaseController extends HomebaseController

{

    protected function check_login(){

        if(isset($_SESSION["user"])){

            return true;

        }else{

            return false;

        }

    }

}