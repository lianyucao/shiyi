<?php
namespace app\admin\controller;

use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
use app\admin\controller\Permissions;
class Userpay extends Permissions
{
    /**
     * 会员系数列表
     * 宇
     */
    public function index()
    {
        $where=array();
        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['name'] = ['like', '%' . $post['keywords'] . '%'];
        }
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_at'] = [['>=',$min_time],['<=',$max_time]];
        }
        $list = Db::name('member_order')
            ->where($where)
            ->order('create_at desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        foreach($list as $k=>$v){
            if ($v['status']=0){
                $list[$k]['status']='订单已取消';
            }elseif ($v['status']=1){
                $list[$k]['status']='已完成';
            }
            if ($v['car_id']=0){
                $list[$k]['car']='未绑定';
            }else{
                $list[$k]['car']='已绑定';
            }
        }
        $this->assign('userpay', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function statistics1()
    {
        return $this->statistics('user');
    }

    public function statistics2()
    {
        return $this->statistics('store');
    }

    public function statistics3()
    {
        return $this->statistics('goods');
    }

    public function statistics($name)
    {
        $titles = [];
        $list = [];
        if ($name === 'user') {
            $res = Db::name('order')
                ->field(['openid', 'SUM(totalprice) AS price'])
                ->group('openid')
                ->paginate(20, false, ['query' => $this->request->param()]);
            $page = $res->render();
            $data = $res->all();
            foreach ($data as $item) {
                $user = Db::name('user')->where('openid', $item['openid'])->find();
                $list[] = [
                    'targetId' => $user['openid'],
                    'field1' => $user['nickname'],
                    'field2' => $user['phone'] ? $user['phone'] : '',
                    'field3' => sprintf('%.2f', $item['price']),
                ];
            }
            $titles = ['用户名', '电话', '金额', '操作'];
        } elseif($name === 'store') {
            $res = Db::name('order')
                ->field(['storeid', 'SUM(totalprice) AS price'])
                ->where('storeid', '<>', 0)
                ->group('storeid')
                ->paginate(20, false, ['query' => $this->request->param()]);
            $page = $res->render();
            $data = $res->all();
            foreach ($data as $item) {
                $store = Db::name('store')->where('id', $item['storeid'])->find();
                $list[] = [
                    'targetId' => $store['id'],
                    'field1' => $store['name'],
                    'field2' => $store['phone'],
                    'field3' => sprintf('%.2f', $item['price']),
                ];
            }
            $titles = ['门店名', '电话', '金额', '操作'];
        } elseif ($name === 'goods') {
            $res = Db::name('order_goods')
                ->alias('og')
                ->field(['og.goodsid', 'SUM(g.price * goodsnum) AS price'])
                ->join('tplay_goods g','g.id = og.goodsid')
                ->group('og.goodsid')
                ->paginate(20, false, ['query' => $this->request->param()]);
            $page = $res->render();
            $data = $res->all();
            foreach ($data as $item) {
                $goods = Db::name('goods')->where('id', $item['goodsid'])->find();
                $list[] = [
                    'targetId' => $goods['id'],
                    'field1' => $goods['name'],
                    'field2' => $goods['size'],
                    'field3' => sprintf('%.2f', $item['price']),
                ];
            }
            $titles = ['商品名', '规格', '金额', '操作'];
        }

        $this->assign('name', $name);
        $this->assign('titles', $titles);
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch('/userpay/statistics');
    }

    public function statistic()
    {
        $name = input('get.name/s', 'user', 'trim');
        $targetId = input('get.targetId/s');

        $list = [];
        if ($name === 'user') {
            $res = Db::name('order')
                ->where('openid', $targetId)
                ->order(['id' => 'desc'])
                ->paginate(20, false, ['query' => $this->request->param()]);
            $page = $res->render();
            $data = $res->all();
            foreach ($data as $item) {
                $list[] = [
                    'field1' => $item['ordersn'],
                    'field2' => sprintf('%.2f', $item['totalprice']),
                    'field3' => date('Y-m-d h:i:s', $item['create_at']),
                ];
            }
        } elseif($name === 'store') {
            $res = Db::name('order')
                ->where('storeid', $targetId)
                ->order(['id' => 'desc'])
                ->paginate(20, false, ['query' => $this->request->param()]);
            $page = $res->render();
            $data = $res->all();
            foreach ($data as $item) {
                $list[] = [
                    'field1' => $item['ordersn'],
                    'field2' => sprintf('%.2f', $item['totalprice']),
                    'field3' => date('Y-m-d h:i:s', $item['create_at']),
                ];
            }
        } elseif ($name === 'goods') {
            $res = Db::name('order_goods')
                ->alias('og')
                ->field(['o.ordersn', 'og.goodsid', 'g.price', 'og.goodsnum', 'o.create_at'])
                ->where('og.goodsid', $targetId)
                ->join('tplay_goods g','g.id = og.goodsid')
                ->join('tplay_order o','o.id = og.orderid')
                ->order(['og.id' => 'desc'])
                ->paginate(20, false, ['query' => $this->request->param()]);
            $page = $res->render();
            $data = $res->all();
            foreach ($data as $item) {
                $list[] = [
                    'field1' => $item['ordersn'],
                    'field2' => sprintf('%.2f', intval($item['price']) * $item['goodsnum']),
                    'field3' => date('Y-m-d h:i:s', $item['create_at']),
                ];
            }
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }
}
