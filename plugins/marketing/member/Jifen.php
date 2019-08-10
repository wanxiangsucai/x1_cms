<?php
namespace plugins\marketing\member;

use app\common\controller\MemberBase; 
use plugins\marketing\model\Moneylog AS Model;
use plugins\marketing\model\RmbInfull;

class Jifen extends MemberBase
{
    public function index()
    {
        $map = [
                'uid'=>$this->user['uid']
        ];
        $data_list = Model::where($map)->order("id desc")->paginate(15);
        $data_list->each(function($rs,$key){
            $rs['title'] = del_html($rs['about']);
            return $rs;
        });
        $pages = $data_list->render();
        $listdb = getArray($data_list)['data'];
        //给模板赋值变量
        $this->assign('pages',$pages);
        $this->assign('listdb',$listdb);
        return $this->pfetch();
    }
    
    private function pay_end($numcode=''){
        $info = RmbInfull::get(['numcode'=>$numcode]);
        if ($info['ifpay']!=1) {
            return '你还没有付款';
        }elseif ( empty( get_cookie('add_jifen') ) ) {
            return '该订单已经充值过了！';
        }
        return $this->rmb_to_jifen($info['money']);
    }
    
    private function rmb_to_jifen($rmb=0){        
        if ($this->user['rmb']>=$rmb){
            $this->webdb['money_ratio']>0 || $this->webdb['money_ratio']=10;
            add_jifen($this->user['uid'],$rmb*$this->webdb['money_ratio'],'在线充值积分');
            add_rmb($this->user['uid'], -abs($rmb), 0,'充值积分消费');
            set_cookie('add_jifen',null);
            return true;
        }else{
            return '你的帐户余额不足 '.$rmb.' 元！';
        }
    }
    
    /**
     * 充值积分
     * @param string $numcode 在线付款后返回的订单号
     * @param number $ispay 在线付款成功或失败
     * @return mixed|string
     */
    public function add($numcode='',$ispay=0){
        if($numcode){   //在线付款返回
            $url = purl('index',[],'member');
            if ($ispay==1) {
                $result = $this->pay_end($numcode);
                if($result===true){
                    $this->success('充值成功',$url);
                }else{
                    $this->error('充值失败,'.$result,$url);
                }
            }else{
                $this->error('你并没有付款',$url);
            }
        }
        if (IS_POST) {
            $data = $this->request->post();
            if ( $data['money']<0.01 ) {
                $this->error('充值金额不能小于0.01元');
            }
            
            if($data['paytype']=='yu_er'){    //选择余额充值
                $result = $this->rmb_to_jifen($data['money']);
                if ($result===true){
                    $this->success('充值成功','index');
                }else{
                    $this->error('充值失败：'.$result);
                }
            }else{             //选择在线充值      
                $numcode = 'j'.date('ymdHis').rands(3);      //订单号
                set_cookie('add_jifen',$numcode);
                //直接跳转支付
                post_olpay([
                    'money'=>$data['money'],
                    'return_url'=>purl('add',['numcode'=>$numcode]),
                    'banktype'=>$data['paytype'],
                    'numcode'=>$numcode,
                    'callback_class'=>'',
                ] , true);
            }
        }
        return $this->fetch();
    }
    
    public function delete($id)
    {
        if( empty($this->admin) ){
            $this->error('非管理员,不能删除积分日志');
        }
        if (Model::destroy([$id])) {
            $this->success('删除成功','index');
        }else{
            $this->error('删除失败');
        }
    }
}
