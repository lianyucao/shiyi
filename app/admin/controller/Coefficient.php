<?php
namespace app\admin\controller;

use \think\Db;
use \think\Cookie;
use \think\Session;
use app\admin\model\Admin as adminModel;//管理员模型
use app\admin\model\AdminMenu;
use app\admin\controller\Permissions;
class Coefficient extends Permissions
{
    /**
     * 会员系数列表
     * 宇
     */
    public function index()
    {
        $web['member_num'] = Db::name('user_member')->where('level_status', 1)->count();
        $web['totalcoefficient'] = Db::name('webconfig')->find()['totalcoefficient'];
        $web['precipitate'] = Db::name('webconfig')->find()['precipitate'];
        $this->assign('web', $web);
        $where=array();
        $post = $this->request->param();
        if (isset($post['keywords']) and !empty($post['keywords'])) {
            $where['nickname'] = ['like', '%' . $post['keywords'] . '%'];
        }
        if(isset($post['create_time']) and !empty($post['create_time'])) {
            $min_time = strtotime($post['create_time']);
            $max_time = $min_time + 24 * 60 * 60;
            $where['create_at'] = [['>=',$min_time],['<=',$max_time]];
        }
        $list = Db::name('coefficient')
            ->where($where)
            ->order('create_at desc')
            ->paginate(20, false, ['query' => $this->request->param()]);
        $page = $list->render();
        $list = $list->all();
        $this->assign('coefficient', $list);
        $this->assign('page', $page);
        $webconfig=Db::name('webconfig')->find();
        $this->assign('webconfig', $webconfig);
        return $this->fetch();
    }
    public function publish()
    {
        if ($this->request->isPost()) {
            //是提交操作
            $post = $this->request->post();
            $webconfig=Db::name('webconfig')->find();
            $totalcoefficient=array();
            $totalcoefficient['totalcoefficient']=$post['coefficient']+$webconfig['totalcoefficient'];
            DB::table('tplay_webconfig')->where('web','web')->update($totalcoefficient);

            $admin_id=Session::get("admin");
            $admin=Db::name('admin')->where('id',$admin_id)->find();
            $post['nickname']=$admin['nickname'];
            $post['info']="平台补贴";
            $post['coefficient']="+".$post['coefficient'];
            $post['create_at']=time();
            if (false == Db::name('coefficient')->strict(false)->insert($post)) {
                return $this->error('添加失败');
            } else {
                return $this->success('补贴成功', 'admin/coefficient/index');
            }
        } else {
            return $this->fetch();
        }
    }

}
