#!/usr/bin/env python3
"""Check the sync/draft integration and serve an isolated read-only UI fixture."""
from pathlib import Path
import argparse,base64,http.server,json,os,re,shutil,subprocess,urllib.parse
ROOT=Path(os.environ.get('SYNC_REPO_ROOT',Path(__file__).resolve().parents[2]))
CANDIDATE=os.environ.get('SYNC_CANDIDATE')
PLUGIN=Path(CANDIDATE) if CANDIDATE else ROOT/'app/source/plugin/flipbook_sync'
NATIVE=PLUGIN.parent/'flipbook'
DOCKER=shutil.which('docker') or '/Users/zhang/.local/bin/docker'
NODE=shutil.which('node') or '/Users/zhang/.local/share/entrorove/node-v24.19.0/bin/node'

def php(source,lint=False):
    result=subprocess.run([DOCKER,'exec','-i']+(['-e','FLIPBOOK_SYNC_CANDIDATE=1'] if CANDIDATE else [])+['flipbook-php','php']+(['-l'] if lint else []),input=source,capture_output=True,timeout=120)
    if result.returncode or b'Discuz! System Error' in result.stdout or b'Discuz! Database Error' in result.stdout:raise RuntimeError(result.stdout.decode(errors='replace')+result.stderr.decode(errors='replace'))
    return result.stdout

def candidate_loader():
    return '''require '/tmp/flipbook-sync-increment-test/flipbook/admin/admin_crud.php';require '/tmp/flipbook-sync-increment-test/flipbook/admin/admin_product.php';
spl_autoload_register(function($name){if(str_starts_with($name,'flipbook_sync\\\\')){$relative=substr($name,14);$file='/tmp/flipbook-sync-increment-test/flipbook_sync/'.(str_starts_with($relative,'admin_')?'admin/':'service/').$relative.'.php';if(is_file($file))require $file;}},true,true);
''' if CANDIDATE else ''

def bootstrap():
    return """<?php
chdir('/var/www/html');$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['HTTP_HOST']='127.0.0.1:8096';$_SERVER['REQUEST_URI']='/admin.php';$_SERVER['REQUEST_METHOD']='GET';define('IN_ADMINCP',true);define('ADMINSCRIPT','admin.php');
require './source/class/class_core.php';C::app()->init();require_once './source/function/function_admincp.php';
"""+candidate_loader()

def render(page,state):
    if page not in ['grants','sources','guide','catalog','drafts','product-editor','draft-editor']:raise ValueError('Unknown page')
    source=bootstrap()+"""
$_G['uid']=4294000002;$_G['adminid']=1;$_GET=['do'=>2,'pmod'=>'product'];DB::query('START TRANSACTION');
try{
 $sourceid=DB::insert('flipbook_sync_source',['name'=>'界面验收来源','endpoint'=>'https://preview-supplier.example/','domain'=>'preview.example','keycipher'=>'fixture-never-rendered','enabled'=>1,'instanceid'=>'','lastsync'=>0],true);
 DB::insert('flipbook_sync_key',['domain'=>'preview-client.example','keyhash'=>str_repeat('f',64),'enabled'=>1,'note'=>'界面验收夹具','ips'=>'','created'=>time(),'lastused'=>0]);
 echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><title>产品同步验收</title><style>body{font-family:Arial,sans-serif}</style></head><body>';
 $page='__PAGE__';$state='__STATE__';
 if(in_array($page,['grants','sources','guide','catalog'],true))flipbook_sync\\admin_view::render(['grants'=>'admincp','sources'=>'receive','guide'=>'guide','catalog'=>'data'][$page]);
 else{
  if($page==='drafts')$_GET['view']='drafts';
  else{
   $_GET['op']='edit';$config=flipbook_sync\\service_draft_editor::config('product');$row=flipbook\\table_flipbook_product::t()->fetch(1);$row['__extra']=flipbook\\admin_product::load(1);$row['cover']='/fixture.svg';$row['video']='';$row['__extra']['showcase_cover']='/fixture.svg';$row['__extra']['showcase_video']='';$row['__extra']['floating_image']='';$skuFixture=json_decode($row['__extra']['skulines'],true);foreach($skuFixture as &$sku)$sku['images']=array_map(fn($url)=>'/fixture.svg',$sku['images']);unset($sku);$row['__extra']['skulines']=json_encode($skuFixture);$row['__extra']['medialines']='';
   if($state==='empty')$row=['__extra'=>[]];
   if($page==='draft-editor'){
    $fields=array_map('strval',array_intersect_key($row,$config['schema']))+array_map('strval',$row['__extra']);
    $draft=flipbook_sync\\service_draft_store::save($_G['uid'],'local','product',0,0,1,['fields'=>$fields,'editor_key'=>str_repeat('a',32)],str_repeat('b',32),1,0);
    $_GET['view']='drafts';$_GET['draftid']=$draft['draftid'];
   }else{flipbook\\admin_product::navigation('product');flipbook\\admin_product::render($config,$row);}
  }
  if($page!=='product-editor')flipbook_sync\\admin_draft::run();
 }
 echo '</body></html>';
}finally{DB::query('ROLLBACK');}
""".replace('__PAGE__',page).replace('__STATE__',state)
    body=php(source.encode())
    # Read-only state fixtures. These are layout evidence, not persistence tests.
    state_script="""<script>document.addEventListener('DOMContentLoaded',()=>{
const state=__STATE__,page=__PAGE__;document.body.dataset.previewState=state;
const status=document.querySelector('[data-sync-status],.fb-product-status');
if(status&&state!=='default'){status.textContent=({empty:'暂无数据',error:'校验失败，当前输入已保留',working:'正在处理…'})[state];if(state==='error')status.classList.add('is-error');}
if(state==='empty')document.querySelectorAll('tbody').forEach(body=>{const row=document.createElement('tr'),cell=document.createElement('td');cell.colSpan=6;cell.textContent='暂无数据';row.appendChild(cell);body.replaceChildren(row);});
if(page==='grants'&&['error','working'].includes(state)){const dialog=document.querySelector('[data-grant-dialog]');dialog.showModal();dialog.querySelector('[name=domain]').value=state==='error'?'invalid_domain':'client.example.com';dialog.querySelector('[data-form-status]').textContent=state==='error'?'域名格式无效，请检查后保存':'正在保存…';dialog.querySelector('[type=submit]').disabled=state==='working';}
if(page==='sources'&&state==='working'){const panel=document.querySelector('[data-sync-progress]');panel.hidden=false;panel.querySelector('progress').value=20;panel.querySelector('[data-sync-count]').textContent='已保存 20 / 100 条草稿';}
if(state==='error')document.querySelectorAll('[name=name]').forEach(input=>input.value='');
});</script>""".replace('__STATE__',json.dumps(state)).replace('__PAGE__',json.dumps(page))
    # Fixture catalog starts with a controlled empty read response; no writes happen in preview.
    before="""<script>window.fetch=async(url,options)=>({ok:true,json:async()=>({ok:1,catalog:{items:[],has_more:false,next_cursor:0,shared_fields:[],allowed_fields:[],field_mode:'shared',local_enabled:true}})});</script>"""
    body=body.replace(b'<body>',b'<body>'+before.encode()).replace(b'</body>',state_script.encode()+b'</body>')
    return body

class Preview(http.server.BaseHTTPRequestHandler):
    def log_message(self,*args):pass
    def do_GET(self):
        parsed=urllib.parse.urlsplit(self.path)
        if parsed.path=='/fixture.svg':
            body=b'<svg xmlns="http://www.w3.org/2000/svg" width="360" height="480"><rect width="360" height="480" fill="#f4f5f7"/><text x="180" y="240" text-anchor="middle" font-family="sans-serif" font-size="20" fill="#606570">Preview fixture</text></svg>';mime='image/svg+xml'
        elif parsed.path in ['/','/admin.php']:
            q=urllib.parse.parse_qs(parsed.query);page=q.get('preview_page',['grants'])[0];state=q.get('preview_state',['default'])[0]
            if state not in ['default','empty','error','working']:self.send_error(400);return
            try:body=render(page,state)
            except Exception as error:print(str(error),flush=True);self.send_error(500);return
            mime='text/html; charset=utf-8'
        else:
            rel=parsed.path.lstrip('/')
            roots={'source/plugin/flipbook_sync/static/':PLUGIN/'static','source/plugin/flipbook/static/admin/':ROOT/'app/source/plugin/flipbook/static/admin'}
            file=None
            for prefix,folder in roots.items():
                if rel.startswith(prefix):
                    tail=rel[len(prefix):]
                    if '/' not in tail and tail.endswith(('.css','.js')):file=folder/tail
            if file is None or not file.is_file():self.send_error(404);return
            body=file.read_bytes();mime='text/css' if file.suffix=='.css' else 'text/javascript'
        self.send_response(200);self.send_header('Content-Type',mime);self.send_header('Cache-Control','no-store');self.send_header('Content-Length',str(len(body)));self.end_headers();self.wfile.write(body)

def tests():
    for file in list(PLUGIN.rglob('*.php'))+[NATIVE/p for p in ['product.inc.php','category.inc.php','series.inc.php','supplier.inc.php','admin/admin_product.php','admin/admin_crud.php']]:php(file.read_bytes(),True)
    print('PASS sync and native integration PHP syntax',flush=True)
    here=Path(__file__).parent
    for script in ['integration.php','draft.php']:
        source=(here/script).read_bytes()
        # Framework HTML error handlers can exit 0; test exceptions must fail
        # without appending test failures to the site's application error log.
        handler=b'''set_exception_handler(static function(\\Throwable $error){fwrite(STDERR,'FAIL '.$error->getMessage()."\\n");exit(1);});'''
        if b'C::app()->init();' not in source:raise RuntimeError(script+' has no known framework bootstrap')
        source=source.replace(b'C::app()->init();',b'C::app()->init();'+handler,1)
        if CANDIDATE and script=='integration.php':source=source.replace(b'C::app()->init();',b'C::app()->init();'+candidate_loader().encode(),1)
        output=php(source).decode();print(output,end='',flush=True)
        if not re.search(r'^TOTAL [1-9][0-9]* (?:draft )?checks$',output,re.M):raise RuntimeError(script+' did not complete its check summary')
    for script in ['ui.cjs','draft_ui.cjs']:
        result=subprocess.run([NODE,str(here/script)],capture_output=True,timeout=60);print(result.stdout.decode(),end='',flush=True)
        if result.returncode:raise RuntimeError(result.stderr.decode())

if __name__=='__main__':
    parser=argparse.ArgumentParser();parser.add_argument('--serve-preview',action='store_true');args=parser.parse_args()
    if args.serve_preview:
        print('Read-only fixture preview at http://127.0.0.1:8096/',flush=True);http.server.ThreadingHTTPServer(('127.0.0.1',8096),Preview).serve_forever()
    else:tests()
