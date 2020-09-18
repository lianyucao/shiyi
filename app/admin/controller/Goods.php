<?php


namespace app\admin\controller;

use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
use app\admin\controller\Permissions;
class Goods extends Permissions
{
    /**
     * 商品列表
     * 宇
     */
    public function index()
    {
        $where=array();
        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['name'] = ['like', '%' . $post['keywords'] . '%'];
        }
        if (isset($post['type']) and $post['type'] > 0) {
            $where['type'] = $post['type'];
        }
        if(isset($post['create_time_range']) and !empty($post['create_time_range'])) {
            list($min_time, $max_time) = explode(' - ', $post['create_time_range']);
            $min_time = strtotime($min_time);
            $max_time = strtotime($max_time);
            $where['create_at'] = [['>=',$min_time],['<=',$max_time]];
        }
        $where['status'] = 1;
        /*$where['stock'] = ['>=',1];*/
        $list = Db::name('goods')
            ->where($where)
            ->order('sort desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        foreach($list as $k=>$v){
            if ($v['status']==0){
                $list[$k]['status']='下架';
            }elseif($v['status']==1){
                $list[$k]['status']='在售';
            }
        }
        $type = Db::name('goods_type')->where('status', 1)->select();
        $this->assign('type', $type);
        $this->assign('goods', $list);
        $this->assign('page', $page);
        //dump($list['actual_purchases']);die;
        return $this->fetch();
    }
    /**
     * 售罄商品
     * 宇
     */
    public function out()
    {
        $where=array();
        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['name'] = ['like', '%' . $post['keywords'] . '%'];
        }
        if (isset($post['type']) and $post['type'] > 0) {
            $where['type'] = $post['type'];
        }
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_at'] = [['>=',$min_time],['<=',$max_time]];
        }
        $where['status'] = 1;
        $where['stock'] = 0;
        $list = Db::name('goods')
            ->where($where)
            ->order('sort desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        foreach($list as $k=>$v){
            if ($v['status']==0){
                $list[$k]['status']='下架';
            }elseif($v['status']==1){
                $list[$k]['status']='在售';
            }
        }
        $type = Db::name('goods_type')->where('status', 1)->select();
        $this->assign('type', $type);
        $this->assign('goods', $list);
        $this->assign('page', $page);
        //dump($list['actual_purchases']);die;
        return $this->fetch();
    }
    /**
     * 下架商品
     * 宇
     */
    public function warehouse()
    {
        $where=array();
        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['name'] = ['like', '%' . $post['keywords'] . '%'];
        }
        if (isset($post['type']) and $post['type'] > 0) {
            $where['type'] = $post['type'];
        }
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_at'] = [['>=',$min_time],['<=',$max_time]];
        }
        $where['status'] = 0;
        $list = Db::name('goods')
            ->where($where)
            ->order('sort desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        foreach($list as $k=>$v){
            if ($v['status']==0){
                $list[$k]['status']='下架';
            }elseif($v['status']==1){
                $list[$k]['status']='在售';
            }
        }
        $type = Db::name('goods_type')->where('status', 1)->select();
        $this->assign('type', $type);
        $this->assign('goods', $list);
        $this->assign('page', $page);
        //dump($list['actual_purchases']);die;
        return $this->fetch();
    }
    /**
     * 商品添加修改
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
                //var_dump($post['info']);die;
                //验证商品是否存在
                $info = Db::name('goods')->where('id', $id)->find();
                if (empty($info)) {
                    return $this->error('id不正确');
                }
                $post['goods_imgs']=array();
                if (!empty($post['file_urls'])){
                    foreach($post['file_urls'] as $imgurl){
                        $img_info = Db::name('goods_imgs')->where('img_url', $imgurl)->find();
                        if(!empty($img_info)){
                            $post['goods_imgs'][]=$imgurl;
                        }
                    }
                }
                $post['goods_imgs']=serialize($post['goods_imgs']);
                if (!empty($post['file_url'])){
                    $post['goods_img']=$post['file_url'];
                }

                //$post['label']=serialize($post['label']);
                $post['create_at']=time();
                unset($post['file_urls']);
                unset($post['file_url']);
                if (false == Db::name('goods')->strict(false)->update($post)) {
                    return $this->error('修改失败');
                } else {
                    return $this->success('修改成功', 'admin/goods/index');
                }
            } else {
                //非提交操作
                $info = Db::name('goods')->where('id', $id)->find();
                $type = Db::name('goods_type')->where('status', 1)->select();
                $label = Db::name('goods_label')->where('status', 1)->select();
                $goods_imgs=unserialize($info['goods_imgs']);
                //$label1=unserialize($info['label']);
                $this->assign('imgs', $goods_imgs);
                //dump($goods_imgs);die;
                $this->assign('type', $type);
                //$this->assign('label', $label);
                $this->assign('info', $info);
                //$this->assign('label1', $label1);
                return $this->fetch();
            }
        } else {
            //是新增操作
            if ($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                $post['goods_imgs']=array();
                if (!empty($post['file_urls'])){
                    foreach($post['file_urls'] as $imgurl){
                        $img_info = Db::name('goods_imgs')->where('img_url', $imgurl)->find();
                        if(!empty($img_info)){
                            $post['goods_imgs'][]=$imgurl;
                        }
                    }
                }
                if (empty($post['file_url'])){
                    return $this->error('请添加商品缩略图!');
                }
                $post['goods_imgs']=serialize($post['goods_imgs']);
                $post['goods_img']=$post['file_url'];
                //$post['label']=serialize($post['label']);
                $post['create_at']=time();
                unset($post['file_urls']);
                unset($post['file_url']);
                //dump($post);die;
                if (false == Db::name('goods')->strict(false)->insert($post)) {
                    return $this->error('添加失败');
                } else {
                    return $this->success('添加成功', 'admin/goods/index');
                }
            } else {
                $type = Db::name('goods_type')->where('status', 1)->select();
                $this->assign('type', $type);
                //$label = Db::name('goods_label')->where('status', 1)->select();
                //$this->assign('label', $label);
                //$this->assign('label1', array());
                return $this->fetch();
            }
        }
    }
    /**
     * 商品缩略图上传
     * 宇
     */
    public function file_url()
    {
        //dump($this->request->file('file'));die;
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
     * 商品详情图上传
     * 宇
     */
    public function file_urls()
    {
        if($this->request->file('file')){
            $file = $this->request->file('file');
        }else{
            $res['code']=1;
            $res['msg']='没有上传文件';
            return json($res);
        }
        $module='admin';
        $use='goods_imgs';
        $web_config = Db::name('webconfig')->where('web','web')->find();
        $info = $file->validate(['size'=>$web_config['file_size']*1024,'ext'=>$web_config['file_type']])->rule('date')->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $module . DS . $use);
        if($info) {
            $data=array();
            $data['img_url']=DS . 'uploads' . DS . $module . DS . $use . DS . $info->getSaveName();
            Db::name('goods_imgs')->insert($data);
            $res['url'] = DS . 'uploads' . DS . $module . DS . $use . DS . $info->getSaveName();
            $res['code'] = 200;
            return json($res);
        } else {
            // 上传失败获取错误信息
            return $this->error('上传失败：'.$file->getError());
        }
    }
    /**
     * 商品详情图删除
     * 宇
     */
    public function img_del()
    {
        if($this->request->isAjax()) {
            $img_url = $this->request->param('imgurl');
            $c=unlink(ROOT_PATH ."public".$img_url);
            //dump($c);
            if(false == Db::name('goods_imgs')->where('img_url',$img_url)->delete()) {
                return to_json('500','删除失败');
            } else {
                return to_json('200','删除成功');
            }
        }
    }
    //商品删除
    public function del()
    {
        if($this->request->isAjax()) {
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            if(false == Db::name('goods')->where('id',$id)->delete()) {
                return $this->error('删除失败');
            } else {
                return $this->success('删除成功','admin/goods/index');
            }
        }
    }








    /**
     * 标签主页
     * 宇
     */
    public function label()
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
        $list = Db::name('goods_label')
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
        }
        $this->assign('label', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }
    /**
     * 标签删除
     * 宇
     */
    public function label_del()
    {
        if($this->request->isAjax()) {
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            if(false == Db::name('goods_label')->where('id',$id)->delete()) {
                return $this->error('删除失败');
            } else {
                return $this->success('删除成功','admin/goods/label');
            }
        }
    }
    /**
     * 标签添加修改
     * 宇
     */
    public function label_publish()
    {
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if ($id > 0) {
            //是修改操作
            if ($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                //验证标签是否存在
                $info = Db::name('goods_label')->where('id', $id)->find();
                if (empty($info)) {
                    return $this->error('id不正确');
                }
                $count = Db::name('goods_label')->where('name', $post['name'])->count();
                if ($info['name'] != $post['name']) {
                    if ($count > 0) {
                        return $this->error('标签已存在');
                    }
                }
                if (false == Db::name('goods_label')->strict(false)->update($post)) {
                    return $this->error('修改失败');
                } else {
                    return $this->success('修改成功', 'admin/goods/label');
                }
            } else {
                //非提交操作
                $info = Db::name('goods_label')->where('id', $id)->find();
                $this->assign('info', $info);
                return $this->fetch();
            }
        } else {
            //是新增操作
            if ($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                $post['create_at']=time();
                $count = Db::name('goods_label')->where('name', $post['name'])->count();
                if ($count > 0) {
                    return $this->error('标签已存在');
                }
                if (false == Db::name('goods_label')->strict(false)->insert($post)) {
                    return $this->error('添加失败');
                } else {
                    return $this->success('添加成功', 'admin/goods/label');
                }
            } else {
                return $this->fetch();
            }
        }
    }
    /**
     * 商品分类主页
     * 宇
     */
    public function type()
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
        $list = Db::name('goods_type')
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
        }
        $this->assign('type', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }
    /**
     * 商品分类删除
     * 宇
     */
    public function type_del()
    {
        if($this->request->isAjax()) {
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            if(false == Db::name('goods_type')->where('id',$id)->delete()) {
                return $this->error('删除失败');
            } else {
                return $this->success('删除成功','admin/goods/label');
            }
        }
    }
    /**
     * 商品分类添加修改
     * 宇
     */
    public function type_publish()
    {
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if ($id > 0) {
            //是修改操作
            if ($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                //验证标签是否存在
                $info = Db::name('goods_type')->where('id', $id)->find();
                if (empty($info)) {
                    return $this->error('id不正确');
                }
                $count = Db::name('goods_type')->where('name', $post['name'])->count();
                if ($info['name'] != $post['name']) {
                    if ($count > 0) {
                        return $this->error('标签已存在');
                    }
                }
                if (false == Db::name('goods_type')->strict(false)->update($post)) {
                    return $this->error('修改失败');
                } else {
                    return $this->success('修改成功', 'admin/goods/type');
                }
            } else {
                //非提交操作
                $info = Db::name('goods_type')->where('id', $id)->find();
                $this->assign('info', $info);
                return $this->fetch();
            }
        } else {
            //是新增操作
            if ($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                $post['create_at']=time();
                $count = Db::name('goods_type')->where('name', $post['name'])->count();
                if ($count > 0) {
                    return $this->error('标签已存在');
                }
                if (false == Db::name('goods_type')->strict(false)->insert($post)) {
                    return $this->error('添加失败');
                } else {
                    return $this->success('添加成功', 'admin/goods/type');
                }
            } else {
                return $this->fetch();
            }
        }
    }
}
