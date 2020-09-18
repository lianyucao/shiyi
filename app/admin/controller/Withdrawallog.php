<?php
namespace app\admin\controller;

use app\admin\controller\Permissions;
use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
class Withdrawallog extends Permissions
{
    public function index()
    {
        $where=array();
        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['name'] = ['like', '%' . $post['keywords'] . '%'];
        }
        $list = Db::name('store')
            ->where($where)
            ->order('create_at desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        $store=array();
        foreach($list as $k=>$v){
            $withdrawal=Db::name('swithdrawal')->where(array('store_id'=>$v['id'],'status'=>1))->select();
            $price=array_sum(array_column($withdrawal, 'price'));
            $store[$k]['price']=$price;
            $store[$k]['storeid']=$v['id'];
            $store[$k]['storename']=$v['name'];
        }
        $this->assign('store', $store);
        $this->assign('page', $page);
        return $this->fetch();
    }
}