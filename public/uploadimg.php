<?php

use think\Db;

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