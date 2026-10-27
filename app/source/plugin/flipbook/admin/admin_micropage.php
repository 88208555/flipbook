<?php
namespace flipbook;

if(!defined('IN_DISCUZ')) exit('Access Denied');

class admin_micropage {
 /** Create only standalone micro pages; built-in page types stay fixed. */
 public static function create($input) {
  if(!is_array($input) || ($input['category'] ?? 'micro') !== 'micro') {
   throw new \RuntimeException('只有微页面允许新增，其他页面分类只能编辑现有页面');
  }
  foreach(['key','pkey','route','url'] as $field) {
   if(isset($input[$field]) && $input[$field] !== '') throw new \RuntimeException('路由和链接由系统自动配置，请勿手动指定');
  }
  $name=self::name($input['name'] ?? '');
  for($attempt=0;$attempt<3;$attempt++) {
   $key='micro_'.bin2hex(random_bytes(8));
   $config=front_micro::sanitize(front_micro::default_config($key));
   unset($config['schema'],$config['typography'],$config['globalTypographyDirty']);
   $version=table_flipbook_micropage::t()->save_page($key,$name,json_encode($config,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),0);
   if($version) return front_micro::page($key) + ['routes'=>front_micro::routes($key)];
  }
  throw new \RuntimeException('页面创建失败，请重试');
 }

 private static function name($value) {
  if(!is_string($value) || !mb_check_encoding($value,'UTF-8')) throw new \RuntimeException('请填写有效的页面名称');
  $value=trim(preg_replace('/[\x00-\x1F\x7F]/u',' ',strip_tags($value)));
  if($value==='' || mb_strlen($value,'UTF-8')>100) throw new \RuntimeException('页面名称需为 1 至 100 个字');
  return $value;
 }

 private static function editable($input) {
  $key=is_array($input)?($input['key']??null):null;
  if(!front_micro::is_custom_key($key)) throw new \RuntimeException('内置页面只能编辑，不能重命名或删除');
  $row=table_flipbook_micropage::t()->fetch_by_key($key);
  if(!$row || empty($row['status'])) throw new \RuntimeException('页面不存在或已删除');
  $version=$input['version']??null;
  if(!is_scalar($version) || !ctype_digit((string)$version) || intval($version)!==intval($row['version'])) throw new \RuntimeException('页面已更新，请刷新列表后重试');
  return $row;
 }

 public static function rename($input) {
  $row=self::editable($input);$name=self::name($input['name']??'');
  if(!table_flipbook_micropage::t()->rename_custom_page($row['pkey'],$name,intval($row['version']))) throw new \RuntimeException('页面已更新，请刷新列表后重试');
  return front_micro::page($row['pkey']);
 }

 public static function remove($input) {
  $row=self::editable($input);
  if(!table_flipbook_micropage::t()->delete_custom_page($row['pkey'],intval($row['version']))) throw new \RuntimeException('页面已更新，请刷新列表后重试');
 }
}
