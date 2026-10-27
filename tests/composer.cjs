const fs=require('node:fs'),path=require('node:path'),assert=require('node:assert/strict'),cp=require('node:child_process'),vm=require('node:vm');
const root=process.env.COMPOSER_CANDIDATES||process.cwd(),plugin=path.join(root,'app/source/plugin/flipbook'),C=require(path.join(plugin,'static/js/composer-core.js')),schema=JSON.parse(fs.readFileSync(path.join(plugin,'static/config/composer.json')));let count=0;const check=(ok,text)=>{assert(ok,text);console.log('PASS '+text);count++;};
const php=path.join(__dirname,'composer.php');cp.execFileSync('docker',['cp',php,'flipbook-php:/tmp/flipbook-composer-test.php']);const args=['exec'];if(process.env.COMPOSER_STAGE)args.push('-e','COMPOSER_STAGE='+process.env.COMPOSER_STAGE);args.push('flipbook-php','php','/tmp/flipbook-composer-test.php');const data=JSON.parse(cp.execFileSync('docker',args,{maxBuffer:4*1024*1024,stdio:['ignore','pipe','pipe']}));
check(fs.readFileSync(path.join(plugin,'static/js/composer-core.js'),'utf8')===fs.readFileSync(path.join(root,'miniprogram/utils/composer-core.js'),'utf8'),'native renderer uses an exact copy of the single authored core');
for(const [key,{page,context}]of Object.entries(data)){const s={...page.config.schema,typography:page.config.typography},nodes=C.prepare(page.config.components,s,context),html=C.render(page.config.components,s,context);check(nodes.length>0,key+' has independent render nodes');check(!JSON.stringify(page.config.components).includes('"regions":'),key+' has no internal-region configuration');check(!html.includes('!important'),key+' emits no CSS overrides');const first=page.config.components[0],copy=JSON.parse(JSON.stringify(first)),tree=[copy,...page.config.components];copy.id+='-copy';check(C.prepare(tree,s,context).length===nodes.length+1,key+' accepts duplicates without business-type singleton removal');}
for(const [k,v]of Object.entries(schema.initialStyle))if(typeof v==='number')check(v===0,k+' defaults zero');
check(/flex-shrink:0(?:;|$)/.test(C.style(schema.initialStyle)),'zero shrink preserves natural component height instead of compressing every page');
check(!C.style({decoration:'underline'}).includes('underline'),'legacy underline configuration cannot reintroduce text underlines');
check(C.href('album:7',{},true)==='/pages/read/index?albumid=7','album links retain the book reader rather than redirecting to a product');
check(C.href('plugin.php?id=flipbook:read&albumid=7&page=3',{},true).endsWith('albumid=7&page=3'),'continue reading preserves the saved page position');
check(C.render([{id:'line',type:'divider',props:{thickness:0}}],schema,{}).includes('border-top:thin'),'an automatic divider remains functional at zero appearance defaults');
check(C.rich('<u>literal underline</u>',{},'px').indexOf('<u>')<0,'rich content uses the same no-underline formatter');
check(C.value({binding:'product.name',text:'定制标题'},{product:{name:'商品数据'}})==='定制标题','explicit custom content is not overwritten by dynamic product data');
check(C.value({binding:'product.name',text:''},{product:{name:'商品数据'}})==='商品数据','empty custom content follows dynamic product data');
check(schema.pageDefaults.layout.sideGap===15&&schema.pageDefaults.back.boxWidth===20&&schema.pageDefaults.back.boxHeight===20,'shared page defaults define 15px gutters and a 20px back icon');
check(schema.defaultTypography.title.size===20&&schema.defaultTypography.subtitle.size===14&&schema.defaultTypography.body.size===16,'all pages share editable standard typography');
const thumbs=C.prepare([{id:'thumbs',type:'product_skus',props:{}}],{...schema,typography:schema.defaultTypography},{product:{slides:[{image:'video-poster',video:'clip.mp4'},{image:'photo.jpg'}]}})[0];check(thumbs.items.every(item=>item.heightStyle===thumbs.items[0].heightStyle),'video posters and images use one thumbnail height');
check(C.render([{id:'info',type:'product_info',props:{}}],{...schema,typography:schema.defaultTypography},{product:{spec:{colors:['白'],sizes:['S'],fit:'宽松',fields:[{label:'材质',value:'棉'}]}}}).match(/data-node=/g).length===1,'product information is one selectable component');
const typo={title:{size:23,color:'#123456'},subtitle:{size:17},body:{size:0}};check(C.style({textRole:'title',size:88},'px',typo).includes('font-size:calc(23 *'),'all main titles use the global role');check(C.style({textRole:'subtitle',size:88},'rpx',typo).includes('font-size:34rpx'),'native subtitles use the same design-unit value');
const formatted=C.rich('<h1 style="font-size:90px;color:red">Title</h1><p style="padding:30px"><b>Body</b></p><script>bad()</script>',typo,'px');check(!formatted.includes('90px')&&!formatted.includes('30px')&&!formatted.includes('bad()'),'incoming content cannot retain appearance or executable markup');
check(schema.types.product_title.pages.length===1&&schema.types.product_title.pages[0]==='detail','product business palette is limited to detail');check(schema.types.member_avatar.pages[0]==='mine','member business palette is limited to mine');check(schema.types.search.kind==='search'&&!schema.types.search.pages,'search is an independent reusable component');
const sandbox={module:{exports:{}},require(name){if(name==='./action-icons')return require(path.join(fs.existsSync(path.join(root,'miniprogram/utils/action-icons.js'))?root:process.cwd(),'miniprogram/utils/action-icons'));if(name==='./composer-core')return C;throw Error(name);},wx:{}};vm.runInNewContext(fs.readFileSync(path.join(root,'miniprogram/utils/micro.js'),'utf8'),sandbox);for(const [key,{page,context}]of Object.entries(data)){const out=sandbox.module.exports.prepare(page.config,{key},context);check(out.nodes.length>0&&out.chrome&&out.footer,key+' native model shares component preparation and chrome');}
check(/virtualHost:true/.test(fs.readFileSync(path.join(root,'miniprogram/components/fb-render/index.js'),'utf8')),'native recursion adds no margin-bearing wrapper');check(!fs.readFileSync(path.join(plugin,'static/admin/micropage.js'),'utf8').includes('内部区域'),'inspector removes internal regions');
check(schema.pageDefaults.layout.topGap===0,'the first component has no automatic gap below navigation');
const rounded=C.prepare([{id:'rounded',type:'product_gallery',props:{radius:12,radiusTopLeft:0,radiusBottomRight:18}}],{...schema,typography:schema.defaultTypography},{product:{slides:[{image:'one.jpg'},{video:'two.mp4'}]}})[0];
check(rounded.style.includes('aspect-ratio:1')&&!rounded.style.includes('height:calc(420'),'product galleries default to their actual width instead of a fixed tall box');
check(rounded.mediaStyle.includes('border-radius:calc(12 *')&&rounded.mediaStyle.includes('border-top-left-radius:calc(0 *')&&rounded.mediaStyle.includes('border-bottom-right-radius:calc(18 *'),'all gallery media receive their independently configurable corner radii');
const colored=C.style({textRole:'title',size:88,color:'#ff6600'},'px',typo);
check(colored.includes('color:#ff6600')&&colored.includes('font-size:calc(23 *')&&typo.title.color==='#123456','a title can change its own color while retaining the shared title size');
const richColor=C.render([{id:'colored-content',type:'markdown',props:{markdown:'# Title\n\nBody',color:'#ff6600'}}],{...schema,typography:schema.defaultTypography},{});
check((richColor.match(/color:#ff6600/g)||[]).length>=3,'formatted headings and body content follow the component color');

const emptyLayout=[{id:'empty-layout',type:'container',props:{children:[]}}];
check(C.render(emptyLayout,schema,{}, {edit:true}).includes('fc-editor-dropzone')&&!C.render(emptyLayout,schema,{}).includes('fc-editor-dropzone'),'empty container hints exist only inside the editor');

const skuShape=C.prepare([{id:'sku-shape',type:'product_skus',props:{radius:12,radiusTopLeft:0}}],{...schema,typography:schema.defaultTypography},{product:{slides:[{image:'a'},{image:'b'}]}})[0];
const skuHtml=C.renderPrepared([skuShape]);
check((skuHtml.match(/border-top-left-radius:calc\(0 \*/g)||[]).length===3&&skuShape.shapeStyle.includes('border-radius:calc(12 *'),'SKU corner styles apply to each image as well as its component');
const mixed=C.prepare(schema.presets.mixed_layout.nodes,{...schema,typography:schema.defaultTypography},{})[0];
check(mixed.style.includes('grid-template-columns:repeat(2')&&mixed.children[0].props.direction==='column'&&mixed.children[1].style.includes('grid-template-columns:repeat(2'),'mixed container layout uses the same configurable grid and flex styles');
check(C.style({display:'none',columns:2}).includes('display:none')&&!C.style({display:'none',columns:2}).includes('display:grid'),'hiding a multicolumn container is respected by the shared renderer');





check(C.searchHref('page:search',{keyword:' 衬衫 ',catid:3,seriesid:2}).includes('catid=3&seriesid=2&keyword=%E8%A1%AC%E8%A1%AB'),'search submission retains category and series filters with an encoded trimmed keyword');
check(C.searchHref('page:catalog?catid=7',{keyword:'',catid:3},true)==='/pages/micro/index?key=catalog&catid=7&keyword=','a configured search destination wins while an empty keyword remains clearable');

check(C.href('binding:item.link',{item:{link:'plugin.php?id=flipbook:read&amp;albumid=7&amp;page=3'}},true)==='/pages/read/index?albumid=7&page=3','escaped server links decode once before native routing and retain reader progress');
check(C.href('https://example.com/&quot;bad',{})==='','decoded link values still pass the unsafe-character check');
console.log(count+' composer checks passed');
