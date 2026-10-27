<?php
namespace flipbook_sync;
if(!defined('IN_DISCUZ')) exit('Access Denied');

/** Server-to-server transport: public HTTPS, pinned DNS and no redirects. */
class service_client {
 public static function endpoint($input) {
  if(!is_string($input) || strlen($input)>500 || preg_match('/[\s\x00-\x1f\x7f%\\\\]/',$input)) throw new \InvalidArgumentException('请填写来源站点的 HTTPS 地址');
  $p=parse_url($input);
  if(!$p || ($p['scheme']??'')!=='https' || empty($p['host']) || isset($p['user']) || isset($p['pass']) || isset($p['query']) || isset($p['fragment']) || (isset($p['port']) && $p['port']!==443)) throw new \InvalidArgumentException('来源地址必须为 HTTPS，不能包含密钥、账号、查询参数或自定义端口');
  if(preg_match('#(?:^|/)\.\.?(/|$)#',$p['path']??'')) throw new \InvalidArgumentException('来源地址路径无效');
  $host=strtolower(rtrim($p['host'],'.'));service_auth::domain($host);
  if(!filter_var($host,FILTER_VALIDATE_IP) && (strpos($host,'.')===false || preg_match('/(?:^|\.)(?:[0-9]+|0x[0-9a-f]+)$/iD',$host))) throw new \InvalidArgumentException('来源必须使用完整域名或规范 IP 地址');
  return 'https://'.$host.'/'.trim($p['path']??'','/').(trim($p['path']??'','/')!==''?'/':'');
 }
 public static function own_domain() {
  global $_G;
  $p=parse_url($_G['siteurl']);
  return service_auth::domain($p['host'].(isset($p['port'])?':'.$p['port']:''));
 }
 public static function address($host) {
  if(!defined('FILTER_FLAG_GLOBAL_RANGE')) throw new \RuntimeException('远程同步需要 PHP 8.2 或更高版本');
  $addresses=[];
  if(filter_var($host,FILTER_VALIDATE_IP)) $addresses[]=$host;
  else foreach(dns_get_record($host,DNS_A|DNS_AAAA)?:[] as $r) if(isset($r['ip'])||isset($r['ipv6'])) $addresses[]=$r['ip']??$r['ipv6'];
  if(!$addresses) throw new \RuntimeException('来源域名无法解析');
  foreach($addresses as $ip) {
   $packed=inet_pton($ip);$size=strlen($packed);$first=ord($packed[0]);
   if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_GLOBAL_RANGE) || ($size===4 && $first>=224) || ($size===16 && $first===255)) throw new \RuntimeException('来源必须是公网服务器，不能请求本机或非公网地址');
  }
  return $addresses[0];
 }
 public static function request($source,$params) {
  $endpoint=self::endpoint($source['endpoint']);
  if($source['domain']!==self::own_domain()) throw new \RuntimeException('本站域名已变化，请重新保存数据源授权');
  $host=parse_url($endpoint,PHP_URL_HOST);$ip=self::address($host);
  $key=service_store::decrypt($source['keycipher']);
  $query=http_build_query(['id'=>'flipbook_sync:api']+$params);
  $curl=curl_init($endpoint.'plugin.php?'.$query);$body='';$tooLarge=false;
  curl_setopt_array($curl,[CURLOPT_HTTPHEADER=>['Accept: application/json','Authorization: Bearer '.$key,'X-Flipbook-Domain: '.$source['domain']],CURLOPT_RETURNTRANSFER=>false,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_PROXY=>'',CURLOPT_RESOLVE=>[$host.':443:'.(strpos($ip,':')!==false?'['.$ip.']':$ip)],CURLOPT_WRITEFUNCTION=>function($handle,$chunk) use (&$body,&$tooLarge){if(strlen($body)+strlen($chunk)>4194304){$tooLarge=true;return 0;}$body.=$chunk;return strlen($chunk);}]);
  $ok=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);
  if($tooLarge) throw new \RuntimeException('来源响应过大，请减小每批商品数量');
  if($ok===false) throw new \RuntimeException('无法连接来源站点，请检查 HTTPS 证书及网络');
  $json=json_decode($body,true);
  if($status!==200 || !is_array($json) || ($json['ok']??0)!==1) throw new \RuntimeException(in_array($status,[401,403],true)?'授权失败，请检查密钥、本站域名及服务器 IP':'来源接口返回错误（HTTP '.$status.'）');
  $data=$json['data']??null;
  if(!is_array($data) || ($data['protocol']??'')!=='flipbook-product-sync/1.0' || !preg_match('/^[a-f0-9]{32}$/D',$data['instanceid']??'')) throw new \RuntimeException('来源数据协议无效');
  if($data['instanceid']===service_store::instance()) throw new \RuntimeException('不能将本站设置为自己的同步来源');
  if($source['instanceid']!=='' && $data['instanceid']!==$source['instanceid']) throw new \RuntimeException('来源站点身份已变化，请新增数据源，不能覆盖原来源映射');
  return $data;
 }
}
