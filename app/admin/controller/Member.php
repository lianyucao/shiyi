<?php
namespace app\admin\controller;

use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
use app\admin\controller\Permissions;
class Member extends Permissions
{

    //会员信息
    public function index()
    {
        $where=array();
        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['car_number'] = ['like', '%' . $post['keywords'] . '%'];
        }
        if (isset($post['openid']) and !empty($post['openid'])) {
            $where['openid'] = $post['openid'];
        }
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_at'] = [['>=',$min_time],['<=',$max_time]];
        }
        $list = Db::name('user_member')
            ->where($where)
            ->order('id desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        foreach ($list as $k=>$v){
            $user=Db::name('user')->where('openid',$v['openid'])->find();
            $list[$k]['uname']=$user['nickname'];
            if ($v['status']==-2){
                $list[$k]['status']="驳回";
            }elseif ($v['status']==0){
                $list[$k]['status']="待审核";
            }elseif ($v['status']==1){
                $list[$k]['status']="已认证";
            }
            if ($v['level_status']==0){
                $list[$k]['level_status']="会员";
            }elseif ($v['level_status']==1){
                $list[$k]['level_status']="VIP";
            }elseif ($v['level_status']==-1){
                $list[$k]['level_status']="会员已过期";
            }
        }
        $this->assign('member', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    //会员详情

    public function publish()
    {
        $id = $this->request->has('id') ? $this->request->param('id', 0, 'intval') : 0;
        //dump($id);die;
        if ($id > 0) {
            //是修改操作
            if ($this->request->isPost()) {
                //是提交操作
                $post = $this->request->post();
                $post['end_level']=strtotime($post['end_level']);
                //dump($post['end_level']);die;
                //验证用户是否存在
                $info = Db::name('user_member')->where('id', $id)->find();
                if (empty($info)) {
                    return $this->error('id不正确');
                }
                if (false == Db::name('user_member')->strict(false)->update($post)) {
                    return $this->error('修改失败');
                } else {
                    return $this->success('修改成功', 'admin/member/index');
                }
            } else {
                //非提交操作
                $member=Db::name('user_member')->where(array('id'=>$id))->find();
                if (!empty($member)){
                    $member['car_img']=unserialize($member['car_img']);
                    if ($member['car_img']){
                        foreach($member['car_img'] as $kk=>$vv){
                            $member['car_img'][$kk]="https://shiyi.cg500.com".str_replace('"','',$vv);
                        }
                    }

                    $member['license_img']=unserialize($member['license_img']);
                    if ($member['license_img']){
                        foreach($member['license_img'] as $kk1=>$vv1){
                            $member['license_img'][$kk1]="https://shiyi.cg500.com".str_replace('"','',$vv1);
                        }
                    }
                }
                $member['end_level']=date('Y-m-d',$member['end_level']);
                $this->assign('info', $member);
                return $this->fetch();
            }
        }
    }

}