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
class Interfaces extends Controller
{
    //接口文件 . 凯
        public function userinfo(){
//            echo 123;
            //通过 code 获得到openid 存到user 表
            $request = request();
            //获取 code
            $code = $request->param('code');
            // appid
            $appid='wxbd4f3c1bbbb8d880';
            //  appsecret
            $secret='7f7aa956632d75d21791cae783cadc5a';
            // api 接口
            $api="https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code";
            function httpGet($url){
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_TIMEOUT, 500);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($curl, CURLOPT_URL, $url);
                $res = curl_exec($curl);
                curl_close($curl);
                return $res;
            }

            $str = httpGet($api);
            echo $str;

        }
            public function user()
            {


                // 传 token  用户
                // 将 数据 存到  user 表
                $request = request();
                $post = $this->request->post();
                $datas = ['nickname' => $post['userinfo']['nickName'],'openid'=>$post['userinfo']['openid'],'city'=>$post['userinfo']['city'],'province'=>$post['userinfo']['province'],'avatar'=>$post['userinfo']['avatarUrl'],'create_at'=>time()];

                //查询 一下 表里的 openid
                $isopenid=Db::name('user')->where('openid',$post['userinfo']['openid'])->find();
                //dump($isopenid);die;
                if($isopenid){
                    //进行修改
//                    $rand_upa=Db::name('user')->where('openid',$post['userinfo']['openid'])->update($datas);

                    $end=Db::name('user')->where('openid',$post['userinfo']['openid'])->find();
                    //邀请人数  先查询自己 id
                    $counts=Db::name('user')->where('openid',$post['userinfo']['openid'])->field('id')->find();
                    //查询一下 agentid 有没有等于这个id的 count 他
                    $invitation=Db::name('user')->where('agentid',$counts['id'])->count();
                    //佣金量
                    $commission=0;
                    //是否是 vip
                    //$vips=Db::name('user')->where('openid',$post['userinfo']['openid'])->find();

                    //生成 token
                    $encryption='lzknb';
                    $encryptions=md5($encryption).$post['userinfo']['openid'];

                    //添进  token 表 按照  openid
                    $add = ['openid'=>$post['userinfo']['openid'],'token'=>$encryptions];
                    $adds=Db::name('token')->insert($add);

                    //车型

                    $other=Db::name('user')->where('openid',$post['userinfo']['openid'])->find();

                    $member=Db::name('user_member')->where(array('openid'=>$post['userinfo']['openid'],'status'=>1))->select();
                    //解析序列化 并且   拼 https

                    if (empty($member)){
                        $other['member']='';
                        $vips=0;
                    }else{
                        foreach ($member as $k=>$v){
                            $member[$k]['car_img']=unserialize($v['car_img']);
                            if ($member[$k]['car_img']){
                                foreach($member[$k]['car_img'] as $kk=>$vv){
                                    $member[$k]['car_img'][$kk]="https://shiyi.cg500.com".str_replace('"','',$vv);

                                }
                            }
                            $member[$k]['license_img']=unserialize($v['license_img']);
                            if ($member[$k]['license_img']){
                                foreach($member[$k]['license_img'] as $kk1=>$vv1){
                                    $member[$k]['license_img'][$kk1]="https://shiyi.cg500.com".str_replace('"','',$vv1);
                                }
                            }
                            $member[$k]['end_level']=date('Y-m-d',$member[$k]['end_level']);
                        }
                        $vips=1;
                        $other['member']=$member;
                    }
                    $data = array('token'=>$encryptions,'invitation'=>$invitation,
                        'commission'=>$commission, 'nickName'=>$other['nickname'],
                        'phone'=>$other['phone'], 'province'=>$other['province'],
                        'city'=>$other['city'], 'county'=>$other['county'],
                        'avatarUrl'=>$other['avatar'], 'member'=>$other['member'],
                        'level'=>$vips);
                    //$data['end_level']=date('Y-m-d',$end['end_level']);
                    to_json(200, '成功', $data);

                }else{
                    //-----------------
                    $res = Db::name('user')->insert($datas);
                }

                    if ($res){


                        // 返回 Token、邀请人数、获得佣金量、是否是VIP 、VIP时效

                    $end=Db::name('user')->where('openid',$post['userinfo']['openid'])->find();

                        //邀请人数  先查询自己 id
                        $counts=Db::name('user')->where('openid',$post['userinfo']['openid'])->field('id')->find();
                        //查询一下 agentid 有没有等于这个id的 count 他
                        $invitation=Db::name('user')->where('agentid',$counts['id'])->count();
                        //佣金量
                        $commission=0;
                        //是否是 vip
                        //$vips=Db::name('user_member')->where('openid',$post['userinfo']['openid'])->find();
                        //生成 token
                        $encryption='lzknb';
                        $encryptions=md5($encryption).$post['userinfo']['openid'];
                        //添进  token 表 按照  openid
                        $add = ['openid'=>$post['userinfo']['openid'],'token'=>$encryptions];
                        $adds=Db::name('token')->insert($add);


                        $other=Db::name('user')->where('openid',$post['userinfo']['openid'])->find();
                        //解析序列化 并且   拼 https
                        $member=Db::name('user_member')->where(array('openid'=>$post['userinfo']['openid'],'level_status'=>1))->select();
                        //解析序列化 并且   拼 https

                        if (empty($member)){
                            $other['member']='';
                            $vips=0;
                        }else{
                            $vips=1;
                            foreach ($member as $k=>$v){
                                $member[$k]['car_img']=unserialize($v['car_img']);
                                if ($member[$k]['car_img']){
                                    foreach($member[$k]['car_img'] as $kk=>$vv){
                                        $member[$k]['car_img'][$kk]="https://shiyi.cg500.com".str_replace('"','',$vv);

                                    }
                                }
                                $member[$k]['license_img']=unserialize($v['license_img']);
                                if ($member[$k]['license_img']){
                                    foreach($member[$k]['license_img'] as $kk1=>$vv1){
                                        $member[$k]['license_img'][$kk1]="https://shiyi.cg500.com".str_replace('"','',$vv1);
                                    }
                                }
                                $member[$k]['end_level']=date('Y-m-d',$member[$k]['end_level']);
                            }
                            $other['member']=$member;
                        }

                        $data = array('token'=>$encryptions,'invitation'=>$invitation,
                            'commission'=>$commission,'level'=>$vips,
                            'nickName'=>$other['nickname'],'phone'=>$other['phone'],
                            'province'=>$other['province'],'city'=>$other['city'],
                            'county'=>$other['county'], 'avatarUrl'=>$other['avatar']);
                        //$data['end_level']=date('Y-m-d',$end['end_level']);
                        to_json(200, '成功', $data);
                    }else{
                        $data=array('token'=>'no');
                        to_json(400, 'bu成功', $data);
                    }
                }



                public function notice(){
                //首页 公告 和 banner
                    $arr=array();
                    $arrs=array();
                    $notice=Db::name('notice')->where('state',2)->select();


                    $banner=Db::name('rotation')->where('status',1)->select();

                    foreach($banner as $b){
                        $b['img_url']='https://shiyi.cg500.com/'. $b['img_url'];
                        $arr[]=$b;
                    }
                    $icon=Db::name('icon')->where('status',1)->where('id','>','0')->select();
                    //遍历
                    foreach($icon as $i){
                        $i['img_url']='https://shiyi.cg500.com/'. $i['img_url'];
                        $arrs[]=$i;
                    }

                    $data = array('notice'=>$notice,'banner'=>$arr,'icon'=>$arrs);
                    to_json(200, '成功', $data);


                }

                public function novice(){
                //新手预约

                    $post = $this->request->post();
                    //添加到novice表
                    $data = ['name' => $post['name'],'mobile'=>$post['mobile']];
                    //查询 库里面 有没有这个名字
                    $isname=Db::name('novice')->where('name',$post['name'])->find();
                    if($isname){
                        $data = array('state'=>'fail');
                        to_json(300, 'fail', $data);
                    }else{
                        $novice=Db::name('novice')->insert($data);
                        if($novice){
                            $data = array('state'=>'success');
                            to_json(200, '成功', $data);
                        }
                    }


                }
                public function  user_integral(){
                    $arr=array();
                    $post = $this->request->post();
                    //会员积分池
                    //统计会员车辆查询一下member_order表 有多少数据
                    $user_count=Db::name('user_member')->where('level_status',1)->count();
                    $user_count+=3125;
                    //昨天新增会员数
                    //获取昨天的时间戳
                    $a=strtotime(date("Y-m-d ",strtotime("-1 day")));
                    $time=strtotime(date('Y-m-d',time()));
                    $where['create_at']=[['>=',$a],['<=',$time]];
                    $where['status']=1;
                    //去库里查询一下昨天添加的会员车辆
                    $yestoday=Db::name('user_member')->where($where)->count();
                    //会员系数
                    $user=Db::name('webconfig')->where('web','web')->find();
                    $user['coefficient']=$user['totalcoefficient'];
                    //会员 权益  查询表 interests
                    $interests=Db::name('interests')->where('status',1)->select();
                    foreach($interests as $in){
                        $in['img_url']='https://shiyi.cg500.com/'. $in['img_url'];
                        $arr[]=$in;
                    }

                    //最新动态  要 最新 10条
                    $neworder=Db::name('service_orders')->order('id desc')->limit(10)->select();
                    foreach($neworder as $k=>$v){
                        $username=Db::name('user')->where('openid',$v['openid'])->find();
                        $neworder[$k]['create_at']=date("Y-m-d H:i:s",$v['create_at']);
                        $neworder[$k]['uname']=$username['nickname'];
                    }
                    $data = array('user_numb'=>$user_count,'yestoday'=>$yestoday,'interests'=>$arr,'user'=>$user['coefficient'],'neworder'=>$neworder);
                    to_json(200, '成功', $data);
                }

                public function shops(){
                    $all=Db::name('store')->select();
                    $data = array('all'=>$all);
                    to_json(200, '成功', $data);
                }




                public function shopinfo(){
                    $arrs=array();


                    //店铺详情
                    //通过传来的 店铺ID 查询店铺

                    $arr=array();
                    $post = $this->request->param();
                    //dump($post);die;
                    $info=Db::name('store')->where('id',$post['id'])->find();
                    //dump($info);die;
                    $info['img_url']="https://shiyi.cg500.com".str_replace('"','',$info['img_url']);
                    //反序列化 服务
                    //$type=unserialize($info['servicetypeid']);
                    $servicetype=Db::name('storeservice')->where('storeid',$post['id'])->select();

                    foreach ($servicetype as $t=>$tt){
                        $service=Db::name('service_type')->where('id',$tt['servicetypeid'])->find();
                        $service['img_url']="https://shiyi.cg500.com".str_replace('"','',$service['img_url']);
                        $arr[]=$service;
                    }
                    //dump($arr);die;
                    $where=array();
                    $where['storeid']=$post['id'];
                    $where['status']=1;
                    $rates=Db::name('store_evaluate')->where($where)->select();
                    if (count($rates)<1){
                        $info['rates']=5;
                    }else{
                        $rate=0;
                        foreach($rates as $k1=>$v1){
                            $rate+=$v1['proportion'];
                        }
                        $info['rates']=$rate/count($rates);
                    }



                    //评价
                    //还是通过 传来 的 商家 ID  获取评价
                    $evaluate=Db::name('store_evaluate')->where('storeid',$post['id'])->find();
                    if($evaluate){
                        $infoo=Db::name('user')->where('openid',$evaluate['openid'])->find();
                        $evaluate['img_url']=unserialize($evaluate['img_url']);
                        foreach ($evaluate['img_url'] as $k=>$v){
                            $evaluate['img_url'][$k]="https://shiyi.cg500.com".str_replace('"','',$v);
                        }
                        $evaluate['create_at']=date('Y-m-d H:i:s', $evaluate['create_at']);
                        $evaluate['nickname']=$infoo['nickname'];
                        $evaluate['avatar']=$infoo['avatar'];
                        $arrs[]=$evaluate;
                        $data = array('info'=>$info,'ser_type'=>$arr,'evaluate'=>$arrs);
                        to_json(200, '成功', $data);
                    }else{
                        $data = array('info'=>$info,'ser_type'=>$arr,'evaluate'=>[]);
                        to_json(200, '成功',$data);
                    }

                    //服务  类别

                }


                public function server(){
                    //服务分类  1    传服务ID
                    if (!empty($_SERVER['HTTP_AUTHORIZATION'])){
                        $token=$_SERVER['HTTP_AUTHORIZATION'];
                        $openid=Db::name('token')->where('token',$token)->find();
                        $user=Db::name('user')->where('openid',$openid['openid'])->find();
                    }

                    $arr=array();
                    $post = $this->request->post();
                    //查询 server 表
                    //通过 传来的postid 查询一下 大服务
                    $bigser=Db::name('service_type')->where('id',$post['id'])->find();
                    $bigser['img_url']='https://shiyi.cg500.com/'. $bigser['img_url'];
                    $server=Db::name('service')->where('typeid',$post['id'])->select();
                    //获取用户车辆信息
                    $car=Db::name('user_member')->where(array('openid'=>$openid['openid'],'status'=>1))->select();
                    if (empty($car)){
                        $car=array();
                    }
                    foreach ($server as $k=>$v){
                        $server[$k]['img_url']='https://shiyi.cg500.com/'.   $v['img_url'];
                        if(!empty($v['price'])){

                            if (empty($post['car_id'])){
                                if ($car[0]['level_status']==1){
                                    //获取会员折扣
                                    $dis['start_num']=['>=',$user['servicenum']];
                                    $discount=Db::name('discount')->where($dis)->find();
                                    $discount['discount']=$discount['discount']/10;
                                    //获取折扣后价格
                                    $price=round(($v['price']*$discount['discount']),2);
                                    $server[$k]['price']=$price;
                                }else{
                                    $server[$k]['price']=$v['price'];
                                }
                            }else{
                                $car_price=Db::name('user_member')->where(array('openid'=>$openid['openid'],'status'=>1,'id'=>$post['car_id']))->find();
                                if ($car_price['level_status']==1){
                                    //获取会员折扣
                                    $dis['start_num']=['>=',$user['servicenum']];
                                    $discount=Db::name('discount')->where($dis)->find();
                                    $discount['discount']=$discount['discount']/10;
                                    //获取折扣后价格
                                    $price=round(($v['price']*$discount['discount']),2);
                                    $server[$k]['price']=$price;
                                }else{
                                    $server[$k]['price']=$v['price'];
                                }
                            }
                        }
                    }
                    $bigser['car']=$car;
                    $bigser['service']=$server;
                    $data = array('server'=> $bigser);
                    //dump($data);die;
                    to_json(200, '成功', $data);
                }
    //服务价格
    public function service_price()
    {

    }

    //报价 门店提交审核
    public function mallsave(){
        $post = $this->request->post();
        $id = $post['id'];



        //$where['ordersn']=$post['ordersn'];
        //查找订单信息
        $order=Db::name('service_order')->where('id',$id)->find();


        if (empty($order)){
            to_json(400,"未找到该订单信息!");
        }
        if ($order['status']==1){
            //获取下单用户信息
            $user=Db::name('user')->where('openid',$order['openid'])->find();
            $car=Db::name('user_member')->where('id',$order['car_id'])->find();
            if ($car['level_status']==1){
                //获取会员折扣
                $dis['start_num']=['>=',$user['servicenum']];
                $discount=Db::name('discount')->where($dis)->find();
                $discount['discount']=$discount['discount']/10;
                //获取折扣后价格
                $price=round(($post['price']*$discount['discount']),2);
            }else{
                $price=$post['price'];
            }
            if (!empty($post['storeimgs'])){
                $up['storeimgs'] = serialize($post['storeimgs']);
            }
            //修改订单数据
            $up['totalprice']=$price;
            $up['price']=$post['price'];
            $up['status']=2;
            $uporder=Db::name('service_order')->where('ordersn',$order['ordersn'])->update($up);
            if ($uporder){
                to_json(200,"报价成功");
            }else{
                to_json(400,"报价失败");
            }
        }elseif($order['status']==4){
            if (!empty($post['endimgs'])){
                $data['endimgs'] = serialize($post['endimgs']);
            }
            $data['status']=5;
            $res = Db::name('service_order')->where('id',$id)->update($data);
        }
        to_json(200, 'SUCCESS', $res);
    }

                //商品
                //最上面 那   个  阿卡丽
                public function platform(){
                    //  soft -1   status  1
                    $arr=array();
                    $platforms=array();
                    $alls=array();
                    $post = $this->request->post();
                    //查询  goods  表
                    $first=Db::name('goods')->where(array('sort'=>-1,'integral_status'=>1))->select();
                    foreach($first as $in){
                        $in['goods_img']='https://shiyi.cg500.com/'. $in['goods_img'];
                        $arr[]=$in;
                    }
                    //平台 展示    sort -1
                    $platform=Db::name('goods')->where(array('sort'=>-1,'integral_status'=>1))->select();
                    foreach($platform as $ins){
                        $ins['goods_img']='https://shiyi.cg500.com/'. $ins['goods_img'];
                        $platforms[]=$ins;
                    }
                    //全部 服务
                    $where['integral_status']=1;
                    $where['sort']=['>',-1];
                    $all=Db::name('goods')->page($post['page'],4)->where($where)->select();

                    foreach($all as $inss){
                        $inss['goods_img']='https://shiyi.cg500.com/'. $inss['goods_img'];
                        $alls[]=$inss;
                    }


                    $totals=Db::name('goods')->count();
                    if($totals<=4){
                        $total=1;
                    }else{
                        $total=ceil($totals/4);
                    }
                    $data = array('first'=>$arr,'platform'=>$platforms,'all'=>$alls,'total'=>$total);
                    to_json(200, '成功', $data);


                }
                //全部商品
    
                //商品详情
               public function goodsinfo(){
                    $arr=array();
                    $post = $this->request->param();
                    if (empty($_SERVER['HTTP_AUTHORIZATION'])){
                        $price=0;
                    }else{
                        $token=$_SERVER['HTTP_AUTHORIZATION'];
                        $openid=Db::name('token')->where('token',$token)->find();
                        $member=Db::name('user_member')->where(array('openid'=>$openid['openid'],'level_status'=>1))->select();
                        if (empty($member)){
                            $price=0;
                        }else{
                            $price=1;
                        }
                    }

                    //通 过 传来 商品ID
                    $info=Db::name('goods')->where('id',$post['id'])->find();
                    //进行 反序列化
                    $info['goods_imgs']=unserialize( $info['goods_imgs']);
                    foreach ($info['goods_imgs'] as $vv){
                        $info['goods_imgss'][]='https://shiyi.cg500.com/'.   $vv;
                    }
                    if($price==0){
                        unset($info['vip_price']);
                    }
                    $array[]=$info;
                    $data = array('array'=>$array);
                    to_json(200, '成功', $data);
                }

                public function icon(){
                        //传递 筛选 服务
                    $icons=Db::name('service_type')->select();
                    //dump($icons);die;
                    $data = array('icons'=>$icons);
                    to_json(200, '成功', $data);
                }


                //门店筛选
                  public function store(){
                  //筛选   门店  如果 传  mdlb 返回 所有 店铺 返回服务 id 传 该服务的店铺

                      //判断用户是否授权
                      if (!empty($_SERVER['HTTP_AUTHORIZATION'])){
                          $token=$_SERVER['HTTP_AUTHORIZATION'];
                          $openid=Db::name('token')->where('token',$token)->find();
                          $user=Db::name('user')->where('openid',$openid['openid'])->find();
                          if (empty($user['store_id'])){
                              $overhead=array();
                          }else{
                              //获取门店信息
                              $overhead[0]=Db::name('store')->where(array('id'=>$user['store_id'],'status'=>1))->find();
                              //获取评价信息
                              $evalateall=Db::name('store_evaluate')->where(array('storeid'=>$overhead[0]['id'],'status'=>1))->select();
                              //获取订单总条数
                              $ordernum=Db::name('service_order')->where(array('storeid'=>$overhead[0]['id'],'status'=>6))->select();
                              $ordernum=count($ordernum);
                              //维修总次数
                              $overhead[0]['ordernum']=$ordernum;
                              //获取评价总条数
                              $evelatenum=count($evalateall);
                              //判断门店是否有评价  如果有评价计算评价
                              if($evelatenum>0){
                                  $proportion=array_sum(array_column($evalateall, 'proportion'));
                                  $overhead[0]['rate']=round($proportion/$evelatenum);
                              }else{
                                  $overhead[0]['rate']=5;
                              }
                              //将门店主图拼接HTTP
                              $overhead[0]['img_url']="https://shiyi.cg500.com".str_replace('"','',$overhead[0]['img_url']);
                              //门店置顶标识
                              $overhead[0]['type']=1;
                              //获取门店 服务分类
                              $servicetypeid=Db::name('storeservice')->where('storeid',$overhead[0]['id'])->select();
                              if (!empty($servicetypeid)) {
                                  foreach ($servicetypeid as $v2) {
                                      $servicetypeids = Db:: name('service_type')->where('id', $v2['servicetypeid'])->find();
                                      $overhead[0]['servicetypeids'][] = $servicetypeids;
                                  }
                              }
                          }
                      }else{
                          $overhead=array();
                      }



                    $post = $this->request->param();
                      //dump($post['iconid']);die;
                    if($post['iconid']==0 && $post['iconid']!='good' && !is_array($post['iconid'])){
                        //返回  所有 店铺
                        $where['status']=1;
                        $where['city_id']=$post['city_id'];
                        $allstore=Db:: name('store')->where($where)->page($post['page'],10)->select();
                        if (empty($allstore)){
                            to_json(400,'暂无数据');die;
                        }
                    }elseif($post['iconid']>0 && $post['iconid']!='goods' && !is_array($post['iconid'])){
                        //通过   返回的 服务ID
                        $servicety=Db::name('storeservice')->where('servicetypeid',$post['iconid'])->select();
                        $allstore=array();
                        foreach($servicety as $kv=>$vk){
                            $store=Db::name('store')->where(array('id'=>$vk['storeid'],'status'=>1,'city_id'=>$post['city_id']))->find();
                            if (!empty($store)){
                                $allstore[]=$store;
                            }
                        }
                        if (empty($allstore)){
                            to_json(400,'暂无数据');die;
                        }
                    }elseif($post['iconid']=='good'){
                        //好评优先
                        $where['status']=1;
                        $where['city_id']=$post['city_id'];
                        $allstore=Db::name('store')->where($where)->page($post['page'],10)->select();
                        if (empty($allstore)){
                            to_json(400,'暂无数据');die;
                        }
                        foreach ($allstore as $key=>$value){
                            $evalateall=Db::name('store_evaluate')->where(array('storeid'=>$value['id'],'status'=>1))->select();
                            $evelatenum=count($evalateall);
                            //dump($evalateall);die;
                            if($evelatenum>0){
                                $proportion=array_sum(array_column($evalateall, 'proportion'));
                                $allstore[$key]['rate']=round($proportion/$evelatenum);
                            }else{
                                $allstore[$key]['rate']=0;
                            }

                        }
                        foreach($allstore as $list2){
                            $sort[]=$list2["rate"];
                        }
                        array_multisort($sort,SORT_DESC,$allstore);
                    }elseif(is_array($post['iconid'])){
                        $where['status']=1;
                        $where['city_id']=$post['city_id'];
                        $store=Db::name('store')->where($where)->page($post['page'],10)->select();
                        if (empty($store)){
                            to_json(400,'暂无数据');die;
                        }
                        foreach ($store as $k=>$v){
                            $earthRadius = 6367000; //approximate radius of earth in meters
                            $lat1=$post['iconid']['lat'];
                            $lng1=$post['iconid']['long'];
                            $lat2=$v['latitude'];
                            $lng2=$v['longitude'];
                            $lat1 = ($lat1 * pi() ) / 180;
                            $lng1 = ($lng1 * pi() ) / 180;
                            $lat2 = ($lat2 * pi() ) / 180;
                            $lng2 = ($lng2 * pi() ) / 180;

                            $calcLongitude = $lng2 - $lng1;
                            $calcLatitude = $lat2 - $lat1;
                            $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
                            $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
                            $calculatedDistance = $earthRadius * $stepTwo;
                            $store[$k]['jl']=$calculatedDistance;
                            if ($calculatedDistance>=1000){
                                $store[$k]['jl1']=round(($calculatedDistance/1000),1)."千米";
                            }elseif ($calculatedDistance<1000){
                                $store[$k]['jl1']=round($calculatedDistance)."米";
                            }
                        }
                        foreach($store as $list2){
                            $sort[]=$list2["jl"];
                        }
                        array_multisort($sort,SORT_ASC,$store);
                        $allstore=$store;
                    }
                    if (empty($allstore)){
                        to_json(400, '暂无数据');die;
                    }

                    foreach($allstore as $k =>$ins){
                        $evalateall=Db::name('store_evaluate')->where(array('storeid'=>$ins['id'],'status'=>1))->select();
                        $ordernum=Db::name('service_order')->where(array('storeid'=>$ins['id'],'status'=>6))->select();
                        $ordernum=count($ordernum);
                        $allstore[$k]['ordernum']=$ordernum;
                        $evelatenum=count($evalateall);
                        if($evelatenum>0){
                            $proportion=array_sum(array_column($evalateall, 'proportion'));
                            $allstore[$k]['rate']=round($proportion/$evelatenum);
                        }else{
                            $allstore[$k]['rate']=0;
                        }
                        $allstore[$k]['img_url']="https://shiyi.cg500.com".str_replace('"','',$ins['img_url']);
                        $servicetypeid=Db::name('storeservice')->where('storeid',$ins['id'])->select();
                        if (!empty($servicetypeid)) {
                            foreach ($servicetypeid as $v2) {
                                $servicetypeids = Db:: name('service_type')->where('id', $v2['servicetypeid'])->find();
                                $allstore[$k]['servicetypeids'][] = $servicetypeids;
                            }
                        }
                    }
                    $totals=Db::name('store')->count();
                    if($totals<=10){
                        $total=1;
                    }else{
                        $total=ceil($totals/10);
                    }

                    $data = array('allstore'=>$allstore,'total'=>$total,'overhead'=>$overhead);
                    to_json(200, '成功', $data);
                }
                //用户车辆信息修改
                public function certificates(){

                    $post = $this->request->param();
                    $token=$_SERVER['HTTP_AUTHORIZATION'];
                    $openid=Db::name('token')->where('token',$token)->find();
                    //提交信息
                    $data['openid']=$openid['openid'];
                    $data['model']=$post['model'];
                    $data['car_number']=$post['license'];
                    $data['car_img']=serialize($post['car_img']);
                    $data['license_img']=serialize($post['driver']);
                    $data['status']=0;

                    if (empty($post['model']) || empty($post['license']) || empty($post['car_img']) || empty($post['driver'])){
                        to_json(400,"请完善车辆信息后再进行提交");
                    }


                    //dump($post);die;
                    //是添加车辆信息操作
                    if (empty($post['car_id'])){
                        //查找是否有未绑定车辆信息的会员订单
                        $where['status']=1;
                        $where['car_id']=['<',1];
                        $where['openid']=$openid['openid'];
                        //查找会员订单信息
                        $member_order=Db::name('member_order')->where($where)->find();
                        $data['create_at']=time();
                        //没有未绑定会员信息
                        if (empty($member_order)){
                            $data['end_level']=0;
                            $data['level_status']=0;
                            $car=Db::name('user_member')->insert($data);
                        //有未绑定会员信息
                        }else{
                            $data['end_level']=$member_order['end_level'];
                            $data['level_status']=1;
                            $car=Db::name('user_member')->insertGetId($data);
                            $upcar['car_id']=$car;
                            $car=Db::name('member_order')->where('id',$member_order['id'])->update($upcar);
                        }
                        //修改操作
                    }elseif(!empty($post['car_id'])){
                        $car=Db::name('user_member')->where('id',$post['car_id'])->update($data);
                    }

                    if ($car){
                        to_json(200,'ok');
                    }else{
                        to_json(400,'no');
                    }
                }

                //车辆信息
                public function zt(){
                    $post = $this->request->param();
                    $token=$_SERVER['HTTP_AUTHORIZATION'];
                    $openid=Db::name('token')->where('token',$token)->find();
                    $zt=Db::name('user_member')->where('id',$post['car_id'])->find();
                    //车辆信息图片借序列化拼接http
                    if (!empty($zt['car_img']) && !empty($zt['license_img'])){
                        $zt['car_img']=unserialize($zt['car_img']);
                        foreach($zt['car_img'] as $k => $v){
                            $zt['car_img'][$k]=str_replace('"','',$v);
                        }
                        $zt['license_img']=unserialize($zt['license_img']);
                        foreach($zt['license_img'] as $k1 => $v1){
                            $zt['license_img'][$k1]=str_replace('"','',$v1);
                        }
                    }
                    $data = array('zt'=>$zt);
                    to_json(200, 'ok', $data);
                }

                    public function personal (){
                    //修改 个人信息
                        $post = $this->request->post();
                        //通过 token 获取 openid
                        $token=$_SERVER['HTTP_AUTHORIZATION'];
                        $openid=Db::name('token')->where('token',$token)->find();
                        $xiugai=['nickname'=>$post['nickname'],'phone'=>$post['phone'],'address'=>$post['address']];
                        //进行修改
                        $ing=Db::name('user')->where('openid',$openid['openid'])->update($xiugai);
                        if($ing){
                            //修改成功 返回200
                            //返回 展示信息 车型 车牌号 车辆照片 驾驶证照片
                            $info=Db::name('user')->where('openid',$openid['openid'])->find();

                            $data = array('state'=>'success','model'=>$info['model'],'license'=>$info['license']);
                            to_json(200, '修改返回成功', $data);
                        }
                    }
                    //\/20200829\/b9c7f4a68c5ed780b1cce4





                //




    /**
     * 图片上传方法
     * @return [type] [description]
     */
    public function upload()
    {
        if($this->request->file('file')){
            $file = $this->request->file('file');
        }else{
            $res['code']=1;
            $res['msg']='没有上传文件';
            return json($res);
        }
        $module='admin';
        $use='goods_img';
        $web_config = Db::name('webconfig')->where('web','web')->find();
        $info = $file->validate(['size'=>$web_config['file_size']*1024,'ext'=>$web_config['file_type']])->rule('date')->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $module . DS . $use);
        if($info) {
            $res= DS . 'uploads' . DS . $module . DS . $use . DS . $info->getSaveName();
            return json($res);
        } else {
            // 上传失败获取错误信息
            return json(400);
        }
    }


    public function kai(){
        $a=(strtotime("+1 month"));
        dump(date('Y-m-d',$a));die;
        $time =date("Y-m-d",time());
        echo  date("Y-m-d", strtotime("+1 months", strtotime($time)));
    }

    public function finals(){
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $users=Db::name('user')->where('openid',$openid['openid'])->find();
        $users['nickName']=$users['nickname'];
        $users['avatarUrl']=$users['avatar'];
        $users['end_level']=date('Y-m-d',$users['end_level']);

        //邀请人数  先查询自己 id
        $counts=Db::name('user')->where('openid',$openid['openid'])->find();
        //查询一下 agentid 有没有等于这个id的 count 他
        $invitation=Db::name('user')->where('agentid',$counts['id'])->count();
        //获得 积分
        $where=array();
        $where['openid']=$openid['openid'];
        $where['status']=1;

        $integral=Db::name('integral_log')->where($where)->select();
        $coupon_price = array_sum(array_column($integral, 'integral'));
        $users['invitation']=$users['credit2'];
        $users['commission']=$coupon_price;
        $data = array('users'=>$users);
        to_json(200, 'success', $data);
    }
    //会员购买
    public function pay(){
        //微信支付
        //生成订单
        //通过token  获取 openid
        $post = $this->request->post();
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $userinfos=Db::name('user')->where('openid',$openid['openid'])->find();
        //调用setcode

        $rand=$this->setCode();
        $order['ordersn']='SH' . date('Y') . date('m') . date('d') . $rand;
        $order['openid']=$openid['openid'];
        $order['uname']=$userinfos['nickname'];
        if($openid['openid']=='oLmfV5M8Didum_z_zovOk3tvWEjk' || $openid['openid']=='oLmfV5JgJ6QuWN7iwkpyHhM2S_Dc'){
            $order['price']=0.01;
        }else{
            $order['price']=29.9;
        }
        if (!empty($post['service_id'])){
            $order['service_id']=$post['service_id'];
        }
        if (empty($post['car_id'])){
            $order['car_id']=0;
        }else{
            $order['car_id']=$post['car_id'];
        }
        $order['create_at']=time();
        $order['end_level']=strtotime(date('Y-m-d H:i:s',strtotime('+3 month')));
        //添加 到 user_goods表
        $addorder=Db::name('member_order')->insert($order);
        //引用weixinpay
        require "../vendor/WeixinPay/WeixinPay.php";
        $appid='wxbd4f3c1bbbb8d880';//公众账号ID
        $openid=$openid['openid'];
        $mch_id=1601596052;//微信支付商户支付号
        $key='shiyishiyi123123123shiyishiyishi';//api秘钥
        $out_trade_no=$order['ordersn'];//商户订单号  自己 订单号
        $body='购买会员';//商品描述
        $total_fee=floatval($order['price']*100);//订单总    金额，单位为分
        //调用 weixinpay

        if ($addorder){
            $weixinpay=new \WeixinPay($appid, $openid, $mch_id, $key, $out_trade_no, $body, $total_fee);
            $a=$weixinpay->pay();
            if($a["package"]==10){
                to_json(1001,'调起微信支付失败!');
            }else{
                //传值
                to_json(200,'success',$a);
            }
        }else{
            to_json(400,'订单创建失败!');
        }


    }
    //折扣商品购买
    public function goodspay(){
        //邮寄 订单
        //通过传来的东西 生成 订单
        $time=time();
        $post = $this->request->post();
        if(empty($post['store_id'])){
            $token=$_SERVER['HTTP_AUTHORIZATION'];
            $openid=Db::name('token')->where('token',$token)->find();
            $userinfos=Db::name('user')->where('openid',$openid['openid'])->find();
        }

        $rand=$this->setCode();
        $ordersn = 'SH' . date('Y') . date('m') . date('d') . $rand;
        //dump($post);die;

        $data=array();
        $data['ordersn']=$ordersn;
        if (empty($post['store_id'])){
            $data['openid']=$openid['openid'];
            //判断用户是否寄存门店
            if ($post['deposit']==1){
                $data['storeid']=$post['address'];
                $data['deposit']=1;
            }elseif ($post['deposit']==0){
                $data['address']=$post['address'];
                $data['deposit']=0;
            }
        }else{
            $data['openid']="store_id";
            $data['address']=$post['address'];
            $data['deposit']=0;
        }
        $data['uname']=$post['name'];
        $data['totalprice']=$post['price'];
        $data['remarks']=$post['remark'];
        $data['create_at']=$time;
        $data['uphone']=$post['mobile'];

        $price=0;

        foreach($post['goodsid'] as $k=>$v){
            $goods=Db::name('goods')->where('id',$v['id'])->find();
            $price+=$goods['price'];
        }
        $data['price']=$price;
        //添加  进库
        $orders=Db::name('order')->insertGetId($data);
        if (empty($orders)){
            to_json(400,'订单创建失败!');
        }
        //添加商品 进去
        $goodsid=$post['goodsid'];
        foreach($goodsid as $good){
            $goods=array();
            $goods['orderid']=$orders;
            $goods['goodsid']=$good['id'];
            $goods['goodsname']=$good['name'];
            $goods['goodsnum']=$good['num'];
            if (!empty($userinfos)){
                if ($userinfos['status']==1){
                    $goods['unitprice']=$good['price'];
                }elseif ($userinfos['status']==2){
                    $goods['unitprice']=$good['vip_price'];
                }
            }else{
                $goods['unitprice']=$good['store_price'];
            }

            Db::name('order_goods')->insert($goods);
        }
        require "../vendor/WeixinPay/WeixinPay.php";
        $appid='wxbd4f3c1bbbb8d880';//公众账号ID
        $openid=$openid['openid'];
        $mch_id=1601596052;//微信支付商户支付号
        $key='shiyishiyi123123123shiyishiyishi';//api秘钥
        $out_trade_no=$ordersn;//商户订单号  自己 订单号
        $body='商品';//商品描述
        $total_fee=floatval($post['price']*100);//订单总金额，单位为分
        //调用 weixinpay

        //查询一下审核状态
        //$state=Db::name('user')->where('openid',$openid)->find();

            //可以调用
            $weixinpay=new \WeixinPay($appid, $openid, $mch_id, $key, $out_trade_no, $body, $total_fee);
            $a=$weixinpay->pay();
            if($a["package"]==10){
                to_json(1001,'调起微信支付失败!');
            }else{
                //传值
                to_json(200,'success',$a);
            }
    }
    //门店购买商品
    public function storegoodspay(){

        $post = $this->request->post();
        $store=Db::name('store')->where('id',$post['store_id'])->find();

        if ($store['credit1']<$post['price']){
            to_json(400,'余额不足');
        }

        $credit1['credit1']=$store['credit1']-$post['price'];
        $a=Db::name('store')->where('id',$post['store_id'])->update($credit1);
        if (empty($a)){
            to_json(400,'订单创建失败');
        }
        $time=time();
        $rand=$this->setCode();
        $ordersn = 'SH' . date('Y') . date('m') . date('d') . $rand;
        $data=array();
        $data['ordersn']=$ordersn;
        $data['openid']="";
        $data['address']=$post['address'];
        $data['deposit']=0;
        $data['uname']=$post['name'];
        $data['totalprice']=$post['price'];
        $data['remarks']=$post['remark'];
        $data['create_at']=$time;
        $data['status']=1;
        $data['uphone']=$post['mobile'];


        $price=0;
        foreach($post['goodsid'] as $k=>$v){
            $goods=Db::name('goods')->where('id',$v['id'])->find();
            $price+=$goods['price'];
        }
        $data['price']=$price;
        //添加  进库
        $orders=Db::name('order')->insertGetId($data);
        if (empty($orders)){
            to_json(400,'订单创建失败!');
        }
        //添加商品 进去
        $goodsid=$post['goodsid'];
        foreach($goodsid as $good){
            $goods=array();
            $goods['orderid']=$orders;
            $goods['goodsid']=$good['id'];
            $goods['goodsname']=$good['name'];
            $goods['goodsnum']=$good['num'];
            if (!empty($userinfos)){
                if ($userinfos['status']==1){
                    $goods['unitprice']=$good['price'];
                }elseif ($userinfos['status']==2){
                    $goods['unitprice']=$good['vip_price'];
                }
            }else{
                $goods['unitprice']=$good['store_price'];
            }
            Db::name('order_goods')->insert($goods);
        }

        $log=array();
        $log['store_id']=$post['store_id'];
        $log['storename']=$store['name'];
        $log['ordersn']=$ordersn;
        $log['price']='-'.$post['price'];
        $log['create_at']=$time;

        Db::name('storecreditlog')->insert($log);
        to_json(200,'OK');
    }




    public function code(){
        //邀请码
        //先查询码
        $post = $this->request->post();
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $err=Db::name('user')->where('openid',$openid['openid'])->where('rand',$post['code'])->find();
        if($err){

            $data = array('users'=>'本人码');
            to_json(600, 'fail', $data);
        }else{
        //通过 传来的  code   查询 上级 id
        $agentid=Db::name('user')->where('rand',$post['code'])->find();
        //查询自己 通过token
            if($agentid){
                //将 上级 id 填入 agentid
                $agid=['agentid'=>$agentid['id']];
                //进行修改
                $zjupa=Db::name('user')->where('openid',$openid['openid'])->update($agid);
                if($zjupa){
                    //绑定成功
                    $data = array('users'=>'绑定成功');
                    to_json(200, 'success', $data);
                }
            }else{
                $data = array('users'=>'绑定失败');
                to_json(400, 'fail', $data);
            }

        //通过 token 判断 是不是本人

        }

    }


    function setCode($lenth = 6)
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str = '';
        for ($i = 0; $i < $lenth; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }

    //全部商品
    public function index(){
        $post = $this->request->param();
        //判断是否登录
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])){
            $token=$_SERVER['HTTP_AUTHORIZATION'];
            $openid=Db::name('token')->where('token',$token)->find();
            $where['openid']=$openid['openid'];
            $where['status']=1;
            $where['level_status']=1;
            //判断用户是否是会员
            $member=Db::name('user_member')->where($where)->select();
            if (empty($member)){
                $level=0;
            }else{
                $level=1;
            }
        }else{
            $level=0;
        }
        if (!empty($post['goodsname'])){
            $where1['name']=['like', '%' . $post['goodsname'] . '%'];
        }
        $where1['status']=1;
        $items = Db::name('goods')->where($where1)->select();
        foreach ($items as $k=>$item){
            $items[$k]['goods_img'] = 'https://shiyi.cg500.com/'.$item['goods_img'];
            //如果是会员返回会员价
            if ($level==0){
                unset($items[$k]['vip_price']);
            }
        }
        to_json(200, '成功', $items);
    }
    //存放到店门店列表
    public function getstore(){
        $post = $this->request->param();
        if (!empty($post['city_id'])){
            $where['city_id']=$post['city_id'];
        }
        $where['status']=1;
        $items = Db::name('store')->where($where)->select();
        if (empty($items)){
            to_json(400, '暂无数据');
        }
        foreach ($items as $k=>$item){
            $evaluate=Db::name('store_evaluate')->where('status',1)->select();
            if (!empty($evaluate)){
                $proportion=array_sum(array_column($evaluate, 'proportion'));
                $proportionnum=count($evaluate);
                $proportion=$proportion/$proportionnum;
                $rate=round($proportion);
            }else{
                $rate=5;
            }

            $items[$k]['img_url'] = 'https://shiyi.cg500.com/'.$item['img_url'];
            $items[$k]['rate']=$rate;
        }

        to_json(200, '成功', $items);
    }
    //门店申请
    public function savestore(){
        $post = $this->request->post();

        $data['store_imgs'] = serialize($post['store_imgs']);
        $data['license_imgs'] = $post['license_imgs'][0];
        $data['img_url'] = $post['store_imgs'][0];
        $data['name'] = $post['name'];
        $data['phone'] = $post['phone'];
        $data['address'] = $post['address'];
        $data['businesstime'] = $post['businesstime'];
        $data['status'] = -1;
        $data['create_at'] = time();

        $result = Db::name('store')->insert($data);

        if ($result){
            to_json(200, '成功', $result);
        }
    }
//  服务订单
    public function serviceorder()
    {
        //订单表数据处理
        $post = $this->request->post();
        //下单用户数据获取
        $token = $_SERVER['HTTP_AUTHORIZATION'];
        $openid = Db::name('token')->where('token',$token)->find();
        $user = Db::name('user')->where('openid',$openid['openid'])->find();
        $post['uid'] = $user['id'];
        $post['openid'] = $openid['openid'];
        //时间转义
        $time = strtr($post['time'], '.', '/');
        $post['appointmenttime'] = strtotime($time);
        //获取用户车辆信息
        $car=Db::name('user_member')->where('id',$post['car_id'])->find();
        $post['car_img']=$car['car_img'];

        $serviceid = $post['serviceid']['id'];
        //获取服务信息,添加服务
        $service = Db::name('service')->where('id',$serviceid)->find();
        //判断该服务是否需要续费会员
        if (!empty($service['is_member'])){
            $mo['openid']=$openid['openid'];
            $mo['service_id']=['>',0];
            $member_order=Db::name('member_order')->where($mo)->select();
            if (empty($member_order)){
                to_json(400, '该服务需要前去购买会员');
            }
        }


        $post['coefficient'] = $service['coefficient'];
        //判断该服务是否有价格
        if(!empty($service['price'])){
            if ($car['level_status']==1){
                //获取会员折扣
                $dis['start_num']=['>=',$user['servicenum']];
                $discount=Db::name('discount')->where($dis)->find();
                $discount['discount']=$discount['discount']/10;
                //获取折扣后价格
                $price=round(($service['price']*$discount['discount']),2);
                $post['totalprice']=$price;
                $post['price']=$service['price'];
                $post['status']=3;
            }else{
                $post['totalprice']=$service['price'];
                $post['price']=$service['price'];
                $post['status']=3;
            }
        }
        //dump($post['totalprice']);die;
        /*if(!empty($post['price'])){
            $post['totalprice']=$post['price'];
            $post['price']=$service['price'];
        }else{
            $post['totalprice']=0;
            $post['price']=0;
        }*/
        if(!empty($post['userimgs'])){
            $post['userimgs'] = serialize($post['userimgs']);
        }
        $post['create_at'] = time();
        unset($post['timestamp']);
        unset($post['device']);
        unset($post['ver']);
        unset($post['time']);
        unset($post['serviceid']);
        //订单编号
        $rand=$this->setCode();
        $post['ordersn'] = 'SH' . date('Y') . date('m') . date('d') . $rand;;
        //添加订单数据获取订单ID
        $orderid = Db::name('service_order')->insertGetId($post);
        if (empty($orderid)){
            to_json(400, '订单创建失败');
        }
        //获取服务信息,添加服务
        $service = Db::name('service')->where('id',$serviceid)->find();
        if ($service['level_status']==1){
            $upa_sta = ['level' => 1,'end_level'=>0];
            Db::name('user')->where('openid',$openid['openid'])->update($upa_sta);
        }
        $order_goods = array();
        $order_goods['orderid'] = $orderid;
        $order_goods['openid'] = $openid['openid'];
        $order_goods['serviceid'] = $serviceid;
        $order_goods['servicename'] = $service['name'];
        /*$order_goods['unitprice'] = $service['price'];*/
        $order_goods['servicenum'] = 1;
        $order_goods['coefficient'] = $service['coefficient'];
        $order_goods['create_at'] = time();
        //判断是否有消耗会员系数
        if (!empty($service['coefficient'])){
            //用户购买服务消耗车辆会员系数
            $coefficient=$car['coefficient']-$service['coefficient'];
            $b['coefficient']=$coefficient;
            Db::name('user_member')->where('id',$post['car_id'])->update($b);
            //用户购买服务消耗总会员系数
            $web=Db::name('webconfig')->where('web', 'web') ->find();
            $web['totalcoefficient']=$web['totalcoefficient']-$service['coefficient'];
            $a['totalcoefficient']=$web['totalcoefficient'];
            //修改平台共享会员系数
            Db::name('webconfig')->where('web', 'web') ->update($a);
            //添加消耗明细
            $log=array();
            $log['openid']=$openid['openid'];
            $log['nickname']=$user['nickname'];
            $log['coefficient']="-".$service['coefficient'];
            $log['info']="购买服务 订单号:".$post['ordersn'];
            $log['create_at']=time();
            Db::name('coefficient')->insert($log);
        }else{
            $b['coefficient']=0;
        }
        $orders=Db::name('service_orders')->insert($order_goods);
        if (empty($order_goods)){
            to_json(400, '订单商品创建失败');
        }
        if (!empty($service['price'])){
            //引用weixinpay
            require "../vendor/WeixinPay/WeixinPay.php";
            $appid='wxbd4f3c1bbbb8d880';//公众账号ID
            $openid=$openid['openid'];
            $mch_id=1601596052;//微信支付商户支付号
            $key='shiyishiyi123123123shiyishiyishi';//api秘钥
            $out_trade_no=$post['ordersn'];//商户订单号  自己 订单号
            $body='购买服务';//商品描述
            $total_fee=floatval($post['totalprice']*100);//订单总    金额，单位为分
            //调用 weixinpay
            $weixinpay=new \WeixinPay($appid, $openid, $mch_id, $key, $out_trade_no, $body, $total_fee);
            $a=$weixinpay->pay();
            if($a["package"]==10){
                to_json(1001,'调起微信支付失败!');
            }else{
                //传值
                to_json(200,'success',$a);
            }
        }else{
            to_json(200,'预约成功');
        }
        die;
    }


    //服务订单支付
    public function sorderpay()
    {
        $post = $this->request->post();
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $sorder=Db::name('service_order')->where('ordersn',$post['ordersn'])->find();
        //引用weixinpay
        require "../vendor/WeixinPay/WeixinPay.php";
        $appid='wxbd4f3c1bbbb8d880';//公众账号ID
        $openid=$openid['openid'];
        $mch_id=1601596052;//微信支付商户支付号
        $key='shiyishiyi123123123shiyishiyishi';//api秘钥
        $out_trade_no=$post['ordersn'];//商户订单号  自己 订单号
        $body='购买服务';//商品描述
        $total_fee=floatval($sorder['totalprice']*100);//订单总    金额，单位为分
        //调用 weixinpay
        $weixinpay=new \WeixinPay($appid, $openid, $mch_id, $key, $out_trade_no, $body, $total_fee);
        $a=$weixinpay->pay();
        if($a["package"]==10){
            to_json(1001,'调起微信支付失败!');
        }else{
            //传值
            to_json(200,'success',$a);
        }
    }





    //用户信息展示
    public function userinfoup(){
        $post = $this->request->post();
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        //dump($openid['openid']);die;
        $where['openid']=$openid['openid'];
        $where['status']=1;

        $member=Db::name('user_member')->where($where)->select();

        if (empty($member)){
            to_json(400, '暂无信息');
        }else{
            to_json(200, 'OK',$member);
        }

    }
    //用户信息修改
    public function douserinfoup(){
        //获取用户openid
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        //获取提交的信息
        $post = $this->request->post();
        $userinfo=array();
        $userinfo['realname']=$post['realname'];
        $userinfo['phone']=$post['phone'];
        $userinfo['address']=$post['address'];
        //修改
        $user=Db::name('user')->where('openid',$openid['openid'])->update($userinfo);
        if ($user){
            to_json(200, '修改成功');
        }else{
            to_json(400, '修改失败',$token);
        }

    }
    //获取的积分 邀请人数
    public function subordinate()
    {
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $user=Db::name('user')->where('openid',$openid['openid'])->find();
        $subordinate=Db::name('user')->where('agentid',$user['id'])->count();
        /*$data=array();
        $data['subordinate']=$subordinate;
        $data['integralall']=$integralall;*/
        to_json(200, 'OK',array('subordinate'=>$subordinate,'integralall'=>$user['credit2']));
    }

    //邮寄到家接口
    public function getposthome(){
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $post = $this->request->post();
       /* $openid['openid'] = 'oLmfV5M8Didum_z_zovOk3tvWEjk';*/
        $where['openid'] = $openid['openid'];
        /*$post['type']=2;*/
        $where['deposit'] = $post['type'];
        $where['status']=['<',3];
        if ($where['deposit'] == 2){
            $where['deposit'] = array('lt',2);
            $where['status']=3;
        }
        $items = Db::name('order')->where($where)->select();

        $arr = array();
        //dump($items);die;
        //$where['deposit']
        foreach($items as $k=>$item){
            if ($where['deposit'] == 1){
                $store = Db::name('store')->where(array('id'=>$item['storeid']))->find();
                $goods = Db::name('order_goods')->where(array('orderid'=>$item['id']))->select();
                //dump($goods);die;
                foreach($goods as $kk=>$good){
                    $gooditem = Db::name('goods')->where(array('id'=>$good['goodsid']))->find();
                    $goods[$kk]['goodsname'] = $gooditem['name'];
                    $goods[$kk]['img'] = 'https://shiyi.cg500.com/'.$gooditem['goods_img'];
                    //$goods[$kk]['price'] = $gooditem['price'];
                    //$goods[$kk]['vip_price'] = $gooditem['vip_price'];
                    //dump($goods);die;
                    if (!empty($good['integral'])){
                        $goods[$kk]['price']="积分: ".$gooditem['integral'];
                    }else{
                        $user=Db::name('user')->where('openid',$openid['openid'])->find();
                        if ($user['level']==2){
                            $goods[$kk]['price']="¥ ".$gooditem['vip_price'];
                        }else{
                            $goods[$kk]['price']="¥ ".$gooditem['price'];
                        }
                    }
                }
                $arr[$k]['goods'] = $goods;
                $arr[$k]['store'] = $store;
                $arr[$k]['img_url'] = 'https://shiyi.cg500.com'.$store['img_url'];

            }elseif ($where['deposit'] == 0){
                if ($item['status']==0){
                    $items[$k]['status1']="待付款";
                }elseif($item['status']==1){
                    $items[$k]['status1']="待发货";
                }elseif($item['status']==2){
                    $items[$k]['status1']="待收货";
                }elseif($item['status']==3){
                    $items[$k]['status1']="已完成";
                }
                $goods = Db::name('order_goods')->where(array('orderid'=>$item['id']))->select();
                foreach($goods as $kk=>$good){
                    $gooditem = Db::name('goods')->where(array('id'=>$good['goodsid']))->find();
                    $goods[$kk]['goodsname'] = $gooditem['name'];
                    $goods[$kk]['img'] = 'https://shiyi.cg500.com'.$gooditem['goods_img'];
                   /* $goods[$kk]['price'] = $gooditem['price'];
                    $goods[$kk]['vip_price'] = $gooditem['vip_price'];*/
                    if (!empty($good['integral'])){
                        $goods[$kk]['price']="积分:".$gooditem['integral'];
                    }else{
                        $user=Db::name('user')->where('openid',$openid['openid'])->find();
                        if ($user['level']==2){
                            $goods[$kk]['price']="¥".$gooditem['vip_price'];
                        }else{
                            $goods[$kk]['price']="¥".$gooditem['price'];
                        }
                    }
                }
                //dump($goods);die;
                $items[$k]['goods'] = $goods;
            }else{
                //dump($item);die;
                if ($item['status']==3){
                    $goods = Db::name('order_goods')->where(array('orderid'=>$item['id']))->select();
                    foreach($goods as $kk=>$good){
                        $gooditem = Db::name('goods')->where(array('id'=>$good['goodsid']))->find();
                        $goods[$kk]['goodsname'] = $gooditem['name'];
                        $goods[$kk]['img'] = 'https://shiyi.cg500.com'.$gooditem['goods_img'];
                        /*$goods[$kk]['price'] = $gooditem['price'];
                        $goods[$kk]['vip_price'] = $gooditem['vip_price'];*/
                        if (!empty($good['integral'])){
                            $goods[$kk]['price']="积分:".$gooditem['integral'];
                        }else{
                            $user=Db::name('user')->where('openid',$openid['openid'])->find();
                            if ($user['level']==2){
                                $goods[$kk]['price']="¥".$gooditem['vip_price'];
                            }else{
                                $goods[$kk]['price']="¥".$gooditem['price'];
                            }
                        }
                    }
                    $items[$k]['goods'] = $goods;
                    $store = Db::name('store')->where(array('id'=>$item['storeid']))->find();
                    $items[$k]['store'] = $store;
                    $items[$k]['store']['img_url'] = 'https://shiyi.cg500.com/'.$store['img_url'];
                    $items[$k]['type'] = $item['deposit'];
                }else{
                    unset($items[$k]);
                }
            }

        }

        if ($where['deposit'] == 1){
            $items = $arr;
        }
        $data['total'] = count($items);
        $items=array_values($items);
        $data['items'] = $items;
        to_json(200, 'OK', $data);
    }


    //待评价门店接口
    public function pjlist(){
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        //$openid['openid']="oLmfV5M8Didum_z_zovOk3tvWEjk";
        $items = Db::name('service_order')->where(array('openid'=>$openid['openid'],'is_pj'=>0,'status'=>6))->select();

        if (empty($items)){
            to_json(400, '暂无需要评价的订单' );
        }
        foreach ($items as $k=>$item){
            $store = Db::name('store')->where('id',$item['storeid'])->find();
            $items[$k]['storename'] = $store['name'];
            $items[$k]['img'] = 'https://shiyi.cg500.com/'.$store['img_url'];
            $items[$k]['tel'] = $store['phone'];
            $items[$k]['date'] = date('Y-m-d',$item['create_at']);
            //预约的服务名称
            $servicetypeids=Db::name('service_orders')->where(array('orderid'=>$item['id']))->find();
            $items[$k]['servicename']=$servicetypeids['servicename'];


        }

        to_json(200, 'OK', $items);
    }

    //保存用户评价接口
    public function saveeva(){
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
//
//        if (!$openid)

        //$openid['openid'] = 'oLmfV5JgJ6QuWN7iwkpyHhM2S_Dc';

        $post = $this->request->post();

        $data['proportion'] = $post['proportion'];
        $data['remarks'] = $post['remarks'];
        $data['storeid'] = $post['storeid'];
        $data['img_url'] = serialize($post['img_url']);
        $oid = $post['orderid'];

        $data['openid'] = $openid['openid'];
        $data['status'] = 1;
        $data['create_at'] = time();

        /*修改订单评价状态*/
        $res = Db::name('service_order')->where('id',$oid)->update(array('is_pj'=>1));

        /*保存评价内容*/
        $adds = Db::name('store_evaluate')->insert($data);

        to_json(200, 'OK', $adds);
    }

    //评价页面店铺信息
    public function storeinfo(){
        $post = $this->request->post();
        $store = Db::name('store')->where('id',$post['storeid'])->find();
        $order = Db::name('service_order')->where('id',$post['orderid'])->find();
        $store['img'] = 'https://shiyi.cg500.com/'.$store['img_url'];
        //dump($order);die;
        $store['date'] = date('Y-m-d',$order['create_at']);
        to_json(200, 'OK', $store);
    }

    //积分兑换逻辑接口
    public function pointsex(){
        $post = $this->request->post();

        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();


        //$openid['openid'] = 'oLmfV5JgJ6QuWN7iwkpyHhM2S_Dc';

        $user = Db::name('user')->where('openid',$openid['openid'])->find();

        if ($user['credit2'] < $post['points']){
            to_json(202, 'FAIL', '余额不足');
        }

        $data['deposit'] = $post['deposit'];

        if ($data['deposit'] == 0){
            $data['address'] = $post['address'];
        }elseif ($data['deposit'] == 1){
            $data['storeid'] = $post['address'];
        }

        $data['uname'] = $post['name'];
        $data['integral'] = $post['points'];
        $data['uphone'] = $post['mobile'];
        $data['openid'] = $openid['openid'];
        $rand=$this->setCode(6);
        $data['ordersn'] = 'SH' . date('Y') . date('m') . date('d') . $rand;
        $data['status'] = 0;
        $data['create_at'] = time();
        $data['type'] = 1;

        $res = Db::name('order')->insertGetId($data);


        $credit2=$user['credit2']-$post['points'];
        Db::name('user')->where('openid',$openid['openid'])->update(array('credit2'=>$credit2));

        $arr['orderid'] = $res;
        $arr['create_at'] = time();

        //插入积分明细
        $log['openid']=$openid['openid'];
        $log['info']="积分商城购买商品 订单号:".$data['ordersn'];
        $log['status']=0;
        $log['integral']=$post['points'];
        $log['create_at'] = time();
        Db::name('integral_log')->insert($log);

        $goods = $post['goodsid'];

        foreach ($goods as $good){
            $arr['goodsid'] = $good['id'];
            $arr['goodsname'] = $good['name'];
            $arr['goodsnum'] = $good['num'];
            $arr['integral'] = $good['integral'];
            Db::name('order_goods')->insert($arr);
        }

        if ($res){
            to_json(200, 'OK', '');
        }
    }

    //积分变动接口
    public function pointsch(){
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $integrallog=Db::name('integral_log')->where('openid',$openid['openid'])->select();
        if (empty($integrallog)){
            to_json(400, '暂无记录');
        }
        foreach($integrallog as $k=>$v){
            if ($v['status']==1){
                $integrallog[$k]['integral']="+ ".$v['integral'];
            }else{
                $integrallog[$k]['integral']="- ".$v['integral'];
            }
            $integrallog[$k]['create_at']=date("Y-m-d H:i:s",$v['create_at']);
        }
        to_json(200, 'OK', $integrallog);
    }
    //服务协议
    public function serviceword(){
        $word = "<p style='text-align: center'>驿站会员服务平台服务协议</p><br><p>&nbsp;</p>
        <p>欢迎您使用驿站会员服务平台服务！</p><br>
        <p>&nbsp;</p>
        <p>为正确使用驿站会员服务平台（以下简称“平台”），您应当阅读并遵守本协议。请您务必审慎阅读、充分理解本协议各条款内容，特别是免除或限制责任的相应条款，并选择接受或不接受。如您不接受本协议的任何内容，或无法准确理解平台对本协议的解释，请停止注册，停止注册的，您将无法使用平台为注册用户提供的相关服务。</p>
        <p>驿站会员服务平台是聚集全国车主及汽车后市场服务商户的互联网平台，旨在通过会员服务，优化服务体系，实现车主及商户利益最大化。用户（会员）以其项下（指用户（会员）所有的或获得所有权人授权的，下同）机动车，加入驿站会员服务平台，成为会员并通过审核之日起，会员可享受规定周期内的会员服务。在驿站会员服务有效期内，会员项下的机动车发生事件可按照驿站会员服务平台的规则申请会员服务，具体以驿站会员服务平台当期商务政策为准。</p>
        <p>驿站会员服务平台服务商户，亦应遵守本协议。平台回馈会员的所有支出（包含钣金喷漆维修费等）最大金额不大于平台会员缴纳会费总额。如按当期平台服务商务政策，平台回馈会员支出大于平台会员缴纳会费总额时，平台合作商户有义务先为客户提供服务，并采取排队的形式领取平台为客户回馈的维修费用。</p><p>&nbsp;&nbsp;&nbsp;下文所指“用户”为“平台注册会员”及“平台注册服务商户”。</p>
        <p>一、总则</p>
        <p>1.1 驿站会员服务平台（以下又简称“本平台”）的所有权和运营权归沈阳实驿科技有限公司所有。</p>
        <p>&nbsp;</p>
        <p>1.2 用户在注册之前，应当仔细阅读本协议，并同意遵守本协议后方可成为本平台的注册用户。一旦注册成功，则用户与驿站会员服务平台之间自动形成协议关系，用户应当受本协议的约束。用户在使用本平台的服务或产品时，应当同意接受相关协议后方能使用。</p>
        <p>1.3 本协议可由驿站会员服务平台随时更新，用户同意本平台对前述变更不承担通知义务。本平台的通知、公告、声明或其它类似内容是本协议的一部分，公示于本平台之上，用户有义务及时查看。</p>
        <p>二、服务内容</p>
        <p>2.1 驿站会员服务平台的具体内容由本平台根据实际情况提供。</p>
        <p>2.2 本平台仅提供相关的网络服务，除此之外与相关网络服务有关的设备(如个人电脑、手机、及其他与接入互联网或移动网有关的装置)及所需的费用(如为接入互联网而支付的电话费及上网费、为使用移动网而支付的手机费)均应由用户自行负担。</p>
        <p>三、用户帐号</p>
        <p>3.1 经本平台注册系统完成注册程序并通过身份认证的用户即成为注册用户，可以获得本平台规定的注册用户所应享有的一切权限；注册会员仅享有本平台规定的部分会员权限，加入相应的产品或服务后方可享有该产品或服务相关的会员权限。驿站会员服务平台有权对会员的权限设计进行变更。</p>
        <p>&nbsp;</p>
        <p>3.2 用户只能按照注册要求使用本人合法拥有的手机号注册。用户有义务保证密码和帐号的安全，用户利用该密码和帐号所进行的一切活动引起的任何损失或损害，由用户自行承担全部责任，本平台不承担任何责任。如用户发现帐号遭到未授权的使用或发生其他任何安全问题，应立即修改帐号密码并妥善保管，如有必要，请通知本平台。因黑客行为或用户的保管疏忽导致帐号非法使用，本平台不承担任何责任。</p>
        <p>四、使用规则</p>
        <p>4.1 遵守中华人民共和国相关法律法规，包括但不限于《中华人民共和国计算机信息系统安全保护条例》、《计算机软件保护条例》、《最高人民法院关于审理涉及计算机网络著作权纠纷案件适用法律若干问题的解释(法释[2004]1号)》、《全国人大常委会关于维护互联网安全的决定》、《互联网电子公告服务管理规定》、《互联网新闻信息服务管理规定》、《互联网著作权行政保护办法》和《信息网络传播权保护条例》等有关计算机互联网规定和知识产权的法律法规、监管规定。</p>
        <p>4.2 用户对其自行发表、上传或传送的内容负全部责任，所有用户不得在本平台任何页面发布、转载、传送含有下列内容之一的信息，否则本平台有权自行处理并不通知用户：</p>
        <p>(1)违反宪法确定的基本原则的；</p>
        <p>&nbsp;</p>
        <p>(2)危害国家安全，泄漏国家机密，颠覆国家政权，破坏国家统一的；</p>
        <p>(3)损害国家荣誉和利益的；</p>
        <p>(4)煽动民族仇恨、民族歧视，破坏民族团结的；</p>
        <p>(5)破坏国家宗教政策，宣扬邪教和封建迷信的；</p>
        <p>(6)散布谣言，扰乱社会秩序，破坏社会稳定的；</p>
        <p>(7)散布淫秽、色情、赌博、暴力、恐怖或者教唆犯罪的；</p>
        <p>(8)侮辱或者诽谤他人，侵害他人合法权益的；</p>
        <p>(9)煽动非法集会、结社、游行、示威、聚众扰乱社会秩序的；</p>
        <p>(10)以非法民间组织名义活动的；</p>
        <p>(11)含有法律法规、监管规定所禁止的其他内容的。</p>
        <p>4.3 用户承诺对其发表或者上传于本平台的所有信息(即属于《中华人民共和国著作权法》规定的作品，包括但不限于文字、图片、音乐、电影、表演和录音录像制品和电脑程序等)均享有完整的知识产权，或者已经得到相关权利人的合法授权；如用户违反本条规定造成本平台被第三人索赔的，用户应全额赔偿本平台因该事件而支出的一切费用(包括但不限于各种赔偿费、诉讼代理费及为此支出的其它合理费用)；</p>
        <p>4.4 当第三方认为用户发表或者上传于本平台的信息侵犯其权利，并根据《信息网络传播权保护条例》或者相关法律法规的规定向本平台发送权利通知书时，用户同意本平台可以自行判断决定删除涉嫌侵权信息，除非用户提交书面证据材料排除侵权的可能性，本平台将不会自动恢复上述删除的信息；</p>
        <p>4.5用户承诺，在使用网络服务时，应当符合以下基本要求：</p>
        <p>(1)不得为任何非法目的而使用网络服务系统；</p>
        <p>(2)遵守所有与网络服务有关的网络协议、规定和程序；</p>
        <p>(3)不得利用本平台进行任何可能对互联网的正常运转造成不利影响的行为；</p>
        <p>(4)不得利用本平台进行任何不利于本平台的行为。</p>
        <p>4.6 如用户在使用网络服务时违反上述任何规定，本平台有权要求用户改正或直接采取一切必要的措施(包括但不限于删除用户张贴的内容、暂停或终止用户使用网络服务的权利)以减轻用户不当行为而造成的影响。</p>
        <p>五、隐私保护</p>
        <p>5.1 本平台不对外公开或向第三方提供单个用户的注册资料及用户在使用网络服务时存储在本平台的非公开内容，但下列情况除外：</p>
        <p>(1)事先获得用户的明确授权；</p>
        <p>(2)根据有关的法律法规、监管规定要求；</p>
        <p>(3)按照相关政府主管部门或有权机构的要求；</p>
        <p>(4)为维护社会公众的利益。</p>
        <p>5.2 本平台可能会与第三方合作向用户提供相关的网络服务，在此情况下，如该第三方同意承担与本平台同等的保护用户隐私的责任，则本平台有权将用户的注册资料等提供给该第三方。用户选择在本平台注册的，即视为同意本条款，并对本平台进行了该项授权，如用户不同意本条款的，应立即终止注册。</p>
        <p>5.3 在不透露单个用户隐私资料的前提下，本平台有权对整个用户数据库进行分析并对用户数据库进行商业上的利用。</p>
        <p>六、版权声明</p>
        <p>6.1 本平台的文字、图片、音频、视频等版权均归沈阳实驿科技有限公司享有或与作者共同享有，未经本平台许可，不得任意转载。</p>
        <p>6.2 本平台特有的标识、版面设计、编排方式等版权均属沈阳实驿科技有限公司享有，未经本平台许可，不得任意复制或转载。</p>
        <p>6.3 使用本平台的任何内容均应注明“来源于驿站会员平台”及署上作者姓名，按法律规定需要支付稿酬的，应当通知本平台及作者并及时支付稿酬，并独立承担一切法律责任。</p>
        <p>6.4 本平台享有将用户上传于本平台的所有内容用于其它用途的优先权，包括但不限于图像、音频、视频、电子杂志、平面出版等，但在使用前会通知作者，并按同行业的标准支付稿酬。</p>
        <p>6.5 用户上传于本平台的所有内容仅代表作者自己的立场和观点，与本平台无关，由作者本人承担一切法律责任。</p><p>6.6 恶意转载本平台内容的，本平台保留将其诉诸法律的权利。</p>
        <p>七、责任声明</p>
        <p>7.1 用户明确同意其使用本平台网络服务所存在的风险及一切后果将完全由用户本人承担，驿站会员服务平台对此不承担任何责任。</p>
        <p>7.2 本平台无法完全保证网络服务一定能满足用户的要求，也不完全保证网络服务的及时性、安全性、准确性。</p>
        <p>7.3 本平台不保证为方便用户而设置的外部链接的准确性和完整性，同时，对于该等外部链接指向的不由本平台实际控制的任何网页上的内容，本平台不承担任何责任。</p>
        <p>7.4 对于因不可抗力或本平台不能控制的原因造成的网络服务中断或其它缺陷，本平台不承担任何责任，但将尽力减少因此而给用户造成的损失和影响。</p>
        <p>7.5 对于本平台向用户提供的下列产品或者服务的质量缺陷本身及其引发的任何损失，本平台无需承担任何责任：</p>
        <p>(1)本平台向用户免费提供的各项网络服务；</p>
        <p>(2)本平台向用户免费赠送的任何产品或者服务。</p>
        <p>7.6 本平台有权于任何时间暂时或永久修改或终止本服务(或其任何部分)，而无论其通知与否，本平台对用户和任何第三人均无需承担任何责任。</p>
        <p>八、附则</p>
        <p>8.1 本协议的订立、执行和解释及争议的解决均应适用中华人民共和国法律（港澳台地区除外）。</p>
        <p>8.2 如本协议中的任何条款无论因何种原因完全或部分无效或不具有执行力，本协议的其余条款仍应有效并且有约束力。</p>
        <p>8.3 本协议解释权及修订权归沈阳实驿科技有限公司所有。</p> 
        <span></span><span></span>";

        to_json(200, '成功', $word);
    }
    //关于我们
    public function aboutus(){
        $word = "<p style='text-align: center'>驿站会员服务平台</p><br><p>&nbsp;</p>
<p>&nbsp;&nbsp;驿站会员服务平台是沈阳实驿科技有限公司全资打造的一款以开放、公开、共享为宗旨的全国性综合会员服务平台。平台以上汽车享家认证服务门店为基础,以清华大学苏州汽车研究院为技术核心。通过华夏银行资金监管体系，中国人民财产保险有限公司保险服务及腾讯大数据平台为汽车后市场搭建汽车生态车主（会员）服务中心。打破对传统汽车行业的认知,以更完备的服务体系、更稳定的客流来源为导向,重塑汽车产业链，构建并升级汽车生态圈。</p>
<p>&nbsp;</p><p>&nbsp;&nbsp;其整合了各类汽车后市场服务商及供应链资源，并配备安全可靠的软硬件服务和专业的运营团队，为广大车主提供更专业、更优质、更便捷、更贴心，更放心、更高效的服务体系。</p>
<p>&nbsp;</p><p>&nbsp;&nbsp;驿站会员服务平台集会员服务项目模块，会员产品模块，会员模块，信息模块，信用评价功能，实名认证功能，门店展示功能，财务数据统计功能等于一体，将为全国客户展现一个新商业模式下的市场信息对称，供需平衡，信用透明的优质市场服务体系。</p>
<p>&nbsp;</p><p>&nbsp;&nbsp;平台以服务会员为己任，在全新的商业模式引导下，采取多补（多补贴回馈会员）少取（少赚取利润）的经营理念，为会员打造多款服务产品，从而满足会员多方面需求。</p>
<p>&nbsp;</p><p>&nbsp;&nbsp;主营服务有：易补漆服务（缴纳补漆维修费用10%的服务费，即可享受免费补漆服务），易检车服务（部分检车线最高五折优惠），汽车金融服务，汽车美容服务、汽车装饰服务、汽车养护服务、汽车改装服务、专业维修服务、会员俱乐部服务、车险服务、汽车玻璃修复服务、汽车救援服务、二手车销售服务、新车销售服务、汽车租赁服务、车品商城等。</p>
<p>&nbsp;</p><p>&nbsp;&nbsp;企业地址：沈阳市铁西区金谷大厦</p>
<p>&nbsp;</p><p>&nbsp;&nbsp;客服电话：13624066659</p>
<p>&nbsp;</p><span></span><span></span>";

        to_json(200, '成功', $word);
    }
    //服务订单列表
    public function soderlist(){
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $post = $this->request->post();
        //$post['type']=0;
        //$openid['openid']="oLmfV5M8Didum_z_zovOk3tvWEjk";
        $where=array();
        $where['openid']=$openid['openid'];
        $where['status']=$post['type'];
        $order=Db::name('service_order')->where($where)->select();
        if (empty($order)){
            to_json(400, '暂无服务订单!');
        }
        foreach($order as $k=>$service){
            $store=Db::name('store')->where('id',$service['storeid'])->find();
            $store['img_url'] = 'https://shiyi.cg500.com'.$store['img_url'];
            $order[$k]['store']=$store;
            $services=Db::name('service_orders')->where('orderid',$service['id'])->select();
            foreach($services as $k1 =>$v1){
                $service1=Db::name('service')->where('id',$v1['serviceid'])->find();
                $services[$k1]['img_ulr']='https://shiyi.cg500.com'.$service1['img_url'];
            }
            $order[$k]['service']=$services;
            $order[$k]['create_at']=date('Y-m-d H:i:s',$service['create_at']);
        }
        to_json(200, '成功', $order);
    }


    //商家登录接口
    public function buslogin(){
        $post = $this->request->post();
        $user = $post['user'];
        $pwd = password($post['pass']);
        $res = Db::name('staff')->where('phone',$user)->find();
        if (empty($res)){
            to_json(400, 'FAIL', '账号不存在');
        }
        $store = Db::name('store')->where('id',$res['storeid'])->find();
        if (empty($store)){
            to_json(400, 'FAIL', '门店不存在');
        }
        $res['storename'] = $store['name'];

        if (!$res) to_json(201, 'FAIL', '该用户信息不存在');
        if ($res && $res['pwd'] != $pwd){
            to_json(202, 'FAIL', '密码错误');
        }elseif ($res && $res['pwd'] == $pwd){
            to_json(200, 'SUCCESS', $res);
        }
    }

    /*商家今日收益*/
    public function todaymoney(){
        $post = $this->request->post();
        $storeid = $post['storeid'];

        $start = strtotime(date('Y-m-d',time()));

        /*获取该商家今日全部订单*/
        $where['create_at'] = array('gt',$start);
        $where['storeid'] = $storeid;
        $where['status'] = array('gt',0);
        $items = Db::name('order')->where($where)->select();

        $total = 0;
        foreach ($items as $item){
            $total += $item['price'];
        }

        $total = strval($total);

        to_json(200, 'SUCCESS', $total);
    }

    /*商家未结算余额*/
    public function buscredit(){
        $post = $this->request->post();
        $storeid = $post['storeid'];

        $res = Db::name('store')->where('id',$storeid)->find();
        to_json(200, 'SUCCESS', $res['credit1']);
    }

    /*会员预约列表*/
    public function memberprev(){
        $post = $this->request->post();
        $storeid = $post['storeid'];

        $start = time();

        $where['appointmenttime'] = array('gt',$start);
        $where['storeid'] = $storeid;
        $where['status'] = 1;

        $items = Db::name('service_order')->where($where)->select();

        foreach ($items as $k=>$item){
            $img = unserialize($item['car_img']);
            $items[$k]['car_img'] = "https://shiyi.cg500.com".str_replace('"','',$img[0]);
            $order = Db::name('service_orders')->where('orderid',$item['id'])->find();
            $car = Db::name('user_member')->where('id',$item['car_id'])->find();

            $items[$k]['servicename'] = $order['servicename'];
            if (!empty($car['car_number'])){
                $items[$k]['car_code'] = $car['car_number'];
            }else{
                $items[$k]['car_code'] = '';
            }

        }

        to_json(200, 'SUCCESS', $items);
    }

    /*会员预约列表搜索接口*/
    public function prevsearch(){
        $post = $this->request->post();
        $key = $post['keyword'];
        if (!empty($post['keyword'])){
            $data['uname'] = array('LIKE','%'.$key.'%');
        }
        $data['status'] = 1;
        $items = Db::name('service_order')->where($data)->select();

        foreach ($items as $k=>$item){
            $img = unserialize($item['car_img']);
            $items[$k]['car_img'] = "https://shiyi.cg500.com".str_replace('"','',$img[0]);
            $order = Db::name('service_orders')->where('orderid',$item['id'])->find();
            $car = Db::name('user_member')->where('id',$item['car_id'])->find();

            $items[$k]['servicename'] = $order['servicename'];
            $items[$k]['car_code'] = $car['car_number'];
        }

        to_json(200, 'SUCCESS', $items);
    }

    public function malldetail(){
        $post = $this->request->post();
        $id = $post['id'];

        $item = Db::name('service_order')->where('id',$id)->find();

        $img = unserialize($item['car_img']);

        $order = Db::name('service_orders')->where('orderid',$item['id'])->find();
        if (!empty($item['storeimgs'])){
            $item['storeimgs']=unserialize($item['storeimgs']);
            foreach ($item['storeimgs'] as $k=>$v){
                $data['storeimgs'][$k]="https://shiyi.cg500.com".str_replace('"','',$v);
            }
        }else{
            $data['storeimgs']=array();
        }
        if (!empty($item['endimgs'])){
            $item['endimgs']=unserialize($item['endimgs']);
            foreach ($item['endimgs'] as $k=>$v){
                $data['endimgs'][$k]="https://shiyi.cg500.com".str_replace('"','',$v);
            }
        }else{
            $data['endimgs']=array();
        }
        $car = Db::name('user_member')->where('id',$item['car_id'])->find();

        $data['id'] = $item['id'];
        $data['uname'] = $item['uname'];
        $data['status'] = $item['status'];
        $data['uphone'] = $item['uphone'];
        $data['totalprice'] = $item['totalprice'];
        $data['servicename'] = $order['servicename'];
        if (!empty($car['car_number'])){
            $data['car_code'] = $car['car_number'];
        }else{
            $data['car_code'] = '';
        }

        $data['ordersn'] = $item['ordersn'];
        $data['servicetime'] = date('Y-m-d H:i',$item['appointmenttime']);
        $data['remark'] = $item['remarks'];
        $data['car_img'] = "https://shiyi.cg500.com".str_replace('"','',$img[0]);

        to_json(200, '成功', $data);
    }

    /*历史维修记录*/
    public function memberhist(){
        $post = $this->request->post();
        $storeid = $post['storeid'];

        $start = time();

        $where['appointmenttime'] = array('lt',$start);
        $where['storeid'] = $storeid;
        $where['status'] = 3;

        $items = Db::name('service_order')->where($where)->select();
        to_json(200, 'SUCCESS', $items);
    }

    /*会员存放列表*/
    public function membersave(){
        $post = $this->request->post();
        $storeid = $post['storeid'];

        $start = time();

        $where['storeid'] = $storeid;

        $items = Db::name('order')->where($where)->select();
        to_json(200, 'SUCCESS', $items);
    }

    //服务订单详情
    public function sorderinfo(){
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $post = $this->request->post();
        $sorder = Db::name('service_order')->where('ordersn',$post['sorderid'])->find();
        $sorder['appointmenttime']=date('Y-m-d H:i:s',$sorder['appointmenttime']);
        $store = Db::name('store')->where('id',$sorder['storeid'])->find();

        $sorder['userimgs']=unserialize($sorder['userimgs']);
        if (!empty($sorder['userimgs'])){
            foreach ($sorder['userimgs'] as $k1=>$v1){
                $sorder['userimgs'][$k1]="https://shiyi.cg500.com".str_replace('"','',$v1);
            }
        }else{
            $sorder['userimgs']=array();
        }
        $sorder['storeimgs']=unserialize($sorder['storeimgs']);
        if (!empty($sorder['storeimgs'])){
            foreach ($sorder['storeimgs'] as $k2=>$v2){
                $sorder['storeimgs'][$k2]="https://shiyi.cg500.com".str_replace('"','',$v2);
            }
        }else{
            $sorder['storeimgs']=array();
        }
        $sorder['endimgs']=unserialize($sorder['endimgs']);
        if (!empty($sorder['endimgs'])){
            foreach ($sorder['endimgs'] as $k3=>$v3){
                $sorder['endimgs'][$k3]="https://shiyi.cg500.com".str_replace('"','',$v3);
            }
        }else{
            $sorder['endimgs']=array();
        }
        $store['img_url'] = "https://shiyi.cg500.com".str_replace('"','',$store['img_url']);
        $sorder['store']=$store;

        $sorders = Db::name('service_orders')->where('orderid',$sorder['id'])->find();
        $service = Db::name('service')->where('id',$sorders['serviceid'])->select();
        foreach ($service as $k=>$v){
            $service[$k]['img_url'] = "https://shiyi.cg500.com".str_replace('"','',$v['img_url']);
        }

        $sorder['service']=$service;
        to_json(200, 'ok', $sorder);

    }
    //用户分享二维码
    public function xcxCode() {

        if (empty($_SERVER['HTTP_AUTHORIZATION'])){
            $post = $this->request->param();
            $id=$post['store_id'];
        }else{
            $token=$_SERVER['HTTP_AUTHORIZATION'];
            $openid=Db::name('token')->where('token',$token)->find();
            $user=Db::name('user')->where('openid',$openid['openid'])->find();
            $id=$user['id'];
        }

        //配置APPID、APPSECRET
        $APPID = "wxbd4f3c1bbbb8d880";
        $APPSECRET =  "7f7aa956632d75d21791cae783cadc5a";
        //获取access_token
        $access_token = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$APPID&secret=$APPSECRET";
        //缓存access_token
        session_start();
        $_SESSION['access_token'] = "";
        $_SESSION['expires_in'] = 0;
        $ACCESS_TOKEN = "";
        if(!isset($_SESSION['access_token']) || (isset($_SESSION['expires_in']) && time() > $_SESSION['expires_in']))
        {
            $json = $this->httpRequest( $access_token );
            $json = json_decode($json,true);
            $_SESSION['access_token'] = $json['access_token'];
            $_SESSION['expires_in'] = time()+7200;
            $ACCESS_TOKEN = $json["access_token"];
        } else{
            $ACCESS_TOKEN =  $_SESSION["access_token"];
        }
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $ACCESS_TOKEN;
        $data['scene'] =  $id;
        //小程序的详情页路径
        if (empty($_SERVER['HTTP_AUTHORIZATION'])){
            $data['page'] = 'pages/index/index';
        }else{
            $data['page'] = 'userpage/relationinfo';
        }

        //二维码大小
        $data['width'] = '430';
        $data=json_encode($data);
        $res = $this->httpRequest($url, $data,"POST");
        if (empty($_SERVER['HTTP_AUTHORIZATION'])){
            $path = 'uploads/qrcode/s' . $id . '.jpg';
        }else{
            $path = 'uploads/qrcode/h' . $id . '.jpg';
        }

        file_put_contents($path, $res);
        $return['code'] = 200;
        $return['msg'] = 'ok';
        $return['img'] = 'https://shiyi.cg500.com/' . $path;
        echo json_encode($return);exit;
    }
    //把请求发送到微信服务器换取二维码
    function httpRequest($url, $data='', $method='GET'){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        if($method=='POST')
        {
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data != '')
            {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }

        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    public function getDistance()
    {
        $request = request();
        $param = $request->param();
        $store=Db::name('store')->where('status','1')->select();
        foreach ($store as $k=>$v){
            $earthRadius = 6367000; //approximate radius of earth in meters
            $lat1=$param['map']['lat'];
            $lng1=$param['map']['long'];
            $lat2=$v['latitude'];
            $lng2=$v['longitude'];
            $lat1 = ($lat1 * pi() ) / 180;
            $lng1 = ($lng1 * pi() ) / 180;
            $lat2 = ($lat2 * pi() ) / 180;
            $lng2 = ($lng2 * pi() ) / 180;
            $calcLongitude = $lng2 - $lng1;
            $calcLatitude = $lat2 - $lat1;
            $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
            $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
            $calculatedDistance = $earthRadius * $stepTwo;
            $store[$k]['jl']=$calculatedDistance;
            if ($calculatedDistance>=1000){
                $store[$k]['jl1']=round(($calculatedDistance/1000),1)."千米";
            }elseif ($calculatedDistance<1000){
                $store[$k]['jl1']=round($calculatedDistance)."米";
            }

        }
        foreach($store as $list2){
            $sort[]=$list2["jl"];
        }
        array_multisort($sort,SORT_ASC,$store);
        to_json(200,'OK',$store);
    }

    //预约服务修改时间
    public function upservertime(){
        $request = request();
        $param = $request->param();
        $ordersn=$param['order'];
        $time['appointmenttime']=strtotime($param['time']);
        $time=Db::name('service_order')->where('ordersn',$ordersn)->update($time);
        if ($time){
            to_json(200,'OK',$param['time']);
        }else{
            to_json(400,'NO');
        }
    }
    //门店地址选择
    public function city()
    {
        $request = request();
        $param = $request->param();
        $where['pid']=1;
        $province=Db::name('city')->where($where)->select();
        if (empty($province)){
            to_json(400,'暂无数据');
        }
        $cityall=array();
        foreach($province as $k=>$v){
            /*if ($v['city_id']==2 || $v['city_id']==3){
                $cityall[]=$v;
            }else{*/
                if (!empty($param['city_name'])){
                    $tj=array();
                    $tj['city_name'] = ['like', '%' . $param['city_name'] . '%'];
                }
                $tj['pid']=$v['city_id'];
                $city=Db::name('city')->where($tj)->select();
                foreach ($city as $kk=>$vv){
                    $cityall[]=$vv;
                }
            //}

        }
        to_json(200,'OK',$cityall);
    }
    //根据定位获取城市
    public function geographic()
    {
        $request = request();
        $param = $request->param();
        /*$param['map']['lat']=41.764914;
        $param['map']['long']=123.426859;*/
        $key = 'LQUBZ-HSIWO-H5FW4-SZ2YB-SKOP6-U6FRU';
        $lat=$param['map']['lat'];
        $lng=$param['map']['long'];
        $url = 'https://apis.map.qq.com/ws/geocoder/v1/?location='.$lat.','.$lng.'&key='.$key.'&get_poi=0';
        $result= $this->curl($url);
        $result=json_decode($result,true);
        $data['city'] = $result['result']['address_component']['city'];
        $where['city_name'] = ['like', '%' . $data['city'] . '%'];

        $city=Db::name('city')->where($where)->find();
        if ($data){
            to_json(200,'OK',$city);
        }else{
            to_json(400,'NO');
        }

    }
    //封装的curl方法
    public static function curl($url, $params = false, $ispost = 0, $https = 0)
    {
        $httpInfo = array();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        }
        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            if ($params) {
                if (is_array($params)) {
                    $params = http_build_query($params);
                }
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }

        $response = curl_exec($ch);

        if ($response === FALSE) {
            //echo "cURL Error: " . curl_error($ch);
            return false;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
        curl_close($ch);
        return $response;
    }
    //门店地图
    public function map()
    {
        $post = $this->request->param();
        if (!empty($post['city_id']) && $post['city_id']>0){
            $where['city_id']=$post['city_id'];
        }
        $where['status']=1;
        $store=Db::name('store')->where($where)->select();
        if (empty($store)){
            to_json(400,"暂无数据!");
        }else{
            to_json(200,"OK",$store);
        }
    }
    //门店补贴
    public function screditlog()
    {
        $post = $this->request->param();
        $where['store_id']=$post['store_id'];
        $where['price']=['>',0];
        $creditlog=Db::name('storecreditlog')->where($where)->select();
        //dump($creditlog);die;
        if (empty($creditlog)){
            to_json(400,"暂无数据!");
        }
        foreach ($creditlog as $k=>$v){
            $order=Db::name('service_order')->where('ordersn',$v['ordersn'])->find();
            $creditlog[$k]['uname']=$order['uname'];
            $creditlog[$k]['uphone']=$order['uphone'];
            $creditlog[$k]['create_at']=date('Y-m-d H:i:s',$v['create_at']);
        }
        to_json(200,"OK",$creditlog);
    }
    //门店报价
    public function offer()
    {
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $post = $this->request->param();

        /*$openid['openid']="oLmfV5M8Didum_z_zovOk3tvWEjk";
        $post['ordersn']='SH202009030F3O1B';*/
        $post['totalprice']=100;
        $user=Db::name('user')->where('openid',$openid['openid'])->find();
        $where['ordersn']=$post['ordersn'];
        //查找订单信息
        $order=Db::name('service_order')->where('ordersn',$post['ordersn'])->find();
        if (empty($order)){
            to_json(400,"报价失败,未找到该订单信息!");
        }
        $car=Db::name('user_member')->where('id',$order['car_id'])->find();
        if ($car['level_status']==1){
            //获取会员折扣
            $dis['start_num']=['>=',$user['servicenum']];
            $discount=Db::name('discount')->where($dis)->find();
            $discount['discount']=$discount['discount']/10;
            //获取折扣后价格
            $price=round(($post['totalprice']*$discount['discount']),2);
        }else{
            $price=$post['totalprice'];
        }
        //修改订单数据
        $up['totalprice']=$price;
        $up['price']=$post['totalprice'];
        $up['status']=2;
        $uporder=Db::name('service_order')->where('ordersn',$post['ordersn'])->update($up);
        if ($uporder){
            to_json(200,"报价成功");
        }else{
            to_json(400,"报价失败");
        }

    }
    //确认到店
    public function sostudent()
    {
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $post = $this->request->param();

        /*$openid['openid']="oLmfV5M8Didum_z_zovOk3tvWEjk";
        $post['ordersn']='SH202009030F3O1B';*/
        //$post['totalprice']=100;
        //查找订单信息
        $order=Db::name('service_order')->where('ordersn',$post['ordersn'])->find();
        if (empty($order)){
            to_json(400,"未找到订单信息!");
        }
        $up['status']=1;
        $uporder=Db::name('service_order')->where('ordersn',$post['ordersn'])->update($up);
        if ($uporder){
            to_json(200,"OK");
        }else{
            to_json(400,"NO");
        }
    }

    //审核通过车辆信息
    public function car_list()
    {
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $post = $this->request->param();
        //$openid['openid']="oLmfV5M8Didum_z_zovOk3tvWEjk";
        $where['openid']=$openid['openid'];
        $where['status']=['>',0];
        $car=Db::name('user_member')->where($where)->select();
        if (empty($car)){
            to_json(400,"暂无车辆信息!");
        }

        foreach ($car as $k=>$v){
            if (!empty($v['car_img'])){
                $car[$k]['car_img']=unserialize($v['car_img']);
                foreach($car[$k]['car_img'] as $k1=>$v1){
                    $car[$k]['car_img'][$k1]="https://shiyi.cg500.com".str_replace('"','',$v1);
                }
            }
            if (!empty($v['license_img'])){
                $car[$k]['license_img']=unserialize($v['license_img']);
                foreach($car[$k]['license_img'] as $k1=>$v1){
                    $car[$k]['license_img'][$k1]="https://shiyi.cg500.com".str_replace('"','',$v1);
                }
            }
            if($v['end_level']>time() && $v['level_status']==1){
                $car[$k]['end_level']=date('Y-m-d',$v['end_level']);
            }else{
                $car[$k]['end_level']='';
            }
        }
        if (empty($car)){
            to_json(400,"暂无车辆信息!");
        }else{
            to_json(200,"OK",$car);
        }
    }
    //所有车辆
    public function carall_list()
    {
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $post = $this->request->param();

        //$openid['openid']="oLmfV5M8Didum_z_zovOk3tvWEjk";
        $where['openid']=$openid['openid'];
        $car=Db::name('user_member')->where($where)->select();
        foreach ($car as $k=>$v){
            if (!empty($v['car_img'])){
                $car[$k]['car_img']=unserialize($v['car_img']);
                foreach($car[$k]['car_img'] as $k1=>$v1){
                    $car[$k]['car_img'][$k1]=str_replace('"','',$v1);
                }
            }
            if (!empty($v['license_img'])){
                $car[$k]['license_img']=unserialize($v['license_img']);
                foreach($car[$k]['license_img'] as $k1=>$v1){
                    $car[$k]['license_img'][$k1]=str_replace('"','',$v1);
                }
            }
            if($v['end_level']>time() && $v['level_status']==1){
                $car[$k]['end_level']=date('Y-m-d',$v['end_level']);
            }else{
                $car[$k]['end_level']='';
            }

            $car[$k]['checked']=false;
        }
        if (empty($car)){
            to_json(400,"暂无车辆信息!");
        }else{
            to_json(200,"OK",$car);
        }
    }
    //用户车辆删除
    public function car_del()
    {
        $post = $this->request->param();
        if (empty($post['car_id'])){
            to_json(400,"请先选择要删除的车辆!");
        }
        foreach ($post['car_id'] as $v){
            Db::name('user_member')->where('id',$v)->delete();
        }

        to_json(200,"OK");
    }
    //下级列表
    public function usersuperior(){
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $user=Db::name('user')->where('openid',$openid['openid'])->find();
        $superior=array();
        if (empty($user['agentid'])){
            $superior['superior']='';
        }else{
            $superior['superior']=Db::name('user')->where('id',$user['agentid'])->find();
            $superior['superior']['create_at']=date('Y-m-d',$superior['superior']['create_at']);
        }
        $superior1=Db::name('user')->where('agentid',$user['id'])->select();
        if (empty($superior1)){
            $superior['superior1']=array();
        }else{
            foreach ($superior1 as $k=>$v){
                $superior1[$k]['create_at']=date('Y-m-d',$v['create_at']);
            }
            $superior['superior1']=$superior1;
        }
        to_json(200,'OK',$superior);
    }
    //会员扫描推荐码进入
    public function agentid(){
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $post = $this->request->param();
        $user=Db::name('user')->where('openid',$openid['openid'])->find();
        if (empty($user['agentid']) && $user['id']!=$post['id']){
            $upuser['agentid']=$post['id'];
            $a=Db::name('user')->where('openid',$openid['openid'])->update($upuser);
        }
        to_json(200,'OK');
    }
    //门店服务订单
    public function storesorder()
    {
        $post = $this->request->param();
        if (empty($post['store_id'])){
            to_json(400,'门店信息错误');
        }else{
            $where['storeid']=$post['store_id'];
        }
        if (!empty($post['type'])){
            $where['status']=$post['type'];
        }
        if (!empty($post['uname'])){
            $where['uname']=['like', '%' . $post['uname'] . '%'];
        }
        //dump($where);die;
        $sorder=Db::name('service_order')->where($where)->select();
        if (empty($sorder)){
            to_json(400,'暂无数据');
        }
        foreach($sorder as $k=>$v){
            //用户上传维修图片
            $sorder[$k]['userimgs']=unserialize($v['userimgs']);
            if (!empty($sorder[$k]['userimgs'])){
                foreach ($sorder[$k]['userimgs'] as $k1=>$v1){
                    $sorder[$k]['userimgs'][$k1]="https://shiyi.cg500.com".str_replace('"','',$v1);
                }
            }else{
                $sorder[$k]['userimgs']=array();
            }
            //门店上传维修前图片
            $sorder[$k]['storeimgs']=unserialize($v['storeimgs']);
            if (!empty($sorder[$k]['storeimgs'])){
                foreach ($sorder[$k]['storeimgs'] as $k2=>$v2){
                    $sorder[$k]['storeimgs'][$k2]="https://shiyi.cg500.com".str_replace('"','',$v2);
                }
            }else{
                $sorder[$k]['storeimgs']=array();
            }
            //维修完成图片
            $sorder[$k]['endimgs']=unserialize($v['endimgs']);
            if (!empty($sorder[$k]['endimgs'])){
                foreach ($sorder[$k]['endimgs'] as $k3=>$v3){
                    $sorder[$k]['endimgs'][$k3]="https://shiyi.cg500.com".str_replace('"','',$v3);
                }
            }else{
                $sorder[$k]['endimgs']=array();
            }
            //用户认证车辆图片
            $sorder[$k]['car_img']=unserialize($v['car_img']);
            if (!empty($sorder[$k]['car_img'])){
                foreach ($sorder[$k]['car_img'] as $k4=>$v4){
                    $sorder[$k]['car_img'][$k4]="https://shiyi.cg500.com".str_replace('"','',$v4);
                }
            }else{
                $sorder[$k]['car_img']=array();
            }

            $car=Db::name('user_member')->where('id',$v['car_id'])->find();
            $service=Db::name('service_orders')->where('orderid',$v['id'])->find();
            $sorder[$k]['servicename']=$service['servicename'];
            $sorder[$k]['car_number']=$car['car_number'];
        }
        to_json(200,'OK',$sorder);
    }
    //门店商品存放列表
    public function storegoods()
    {
        $post = $this->request->param();
        //判断门店id
        if (empty($post['store_id'])){
            to_json(400,'门店信息错误');
        }else{
            $where['storeid']=$post['store_id'];
        }
        //判断是否有搜索
        if (!empty($post['uname'])){
            $where['uname']=['like', '%' . $post['uname'] . '%'];
        }
        $where['status']=2;

        //查找该门店所有订单
        $order=DB::name('order')->where($where)->select();

        //判断是否存在订单
        if (empty($order)){
            to_json(400,'暂无数据');
        }
        foreach ($order as $k=>$v){
            $goods=DB::name('order_goods')->where('orderid',$v['id'])->select();
            foreach ($goods as $kk=>$vv){
                $goodinfo=Db::name('goods')->where('id',$vv['goodsid'])->find();
                //dump($goodinfo);die;
                $goods[$kk]['goods_img']="https://shiyi.cg500.com".str_replace('"','',$goodinfo['goods_img']);
                $goods[$kk]['price']=$goodinfo['price'];
            }
            $order[$k]['goods']=$goods;
            $order[$k]['user']=DB::name('user')->where('openid',$v['openid'])->find();
        }
        to_json(200,'OK',$order);
    }
    //用户扫描门店码进入  将门店码绑定用户
    public function user_store()
    {
        if(empty($_SERVER['HTTP_AUTHORIZATION'])){
            to_json(400,'用户未授权!');
        }
        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();
        $post = $this->request->param();
        $user=Db::name('user')->where('openid',$openid['openid'])->find();
        if (empty($user['store_id'])){
            $upuser['store_id']=$post['store_id'];
            $a=Db::name('user')->where('openid',$openid['openid'])->update($upuser);
            to_json(200,'OK');
        }else{
            to_json(400,'已有顶置门店');
        }
    }
    //会员状态
    public function car_status()
    {

        $token=$_SERVER['HTTP_AUTHORIZATION'];
        $openid=Db::name('token')->where('token',$token)->find();

        $car=DB::name('user_member')->where('openid',$openid['openid'])->select();
        if(empty($car)){
            $order=DB::name('member_order')->where(array('openid'=>$openid['openid'],'status'=>1,'car_id'=>0,'band_status'=>0))->select();
            //dump($order);die;
            if (empty($order)){
                to_json(1001,'暂无车辆信息!');
            }else{
                to_json(1002,'VIP已购买,请前去完善车辆信息!',$order);
            }

        }
        foreach($car as $k => $v){
            if ($v['level_status']==1 && $v['status']==1){
                $car[$k]['msg']='当前车辆VIP将于'.date('Y-m-d',$v['end_level']).'过期';
                $car[$k]['btnstatus']=1;
            }elseif ($v['level_status']==1 && $v['status']==0){
                $car[$k]['msg']='当前车辆审核中VIP将于'.date('Y-m-d',$v['end_level']).'过期';
            }elseif ($v['level_status']==0 && $v['status']==0){
                $order=DB::name('member_order')->where(array('openid'=>$openid['openid'],'status'=>1,'car_id'=>$v['id'],'band_status'=>0))->select();
                if (!empty($order)){
                    $car[$k]['msg']='当前车辆审核中VIP将于审核通过后开始计算';
                }else{
                    $car[$k]['msg']='您的爱车正在审核中';
                }
            }elseif ($v['level_status']==0 && $v['status']==1){
                $car[$k]['msg']='当前车辆未开通VIP';
                $car[$k]['btnstatus']=1;
            }elseif ($v['level_status']==1 && $v['status']==-2){
                $car[$k]['msg']='当前会员车辆审核被驳回请重新提交';
            } elseif ($v['level_status']==0 && $v['status']==-2){
                $car[$k]['msg']='当前车辆审核被驳回请重新提交';
            }
            if (!empty($v['end_level'])){
                $car[$k]['end_level']=date('Y-m-d',$v['end_level']);
            }
        }

        to_json(200,'OK',$car);

    }
    //门店提现记录
    public function sw_log()
    {
        $post = $this->request->param();
        if (empty($post['store_id'])){
            to_json(400,'门店不存在');
        }
        $swall=Db::name('swithdrawal')->where('id',$post['store_id'])->select();
        if (empty($swall)){
            to_json(400,'暂无数据');
        }else{
            to_json(200,'OK',$swall);
        }
    }
    //门店申请提现
    public function sw()
    {
        $post = $this->request->param();
        if (empty($post['store_id'])){
            to_json(400,'门店不存在');
        }
        //查询商家信息
        $store=Db::name('store')->where('id',$post['store_id'])->find();

        //判断当天是否是1号 15号
        $time=date('d',time());
        if($time!=1 && $time!=15 && $store['store_type']!=1){
            to_json(400,'请于每月1号或15号发起提现');
        }
        //判断商家余额是否充足
        if ($store['credit1']<=$post['price']){
            to_json(400,'余额不足');
        }

        $credit['credit1']=($store['credit1']-$post['price']);
        $upstore=Db::name('store')->where('id',$post['store_id'])->update($credit);

        $sw['store_id']=$post['store_id'];
        $sw['storename']=$store['name'];
        $sw['price']=$post['price']-0.006*$post['price'];
        $sw['create_at']=time();
        Db::name('swithdrawal')->insert($sw);
        if (empty($swall)){
            to_json(400,'暂无数据');
        }else{
            to_json(200,'OK',$swall);
        }
    }

}