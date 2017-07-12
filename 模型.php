<?php

/**
20170518
tangyao
 */
class Test_python_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    //买入股票返回卖一价格
    public function get_sell_price1($type,$code)
    {

        if($type=='sh'){

            $sql = "select SellPrice1 from sh where SecurityID = '$code' ";

            $query = $this->db->query($sql);
            $data_list = $query->result_array();
            return $data_list[0]['SellPrice1'];


        }else if($type=='sz'){

            $sql = "select SellPrice1 from sz where SecurityID = '$code' ";

            $query = $this->db->query($sql);
            $data_list = $query->result_array();
            return $data_list[0]['SellPrice1'];

        }else{

            $error = "something error happened";
            return $error;
        }


    }


    //卖出股票返回买一价格
    public function get_buy_price1($type,$code)
    {

        if($type=='sh'){

            $sql = "select BuyPrice1 from sh where SecurityID = '$code' ";

            $query = $this->db->query($sql);
            $data_list = $query->result_array();
            return $data_list[0]['BuyPrice1'];


        }else if($type=='sz'){

            $sql = "select BuyPrice1 from sz where SecurityID = '$code' ";

            $query = $this->db->query($sql);
            $data_list = $query->result_array();
            return $data_list[0]['BuyPrice1'];

        }else{

            $error = "database search something error happened!!!!";
            return $error;
        }


    }


    //获取股票id对应的股票名称值
    public function get_stock_name($type,$code)
    {

        if($type=='sh'){

            $sql = "select Symbol from sh where SecurityID = '$code' ";

            $query = $this->db->query($sql);
            $data_list = $query->result_array();
            return $data_list[0]['Symbol'];


        }else if($type=='sz'){

            $sql = "select Symbol from sz where SecurityID = '$code' ";

            $query = $this->db->query($sql);
            $data_list = $query->result_array();
            return $data_list[0]['Symbol'];

        }else{

            $error = "database search something error happened!!!!";
            return $error;
        }


    }



    //返回上海的所有股票编码，以买一价是否为0判断是否活跃

    public function get_sh_stock(){

        $sql = "select SecurityID from sh where SecurityID REGEXP '^60[01]' and BuyPrice1 != 0 ";
        $query = $this->db->query($sql);
        $data_list = $query->result_array();
        $data = array();

        foreach($data_list as $row)
        {
            array_push($data,$row['SecurityID']);
        }

        return $data;


    }


    //返回深圳的所有股票编码，以买一价是否为0判断是否活跃
    public function get_sz_stock(){

        $sql = "select SecurityID from sz where SecurityID REGEXP '^[000|002|300]' and BuyPrice1 != 0 ";
        $query = $this->db->query($sql);
        $data_list = $query->result_array();
        $data = array();

        foreach($data_list as $row)
        {
            array_push($data,$row['SecurityID']);
        }

        return $data;

    }

    //返回某人的所有持仓股票代码
    public function get_hold_stocks($user_id){

        $sql = "select * from hold_stock where user_id='$user_id' ";
        $query = $this->db->query($sql);
        $data_list = $query->result_array();
        $data = array();
        foreach($data_list as $row)
        {
            array_push($data,$row['SecurityID']);
        }
        return $data;
    }



}
