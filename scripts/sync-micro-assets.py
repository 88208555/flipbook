from pathlib import Path
import re
import sys
root=Path(__file__).resolve().parents[1]
check_only='--check' in sys.argv[1:]
def output(relative,content):
 target=root/relative
 if check_only:
  if not target.exists() or target.read_text()!=content:raise SystemExit('Out of sync: '+relative)
 else:target.write_text(content)

s=(root/'miniprogram/app.wxss').read_text()
s=re.sub(r'(-?\d*\.?\d+)rpx',lambda m:'calc('+m[1]+' * var(--fb-rpx))',s)
s=re.sub(r'(?<![\w.-])page\s*\{','.fb-mc-main {',s)
s=re.sub(r'(?<![\w-])image(?![\w-])','img',s)
output('app/source/plugin/flipbook/static/css/micro-business.css','/* Generated from miniprogram/app.wxss by scripts/sync-micro-assets.py. */\n:root { --fb-rpx:min(0.13333333vw,0.64px); }\n'+s)
