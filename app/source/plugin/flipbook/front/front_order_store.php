<?php
namespace flipbook;
use DB;
if(!defined('IN_DISCUZ'))exit('Access Denied');

/** Only this plugin's own InnoDB order table is written; content and member tables remain sources. */
class front_order_store {
 public static function schema(){return "CREATE TABLE IF NOT EXISTS %t (
  orderid bigint unsigned NOT NULL AUTO_INCREMENT, uid mediumint unsigned NOT NULL,
  request_key varchar(64) NOT NULL, request_digest char(64) NOT NULL,
  out_trade_no varchar(32) NOT NULL, appid varchar(32) NOT NULL, mchid varchar(32) NOT NULL,
  productid int unsigned NOT NULL, skuid int unsigned NOT NULL DEFAULT 0, quantity smallint unsigned NOT NULL,
  unit_fen int unsigned NOT NULL, shipping_fen int unsigned NOT NULL DEFAULT 0, total_fen int unsigned NOT NULL,
  title varchar(150) NOT NULL, snapshot text NOT NULL, address text NOT NULL, payer text NOT NULL,
  status varchar(16) NOT NULL DEFAULT 'pending', transaction_id varchar(64) DEFAULT NULL,
  prepay text NOT NULL, created int unsigned NOT NULL, expires int unsigned NOT NULL,
  paid_at int unsigned NOT NULL DEFAULT 0, closed_at int unsigned NOT NULL DEFAULT 0,
  shipped_at int unsigned NOT NULL DEFAULT 0, carrier varchar(80) NOT NULL DEFAULT '', tracking varchar(80) NOT NULL DEFAULT '',
  PRIMARY KEY (orderid), UNIQUE KEY request_key(uid,request_key), UNIQUE KEY out_trade_no(out_trade_no),
  UNIQUE KEY transaction_id(transaction_id), KEY member_status(uid,status,orderid)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";}
 public static function ready(){
  static $ready;if($ready!==null)return $ready;
  $name=DB::table('flipbook_order');$rows=DB::fetch_all('SHOW TABLES');foreach($rows as $row)if(in_array($name,$row,true))return $ready=true;return $ready=false;
 }
 public function transaction($run){DB::query('START TRANSACTION');try{$result=$run();DB::query('COMMIT');return $result;}catch(\Throwable $error){DB::query('ROLLBACK');throw $error;}}
 public function by_request($uid,$key){return DB::fetch_first('SELECT * FROM %t WHERE uid=%d AND request_key=%s FOR UPDATE',['flipbook_order',$uid,$key]);}
 public function by_id($uid,$id){return DB::fetch_first('SELECT * FROM %t WHERE uid=%d AND orderid=%d',['flipbook_order',$uid,$id]);}
 public function by_number($number,$lock=false){return DB::fetch_first('SELECT * FROM %t WHERE out_trade_no=%s'.($lock?' FOR UPDATE':''),['flipbook_order',$number]);}
 public function product($id){return DB::fetch_first('SELECT * FROM %t WHERE productid=%d FOR UPDATE',['flipbook_product',$id]);}
 public function skus($id){return DB::fetch_all('SELECT * FROM %t WHERE productid=%d AND available=1 ORDER BY displayorder,skuid FOR UPDATE',['flipbook_sku',$id]);}
 public function insert($values){$id=DB::insert('flipbook_order',$values,true);if(!$id)throw new \RuntimeException('订单保存失败，请重试');return $id;}
 public function update($id,$values){DB::update('flipbook_order',$values,['orderid'=>$id]);}
 public function pending_count($uid,$since){return intval(DB::result_first('SELECT COUNT(*) FROM %t WHERE uid=%d AND created>%d',['flipbook_order',$uid,$since]));}
 public function list($uid,$page,$size,$status=''){
  $where=$uid>0?' WHERE uid='.intval($uid):' WHERE 1=1';if(in_array($status,['pending','paid','closed','shipped'],true))$where.=" AND status='".$status."'";
  $count=intval(DB::result_first('SELECT COUNT(*) FROM %t'.$where,['flipbook_order']));$items=DB::fetch_all('SELECT * FROM %t'.$where.' ORDER BY orderid DESC LIMIT '.(($page-1)*$size).','.$size,['flipbook_order']);return ['items'=>$items,'total'=>$count,'page'=>$page,'pageSize'=>$size];
 }
}
