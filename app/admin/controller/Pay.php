<?php
// +----------------------------------------------------------------------
// | Tplay [ WE ONLY DO WHAT IS NECESSARY ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://tplay.pengyichen.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 听雨 < 389625819@qq.com >
// +----------------------------------------------------------------------


namespace app\admin\controller;

use think\Controller;
use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
use app\admin\controller\Permissions;
class Pay extends Controller
{
    public function pay(){
//                echo 123;
//微信支付
 //生成订单
        //通过token  获取 openid
//        $token=$_SERVER['HTTP_AUTHORIZATION'];
//        $openid=Db::name('token')->where('token',$token)->find();

  //调用setcode
        $time=time();
//        dump($time);die;
        $rand=$this->setCode();

//    dump($rand);
        $ordersn = 'SH' . date('Y') . date('m') . date('d') . $rand;
//        dump($ordersn);
        $order=['ordersn'=>$ordersn,'openid'=>'oLmfV5JgJ6QuWN7iwkpyHhM2S_Dc','name'=>'商品1','totaiprice'=>'0.01','price'=>'0.01','create_at'=>$time];
        //添加 到 user_goods表
//        dump($order);die;
        $addorder=Db::name('user_order')->insert($order);
//        dump($addorder);die;
        if($addorder){
            //取订单
            $quorder=Db::name('user_order')
            ->where('ordersn',$ordersn)
            ->find();
//            dump($quorder);
        }


        //引用weixinpay


        require "../vendor/WeixinPay/WeixinPay.php";
//die;
        $appid='wxbd4f3c1bbbb8d880';//公众账号ID
        $openid='oLmfV5JgJ6QuWN7iwkpyHhM2S_Dc';
        $mch_id=1601596052;//微信支付商户支付号
        $key='shiyishiyi123123123shiyishiyishi';//api秘钥
        $out_trade_no=$quorder['ordersn'];//商户订单号  自己 订单号
        $body='商品描述凯';//商品描述
        $total_fee=floatval($quorder['price']*100);//订单总    金额，单位为分
        //调用 weixinpay

        $weixinpay=new \WeixinPay($appid, $openid, $mch_id, $key, $out_trade_no, $body, $total_fee);
        $a=$weixinpay->pay();
        dump($a);






            }


    function setCode($lenth = 6)
    {
        $chars = '0123456789';
        $str = '';
        for ($i = 0; $i < $lenth; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }
















}








