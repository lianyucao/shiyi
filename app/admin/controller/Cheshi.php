<?php
namespace app\admin\controller;

use app\admin\controller\Permissions;
use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
class Cheshi extends Permissions
{
    /**
     * 门店列表
     * 宇
     */
    public function index()
    {
        $where=array();
        $post = $this->request->param();
        $where['status'] = 1;
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
        $list = Db::name('store')
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
            }elseif($v['status']==-1){
                $list[$k]['status']='待审核';
            }elseif($v['status']==-2){
                $list[$k]['status']='驳回';
            }
            $list[$k]['img_url']="https://shiyi.cg500.com".str_replace('"','',$v['img_url']);
        }
        $this->assign('store', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }
    /**
     * 门店创建/修改
     * 宇
     */
    public function publish()
    {

        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if ($id > 0) {
            //是修改操作
            if ($this->request->isPost()) {
                $post = $this->request->post();
                $info = Db::name('store')->where('id', $id)->find();
                if (empty($info)) {
                    return $this->error('id不正确');
                }
                //$post['servicetypeid']=serialize($post['servicetypeid']);
                dump($post['servicetypeid']);die;



                $where['city_name']=['like', '%' . $post['city_id'] . '%'];
                $city=Db::name('city')->where($where)->find();
                if (empty($city)){
                    return $this->error('请输入正确的城市名称');
                }else{
                    $post['city_id'] = $city['city_id'];
                }

                //dump($post);die;
                if (false == Db::name('store')->strict(false)->update($post)) {
                    return $this->error('修改失败');
                } else {
                    return $this->success('修改成功', 'admin/store/index');
                }
            } else {
                //非提交操作
                $info = Db::name('store')->where('id', $id)->find();
                $info['store_imgs']=unserialize($info['store_imgs']);
                foreach($info['store_imgs'] as $k=>$v){
                    $info['store_imgs'][$k]="https://shiyi.cg500.com".str_replace('"','',$v);
                }
                $info['img_url']="https://shiyi.cg500.com".str_replace('"','',$info['img_url']);
                $info['license_imgs']="https://shiyi.cg500.com".str_replace('"','',$info['license_imgs']);
                $servicetype = Db::name('service_type')->where('status', 1)->select();
                $city=Db::name('city')->where('city_id', $info['city_id'])->find();
                $info['city_id']=$city['city_name'];
                $this->assign('servicetype', $servicetype);
                $this->assign('info', $info);
                $this->assign('servicetypeid', unserialize($info['servicetypeid']));
                return $this->fetch();
            }
        } else {
            //是新增操作
            if ($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                $where['city_name']=['like', '%' . $post['city_id'] . '%'];
                $city=Db::name('city')->where($where)->find();
                if (empty($city)){
                    return $this->error('请输入正确的城市名称');
                }else{
                    $post['city_id'] = $city['city_id'];
                }
                //dump($post);die;
                $post['servicetypeid']=serialize($post['servicetypeid']);
                $post['store_imgs']=serialize($post['store_imgs']);
                $post['create_at']=time();

                if (false == Db::name('store')->strict(false)->insert($post)) {
                    return $this->error('添加失败');
                } else {
                    return $this->success('添加成功', 'admin/store/index');
                }
            } else {
                $servicetype = Db::name('service_type')->where('status', 1)->select();
                $this->assign('servicetype', $servicetype);
                $this->assign('servicetypeid', '');
                return $this->fetch();
            }
        }
    }
    /**
     * 门店图片
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
    public function del()
    {
        if($this->request->isAjax()) {
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            if(false == Db::name('store')->where('id',$id)->delete()) {
                return $this->error('删除失败');
            } else {
                return $this->success('删除成功','admin/store/index');
            }
        }
    }
    public function statusindex()
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
        $where['status'] = -1;
        $list = Db::name('store')
            ->where($where)
            ->order('create_at desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        foreach ($list as &$item) {
            $item['img_url'] = 'https://shiyi.cg500.com' . str_replace('"', '', $item['img_url']);
        }
        $this->assign('store', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }
}
