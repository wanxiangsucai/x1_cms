<?php
namespace plugins\weixin\util;

use app\common\model\Msg AS MsgModel;

class Msg
{
    protected static $wxid;
    protected static $user;
    protected static $content;
    
    protected static function getUrl($array=[]){
        if (empty($array[1])) {
            return ;
        }
        $token = '';
        $user = self::$user;
        if ($user) {
            $token = md5( self::$wxid . time() . $user['lastvist'] . rands(5) );
            cache2(md5($token),"{$user['uid']}\t{$user['username']}\t".mymd5($user['password'],'EN')."\t",3600*24);
        }
        //once=1 出于安全考虑,限制只有一次有效
        if(strstr($array[2],'?')){
            $array[2].='&once=1&token='.$token;
        }else{
            $array[2].='?once=1&token='.$token;
        }
        return $array[1].':'.$array[2].$array[3];
    }
    
    protected static function stringLength($string){
        $re   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        preg_match_all($re, $string, $match);
        return count($match[0]);
    }
    
    
    protected static function sendmsg($content=''){
        $info = MsgModel::where('touid',self::$user['uid'])->order('id desc')->find();
        if (!self::$content) {
            self::$content = $content;
        }
        if ( (time()-strtotime($info['create_time']))>60 || substr(del_html($info['content']) , 0 , 30) != substr(del_html(self::$content) , 0 , 30) ){
            $data = [
                'touid'=>self::$user['uid'],
                'title'=>'微信消息',
                'content'=>self::$content,
                'create_time'=>time(),
            ];
            $result = MsgModel::create($data);
            $id = $result->id;            
        }else{
            $id = $info['id'];
        }
        $user = self::$user;
        $token = md5( self::$wxid . time() . $user['lastvist'] . rands(5) );
        cache2(md5($token),"{$user['uid']}\t{$user['username']}\t".mymd5($user['password'],'EN')."\t",3600*24);
        $url = get_url(murl('member/msg/show',['id'=>$id])).'?once=1&token='.$token;
        return $url;
    }
     
    
    /**
     * 公众号订阅消息
     * @param unknown $openid
     * @param unknown $content
     * @param string $access_token
     * @param array $array
     */
    public static function mp_subscribe($openid,$content,$array=[],$access_token=''){
        $first = "你好!";
        $subject = "来自《".config('webdb.webname')."》的消息";
        
        $content = stripslashes($content);
        preg_match("/(http|https):([^ ]+)(\"|')/is",$content,$_array);
        $url = $_array[2] ? "$_array[1]:$_array[2]" : request()->domain();
        $content = preg_replace('/<([^<]*)>/is',"",$content);
        if (self::stringLength($content)>20) {
            if (!$array['sendmsg']) {   //不是私信的情况
                $_url = self::sendmsg($content);
                if ($_url) {
                    $url = $_url;
                }
            }            
            $content = mb_substr($content,0,17,'utf-8').'...';
        }
        if (self::stringLength($subject)>20) {
            $subject = mb_substr($subject,0,17,'utf-8').'...';
        }
        
        $time = date('Y年m月d日 H:i');
        
        $tinfo = [];
        if($array['template_data']){
            $tinfo = fun('msg@template',$array['template_data']['key_word']);
            if (!$tinfo || $tinfo['type']==0) {
                return ;
            }
        }
        
        if ($tinfo) {
            $_data = [];
            foreach(json_decode($tinfo['data_field'],true) AS $rs){
                $_data[$rs['title3']] = [
                    'value'=>$array['template_data'][$rs['title2']],
                    'color'=>'#0000ff',
                ];
            }
            $array = [
                'touser'=>$openid,
                'template_id'=>$tinfo['template_id'],
                'url'=>$array['template_data']['page_url'] ?: $url,
                'page'=>$array['template_data']['page_url'] ?: $url,
                'data'=>$_data,
            ];
            $data = json_encode($array);
        }else{
            $data="      {
                \"touser\":\"$openid\",
                \"template_id\":\"".config('webdb.mp_subscribe_template_id')."\",
                \"page\":\"$url\",
                \"data\":{
                \"thing2\": {
                \"value\":\"$content\",
                \"color\":\"#0000ff\"
            },
            \"thing4\":{
            \"value\":\"$content\",
            \"color\":\"#666666\"
            },
            \"thing3\":{
            \"value\":\"$content\",
            \"color\":\"#666666\"
            },
            \"time3\": {
            \"value\":\"$time\",
            \"color\":\"#666666\"
            },
            \"thing1\":{
            \"value\":\"$content\",
            \"color\":\"#0000ff\"
            }
            }
            }";
        }
        $surl = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/bizsend?access_token=';
        if($tinfo['type']==2){
            $surl = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=';
        }        
        $string = http_Curl($surl.$access_token,$data);
        if(strstr($string,'ok')){
            return true;
        }
    }
    
    /**
     * 小程序订阅消息
     * @param unknown $openid
     * @param unknown $content
     * @param array $array
     * @param string $access_token
     * @param string $template_id
     * @return boolean
     */
    public static function wxapp_subscribe($openid,$content,$array=[],$access_token='',$template_id=''){
        $first = "你好!";
        $subject = "来自《".config('webdb.webname')."》的消息";
        
        $content = stripslashes($content);
        preg_match("/(http|https):([^ ]+)(\"|')/is",$content,$_array);
        $url = $_array[2] ? "$_array[1]:$_array[2]" : request()->domain();
        $content = preg_replace('/<([^<]*)>/is',"",$content);
        if (self::stringLength($content)>20) {
            if (!$array['sendmsg']) {   //不是私信的情况
                $_url = self::sendmsg($content);
                if ($_url) {
                    $url = $_url;
                }
            }            
            $content = mb_substr($content,0,17,'utf-8').'...';
        }
        if (self::stringLength($subject)>20) {            
            $subject = mb_substr($subject,0,17,'utf-8').'...';
        }
        
        
        $url = urlencode($url);
        $time = date('Y年m月d日 H:i');
        
        $tinfo = [];
        if($array['template_data']){
            $tinfo = fun('msg@template',$array['template_data']['key_word']);
            if (!$tinfo || $tinfo['type']!=0) {
                return ;
            }
        }
        
        if ($tinfo) {
            $_data = [];
            foreach(json_decode($tinfo['data_field'],true) AS $rs){
                $_data[$rs['title3']] = [
                    'value'=>$array['template_data'][$rs['title2']],
                    'color'=>'#0000ff',
                ];
            }
            $array = [
                'touser'=>$openid,
                'template_id'=>$tinfo['template_id'],
                'page'=>'pages/hy/web/index?url='.$array['template_data']['page_url']?urlencode($array['template_data']['page_url']):$url,
                'data'=>$_data,
            ];
            $data = json_encode($array);
        }else{
            $data="      {
            \"touser\":\"$openid\",
            \"template_id\":\"".($template_id?:config('webdb.wxapp_subscribe_template_id'))."\",
            \"page\":\"pages/hy/web/index?url=$url\",
            \"data\":{
            \"thing3\": {
            \"value\":\"$content\",
            \"color\":\"#0000ff\"
            },
            \"thing4\":{
            \"value\":\"$content\",
            \"color\":\"#666666\"
            },
            \"thing2\":{
            \"value\":\"$content\",
            \"color\":\"#666666\"
            },
            \"time3\": {
            \"value\":\"$time\",
            \"color\":\"#666666\"
            },
            \"thing1\":{
            \"value\":\"$content\",
            \"color\":\"#0000ff\"
            }
            }
            }";
        }
        
        
        $string = http_Curl("https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=".$access_token,$data);
        if(strstr($string,'ok')){
            return true;
        }
    }
    
    /**
     * 给用户发送公众号或者是小程序消息
     * @param unknown $openid 有可能是公众号，也有可能是小程序的ID，将要弃用
     * @param unknown $content
     * @param array $array
     * @param array $user 用户的信息
     * @return void|boolean|mixed
     */
    public function send($openid,$content,$array=[],$user=[]){
        
        if(empty($openid) && empty($user)){
            return ;
        }
        
        self::$wxid = $openid?:$user['uid'];
        self::$user = $user;
        self::$content = $content;
        
        $content = preg_replace_callback("/(http|https):([^ ]+)(\"|')/is",array(self,'getUrl'),$content);  //加入用户登录信息,让用户直接登录
        
        if($array['type']=='image'){
            $data="{
            \"touser\":\"$openid\",
            \"msgtype\":\"image\",
            \"image\":
            {
            \"media_id\":\"$array[id]\"
        }
        }";
        }elseif($array['type']=='voice'){
            $data="{
            \"touser\":\"$openid\",
            \"msgtype\":\"voice\",
            \"voice\":
            {
            \"media_id\":\"$array[id]\"
        }
        }";
        }elseif($array['type']=='video'){
            $array['thumb_media_id'] || $array['thumb_media_id']=$array['id'];
            $data="{
            \"touser\":\"$openid\",
            \"msgtype\":\"video\",
            \"video\":
            {
            \"media_id\":\"$array[id]\",
            \"thumb_media_id\":\"$array[thumb_media_id]\",
            \"title\":\"$array[title]\",
            \"description\":\"$array[description]\"
        }
        }";
        }else{
            strstr($content,'"') && $content = addslashes($content);
            $data="{
            \"touser\":\"$openid\",
            \"msgtype\":\"text\",
            \"text\":
            {
            \"content\":\"$content\"
        }
        }";
        }
        $ac = wx_getAccessToken();
        
        if(in_array($array['type'],['image','voice','video'])&&strstr( http_curl("https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=$ac",$data) ,'ok')){
            return true;
        }
        
        //圈子小程序消息的处理
        if (get_wxappAppid() && $user['qun_msg_dy'] && $user['qun_msg_dy'][get_wxappAppid()]) {
            $template_id = wxapp_open_cfg(get_wxappAppid())['wxapp_subscribe_template_id'];
            if ($template_id && self::wxapp_subscribe($user['qun_msg_dy'][get_wxappAppid()], $content,$array,wx_getAccessToken(false,true,get_wxappAppid()),$template_id)===true) {
                return true;
            }
        }elseif(!$user['subscribe_wxapp'] && $user['qun_msg_dy']){
            foreach($user['qun_msg_dy'] AS $qun_appid=>$qun_openid){
                $template_id = wxapp_open_cfg($qun_appid)['wxapp_subscribe_template_id'];
                if ( $template_id && self::wxapp_subscribe($qun_openid, $content,$array,wx_getAccessToken(false,true,$qun_appid),$template_id)===true) {
                    return true;
                }
            }
        }
        
//         if ($user['subscribe_qun_wxapp']) {
//             $info = \app\qun\model\Weixin::where('uid',$user['uid'])->where('if_dy',1)->find();
//             if ($info && self::wxapp_subscribe($info['wxapp_api'], $content,$array,wx_getAccessToken(false,true,$info['wxapp_appid']))===true) {
//                 return true;
//             }
//         }
        
        //个性模板消息优先
        if(!get_wxappAppid() && $array['template_data'] && ($tinfo = fun('msg@template',$array['template_data']['key_word'])) ){
            if($tinfo['type']==0 && $user['wxapp_api'] && self::wxapp_subscribe($user['wxapp_api'], $content,$array,wx_getAccessToken(false,true))===true ){
                return true;
            }elseif($user['weixin_api'] && self::mp_subscribe($user['weixin_api'], $content,$array,$ac)===true ){
                return true;
            }
        }
        
        if($user['wx_attention'] && self::gz_mp_msg($user['weixin_api'],$content,$ac)===true){
            return true;
        }
        
        //通用模板消息的处理
        if($user['subscribe_wxapp'] && self::wxapp_subscribe($user['wxapp_api'], $content,$array,wx_getAccessToken(false,true))===true){
            return true;
        //}elseif($user['subscribe_mp'] && self::mp_subscribe($user['weixin_api'], $content,$array,$ac)===true){
        }elseif( self::mp_subscribe($user['weixin_api'], $content,$array,$ac)===true ){
            return true;
        }elseif(strstr( http_curl("https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=$ac",$data) ,'ok')){
            return true;
        }elseif($user['weixin_api'] && self::gz_mp_msg($user['weixin_api'],$content,$ac)===true){
            return true;
        }
    }
    
    /**
     * 关注了公众号，才能使用的模板消息。非公众号订阅消息
     * @param string $openid
     * @param string $content
     * @param string $ac
     * @return boolean
     */
    private static function gz_mp_msg($openid='',$content='',$ac=''){
        $first = "你好!";
        $subject = "来自《".config('webdb.webname')."》的消息";
        $sender = "系统消息";
        
        $content = stripslashes($content);
        preg_match("/(http|https):([^ ]+)(\"|')/is",$content,$array);
        $url = $array[2] ? "$array[1]:$array[2]" : request()->url(true);
        $content = preg_replace('/<([^<]*)>/is',"",$content);
        $content = addslashes($content);
        
        $data="      {
        \"touser\":\"$openid\",
        \"template_id\":\"".config('webdb.weixin_msg_template_id')."\",
        \"url\":\"$url\",
        \"data\":{
        \"first\": {
        \"value\":\"$content\",
        \"color\":\"#0000ff\"
    },
    \"subject\":{
    \"value\":\"$subject\",
    \"color\":\"#666666\"
    },
    \"sender\": {
    \"value\":\"$sender\",
    \"color\":\"#666666\"
    },
    \"remark\":{
    \"value\":\"$content\",
    \"color\":\"#0000ff\"
    }
    }
    }";
        $string = http_Curl("https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=$ac",$data);
        if(strstr($string,'ok')){
            return true;
        }
    }
}