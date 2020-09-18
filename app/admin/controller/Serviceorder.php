<?php
namespace app\admin\controller;
use app\admin\controller\Permissions;
use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
class Serviceorder extends Permissions
{
    public function index(){
        $where=array();
        $post = $this->request->param();
        if (isset($post['uid']) and !empty($post['uid'])) {
            $where['uid'] = $post['uid'];
        }
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['ordersn'] = ['like', '%' . $post['keywords'] . '%'];
        }
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_at'] = [['>=',$min_time],['<=',$max_time]];
        }
        if (isset($post['status']) and !empty($post['status'])) {
            $where['status'] = $post['status'];
        }
        $list = Db::name('service_order')
            ->where($where)
            ->order('create_at desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        foreach($list as $k=>$v){
            //门店信息
            $store=Db::name('store')->where('id',$v['storeid'])->find();
            $list[$k]['storeid']=$store['name'];
            //该订单所有商品
            $order_goods=Db::name('service_orders')->where('orderid',$v['id'])->select();
            foreach ($order_goods as $k1 =>$v1){
                $goods=Db::name('service')->where('id',$v1['serviceid'])->find();
                //dump($v1);
                $order_goods[$k1]['price']=$goods['price'];
                $order_goods[$k1]['img_url']=$goods['img_url'];
                $order_goods[$k1]['servicename']=$goods['name'];
            }
            $list[$k]['order_goods']=$order_goods;
        }

        $this->assign('order', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function publish()
    {
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            $order = Db::name('service_order')->where('id', $id)->find();
            //门店信息
            $store = Db::name('store')->where('id', $order['storeid'])->find();
            $order['storeid'] = $store['name'];
            //支付信息
            if ($order['totalprice'] <= 0 && $order['integral'] > 0) {
                $order['pay'] = '积分支付';
            } elseif($order['totalprice'] <= 0 && $order['integral'] <= 0) {
                $order['pay'] = '待支付';
            }else{
                $order['pay'] = '微信支付';
            }

            //认证信息
            if (!empty($order['car_img'])){
                $order['car_img']=unserialize($order['car_img']);
                foreach($order['car_img'] as $k=>$v){
                    $order['car_img'][$k]="https://shiyi.cg500.com".str_replace('"','',$v);
                }
            }
            //用户图片
            if (!empty($order['userimgs'])){
                $order['userimgs']=unserialize($order['userimgs']);
                foreach($order['userimgs'] as $k=>$v){
                    $order['userimgs'][$k]="https://shiyi.cg500.com".str_replace('"','',$v);
                }
            }
            //维修前图片
            if (!empty($order['storeimgs'])){
                $order['storeimgs']=unserialize($order['storeimgs']);
                foreach($order['storeimgs'] as $k1=>$v1){
                    $order['storeimgs'][$k1]="https://shiyi.cg500.com".str_replace('"','',$v1);
                }
            }
            //维修后
            if (!empty($order['endimgs'])){
                $order['endimgs']=unserialize($order['endimgs']);
                foreach($order['endimgs'] as $k2=>$v2){
                    $order['endimgs'][$k2]="https://shiyi.cg500.com".str_replace('"','',$v2);
                }
            }
            //该订单所有商品
            $order_goods = Db::name('service_orders')->where('orderid', $id)->select();
            foreach ($order_goods as $k1 => $v1) {
                $goods = Db::name('service')->where('id', $v1['serviceid'])->find();
                $order_goods[$k1]['img_url'] = $goods['img_url'];
                $order_goods[$k1]['price'] = $goods['price'];
                $order_goods[$k1]['coefficient']=$goods['coefficient'];
            }
            $this->assign('order', $order);
            $this->assign('order_goods', $order_goods);
            return $this->fetch();
    }

    public function status(){
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            $order=Db::name('service_order')->where('id',$id)->find();
            $status=array();
            $status['status']=$order['status']+1;
            if(false == Db::table('tplay_service_order')->where('id',$id)->update($status)) {
                return $this->error('修改失败');
            } else {
                return $this->success('修改成功','admin/serviceorder/index');
            }
    }
    public function outstatus(){
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            $order=Db::name('service_order')->where('id',$id)->find();
            $status=array();
            $status['status']=-1;
            if(false == Db::table('tplay_service_order')->where('id',$id)->update($status)) {
                return $this->error('修改失败');
            } else {
                return $this->success('修改成功','admin/serviceorder/index');
            }
    }
    public function paystore(){
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            //获取订单详情
            $order=Db::name('service_order')->where('id',$id)->find();
            //获取订单服务
            $order_service=Db::name('service_orders')->where('id',$order['id'])->find();
            //获取订单服务详情
            $service=Db::name('service')->where('id',$order_service['serviceid'])->find();
            if (!empty($service['proportion'])){
                $proportion=$service['proportion'];
            }
            //获取商户余额
            $store=Db::name('store')->where('id',$order['storeid'])->find();
            $payprice['credit1']=$store['credit1']+($order['price']*$proportion);
            //添加打款记录
            $log=array();
            $log['store_id']=$store['id'];
            $log['storename']=$store['name'];
            $log['ordersn']=$order['ordersn'];
            $log['price']=$order['price']*0.8;
            $log['create_at']=time();
            Db::table('tplay_storecreditlog')->insert($log);

            //用户维修次数+1
            $user=Db::name('user')->where('openid',$order['openid'])->find();
            $servicenum['servicenum']=$user['servicenum']+1;
            Db::name('user')->where('openid',$user['openid'])->update($servicenum);
            //修改订单状态
            $status['status']=6;
            if(false == Db::name('service_order')->where('id',$id)->update($status)) {
                return $this->error('订单状态修改失败');
            } else {
                //商户余额修改
                if(false == Db::table('tplay_store')->where('id',$order['storeid'])->update($payprice)) {
                    return $this->error('打款失败');
                } else {
                    return $this->success('打款成功','admin/serviceorder/index');
                }
            }

    }
    public function express()
    {
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if ($this->request->isPost()) {
            //是提交操作
            $post = $this->request->post();
            //验证订单是否存在
            $info = Db::name('order')->where('id', $id)->find();
            if (empty($info)) {
                return $this->error('id不正确');
            }
            if (false == Db::name('order')->strict(false)->update($post)) {
                return $this->error('发货失败');
            } else {
                return $this->success('发货成功', 'admin/order/index');
            }
        }else{
            $info = Db::name('order')->where('id', $id)->find();

            $this->assign('info', $info);
            return $this->fetch();
        }
    }

}
