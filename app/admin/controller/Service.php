<?php
namespace app\admin\controller;

use app\admin\controller\Permissions;
use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
class Service extends Permissions
{
    /**
     * 服务列表
     * 宇
     */
    public function index(){
        $where=array();
        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['name'] = ['like', '%' . $post['keywords'] . '%'];
        }
        if (isset($post['typeid']) and $post['typeid'] > 0) {
            $where['typeid'] = $post['typeid'];
        }
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_at'] = [['>=',$min_time],['<=',$max_time]];
        }
        if (isset($post['status']) and !empty($post['status'])) {
            $where['status'] = $post['status'];
        }
        $list = Db::name('service')
            ->where($where)
            ->order('create_at desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        foreach($list as $k=>$v){
            if ($v['status']==0){
                $list[$k]['status']='下架';
            }elseif($v['status']==1){
                $list[$k]['status']='上架';
            }
        }
        $servicetype=Db::name('service_type')->select();
        foreach($list as $k1=>$v1){
            //dump($v1);die;
            foreach($servicetype as $v2){
                if ($v1['typeid']==$v2['id']){
                    $list[$k1]['typeid']=$v2['name'];
                }
            }
        }
        //dump($servicetype);die;
        $this->assign('service', $list);
        $this->assign('servicetype', $servicetype);
        $this->assign('page', $page);
        return $this->fetch();
    }

    /**
     * 服务创建/修改
     * 宇
     */
    public function publish()
    {
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if ($id > 0) {
            //是修改操作
            if ($this->request->isPost()) {
                $post = $this->request->post();
                $info = Db::name('goods')->where('id', $id)->find();
                if (empty($info)) {
                    return $this->error('id不正确');
                }
                //dump($post);die;
                if (false == Db::name('service')->strict(false)->update($post)) {
                    return $this->error('修改失败');
                } else {
                    return $this->success('修改成功', 'admin/service/index');
                }
            } else {
                //非提交操作
                $info = Db::name('service')->where('id', $id)->find();
                $servicetype = Db::name('service_type')->where('status', 1)->select();
                $this->assign('servicetype', $servicetype);
                $this->assign('info', $info);
                return $this->fetch();
            }
        } else {
            //是新增操作
            if ($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                $post['create_at']=time();
                if (false == Db::name('service')->strict(false)->insert($post)) {
                    return $this->error('添加失败');
                } else {
                    return $this->success('添加成功', 'admin/service/index');
                }
            } else {
                $servicetype = Db::name('service_type')->where('status', 1)->select();
                $this->assign('servicetype', $servicetype);
                return $this->fetch();
            }
        }
    }
    public function del()
    {
        if($this->request->isAjax()) {
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            if(false == Db::name('service')->where('id',$id)->delete()) {
                return $this->error('删除失败');
            } else {
                return $this->success('删除成功','admin/service/index');
            }
        }
    }
}