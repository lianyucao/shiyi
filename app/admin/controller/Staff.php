<?php
namespace app\admin\controller;

use app\admin\controller\Permissions;
use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
class Staff extends Permissions
{
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
        if (isset($post['status']) and !empty($post['status'])) {
            $where['status'] = $post['status'];
        }
        $list = Db::name('staff')
            ->where($where)
            ->order('create_at desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        foreach($list as $k=>$v){
            if ($v['status']==0){
                $list[$k]['status']='禁用';
            }elseif($v['status']==1){
                $list[$k]['status']='开启';
            }
            $store=Db::name('store')->where('id',$v['storeid'])->find();
            $list[$k]['storeid']=$store['name'];
        }
        $this->assign('staff', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }
    public function publish()
    {
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if ($id > 0) {
            //是修改操作
            if ($this->request->isPost()) {
                $post = $this->request->post();
                $info = Db::name('staff')->where('id', $id)->find();
                if (empty($info)) {
                    return $this->error('id不正确');
                }
                if (false == Db::name('staff')->strict(false)->update($post)) {
                    return $this->error('修改失败');
                } else {
                    return $this->success('修改成功', 'admin/staff/index');
                }
            } else {
                //非提交操作
                $info = Db::name('staff')->where('id', $id)->find();
                $store = Db::name('store')->where('status', 1)->select();
                $this->assign('info', $info);
                $this->assign('store', $store);
                return $this->fetch();
            }
        } else {
            //是新增操作
            if ($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                $a=preg_match('/^[0-9a-z_$]{6,16}$/i', $post['pwd']);
                if($a==0){
                    return $this->error('密码格式不正确,请输入6-16位数字/字母/下划线!');
                }elseif ($post['pwd']!=$post['pwds']){
                    return $this->error('两次密码不一致请重新输入!');
                }elseif ($a==1 && $post['pwd']==$post['pwds']){
                    $post['pwd']=password($post['pwd']);
                }
                unset($post['pwds']);
                $post['create_at']=time();
                if (false == Db::name('staff')->strict(false)->insert($post)) {
                    return $this->error('添加失败');
                } else {
                    return $this->success('添加成功', 'admin/staff/index');
                }
            } else {
                $store = Db::name('store')->where('status', 1)->select();
                $this->assign('store', $store);
                return $this->fetch();
            }
        }
    }
    public function publishpwd()
    {

        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if ($id > 0) {
            //是修改操作
            if ($this->request->isPost()) {
                $post = $this->request->post();
                $info = Db::name('staff')->where('id', $id)->find();
                if (empty($info)) {
                    return $this->error('id不正确');
                }
                
                $a=preg_match('/^[0-9a-z_$]{6,16}$/i', $post['pwd']);
                if($a==0){
                    return $this->error('密码格式不正确,请输入6-16位数字/字母/下划线!');
                }elseif ($post['pwd']!=$post['pwds']){
                    return $this->error('两次密码不一致请重新输入!');
                }elseif ($a==1 && $post['pwd']==$post['pwds']){
                    $post['pwd']=password($post['pwd']);
                }
                unset($post['pwds']);
                if (false == Db::name('staff')->strict(false)->update($post)) {
                    return $this->error('修改失败');
                } else {
                    return $this->success('修改成功', 'admin/staff/index');
                }
            } else {
                $info = Db::name('staff')->where('id', $id)->find();
                $this->assign('info', $info);
                return $this->fetch();
            }
        }
    }
    public function del()
    {
        if($this->request->isAjax()) {
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            if(false == Db::name('staff')->where('id',$id)->delete()) {
                return $this->error('删除失败');
            } else {
                return $this->success('删除成功','admin/staff/index');
            }
        }
    }
}