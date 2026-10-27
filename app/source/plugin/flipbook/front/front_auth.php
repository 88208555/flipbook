<?php
namespace flipbook;
use DB;
if(!defined('IN_DISCUZ')) exit('Access Denied');

/** WeChat identity binds to a native forum UID; the forum remains the account source. */
class front_auth {
 public static function handle() {
  global $_G;
  if(($_SERVER['REQUEST_METHOD']??'')!=='POST') self::out(0,'method','请使用 POST');
  if(!is_string($_POST['formhash']??null) || !hash_equals(front_helper::formhash(),$_POST['formhash'])) self::out(0,'formhash','页面已过期，请重试');
  require_once libfile('function/member'); require_once libfile('function/misc');
  switch($_GET['op']??'') {
   case 'logout':
    clearcookies();$_G['uid']=0;$_G['member']=[];$_G['username']='';$_G['groupid']=7;
    self::out(1,'','已退出登录');break;
   case 'forum': self::forum();break;
   case 'wechat': self::wechat();break;
   case 'bind': self::bind();break;
   default:self::out(0,'op','不支持的操作');
  }
 }
 private static function forum() {
  global $_G;
  $username=trim((string)($_POST['username']??''));$password=(string)($_POST['password']??'');
  if($username==='' || strlen($username)>100 || $password==='' || strlen($password)>256) self::out(0,'credentials','请输入论坛账号和密码');
  // Sites requiring extra verification retain their native login flow.
  list($code,$qa)=seccheck('login');
  if($code || $qa || !empty($_G['setting']['account']['loginRedirect']) || !empty($_G['setting']['account']['loginRedirectDefault']) || !empty($_G['setting']['accountguard']['loginoutofdate']) || !empty($_G['setting']['accountguard']['loginpwcheck'])) self::out(0,'native_verification','当前论坛要求额外验证，请先在论坛完成登录', ['url'=>'member.php?mod=logging&action=login']);
  $_G['member_loginperm']=logincheck($username);
  if(!$_G['member_loginperm']) self::out(0,'rate','尝试次数过多，请稍后再试');
  $result=userlogin($username,$password,max(0,min(7,intval($_POST['questionid']??0))),(string)($_POST['answer']??''),'username',$_G['clientip']);
  if(($result['status']??0)!==1) {loginfailed($username);self::out(0,'credentials',($result['ucresult']['uid']??0)===-3?'请填写正确的安全提问与答案':'账号或密码不正确');}
  self::login($result['member']);
  self::out(1,'','登录成功',['member'=>self::member()]);
 }
 private static function login($member) {
  global $_G;
  $group=\C::t('common_usergroup')->fetch($member['groupid']);
  if(!$member || in_array(intval($member['groupid']),[4,5,6,7,8],true) || !empty($member['freeze']) || !empty($group['forcelogin'])) self::out(0,'native_verification','账号需要在论坛完成验证后登录');
  setloginstatus($member,2592000);
 }
 private static function wechat() {
  global $_G;
  $appid=front_service_config::get('wechat_appid');$secret=front_service_config::get('wechat_secret');
  if(front_service_config::get('wechat_enabled')!=='1' || !$appid || !$secret) self::out(0,'wechat_unconfigured','微信登录尚未配置，请先使用论坛账号');
  $code=(string)($_POST['code']??'');if(!preg_match('/^[a-zA-Z0-9_-]{10,256}$/',$code)) self::out(0,'code','微信登录凭证无效，请重试');
  $curl=curl_init('https://api.weixin.qq.com/sns/jscode2session?'.http_build_query(['appid'=>$appid,'secret'=>$secret,'js_code'=>$code,'grant_type'=>'authorization_code']));
  curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
  $body=curl_exec($curl);$http=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);$data=json_decode($body?:'',true);
  if($http!==200 || empty($data['openid']) || !empty($data['errcode'])) self::out(0,'wechat_failed','微信验证失败，请重新点击登录');
  $identity=hash('sha256',$appid.':'.$data['openid']);
  $row=DB::fetch_first('SELECT * FROM %t WHERE identity=%s',['flipbook_identity',$identity]);
  if($row) { self::login(getuserbyuid($row['uid'],1));self::out(1,'','登录成功',['member'=>self::member()]); }
  // Encrypted, expiring ticket tied to this client's cookie. Never expose openid/session_key.
  $ticket=authcode(json_encode(['identity'=>$identity,'nonce'=>bin2hex(random_bytes(16))]),'ENCODE','',300);
  dsetcookie('fb_wx_bind',hash('sha256',$ticket),300,1,true);
  self::out(0,'bind_required','首次微信登录，请绑定已有论坛账号',['ticket'=>$ticket]);
 }
 private static function bind() {
  global $_G;
  $ticket=(string)($_POST['ticket']??'');$cookie=$_G['cookie']['fb_wx_bind']??'';
  if(!$cookie || !hash_equals($cookie,hash('sha256',$ticket))) self::out(0,'ticket','绑定已过期，请重新微信登录');
  $data=json_decode(authcode($ticket,'DECODE'),true);
  if(!$data || !preg_match('/^[a-f0-9]{64}$/',$data['identity']??'')) self::out(0,'ticket','绑定已过期，请重新微信登录');
  if(front_helper::uid()<=0) self::out(0,'needlogin','请先使用论坛账号登录，再确认绑定');
  $uid=front_helper::uid();
  DB::query('INSERT IGNORE INTO %t (identity,uid,dateline) VALUES (%s,%d,%d)',['flipbook_identity',$data['identity'],$uid,TIMESTAMP]);
  $row=DB::fetch_first('SELECT uid FROM %t WHERE identity=%s',['flipbook_identity',$data['identity']]);
  if(!$row || intval($row['uid'])!==$uid) self::out(0,'bound','此微信已绑定其他论坛账号');
  dsetcookie('fb_wx_bind','',-1,1,true);self::out(1,'','微信与论坛账号已绑定',['member'=>self::member()]);
 }
 public static function member() {
  global $_G;
  return ['uid'=>front_helper::uid(),'name'=>front_helper::member_name(),'avatar'=>front_helper::uid()>0?avatar(front_helper::uid(),'middle',true):'', 'group'=>strip_tags($_G['group']['grouptitle']??''),'adminid'=>intval($_G['adminid']??0)];
 }
 private static function out($ok,$code,$message,$extra=[]) {
  header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
  echo json_encode(['ok'=>$ok,'code'=>$code,'message'=>$message,'formhash'=>front_helper::formhash()]+$extra,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
 }
}
