<?php
namespace app\admin\controller;

use app\admin\controller\Permissions;
use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
class Evaluate extends Permissions
{
    /**
     * 评价列表
     * 宇
     */
    public function index()
    {
        $where=array();
        $post = $this->request->param();
        if (isset($post['store']) and $post['store'] > 0) {
            $where['storeid'] = $post['store'];
        }
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_at'] = [['>=',$min_time],['<=',$max_time]];
        }
        $list = Db::name('store_evaluate')
            ->where($where)
            ->order('create_at desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        foreach($list as $k=>$v){
            if ($v['status']==0){
                $list[$k]['status']='关闭';
            }elseif($v['status']==1){
                $list[$k]['status']='开启';
            }
            $storeid=Db::name('store')->where('id', $v['storeid'])->find();
            $list[$k]['storeid']=$storeid['name'];
            $storeid=Db::name('user')->where('openid', $v['openid'])->find();
            $list[$k]['user']=$storeid['nickname'];
        }
        $store = Db::name('store')->where('status', 1)->select();
        $this->assign('store', $store);
        $this->assign('evaluate', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }
    /**
     * 评价修改
     * 宇
     */
    public function publish()
    {
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if ($id > 0) {
            //是修改操作
            if ($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                $info = Db::name('store_evaluate')->where('id', $id)->find();
                if (empty($info)) {
                    return $this->error('id不正确');
                }
                if (false == Db::name('store_evaluate')->strict(false)->update($post)) {
                    return $this->error('修改失败');
                } else {
                    return $this->success('修改成功', 'admin/evaluate/index');
                }
            } else {
                //非提交操作
                $info = Db::name('store_evaluate')->where('id', $id)->find();
                $user= Db::name('user')->where('openid', $info['openid'])->find();
                $info['uname']=$user['nickname'];
                $info['img_url']=unserialize($info['img_url']);
                foreach($info['img_url'] as $k=>$v){
                    $info['img_url'][$k]="https://shiyi.cg500.com".str_replace('"','',$v);
                }
                //dump($info['img_url']);die;
                $store=Db::name('store')->where('id', $info['storeid'])->find();
                $info['store']=$store['name'];
                //dump($info);die;
                $this->assign('info', $info);
                return $this->fetch();
            }
        }
    }
}