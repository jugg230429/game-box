<?php

namespace app\game\logic;

class BaseLogic
{


    //返回数据
    protected $data = [];

    //错误信息
    protected $errorMsg = '';


    /**
     * @return array
     */
    public function getData()
    {
        return $this -> data;
    }

    /**
     * @return string
     */
    public function getErrorMsg()
    {
        return $this -> errorMsg;
    }


}
