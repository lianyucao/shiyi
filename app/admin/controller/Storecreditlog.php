<?php
namespace app\admin\controller;
use app\admin\controller\Permissions;
use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
class Storecreditlog extends Permissions
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
        if (isset($post['store']) and !empty($post['store'])) {
            $storename= Db::name('store')->where('id',$post['store'])->find();
            $where['storename'] = $storename['name'];
        }
        $list = Db::name('storecreditlog')
            ->where($where)
            ->order('create_at desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        $store= Db::name('store')->select();
        $this->assign('store', $store);
        $this->assign('log', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }
}