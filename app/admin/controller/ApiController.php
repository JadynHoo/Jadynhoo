<?php
// +----------------------------------------------------------------------
// | vaeThink [ Programming makes me happy ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018 http://www.vaeThink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 听雨 < 389625819@qq.com >
// +---------------------------------------------------------------------
namespace app\admin\controller;
use vae\controller\AdminCheckLogin;

class ApiController extends AdminCheckLogin
{
    //上传文件
    public function upload()
    {
        $param = jt_get_param();
        $module = isset($param['module']) ? $param['module'] : 'admin';
        $use = isset($param['use']) ? $param['use'] : 'thumb';
        $res = jt_upload($module,$use);
        if($res['code'] == 1){
            return jt_assign(1,'',$res['data']);
        }
        return jt_assign(0,$res['msg']);
    }


    public function list_to_tree($list, $group=[], $pk = 'id', $pid = 'pid', $child = 'list', $root = 0)
    {
        // 创建Tree
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] =& $list[$key];
                $refer[$data[$pk]]['name'] = $list[$key]['title'];
                $refer[$data[$pk]]['value'] = $list[$key]['id'];
                if(!empty($group) and in_array($list[$key]['id'], $group)) {
                    $refer[$data[$pk]]['checked'] = true;
                }
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    $tree[$data[$pk]] =& $list[$key];
                    $tree[$data[$pk]]['name'] = $list[$key]['title'];
                    $tree[$data[$pk]]['value'] = $list[$key]['id'];
                    if(!empty($group) and in_array($list[$key]['id'], $group)) {
                        $tree[$data[$pk]]['checked'] = true;
                    }
                } else {
                    if (!empty($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][$data[$pk]] =& $list[$key];
                        $parent[$child][$data[$pk]]['name'] = $list[$key]['title'];
                        $parent[$child][$data[$pk]]['value'] = $list[$key]['id'];
                        if(!empty($group) and in_array($list[$key]['id'], $group)) {
                            $parent[$child][$data[$pk]]['checked'] = true;
                        }
                    }
                }
            }
        }
        return $tree;
    }

    //获取权限树所需的节点列表
    public function getRuleTree()
    {
        $rule = jt_get_admin_rule();
        $group = [];
        if(!empty(jt_get_param('id'))) {
            $group = jt_get_admin_group_info(jt_get_param('id'))['rules'];
        }
        $list = $this->list_to_tree($rule,$group);
        $data['trees'] = $list;
        return jt_assign(0,'',$data);
    }

    //获取菜单树列表
    public function getMenuTree()
    {
        $rule = jt_get_admin_menu();
        $group = [];
        if(!empty(jt_get_param('id'))) {
            $group = jt_get_admin_group_info(jt_get_param('id'))['menus'];
        }
        $list = $this->list_to_tree($rule,$group);
        $data['trees'] = $list;
        return jt_assign(0,'',$data);
    }

    //清空缓存
    public function cacheClear()
    {
        \think\Cache::clear();
        return jt_assign(1,'系统缓存已清空');
    }

    //发送测试邮件
    public function emailto($email)
    {
        $name = empty(jt_get_config('webconfig.admin_title'))?'vaeThink':jt_get_config('webconfig.admin_title');
        if(jt_send_email($email,"一封来自{$name}的测试邮件。")){
            return jt_assign(1,'发送成功，请注意查收');
        }
        return jt_assign(0,'发送失败');
    }

    //修改个人信息
    public function editPersonal()
    {
        return view('admin/edit_personal',[
            'admin'=>jt_get_login_admin()
        ]);
    }

    //保存个人信息修改
    public function editPersonalSubmit()
    {
        if($this->request->isPost()){
            $param = jt_get_param();
            $result = $this->validate($param, 'app\admin\validate\Admin.editPersonal');
            if ($result !== true) {
                return jt_assign(0,$result);
            } else {
                unset($param['username']);
                $aid = jt_get_login_admin('id');
                \think\loader::model('Admin')->where([
                    'id'=>$aid
                ])->strict(false)->field(true)->update($param);
                \think\Session::set('vae_admin',\think\Db::name('admin')->find($aid));
                return jt_assign();
            }
        }
    }

    //修改密码
    public function editpassword()
    {
        return view('admin/edit_password',[
            'admin'=>jt_get_login_admin()
        ]);
    }

    //保存密码修改
    public function editpasswordSubmit()
    {
        if($this->request->isPost()){
            $param = jt_get_param();
            $result = $this->validate($param, 'app\admin\validate\Admin.editpwd');
            if ($result !== true) {
                return jt_assign(0,$result);
            } else {
                $admin = jt_get_login_admin();
                if(jt_set_password($param['old_pwd'],$admin['salt']) !== $admin['pwd']) {
                    return jt_assign(0,'旧密码不正确!');
                }
                unset($param['username']);
                $param['salt']     = jt_set_salt(20);
                $param['pwd'] = jt_set_password($param['pwd'],$param['salt']);
                \think\loader::model('Admin')->where([
                    'id'=>$admin['id']
                ])->strict(false)->field(true)->update($param);
                \think\Session::set('vae_admin',\think\Db::name('admin')->find($admin['id']));
                return jt_assign();
            }
        }
    }
}
