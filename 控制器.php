<?php
class Test_python extends MY_Controller {



    public function __construct(){
        parent::__construct();
        $this->load->model("user_model","user");
        $this->load->model('Stock_model','stock_model');
        $this->load->model("wallet_operate_model","wallet");
        $this->load->model("Optional_model","optional");
        $this->load->model("Test_python_model","testpy");
        $this->load->helper('download');

    }





    public function index()
    {

        $user_id = '888000058$';

        $test = $this->testpy->get_hold_stocks($user_id);

        var_dump($test);

    }

    public function name()
    {

        $name = $this->testpy->get_sz_stock();
        var_dump($name);


    }

    //判断是上海还是深圳的股票
    private function choose_table($SecurityID){
        $pattern_sh = "/^(60|01|02|12|20)\\d{4}$/i";
        $pattern_sh_a = "/^130\\d{3}$/i";
        $pattern_sh_136 = "/^136\\d*$/i";#临时加的，支持136
        $pattern_sz = "/^(00|30|10|11)\\d{4}$/i";
        $pattern_sz_a = "/^131\\d{3}$/i";
        if(preg_match($pattern_sh,$SecurityID,$matches)||preg_match($pattern_sh_a,$SecurityID,$matches)){
            return 'sh';
        }elseif(preg_match($pattern_sz,$SecurityID,$matches)||preg_match($pattern_sz_a,$SecurityID,$matches)){
            return 'sz';
        }elseif(preg_match($pattern_sh_136,$SecurityID,$matches)){
            return 'sh';
        }else{
            $response = array('status' => '1',
                'msg' => 'no this stock');
            $this->output
                ->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return;
        }

    }


    //买入股票的函数-控制参数
//    $user_id  用户id
//    $stock_id  股票id
//    $stock_name  股票名称
//    $sell_price1  卖一价格
//    $buy_volume  买入量
//    $expire_day  委托期限

    public function fun_buy_stock($user_id,$stock_id){


        //判断当前代码所属的表　sz|sh
        $type = $this->choose_table($stock_id);

        //需要输入的参数值开始
        $user_id = $user_id;
        $SecurityID = $stock_id;
        $Symbol = $this->testpy->get_stock_name($type,$stock_id);
        $BuyPrice = $this->testpy->get_sell_price1($type,$stock_id);

        //买入量之后会乘以一百
        $BuyVolume = 3;

        //时间期限为空即默认为当天
        $expire_day = "";
        //需要输入的参数值结束


        $is_date = strtotime($expire_day)?strtotime($expire_day):false;
        if($is_date === false)
        {
            $expire_day = date('Y-m-d');
        }
        $unit = 100;
        $BuyVolume = $BuyVolume*$unit;
        //买入熟练必须100倍数　　数量不能为０　　价格不能为０防止买入涨停跌停或者停牌股　　


        //不能为涨停股
        $SellPrice1 = floatval($this->stock_model->get_price1($type,'sell',$SecurityID));
        //停牌的股票是不可以交易的
        if($SellPrice1 == 0){
            return;
        }

        //计算应该冻结资金　　手续费　　和债券利息

        //买入股票需要的资金
        $fund = floatval($BuyPrice)*floatval($BuyVolume);

        //计算买入的这只股票需要的手续费
        $fee = $fund * 0.0003;
        //计算这笔买入操作纯买入金额与手续费总计总金额
        $hap_fund = $fund+$fee;

        //税费
        $tax = 0;
        //其它杂费
        $other_fee = 0;

        //债券相关的字段全部为零
        $interest_per = 0;
        $is_bond = 0;
        $interest = 0;
        //获取当前交易量
        $tradevolume = floatval($this->stock_model->get_tradevolume($type,$SecurityID));
        //开启事务
        $this->stock_model->trans_start();
        $this->db->insert('create_pre_id',array('nonsense'=>1));
        $pre_id = date('YmdHis').$this->db->insert_id();
        //获得用户可用现金金额
        $data_user = $this->stock_model->get_user_data($user_id);

        //可用现金金额不够时交易失败
        if(floatval($data_user['cash_use']) < $hap_fund){
            return;
        }
        //预交易单和交易记录
        $data = array('pre_id' => $pre_id, 'user_id' => $user_id, 'SecurityID' => $SecurityID,
            'Symbol' => $Symbol, 'Volume' => $BuyVolume, 'price_order' => $BuyPrice,'price_full' =>$BuyPrice+$interest_per,'price_deal' => 0,
            'trade_type' => '0', 'db' => $type,'is_bond' => $is_bond,'fund_deal' => $fund,'fee' => $fee,
            'tax' => $tax,'interest' => $interest,'other_fee' => $other_fee,'hap_fund' => $hap_fund,'remain_fund' =>$data_user['cash_all'],'tip' => "冻结".number_format($hap_fund,2),'expire_day'=>$expire_day,'tradevolume'=>$tradevolume);
        //进入预买单表
        $this->stock_model->pre_trade($data);

        //更新可用现金和冻结资金  总资金未改变
        $cash_use = $data_user['cash_use'];
        $case_use_after = $cash_use - $hap_fund;
        $cash_freeze = $data_user['cash_freeze'];
        $cash_freeze_after = $cash_freeze + $hap_fund;
        $data_up = array('cash_use' => $case_use_after, 'cash_freeze' => $cash_freeze_after);

        $this->stock_model->update_stock_user($data_up);

        //日志
        unset($data['expire_day']);		//实现用户自定义订单作废时间功能，此字段只存在于预交易订单表(pre_trade)，交易日志表（trade_log)中无此字段因此要删除。
        unset($data['tradevolume']);		//用下订单时刻的证券交易量进行更加正确交易成功与交易价格判断,只存在预交易订单表（pre_trade),交易日志表（trade_log)中无此字段因此要删除。
        $this->stock_model->add_log($data);
        $state = $this->stock_model->trans_end();


        if($state){

            $msg =  "买入成功，success....";

            $this->output
                ->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($msg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        }else{
            $msg =  "失败...sorry.something error is happened..";

            $this->output
                ->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($msg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        }
    }





    //卖出股票的函数
    //$user_id用户的id账号
    //要卖出 的股票编码

    public function fun_sell_stock($user_id,$stock_id){


        $type = $this->choose_table($stock_id);        //接收数据
        $user_id = $user_id;
        $SecurityID = $stock_id;


        $Symbol = $this->testpy->get_stock_name($type,$stock_id);
        $SellPrice = $this->testpy->get_buy_price1($type,$stock_id);
        $SellVolume = '';

        $expire_day = "";
        $is_date = strtotime($expire_day)?strtotime($expire_day):false;
        if($is_date === false)
        {
            $expire_day = date('Y-m-d');
        }
        //卖出数据结束

        $is_date = strtotime($expire_day)?strtotime($expire_day):false;

        if($is_date === false)
        {
            $expire_day = date('Y-m-d');
        }

        $BuyPrice1 = floatval($this->stock_model->get_price1($type,'buy',$stock_id));

        //停牌数据不可交易，中止程序执行
        if($BuyPrice1 == '0'){

            echo "停牌数据不可交易";
            return;

        }
        //判断是否持有这只股票
        $data_se = $this->stock_model->get_origin_data(array('user_id' => $user_id,'SecurityID' => $stock_id));
        if($data_se == null){

            echo "您暂未持有这只股票";
            return;

        }
        //判断是否超出了最大交易量，超出可交易量中止程序
        $max_volume = intval($data_se['Volume_All'])-intval($data_se['Ban_Volume'])-intval($data_se['Order_Volume']);
        if($max_volume<99){

            echo "超出最大可卖量";
            return;
        }else{
            $SellVolume = $max_volume;
        }

        //卖出时不涉及冻结资金　　此处只记录债券利息
        $fund = floatval($SellPrice)*floatval($SellVolume);
        $interest_per = 0;
        $is_bond = 0;
        $interest = $interest_per*$SellVolume;
        $fee = $fund * 0.0013;
        $tax = $fund * 0.001;

        //卖出时没有冻结资金　发生金额为0
        $hap_fund = 0;
        $other_fee = 0;

        $tradevolume = floatval($this->stock_model->get_tradevolume($type,$stock_id));

        $this->stock_model->trans_start();
        $this->db->insert('create_pre_id',array('nonsense'=>1));
        $pre_id = date('YmdHis').$this->db->insert_id();
        $data_user = $this->stock_model->get_user_data($user_id);
        $data = array('pre_id' => $pre_id, 'user_id' => $user_id, 'SecurityID' => $SecurityID,
            'Symbol' => $Symbol, 'Volume' => $SellVolume, 'price_order' => $SellPrice,'price_full' =>$SellPrice+$interest_per,'price_deal' => 0,
            'trade_type' => '2', 'db' => $type,'is_bond' => $is_bond,'fund_deal' => $fund,'fee' => $fee,
            'tax' => $tax,'interest' => $interest,'other_fee' => $other_fee,'hap_fund' => $hap_fund,'remain_fund' =>$data_user['cash_all'],'expire_day'=>$expire_day,'tradevolume'=>$tradevolume);
        //进入预买单
        $this->stock_model->pre_trade($data);
        $this->stock_model->update_order_volume(array('user_id' => $user_id, 'SecurityID' => $stock_id), intval($SellVolume));

        //日志处理部分
        unset($data['expire_day']);
        unset($data['tradevolume']);

        $this->stock_model->add_log($data);
        $state = $this->stock_model->trans_end();
        if($state){
            $msg =  "卖出成功...success....";

            $this->output
                ->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($msg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        }else{

            $msg = "卖出失败...sorry.something error is happened..";

            $this->output
                ->set_content_type('application/json', 'utf-8')
                ->set_output(json_encode($msg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        }

    }

    //返回深圳市今天所有活跃股票代码，以买一价格作为判断依据
    public function get_sz_stocks(){

        $data = $this->testpy->get_sz_stock();
        $this->output
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }


    //返回上海市今天所有活跃股票代码，以买一价格作为判断依据
    public function get_sh_stocks(){


        $data = $this->testpy->get_sh_stock();
        $this->output
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }


    //获取某人现在所持有的所有股票
    public function get_hold_stock($user_id){

        $data = $this->testpy->get_hold_stocks($user_id);
        $this->output
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }


}