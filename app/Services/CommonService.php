<?php
/**
 * Created by PhpStorm.
 * User: Gary.F.Dong
 * Date: 17-11-13
 * Time: 下午2:34
 * Desc:
 */

namespace App\Services;


use App\Models\Permission;
use App\Models\Role;

class CommonService
{
    public static function getRoles()
    {
        $allRoles = Role::get(['id','display_name']);
        $arr = [];
        foreach ($allRoles as $role) {
            $arr[$role['id']] = $role['display_name'];
        }
        $arr = [1=>'sad'];
        return $arr;
    }

    public static function getTopPermissions()
    {
        $topPermissions = Permission::where('fid', 0)->orderBy('sort', 'asc')->orderBy('id', 'asc')->get();
        $arr = [];
        foreach ($topPermissions as $topPermission) {
            $arr[$topPermission['id']] = $topPermission['display_name'] . '[' . $topPermission->name . ']';
        }
        $arr[0] = '--顶级权限--';
        return $arr;
    }

    /**
     * 感觉这样做并不是很好，目前还没有好的思路，先这样，后续有好的想法再来完善
     * @param bool $hidewx
     * @return array
     */
    public static function getMenus($hidewx = true)
    {
        //获取当前的路由控制器部分
        $current_route = Request::route()->getName();
        $curRoutes = explode('.', $current_route);
        $curRoutes = $curRoutes[1];
        $fmenus = Permission::where('fid', 0)->where('is_menu', 1)->orderBy('sort', 'asc')->orderBy('id', 'asc')->get()->toArray();
        $token = '';
        if($token = Request::get('token')) $token = ['token' => $token];
        if(!empty($fmenus)){
            foreach ($fmenus as $item) {
                if(($item['name'] !== '#' && $item['name'] !== '##') && !Route::has($item['name'])) {
                    continue;
                }
                if($hidewx){
                    if(str_contains($item['name'], 'wxadm') || $item['name'] == '##') continue;
                }else{
                    if(!str_contains($item['name'], 'wxadm') && $item['name'] != '##') continue;
                }
                if($item['name'] == '##') $item['name'] = '#';
                $class = '';
                if($item['name'] == Route::currentRouteName()) {
                    $class= 'active';
                }

                if(!Auth::guard('admin')->user()->is_super && !Auth::guard('admin')->user()->can($item['name'])){
                    $class = 'hide';
                }
                $item['class'] = $class;
                if($item['name'] == '#'){
                    $url = '#';
                }else{
                    $url = empty($token) ? route($item['name']) : route($item['name'],$token);
                }
                $item['href'] =  ($item['name'] == '#') ? '#' : $url;

                if($item['sub_permission']) {
                    foreach ($item['sub_permission'] as $key => $sub) {
                        if($sub['is_menu']) {
                            /*if(stripos($sub['name'],'.*')){
                                $sub['name'] = str_replace('.*','.index',$sub['name']);
                            }*/
                            if($sub['name'] !== '#' && !Route::has($sub['name'])) {
                                continue;
                            }
                            if($sub['name'] == '#'){
                                $suburl = '#';
                            }else{
                                $suburl = empty($token) ? route($sub['name']) : route($sub['name'],$token);
                            }
                            $sub['href'] = ($sub['name'] == '#') ? '#' : $suburl;
                            $sub['icon'] = $sub['icon_html'] ? $sub['icon_html'] : '<i class="fa fa-caret-right"></i>';
                            $sub['class'] = '';
                            if(str_contains($sub['name'],$curRoutes)) {
                                $sub['class'] = 'active';
                                $item['class'] = $item['class'] . ' active';
                            }
                            if(!Auth::guard('admin')->user()->is_super && !Auth::guard('admin')->user()->can($sub['name'])){
                                $sub['class'] = 'hide';
                            }
                            $item['sub'][] = $sub;
                        }
                    }
                    unset($item['sub_permission']);
                }
                $menus[] = $item;
            }
        }
        return $menus;
    }


}