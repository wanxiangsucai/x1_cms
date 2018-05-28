<?php
namespace app\common\model;

use think\Model;
//use think\Db;


class User extends Model
{
    //protected static $passport_table = 'members';   //整合论坛的话，就要写上论坛的数据表前缀
	
    // 设置当前模型对应的完整数据表名称memberdata
    protected $table = '__MEMBERDATA__';
	
	//主键不是ID,要单独指定
	public $pk = 'uid';

    // 自动写入时间戳
    protected $autoWriteTimestamp = false;

    /**
     * 根据帐号获取用户信息
     * @param string $name 帐号
     * @return unknown
     */
    public static function getByName($name = '')
    {
        $result = self::get(['username' => $name]);
        return is_object($result) ? $result->toArray() : $result;
    }
	
    /**
     * 根据UID获取用户信息
     * @param string $id 用户UID
     * @return unknown
     */
	public static function getById($id = '')
    {
        $result = self::get(['uid' => $id]);
        return is_object($result) ? $result->toArray() : $result;
    }
	
	
	/**
	 * 获取某个用户的所有信息
	 * @param unknown $value 可以是数组
	 * @param string $type 可以取任何字段
	 * @return \app\common\model\User|NULL
	 */
	public static function get_info($value,$type='uid'){
	    if(is_array($value)){
	        $map = $value;
	    }elseif($type=='name'){
	        $map['username'] = $value;
	    }elseif(preg_match('/^[\w]+$/', $type)){
	        $map[$type] = $value;
	    }
	    $result = self::get($map);
	    return is_object($result) ? $result->toArray() : $result;
	}
	
	/**
	 * 检查密码是否正确,密码正确,返回用户所有信息, 用户不存在,返回0, 密码不正确返回-1
	 * @param string $username 默认是用户帐号,也可以是UID或手机号,要重新定义$type值
	 * @param string $password 密码,也可以是加密后的密码,但用的很少,一般是原始密码
	 * @param string $type 对应第一项的字段,默认是username
	 * @param string $checkmd5
	 * @return number|unknown
	 */
	public static function check_password($username='',$password='',$type='username',$checkmd5=false){
	    $rs = self::get_info($username,$type);
		if(!$rs){
			return 0;
		}
		if($checkmd5===true && strlen($password)==32 && $password==$rs['password'] ){
		    return $rs;
		}elseif(static::md5pwd($password,$rs['password_rand'])==$rs['password']){
		    return $rs;
		}
		return -1;
	}
	
	/**
	 * 检查帐号即用户名是否合法,合法返回true,不合法返回false
	 * @param unknown $username
	 * @return boolean
	 */
	public static function check_username($username) {
		$guestexp = '\xA1\xA1|\xAC\xA3|^Guest|^\xD3\xCE\xBF\xCD|\xB9\x43\xAB\xC8';
		$len = strlen($username);
		if($len > 50 || $len < 3 || preg_match("/\s+|^c:\\con\\con|[%,\*\'\"\s\<\>\&]|$guestexp/is", $username)) {
			return FALSE;
		} else {
			return TRUE;
		}
	}
	
	/**
	 * 检查用户名是否存在,存在返回
	 * @param unknown $value
	 * @return unknown
	 */
	public static function check_userexists($value) {
	    $info = self::get(['username'=>$value]);
	    return $info?$info:false;
	}

	/**
	 * 检查邮箱是否存在
	 * @param unknown $value
	 * @return boolean|unknown
	 */
	public static function check_emailexists($value) {
	    $rs = self::get(['email'=>$value]);
	    return $rs?$rs:false;
	}
	
	/**
	 * 用户注册 注册成功,只返回UID数值,不成功,返回对应的提示字符串
	 * @param unknown $array
	 * @return string|mixed
	 */
	public static function register_user($array){
	    
	    if(self::get_info($array['username'],'username')){
	        return '当前用户已经存在了';
	    }
	    if(config('webdb.forbidRegName')!=''){
	        $detail = str_array(config('webdb.forbidRegName'));
	        if(in_array($array['username'], $detail)){
	            return '请换一个用户名,当前用户名不允许使用';
	        }
	    }
	    if(!$array['username']){
	        return '用户名不能为空';
	    }elseif(!$array['email']){
	        return '邮箱不能为空';
	    }elseif(!$array['password']){
	        return '密码不能为空';
	    }elseif(strlen($array['username'])>50||strlen($array['username'])<3){
	        return '用户名不能小于3个字节或大于50个字节';
	    }elseif (strlen($array['password'])>30 || strlen($array['password'])<5){
	        return '密码不能小于5个字符或大于30个字符';
	    }elseif(!preg_match("/^[-a-zA-Z0-9_\.]+\@([0-9A-Za-z][0-9A-Za-z-]+\.)+[A-Za-z]{2,5}$/",$array['email'])){
	        return '邮箱不符合规则';
	    }elseif( config('webdb.emailOnly') && self::check_emailexists($array['email'])){
	        return "当前邮箱“{$array['email']}”已被注册了,请更换一个邮箱!";
	    }
	    
	    $S_key=array('|',' ','',"'",'"','/','*',',','~',';','<','>','$',"\\","\r","\t","\n","`","!","?","%","^");
	    
	    //后来增加
	    $array['username'] = str_replace(array('|',' ','',"'",'"','/','*',',','~',';','<','>','$',"\\","\r","\t","\n","`","!","?","%","^"),'',$array['username']);
	    
	    foreach($S_key as $value){
	        if (strpos($array['username'],$value)!==false){
	            return "用户名中包含有禁止的符号“{$value}”";
	        }
	        if (strpos($array['password'],$value)!==false){
	            //return "密码中包含有禁止的符号“{$value}”";
	        }
	    }
	    if($array['username']==''){
	        return '用户名为空了!';
	    }
	    
	    foreach($array AS $key=>$value){
	        $array[$key] = filtrate($value);
	    }
	    hook_listen('user_add_begin',$array);

	    if(($array['uid'] = static::insert_data($array))==false){
	        return "创建用户失败";
	    }
	    hook_listen('user_add_end',$array);
	    return $array['uid'];
	}
	
	/**
	 * 注册用户信息入库,成功返回uid,失败返回false
	 * @param unknown $array
	 * @return boolean|unknown
	 */
	protected static function insert_data($array){
		$array['groupid'] || $array['groupid']=8;
		isset($array['yz']) || $array['yz']=1;
		$array['regdate'] = time();
		$array['lastvist'] = time();
		$array['regip'] = get_ip();
		$array['lastip'] = get_ip();
        
		//用户昵称
		$array['nickname'] = $array['username'];
		$array['password_rand'] = rands(rand(5,10));
		$array['password'] = static::md5pwd ($array['password'],$array['password_rand']);

		if( ($result = self::create($array))!=false){
		    return $result->uid;
		}
		return false;
	}
	
	/**
	 * 修改用户任意信息,修改成功 返回true
	 * @param unknown $array 数值当中必须要存在uid
	 * @return string|boolean
	 */
	public static function edit_user($array) {
        
	    cache('user_'.$array['uid'],null);
	    
	    hook_listen('user_edit_begin',$array);
		
	    if( config('webdb.emailOnly') && $array['email'] ){
	        $r = self::check_emailexists($array['email']);
	        if($r && $r['uid']!=$array['uid']){
	            return "当前邮箱存在了,请更换一个!";
	        }
	    }
	    
	    if($array['password'] && strlen($array['password'])<32){
	        $array['password_rand'] = rands(rand(5,10));
	        $array['password'] = static::md5pwd($array['password'],$array['password_rand']);
	    }else{
	        unset($array['password'],$array['password_rand']);
	    }
		
		if(self::update($array)){
		    cache('user_'.$array['uid'],null);
		    hook_listen('user_edit_end',$array);
		    return true;
		}else{
		    return '数据库修改失败';
		}
	}

	
	/**
	 * 删除会员
	 * @param unknown $uid
	 * @return boolean
	 */
	public static function delete_user($uid=0) {
	    hook_listen('user_delete_begin',$uid);

		if(self::destroy($uid)){
		    cache('user_'.$uid,null);
		    hook_listen('user_delete_end',$uid);
		    return true;
		}
	}
	
	/**
	 * 获取会员总数
	 * @param array $map 查询条件
	 * @return mixed
	 */
	public static function total_num($map = []) {
	    return self::where($map)->count('uid');
	}
	
	/**
	 * 获取一批会员资料信息
	 * @param array $map 查询条件
	 * @param number $rows 每页几条
	 * @param string $order 排序方式
	 * @param array $pages 分页格式
	 * @return unknown
	 */
	public static function get_list($map=[], $rows=10, $order='uid desc',$pages=[]) {
	    $data_list = self::where($map)->order($order)->paginate(
	            empty($rows)?null:$rows,    //每页显示几条记录
	            empty($pages[0])?false:$pages[0],
	            empty($pages[1])?[]:$pages[1]
	           );
	    $data_list->each(function($rs,$key){
	        $rs['icon'] && $rs['icon'] = tempdir($rs['icon']);
	    });
	    return $data_list;
	}
	
	
	
	/**
	 * 用户登录,登录成功返回用户的所有信息, 0代表用户不存在,-1代表密码错误
	 * @param string $username 用户名或者是手机号
	 * @param string $password 原始密码
	 * @param unknown $cookietime 登录有效时长
	 * @param string $not_pwd 是否不需要密码,比如QQ或微信登录
	 * @param string $type 用户的方式,帐号还是手机号还是邮箱
	 * @return number|unknown 登录成功返回用户的所有信息, 0代表用户不存在,-1代表密码错误
	 */
	public static function login($username='',$password='',$cookietime=null,$not_pwd=false,$type='username'){
	    if(!table_field('memberdata','password_rand')){    //升级数据库
	        into_sql(APP_PATH.'common/upgrade/5.sql');
	    }
	    $array = [
	            'username'=>$username,
	            'password'=>$password,
	            'time'=>$cookietime,
	            'not_pwd'=>$not_pwd,
	            'type'=>$type,
	    ];
	    hook_listen('user_login_begin', $array);
	    if($username==''){
            return 0;
        }
		if($not_pwd){	//不需要知道原始密码就能登录
		    $rs = static::get_info($username,$type);
		}else{
		    $rs = static::check_password($username,$password,$type);
			if(!is_array($rs)){
				return $rs;		//0为用户不存在,-1为密码不正确
			}
			
			$data = [
			        'uid'=>$rs['uid'],
			        'lastvist'=>time(),
			        'lastip'=>get_ip(),
			];
			self::edit_user($data);
		}

		set_cookie("passport","{$rs['uid']}\t$username\t".mymd5($rs['password'],'EN'),$cookietime);

		$array = [
		        'uid'=>$rs['uid'],
		        'username'=>$username,
		        'password'=>$password,
		        'time'=>$cookietime,
		        'not_pwd'=>$not_pwd,
		        'type'=>$type,
		];
		hook_listen('user_login_end', $array);
		return $rs;
	}
	
	/**
	 * 用户退出
	 * @param number $uid
	 */
	public static function quit($uid=0){
		set_cookie('passport',null);
		cache('user_'.$uid,null);
		set_cookie('token_secret','');
		setcookie('adminID','',0,'/');	//同步后台退出
		hook_listen('user_quit_end',$uid);
	}
	
	/**
	 * 获取用户的登录token
	 * @return unknown[]|array[]
	 */
	public static  function get_token(){
	    $token = input('token');
	    if($token && cache($token)){   //APP或小程序
	        list($uid,$username,$password) = explode("\t",cache($token));
	        if($uid&&$username&&$password){
	            return ['uid'=>$uid,'username'=>$username,'password'=>$password];
	        }
	    }
	    
	    list($uid,$username,$password) = explode("\t",get_cookie('passport'));
	    if($uid&&$username&&$password){
	        return ['uid'=>$uid,'username'=>$username,'password'=>$password];
	    }
	}
	
	/**
	 * 用户登录状态的信息
	 * @return void|mixed|\think\cache\Driver|boolean
	 */
	public static function login_info(){        
	    if(!$token=self::get_token()){
	        return false;
	    }	    
	    $usr_info = cache('user_'.$token['uid']);
	    if(empty($usr_info['password'])){
	        $usr_info = self::get_info(intval($token['uid']));
	        cache('user_'.$usr_info['uid'],$usr_info,3600);
	    }
	    if( mymd5($usr_info['password'],'EN') != $token['password'] ){
	        self::quit($usr_info['uid']);
	        return false;
		}
		return $usr_info;
	}

	/**
	 * 检查微信openid是否存在
	 * @param unknown $openid
	 * @return unknown
	 */
	public static function check_wxIdExists($openid) {
		return self::get(['weixin_api'=>$openid]);
	}
	
	/**
	 * 检查QQ的openid是否存在
	 * @param unknown $openid
	 * @return unknown
	 */
	public static function check_qqIdExists($openid) {
	    return self::get(['qq_api'=>$openid]);
	}
	
	/**
	 * 检查小程序openid是否存在
	 * @param unknown $openid
	 * @return unknown
	 */
	public static function check_wxappIdExists($openid) {
	    return self::get(['wxapp_api'=>$openid]);
	}
	
	/**
	 * 密码加密方式
	 * @param string $password 原始密码
	 * @param string $pwdRand 随机串
	 * @return string
	 */
	protected static function md5pwd($password='',$pwdRand=''){
	    switch (config('md5_pwd_type')){
	        case 1:
	            return md5(md5($password).$pwdRand);
	            break;
	        case 2:
	            return md5($password.md5($pwdRand));
	            break;
	        case 3:
	            return md5(md5($password.$pwdRand));
	            break;
	        default:
	            return md5($password.$pwdRand);
	    }
	}
	
	/**
	 * 会员标签调用数据
	 * @param unknown $tagArray
	 * @param number $page
	 * @return string
	 */
	public static function labelGet($tagArray , $page=0)
	{
	    $map = [];
	    $cfg = unserialize($tagArray['cfg']);
	    $cfg['rows'] || $cfg['rows'] = 10;
	    $cfg['order'] || $cfg['order'] = 'uid';
	    $cfg['by'] || $cfg['by'] = 'desc';
	    
	    $page = intval($page);
	    if ($page<1) {
	        $page=1;
	    }
	    $min = ($page-1)*$cfg['rows'];
	    
	    if($cfg['where']){  //用户自定义的查询语句
	        $_array = label_format_where($cfg['where']);
	        if($_array){
	            $map = array_merge($map,$_array);
	        }
	    }
	    $whereor = [];
	    if($cfg['whereor']){  //用户自定义的查询语句
	        $_array = label_format_where($cfg['whereor']);
	        if($_array){
	            $whereor = $_array;
	        }
	    }
	    $obj = self::where($map)->whereOr($whereor);
	    if(strstr($cfg['order'],'rand()')){
	        $obj -> orderRaw('rand()');
	    }else{
	        $obj -> order($cfg['order'],$cfg['by']);
	    }	    
	    $array = $obj -> paginate($cfg['rows'],false,['page'=>$page]);
	    $array->each(function($rs,$key){
	        $rs['title'] = $rs['username'];
	        $rs['full_lastvist'] = $rs['lastvist'];
	        $rs['lastvist'] = date('Y-m-d H:i',$rs['lastvist']);
	        $rs['full_regdate'] = $rs['regdate'];
	        $rs['regdate'] = date('Y-m-d H:i',$rs['regdate']);
	        $rs['icon'] = $rs['picurl'] = tempdir($rs['icon']);
	        $rs['url'] = get_url('user',['uid'=>$rs['uid']]);
	        $rs['group_name'] = getGroupByid($rs['groupid']);
	        return $rs;
	    });
	    return $array;
	}
	
}