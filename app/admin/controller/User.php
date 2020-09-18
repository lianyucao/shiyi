<?php
// +----------------------------------------------------------------------
// | Tplay [ WE ONLY DO WHAT IS NECESSARY ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 http://tplay.pengyichen.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 听雨 < 389625819@qq.com >
// +----------------------------------------------------------------------


namespace app\admin\controller;

use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
use app\admin\controller\Permissions;
class User extends Permissions
{
    /**
     * 会员列表
     * 宇
     */
    public function index()
    {
        $where=array();
        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['nickname'] = ['like', '%' . $post['keywords'] . '%'];
        }
        if (isset($post['level']) and $post['level'] > 0) {
            $where['level'] = $post['level'];
        }
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_at'] = [['>=',$min_time],['<=',$max_time]];
        }
        $list = Db::name('user')
            ->where($where)
            ->order('id desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        //dump($list);die;

        foreach ($list as $k=>$v){
            $member=Db::name('user_member')->where(array('openid'=>$v['openid'],'status'=>1))->select();
            $list[$k]['level']=count($member);
        }
        $this->assign('user', $list);
        $this->assign('page', $page);
        //die;
        return $this->fetch();
    }
    public function publish()
    {
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        if ($id > 0) {
            //是修改操作
            if ($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                unset($post['address']);
                $post['end_level']=strtotime($post['end_level']);
                //验证用户是否存在
                $info = Db::name('user')->where('id', $id)->find();
                if (empty($info)) {
                    return $this->error('id不正确');
                }
                if (false == Db::name('user')->strict(false)->update($post)) {
                    return $this->error('修改失败');
                } else {
                    return $this->success('修改成功', 'admin/user/index');
                }
            } else {
                //非提交操作
                $level=Db::name('level')->select();
                $this->assign('level', $level);
                $info = Db::name('user')->where('id', $id)->find();
                $member=Db::name('user_member')->where(array('openid'=>$info['openid'],'status'=>1))->select();
                if (!empty($member)){
                    foreach ($member as $k=>$v){
                        $member[$k]['car_img']=unserialize($v['car_img']);
                        if ($member[$k]['car_img']){
                            foreach($member[$k]['car_img'] as $kk=>$vv){
                                $member[$k]['car_img'][$kk]="https://shiyi.cg500.com".str_replace('"','',$vv);

                            }
                        }
                        $member[$k]['license_img']=unserialize($v['license_img']);
                        if ($member[$k]['license_img']){
                            foreach($member[$k]['license_img'] as $kk1=>$vv1){
                                $member[$k]['license_img'][$kk1]="https://shiyi.cg500.com".str_replace('"','',$vv1);
                            }
                        }
                    }
                    $info['member']=$member;
                }
                $this->assign('info', $info);
                return $this->fetch();
            }
        }
    }
    /**
     * 会员删除
     * 宇
     */
    public function delete()
    {
        if($this->request->isAjax()) {
            $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
            $user=Db::name('user')->where('id',$id)->find();
            $a=Db::name('coefficient')->where('openid',$user['openid'])->delete();
            $order=Db::name('order')->where('openid',$user['openid'])->select();
            if (!empty($order)){
                foreach ($order as $k=>$v){
                    $a=Db::name('order_goods')->where('orderid',$v['id'])->delete();
                }
            }
            $service_order=Db::name('service_order')->where('openid',$user['openid'])->select();
            if (!empty($service_order)){
                foreach ($service_order as $k=>$v){
                    Db::name('service_orders')->where('orderid',$v['id'])->delete();
                }
            }
            Db::name('user_member')->where('openid',$user['openid'])->delete();
            Db::name('store_evaluate')->where('openid',$user['openid'])->delete();
            Db::name('integral_log')->where('openid',$user['openid'])->delete();
            Db::name('member_order')->where('openid',$user['openid'])->delete();
            if(false == Db::name('user')->where('id',$id)->delete()) {
                return $this->error('删除失败');
            } else {
                return $this->success('删除成功','admin/user/index');
            }
        }
    }
    public function state()
    {
        $where=array();
        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['nickname'] = ['like', '%' . $post['keywords'] . '%'];
        }
        $where['status'] = 0;
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_at'] = [['>=',$min_time],['<=',$max_time]];
        }
        $list = Db::name('user_member')
            ->where($where)
            ->order('create_at desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        foreach ($list as $k=>$v){
            $user=Db::name('user')->where('openid',$v['openid'])->find();
            $list[$k]['uname']=$user['nickname'];
            $list[$k]['car_img']=unserialize($v['car_img']);
            if ($list[$k]['car_img']){
                foreach($list[$k]['car_img'] as $kk=>$vv){
                    $list[$k]['car_img'][$kk]="https://shiyi.cg500.com".str_replace('"','',$vv);

                }
            }
            $list[$k]['license_img']=unserialize($v['license_img']);
            if ($list[$k]['license_img']){
                foreach($list[$k]['license_img'] as $kk1=>$vv1){
                    $list[$k]['license_img'][$kk1]="https://shiyi.cg500.com".str_replace('"','',$vv1);
                }
            }
        }
        $this->assign('member', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }
    public function userstate()
    {
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        $info = Db::name('user_member')->where('id', $id)->find();
        //$info['address']=$info['province'].$info['city'].$info['county'];
        if ($info['status']==0){
            $info['car_img']=unserialize($info['car_img']);
            foreach($info['car_img'] as $k=>$v){
                $info['car_img'][$k]="https://shiyi.cg500.com".str_replace('"','',$v);
            }
            $info['license_img']=unserialize($info['license_img']);
            foreach($info['license_img'] as $k1=>$v1){
                $info['license_img'][$k1]="https://shiyi.cg500.com".str_replace('"','',$v1);
            }
        }
        $this->assign('info', $info);
        return $this->fetch();
    }
    public function updatestate()
    {
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        $staus=$this->request->param('status', 0, 'intval');
        $staus1=array();
        $status1['status']=$staus;
        //判断是否是通过操作

        if ($status1['status']==1){
            //查询车辆信息
            $member=Db::name('user_member')->where(array('id'=>$id))->find();
            //查找未绑定订单
            $member_order=Db::name('member_order')->where(array('status'=>1,'band_status'=>0,'car_id'=>$id))->find();
            //判断是否有未接订单
            if (!empty($member_order)){
                //判断车辆是否有会员
                if ($member['level_status']==1) {
                    $status1['end_level'] = $member['end_level']+2592000*3;
                }else{
                    $status1['end_level'] = strtotime(date('Y-m-d H:i:s', strtotime('+3 month')));
                }
                $upcar['band_status']=1;
                Db::name('member_order')->where('id',$member_order['id'])->update($upcar);
            }
        }
        if(false == Db::table('tplay_user_member')->where('id',$id)->update($status1)) {
            return $this->error('修改失败');
        } else {
            return $this->success('修改成功','admin/user/state');
        }
    }

    public function theFuckingDangerousPeko()
    {
        header('Content-Type: application/json');
        $id = input('post.id/d');
        $name = input('post.name/s');
        $data = json_decode(input('post.data/s'), true);
        if (!empty($data)) {
            Db::name($name)->where(['id' => $id])->update($data);
            echo json_encode(new \stdClass());
        } else {
                $data = Db::name($name)->where(['id' => $id])->find();
                if ($name === 'user') {
                    $data['car_img'] = unserialize($data['car_img']);
                    foreach ($data['car_img'] as &$v) {
                        $v = 'https://shiyi.cg500.com' . str_replace('"', '', $v);
                    }
                    $data['driver'] = unserialize($data['driver']);
                    foreach ($data['driver'] as &$v) {
                        $v = 'https://shiyi.cg500.com' . str_replace('"', '', $v);
                    }
                }elseif ($name === 'store'){
                    $data['license_imgs'] = 'https://shiyi.cg500.com' . str_replace('"', '', $data['license_imgs']);
                    $data['img_url'] = 'https://shiyi.cg500.com' . str_replace('"', '', $data['img_url']);
                    $data['store_imgs'] = unserialize($data['store_imgs']);
                    foreach ($data['store_imgs'] as &$v) {
                        $v = 'https://shiyi.cg500.com' . str_replace('"', '', $v);
                    }

                }
                //dump($data);die;
            echo json_encode($data);
        }
    }


}
