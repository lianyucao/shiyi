<?php
namespace app\admin\controller;
use app\admin\controller\Permissions;
use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
class Swithdrawal extends Permissions
{
    public function index(){
        $where=array();
        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['storename'] = ['like', '%' . $post['keywords'] . '%'];
        }
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_at'] = [['>=',$min_time],['<=',$max_time]];
        }
        if (empty($post['storeid'])){
            $where['status'] = 0;
        }else{
            $where['store_id'] = $post['storeid'];
        }
        $list = Db::name('swithdrawal')
            ->where($where)
            ->order('create_at desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        foreach ($list as $k=>$v){
            if ($v['status']==0){
                $list[$k]['status']='待打款';
            }else{
                $list[$k]['status']='已打款';
            }
        }
        $this->assign('swithdrawal', $list);
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
                if (empty($post['banksn'])){
                    return $this->error('请输入银行流水号!');
                }
                $post['status']=1;
                $info = Db::name('swithdrawal')->where('id', $id)->find();
                if (empty($info)) {
                    return $this->error('id不正确');
                }
                if (false == Db::name('swithdrawal')->strict(false)->update($post)) {
                    return $this->error('修改失败');
                } else {
                    return $this->success('修改成功', 'admin/swithdrawal/index');
                }
            } else {
                //非提交操作
                $info = Db::name('swithdrawal')->where('id', $id)->find();
                $this->assign('info', $info);
                return $this->fetch();
            }
        }
    }
}