<?php
namespace app\admin\controller;
use app\admin\controller\Permissions;
use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
class Order extends Permissions
{
    public function index(){
        $where=array();
        $post = $this->request->param();
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
        $list = Db::name('order')
            ->where($where)
            ->order('create_at desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        foreach($list as $k=>$v){
            /*if ($v['status']==0){
                $list[$k]['status']='未付款';
            }elseif($v['status']==1){
                $list[$k]['status']='待发货';
            }elseif($v['status']==2){
                $list[$k]['status']='待收货';
            }elseif($v['status']==3){
                $list[$k]['status']='已完成';
            }elseif($v['status']==-1){
                $list[$k]['status']='维权';
            }*/
            //是否寄存到店
            if ($v['deposit']!=0){
                $store=Db::name('store')->where('id',$v['storeid'])->find();
                $list[$k]['storeid']=$store['name'];
            }
            //该订单所有商品
            $order_goods=Db::name('order_goods')->where('orderid',$v['id'])->select();
            foreach ($order_goods as $k1 =>$v1){
                $goods=Db::name('goods')->where('id',$v1['goodsid'])->find();
                $order_goods[$k1]['img_url']=$goods['goods_img'];
                $order_goods[$k1]['coefficient']=$goods['coefficient'];
                $order_goods[$k1]['price']=$goods['price'];
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
        $order=Db::name('order')->where('id',$id)->find();
        //dump($order);die;
        //是否寄存到店
        if ($order['deposit']!=0){
            $store=Db::name('store')->where('id',$order['storeid'])->find();
            $order['storeid']=$store['name'];
        }
        if ($order['price']<=0 && $order['integral']>0){
            $order['pay']='积分支付';
        }else{
            $order['pay']='微信支付';
        }
        //该订单所有商品
        $order_goods=Db::name('order_goods')->where('orderid',$id)->select();
        //dump($order_goods);die;
        foreach ($order_goods as $k1 =>$v1){
            $goods=Db::name('goods')->where('id',$v1['goodsid'])->find();
            //dump($goods);die;
            $order_goods[$k1]['img_url']=$goods['goods_img'];
            $order_goods[$k1]['price']=$goods['price'];
            $order_goods[$k1]['size']=$goods['size'];
            $order_goods[$k1]['coefficient']=$goods['coefficient'];
            $order_goods[$k1]['give_price']=$goods['give_credit1'];
            $order_goods[$k1]['give_integral']=$goods['give_integral'];
        }
        $this->assign('order', $order);
        $this->assign('order_goods', $order_goods);
        //dump($order_goods);die;
        return $this->fetch();
    }

    public function status(){
        if($this->request->isAjax()) {
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            $order=Db::name('order')->where('id',$id)->find();
            $status=array();
            if ($order['status']==0){
                $status['status']=1;
            }elseif ($order['status']==2){
                $status['status']=3;
            }
            if(false == Db::name('order')->where('id',$id)->update($status)) {
                return $this->error('修改失败');
            } else {
                return $this->success('修改成功','admin/order/index');
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
            $post['status']=2;
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