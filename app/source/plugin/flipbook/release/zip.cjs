'use strict';
const zlib=require('node:zlib');
const LIMIT=50*1024*1024,MAX_FILES=2500;
const table=Array.from({length:256},(_,n)=>{for(let i=0;i<8;i++)n=n&1?0xedb88320^(n>>>1):n>>>1;return n>>>0;});
function crc32(bytes){let n=0xffffffff;for(const b of bytes)n=table[(n^b)&255]^(n>>>8);return (n^0xffffffff)>>>0;}
function safeName(name){return typeof name==='string'&&name.length<=240&&!/[\\\x00-\x1f:]/.test(name)&&!name.startsWith('/')&&!name.split('/').some(s=>s==='..'||s==='.')&&name.split('/').filter(Boolean).length<=20;}
/** Read only regular, bounded ZIP entries; central/local names, sizes and CRC must agree. */
function unpack(bytes){
 if(!Buffer.isBuffer(bytes)||bytes.length<22||bytes.length>22*1024*1024)throw Error('代码包需要为 22MB 以内的 ZIP');
 let end=-1;for(let i=bytes.length-22;i>=Math.max(0,bytes.length-65557);i--)if(bytes.readUInt32LE(i)===0x06054b50&&i+22+bytes.readUInt16LE(i+20)===bytes.length){end=i;break;}
 if(end<0||bytes.readUInt16LE(end+4)||bytes.readUInt16LE(end+6))throw Error('不支持分卷或损坏的 ZIP');
 const count=bytes.readUInt16LE(end+10),centralSize=bytes.readUInt32LE(end+12),start=bytes.readUInt32LE(end+16);
 if(!count||count>MAX_FILES||count!==bytes.readUInt16LE(end+8)||start+centralSize!==end)throw Error('代码包文件目录无效或数量超限');
 let offset=start,total=0;const files=new Map();
 for(let i=0;i<count;i++){
  if(offset+46>end||bytes.readUInt32LE(offset)!==0x02014b50)throw Error('ZIP 文件目录损坏');
  const flags=bytes.readUInt16LE(offset+8),method=bytes.readUInt16LE(offset+10),crc=bytes.readUInt32LE(offset+16),packed=bytes.readUInt32LE(offset+20),size=bytes.readUInt32LE(offset+24),nlen=bytes.readUInt16LE(offset+28),extra=bytes.readUInt16LE(offset+30),comment=bytes.readUInt16LE(offset+32),mode=bytes.readUInt32LE(offset+38)>>>16,local=bytes.readUInt32LE(offset+42);
  if(offset+46+nlen+extra+comment>end)throw Error('ZIP 文件名超出目录');
  const raw=bytes.subarray(offset+46,offset+46+nlen),name=raw.toString('utf8');offset+=46+nlen+extra+comment;
  if(!safeName(name)||name.includes('\ufffd')||(flags&1)||![0,8].includes(method)||(mode&0xf000)===0xa000||((mode&0xf000)&&![0x4000,0x8000].includes(mode&0xf000)))throw Error('代码包包含非法路径、链接、加密或不支持的文件');
  if(files.has(name))throw Error('代码包有重复文件');
  total+=size;if(size>8*1024*1024||total>LIMIT)throw Error('解压后代码包过大');
  if(local+30>start||bytes.readUInt32LE(local)!==0x04034b50||bytes.readUInt16LE(local+6)!==flags||bytes.readUInt16LE(local+8)!==method)throw Error('ZIP 本地文件头损坏');
  const ln=bytes.readUInt16LE(local+26),le=bytes.readUInt16LE(local+28),dataStart=local+30+ln+le;
  if(!bytes.subarray(local+30,local+30+ln).equals(raw)||dataStart+packed>start)throw Error('ZIP 本地文件路径或范围不一致');
  const data=bytes.subarray(dataStart,dataStart+packed),value=method===8?zlib.inflateRawSync(data,{maxOutputLength:Math.max(1,size+1)}):Buffer.from(data);
  if(value.length!==size||crc32(value)!==crc)throw Error('代码包文件校验失败');
  files.set(name,value);
 }
 if(offset!==end)throw Error('ZIP 目录长度不一致');
 const candidates=[...files.keys()].filter(n=>n.endsWith('project.config.json')&&!n.split('/').some(s=>s.startsWith('.')||s==='__MACOSX'));
 if(candidates.length!==1)throw Error('ZIP 中应包含且只包含一个 project.config.json');
 const prefix=candidates[0].slice(0,-'project.config.json'.length),out=new Map();
 for(const [name,value]of files){if(name.endsWith('/')||name.split('/').some(s=>s.startsWith('.')||s==='__MACOSX'))continue;if(!name.startsWith(prefix))throw Error('代码包包含项目目录以外的文件');const key=name.slice(prefix.length);if(!safeName(key)||out.has(key))throw Error('项目路径无效');out.set(key,value);}
 return out;
}
module.exports={unpack,safeName,crc32};
