<?php

namespace app\scrm\logic;

class BaseLogic
{

    protected $errorMsg = '';

    protected $data = [];


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

