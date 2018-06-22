<?php
namespace app\common\field;

/**
 * 列表页的表格自定义字段 
 */
class Table extends Base
{
    protected static $pagetype = 'table';
    
    /**
     * 把程序中定义的列表字段,转成有字母数组下标key
     * @param array $field
     * @return unknown|unknown[]
     */
    public static function num2letter($field=[]){
        if (empty($field[0])) {
            return $field;
        }
        $array = [
                'type'=>$field[2],
                'name'=>$field[0],
                'title'=>$field[1],
        ];
        if(is_array($field[3])){
            $array['array'] = $field[3];
        }elseif($field[2]=='link'){
            $array['url'] = $field[3];
            $array['target'] = $field[4];
        }elseif($field[2]=='callback'){
            $array['fun'] = $field[3];
            $array['opt'] = $field[4];
        }
        if($field[2]=='select'){   //频道模型那里的栏目不能选择本模型之外的栏目
            $array['sys'] = $field[4];
        }
        return $array;
    }
    
    /**
     * 取得某个字段的表单HTML代码
     * @param array $field 具体某个字段的配置参数, 只能是数据库中的格式,不能是程序中定义的数字下标的格式
     * @param array $info 信息内容
     * @return string[]|unknown[]|mixed[]
     */
    public static function get_tab_field($field=[],$info=[]){
        
        $field = self::num2letter($field);
        
        $name = $field['name'];
        $field_value = $info[$name];

        if(empty($info)){
            return [
                    'title'=>$field['title'],
                    'value'=>'',
            ];
        }
        
        if ( ($show = self::get_item($field['type'],$field,$info)) !='' ) {    //个性定义的表单模板,优先级最高
        
        }elseif ($field['type'] == 'username') {
            $_ar = get_user($field_value);
            $show = "<a href='".get_url('user',$field_value)."' target='_blank'>{$_ar['username']}</a>";        
        }elseif ($field['type'] == 'link') {
            //$field['url'] = str_replace('__id__', $info['id'], $field['url']);
            $field['url'] = preg_replace_callback('/__([\w]+)__/i',function($ar)use($info){return $info[$ar[1]]; }, $field['url']);
            $show = "<a href='{$field['url']}' target='{$field['target']}'>$field_value</a>";
        }elseif($field['type'] == 'select'){
            $mid = 0;
            if($field['sys'] && sort_config($field['sys'])){    //频道模型那里的栏目不能选择本模型之外的栏目
                $sort_arrray =sort_config($field['sys']);
                foreach($sort_arrray AS $rs){
                    if($rs['id']==$field_value){
                        $mid = $rs['mid'];
                    }
                }
                if($mid){
                    foreach ($field['array'] AS $key=>$v){
                        if($sort_arrray[$key]['mid']!=$mid){
                            unset($field['array'][$key]);
                        }
                    }
                }                
            }
            $show = "<select class='select_edit' data-name='$name' data-value='{$field_value}' data-id='{$info['id']}'>";
            foreach($field['array'] AS $key=>$v){
                $select = $field_value==$key ? 'selected' : '' ;
                $show .="<option value='$key' $select>$v";
            }
            $show .= "</select>";
        }elseif($field['type'] == 'switch'||$field['type'] == 'yesno'){
            $show = $field_value ? "<i class='fa fa-check-circle' style='color:green;font-size:16px;'></i>": "<i class='glyphicon glyphicon-ban-circle'></i>" ;
        }elseif($field['type'] == 'icon'){
            $show = $field_value?"<i class='{$field_value}'></i>":'';
        }elseif($field['type'] == 'select2'){
            $show = $field['array'][$field_value];
        }elseif($field['type'] == 'datetime'){
            $show = date('Y-m-d H:i',$field_value);
        }elseif($field['type'] == 'text.edit'){
            $size = 8;
            $_class = '_num';
            if(!is_numeric($field_value)){
                $size = '15';
                $_class = '_string';
            }
            $show = "<input type='text' class='quick_edit {$_class}' data-value='{$field_value}' data-name='$name' data-id='{$info['id']}' name='{$name}[{$info['id']}]' size='$size' value='{$field_value}'>";
        }elseif($field['type'] == 'callback'){
            $field['opt'] = str_replace('__','',$field['opt']);
            if($field['opt']=='data'){
                $qs = $info;
            }else{
                $qs = $info[$field['opt']];
            }
            $show = $field['fun']($field_value,$qs);
        }else{
            $show = $info[$name];
        }

        return [
                'title'=>$field['title'],
                'value'=>$show,
        ];
    }
    
    /**
     * 右边菜单
     * @param array $btns
     * @param array $info
     * @return string[][]|unknown[][]
     */
    public static function get_rbtn($btns=[],$info=[]){
        $data = [];
        foreach($btns AS $rs){
            $rs['icon'] || $rs['icon']='glyphicon glyphicon-menu-hamburger';
            $rs['href'] || $rs['href']=$rs['url'];
            //$rs['href'] = str_replace('__id__', $info['id'], $rs['href']);
            $rs['href'] = preg_replace_callback('/__([\w]+)__/i',function($ar)use($info){return $info[$ar[1]]; }, $rs['href']);
            $alert = $rs['type']=='delete' ? ' class="_dels" onclick="return confirm(\'你确实要删除吗?不可恢复!\')"' : ' ';
            $data[] = [
                    'title'=>$rs['title'],
                    'value'=>"<a href='{$rs['href']}' title='{$rs['title']}' $alert><li class='{$rs['icon']}'></li></a>",
            ];
        }
        return $data;
    }
    
}
