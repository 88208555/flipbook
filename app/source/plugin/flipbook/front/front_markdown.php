<?php
namespace flipbook;
if(!defined('IN_DISCUZ')) exit('Access Denied');
/** Deliberately small Markdown dialect. Raw HTML is always escaped. */
class front_markdown {
 private static function inline($text) {
  $text=htmlspecialchars($text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
  $text=preg_replace_callback('/\[([^\]\n]+)\]\((https?:\/\/[^\s)]+)\)/',function($m){return '<a href="'.$m[2].'">'.$m[1].'</a>';},$text);
  $text=preg_replace('/\*\*([^*]+)\*\*/','<strong>$1</strong>',$text);
  $text=preg_replace('/`([^`]+)`/','<code>$1</code>',$text);
  return $text;
 }
 public static function render($source) {
  $out='';$code=false;$list=false;
  foreach(explode("\n",str_replace("\r",'',(string)$source)) as $line) {
   if(preg_match('/^```/',$line)) {if($list){$out.='</ul>';$list=false;}$out.=$code?'</code></pre>':'<pre><code>';$code=!$code;continue;}
   if($code){$out.=htmlspecialchars($line,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')."\n";continue;}
   if(preg_match('/^[-*] +(.+)$/',$line,$m)){if(!$list){$out.='<ul>';$list=true;}$out.='<li>'.self::inline($m[1]).'</li>';continue;}
   if($list){$out.='</ul>';$list=false;}
   if(trim($line)==='')continue;
   if(preg_match('/^(#{1,6}) +(.+)$/',$line,$m)){$n=strlen($m[1]);$out.='<h'.$n.'>'.self::inline($m[2]).'</h'.$n.'>';}
   elseif(preg_match('/^> ?(.*)$/',$line,$m))$out.='<blockquote>'.self::inline($m[1]).'</blockquote>';
   elseif(preg_match('/^---+$/',trim($line)))$out.='<hr>';
   else $out.='<p>'.self::inline($line).'</p>';
  }
  if($list)$out.='</ul>';if($code)$out.='</code></pre>';
  return $out;
 }
}
