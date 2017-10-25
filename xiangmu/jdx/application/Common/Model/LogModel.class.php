<?php



namespace Common\Model;



class LogModel extends CommonModel

{

    public function addLog($remark,$adminId=0)

    {

        if($adminId == 0){

            $adminId = session('ADMIN_ID');

        }

        $data = array(

            'admin'=>$adminId,

            'remark'=>$remark

        );
        return $this->add($data);

    }

}