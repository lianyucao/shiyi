<?php
namespace app\admin\controller;

use app\admin\controller\Permissions;
use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
class Servicetype extends Permissions
{
    /**
     * 服务类别列表
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
        $list = Db::name('service_type')
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
        }
        $this->assign('type', $list);
        $this->assign('page', $page);
        //dump($list['actual_purchases']);die;
        return $this->fetch();
    }
    /**
     * 服务类别创建/修改
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
                $info = Db::name('service_type')->where('id', $id)->find();
                if (empty($info)) {
                    return $this->error('id不正确');
                }
                if (false == Db::name('service_type')->strict(false)->update($post)) {
                    return $this->error('修改失败');
                } else {
                    return $this->success('修改成功', 'admin/servicetype/index');
                }
            } else {
                //非提交操作
                $info = Db::name('service_type')->where('id', $id)->find();
                $this->assign('info', $info);
                return $this->fetch();
            }
        } else {
            //是新增操作
            if ($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                $post['create_at']=time();
                if (false == Db::name('service_type')->strict(false)->insert($post)) {
                    return $this->error('添加失败');
                } else {
                    return $this->success('添加成功', 'admin/servicetype/index');
                }
            } else {
                return $this->fetch();
            }
        }
    }
    /**
     * 服务类别图标
     * 宇
     */
    public function img_url()
    {
        if($this->request->file('file')){
            $file = $this->request->file('file');
        }else{
            $res['code']=1;
            $res['msg']='没有上传文件';
            return json($res);
        }
        $module='admin';
        $use='goods_img';
        $web_config = Db::name('webconfig')->where('web','web')->find();
        $info = $file->validate(['size'=>$web_config['file_size']*1024,'ext'=>$web_config['file_type']])->rule('date')->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $module . DS . $use);
        if($info) {
            $res['url'] = DS . 'uploads' . DS . $module . DS . $use . DS . $info->getSaveName();
            $res['code'] = 200;
            return json($res);
        } else {
            // 上传失败获取错误信息
            return $this->error('上传失败：'.$file->getError());
        }
    }
    /**
     * 服务类别删除
     * 宇
     */
    public function del()
    {
        if($this->request->isAjax()) {
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            if(false == Db::name('service_type')->where('id',$id)->delete()) {
                return $this->error('删除失败');
            } else {
                return $this->success('删除成功','admin/service_type/index');
            }
        }
    }
}