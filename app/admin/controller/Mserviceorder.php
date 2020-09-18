<?php
namespace app\admin\controller;

use think\Controller;
use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
use app\admin\controller\Permissions;
class Mserviceorder extends Controller
{







    public function xcxCode() {
        //$id = trim($this->request->param('id','5084','intval'));
        $id=55;
        //$access_token = $this->getAccessToken();
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
        }
        else{
            $ACCESS_TOKEN =  $_SESSION["access_token"];
        }
        //dump($ACCESS_TOKEN);die;
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $ACCESS_TOKEN;
        $data['scene'] = 'h' . $id;
        //小程序的详情页路径
        $data['path'] = 'pages/detail/detail';
        //二维码大小
        $data['width'] = '430';
        $data=json_encode($data);
        $res = $this->httpRequest($url, $data,"POST");
        $path = 'uploads/qrcode/h' . $id . '.jpg';
        file_put_contents($path, $res);
        $return['status_code'] = 2000;
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

        $store=Db::name('store')->where('status','1')->select();
        foreach ($store as $k=>$v){
            $earthRadius = 6367000; //approximate radius of earth in meters
            $lat1=41.764914;
            $lng1=123.426859;
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
        dump($store);die;
    }
    public function storeservice()
    {
        $store=Db::name('store')->select();
        foreach($store as $k=>$v){
            $servicetype=unserialize($v['servicetypeid']);
            foreach ($servicetype as $k1=>$v1){
                $post['storeid']=$v['id'];
                $post['servicetypeid']=$v1;
                Db::name('storeservice')->insert($post);
            }
        }

    }

}