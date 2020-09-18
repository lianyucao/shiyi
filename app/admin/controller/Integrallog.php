<?php
namespace app\admin\controller;
use app\admin\controller\Permissions;
use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
class Integrallog extends Permissions
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
        $list = Db::name('integral_log')
            ->where($where)
            ->order('create_at desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        foreach($list as $k=>$v){
            $user=Db::name('user')->where('openid',$v['openid'])->find();
            $list[$k]['uname']=$user['nickname'];
            if ($v['status']==0){
                $list[$k]['integral']='-'.$list[$k]['integral'];
            }elseif($v['status']==1){
                $list[$k]['integral']='+'.$list[$k]['integral'];
            }
        }
        $this->assign('integrallog', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }
}