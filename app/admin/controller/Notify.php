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
class Notify extends Controller
{
    //回调文件 . 凯
    public function control(){

        $f=fopen('./kai.log','w');
//        fwrite($f,'wudi');die;
        $testxml  = file_get_contents("php://input");
//        fwrite($f,$testxml);die;
        $jsonxml = json_encode(simplexml_load_string($testxml, 'SimpleXMLElement', LIBXML_NOCDATA));
        $result = json_decode($jsonxml, true);//转成数组，
//        fwrite($f,$result);die;
        if($result) {
            $out_trade_no = $result['out_trade_no'];//订单号
            $openid = $result['openid'];
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                if($out_trade_no) {
                    // 通过此订单号判断一下这是不是哪个订单表的是再进行以下操作
                    $ishui = Db::name('member_order')->where('ordersn',$out_trade_no)->find();
                    $goods = Db::name('order')->where('ordersn',$out_trade_no)->find();
                    $service = Db::name('service_order')->where('ordersn',$out_trade_no)->find();
                    if ($ishui) {
                        try {

                        //查询是否选择了车辆
                        if (!empty($ishui['car_id'])){
                            $member=Db::name('user_member')->where('id',$ishui['car_id'])->find();
                            //判断用户车辆是否过审
                            if ($member['status']==1){
                                //判断续费与否
                                if ($member['level_status']==0){
                                    $data['end_level']=strtotime(date('Y-m-d H:i:s',strtotime('+3 month')));
                                    $data['level_status']=1;
                                }else{
                                    $data['end_level']=$member['end_level']+2592000*3;
                                }
                                $upa_sta['band_status']=1;
                                //修改车辆状态
                                $upmember=Db::name('user_member')->where('id',$ishui['car_id'])->update($data);
                                //会员系数 按照 car_id 搜一下 会员系数
                                $userco = Db::name('user_member')->where('id', $ishui['car_id'])->find();
                                $coefficient = $userco['coefficient'] + 299;
                                $co_data['coefficient'] = $coefficient;
                                $coupa = Db::name('user_member')->where('id', $ishui['car_id'])->update($co_data);
                            }

                        }
                        //将订单表状态修改
                        $upa_sta = ['status' => 1];
                        $dd = Db::name('member_order')->where('ordersn', $out_trade_no)->update($upa_sta);
                        //  总会员系数
                        //获取之前的总会员系数
                        $again = Db::name('webconfig')->where('web', 'web') -> find();
                        $total = $again['totalcoefficient'] + 299;
                        $again_data['totalcoefficient'] = $total;
                        //进行修改
                        $totalupa = Db::name('webconfig')->where('web', 'web')->update($again_data);

                        //添加会员系数明细
                        $log=array();
                        $log['openid']=$openid;
                        $log['nickname']=$userco['nickname'];
                        $log['info']="购买会员 订单号:".$out_trade_no;
                        $log['coefficient']="+299";
                        $log['create_at']=time();
                        Db::name('coefficient')->insert($log);

                        } catch(\Exception $e) {
                            $f = fopen('memberpeko.log', 'a');
                            fwrite($f, $e->getMessage());
                            fclose($f);
                        }
                    }
                    if ($goods){

                        if (empty($goods['storeid'])){
                            $upa_sta = ['status' => 1];
                        }else{
                            $upa_sta = ['status' => 2];
                        }
                        $dd = Db::name('order')->where('ordersn', $out_trade_no)->update($upa_sta);
                    }
                    if ($service){
                        try {


                        //修改订单状态
                        $upa_sta = ['status' => 4];
                        $dd = Db::name('service_order')->where('ordersn', $out_trade_no)->update($upa_sta);
                        /*//修改服务过的车辆
                        $upcar['end_level']=0;
                        $upcar['level_status']=0;
                        $car=Db::name('user_member')->where('id', $service['car_id'])->update($upcar);*/
                        //查询是否有上级
                        $user = Db::name('user')->where('openid', $openid)->find();
                        if ($user['agentid']>0){
                            $integral = $dd['totalprice']*10;
                            $agent = Db::name('user')->where('id', $user['agentid'])->find();
                            if (!empty($agent)){
                                $credit2 = $agent['creait2']+$integral;
                                $f['creait2']=$credit2;
                                Db::name('user')->where('id', $user['agentid'])->update($f);

                                $log=array();
                                $log['openid']=$agent['openid'];
                                $log['info']="下级用户".$user['nickname']."购买服务";
                                $log['integral']=$integral;
                                $log['status']=1;
                                $log['create_at']=time();
                                Db::name('integral_log')->insert($log);
                            }

                        }
                        } catch(\Exception $e) {
                            $f = fopen('peko.log', 'a');
                            fwrite($f, $e->getMessage());
                            fclose($f);
                            }

                    }

                    exit('SUCCESS');
                }

            }

        }






    }



}








