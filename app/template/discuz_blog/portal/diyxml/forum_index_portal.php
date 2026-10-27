<?PHP exit('Access Denied');?>
<!--[name]聚合首页[/name]-->
<?xml version="1.0" encoding="ISO-8859-1"?>
<root>
	<item id="spacecss"><![CDATA[#portal_block_320 { background-color:transparent !important;background-image:none !important;}]]></item>
	<item id="layoutdata">
		<item id="diynavtop">
			<item id="frame`frame53tG7h">
				<item id="attr">
					<item id="name"><![CDATA[frame53tG7h]]></item>
					<item id="moveable"><![CDATA[true]]></item>
					<item id="className"><![CDATA[frame move-span cl frame-1]]></item>
					<item id="titles"><![CDATA[]]></item>
				</item>
				<item id="column`frame53tG7h_left">
					<item id="attr">
						<item id="name"><![CDATA[frame53tG7h_left]]></item>
						<item id="className"><![CDATA[column frame-1-c]]></item>
					</item>
					<item id="block`portal_block_320">
						<item id="attr">
							<item id="name"><![CDATA[portal_block_320]]></item>
							<item id="className"><![CDATA[sg-diy-feature-block block move-span]]></item>
							<item id="titles"><![CDATA[]]></item>
						</item>
					</item>
				</item>
			</item>
		</item>
		<item id="diy1"><![CDATA[]]></item>
		<item id="diy2">
			<item id="frame`frame6e91Pm">
				<item id="attr">
					<item id="name"><![CDATA[frame6e91Pm]]></item>
					<item id="moveable"><![CDATA[true]]></item>
					<item id="className"><![CDATA[frame move-span cl frame-1]]></item>
					<item id="titles"><![CDATA[]]></item>
				</item>
				<item id="column`frame6e91Pm_left">
					<item id="attr">
						<item id="name"><![CDATA[frame6e91Pm_left]]></item>
						<item id="className"><![CDATA[column frame-1-c]]></item>
					</item>
					<item id="block`portal_block_321">
						<item id="attr">
							<item id="name"><![CDATA[portal_block_321]]></item>
							<item id="className"><![CDATA[sg-diy-block block move-span]]></item>
							<item id="titles"><![CDATA[]]></item>
						</item>
					</item>
					<item id="block`portal_block_322">
						<item id="attr">
							<item id="name"><![CDATA[portal_block_322]]></item>
							<item id="className"><![CDATA[sg-diy-block block move-span]]></item>
							<item id="titles"><![CDATA[]]></item>
						</item>
					</item>
					<item id="block`portal_block_323">
						<item id="attr">
							<item id="name"><![CDATA[portal_block_323]]></item>
							<item id="className"><![CDATA[sg-diy-block block move-span]]></item>
							<item id="titles"><![CDATA[]]></item>
						</item>
					</item>
				</item>
			</item>
		</item>
		<item id="diy3"><![CDATA[]]></item>
	</item>
	<item id="blockdata">
		<item id="block">
			<item id="320">
				<item id="bid"><![CDATA[320]]></item>
				<item id="blockclass"><![CDATA[forum_thread]]></item>
				<item id="blocktype"><![CDATA[0]]></item>
				<item id="name"><![CDATA[拾光·首页精选幻灯片]]></item>
				<item id="title"><![CDATA[]]></item>
				<item id="classname"><![CDATA[sg-diy-feature-block]]></item>
				<item id="summary"><![CDATA[]]></item>
				<item id="uid"><![CDATA[1]]></item>
				<item id="username"><![CDATA[admin]]></item>
				<item id="styleid"><![CDATA[0]]></item>
				<item id="blockstyle">
					<item id="name"><![CDATA[]]></item>
					<item id="blockclass"><![CDATA[forum_thread]]></item>
					<item id="makethumb"><![CDATA[0]]></item>
					<item id="getpic"><![CDATA[1]]></item>
					<item id="getsummary"><![CDATA[1]]></item>
					<item id="settarget"><![CDATA[1]]></item>
					<item id="moreurl"><![CDATA[0]]></item>
					<item id="fields">
						<item id="0"><![CDATA[currentorder]]></item>
						<item id="1"><![CDATA[url]]></item>
						<item id="2"><![CDATA[title]]></item>
						<item id="3"><![CDATA[pic]]></item>
						<item id="4"><![CDATA[typeurl]]></item>
						<item id="5"><![CDATA[typename]]></item>
						<item id="6"><![CDATA[summary]]></item>
						<item id="7"><![CDATA[authorid]]></item>
						<item id="8"><![CDATA[author]]></item>
						<item id="9"><![CDATA[dateline]]></item>
						<item id="10"><![CDATA[views]]></item>
					</item>
					<item id="template">
						<item id="raw"><![CDATA[<section class="sg-diy-feature-grid" aria-label="精选内容" data-sg-feature-slider>
[loop]
<article class="sg-diy-feature-card sg-diy-feature-item--{currentorder}" data-sg-feature-order="{currentorder}">
<a class="sg-diy-feature-card__media" href="{url}" title="{title}"{target}><img src="{pic}" alt="{title}" /></a>
<span class="sg-diy-feature-card__shade" aria-hidden="true"></span>
<div class="sg-diy-feature-card__content">
<span class="sg-diy-feature-tag sg-diy-feature-tag--lead">精选</span>
<a class="sg-diy-feature-tag sg-diy-feature-tag--green sg-diy-feature-tag--category" href="{typeurl}">{typename}</a>
<h2><a href="{url}"{target}>{title}</a></h2>
<div class="sg-diy-feature-summary">{summary}</div>
<div class="sg-diy-feature-meta"><a class="sg-diy-feature-author" href="home.php?mod=space&amp;uid={authorid}"><img class="_avt" data-uid="{authorid}" data-size="small" alt="{author}" /><span>{author}</span></a><span class="sg-diy-feature-date">{dateline}</span><span>{views} 阅读</span></div>
</div>
</article>
[/loop]
<div class="sg-diy-feature-dots" aria-label="幻灯片切换"></div>
</section>]]></item>
						<item id="footer"><![CDATA[]]></item>
						<item id="header"><![CDATA[]]></item>
						<item id="indexplus">
						</item>
						<item id="index">
						</item>
						<item id="orderplus">
						</item>
						<item id="order">
						</item>
						<item id="loopplus">
						</item>
						<item id="loop"><![CDATA[<article class="sg-diy-feature-card sg-diy-feature-item--{currentorder}" data-sg-feature-order="{currentorder}">
<a class="sg-diy-feature-card__media" href="{url}" title="{title}"{target}><img src="{pic}" alt="{title}" /></a>
<span class="sg-diy-feature-card__shade" aria-hidden="true"></span>
<div class="sg-diy-feature-card__content">
<span class="sg-diy-feature-tag sg-diy-feature-tag--lead">精选</span>
<a class="sg-diy-feature-tag sg-diy-feature-tag--green sg-diy-feature-tag--category" href="{typeurl}">{typename}</a>
<h2><a href="{url}"{target}>{title}</a></h2>
<div class="sg-diy-feature-summary">{summary}</div>
<div class="sg-diy-feature-meta"><a class="sg-diy-feature-author" href="home.php?mod=space&amp;uid={authorid}"><img class="_avt" data-uid="{authorid}" data-size="small" alt="{author}" /><span>{author}</span></a><span class="sg-diy-feature-date">{dateline}</span><span>{views} 阅读</span></div>
</div>
</article>]]></item>
					</item>
					<item id="hash"><![CDATA[aad49c4e]]></item>
				</item>
				<item id="picwidth"><![CDATA[0]]></item>
				<item id="picheight"><![CDATA[0]]></item>
				<item id="target"><![CDATA[blank]]></item>
				<item id="dateformat"><![CDATA[Y-m-d]]></item>
				<item id="dateuformat"><![CDATA[0]]></item>
				<item id="script"><![CDATA[thread]]></item>
				<item id="param">
					<item id="tids"><![CDATA[]]></item>
					<item id="uids"><![CDATA[]]></item>
					<item id="keyword"><![CDATA[]]></item>
					<item id="tagkeyword"><![CDATA[]]></item>
					<item id="typeids"><![CDATA[]]></item>
					<item id="reply"><![CDATA[0]]></item>
					<item id="recommend"><![CDATA[0]]></item>
					<item id="viewmod"><![CDATA[0]]></item>
					<item id="rewardstatus"><![CDATA[0]]></item>
					<item id="picrequired"><![CDATA[1]]></item>
					<item id="orderby"><![CDATA[lastpost]]></item>
					<item id="postdateline"><![CDATA[0]]></item>
					<item id="lastpost"><![CDATA[0]]></item>
					<item id="highlight"><![CDATA[0]]></item>
					<item id="titlelength"><![CDATA[40]]></item>
					<item id="summarylength"><![CDATA[80]]></item>
					<item id="startrow"><![CDATA[0]]></item>
					<item id="items"><![CDATA[10]]></item>
				</item>
				<item id="shownum"><![CDATA[10]]></item>
				<item id="cachetime"><![CDATA[3600]]></item>
				<item id="cachetimerange"><![CDATA[]]></item>
				<item id="punctualupdate"><![CDATA[0]]></item>
				<item id="hidedisplay"><![CDATA[0]]></item>
				<item id="dateline"><![CDATA[1784254953]]></item>
				<item id="notinherited"><![CDATA[0]]></item>
				<item id="isblank"><![CDATA[0]]></item>
			</item>
			<item id="321">
				<item id="bid"><![CDATA[321]]></item>
				<item id="blockclass"><![CDATA[member_member]]></item>
				<item id="blocktype"><![CDATA[0]]></item>
				<item id="name"><![CDATA[拾光·关于我]]></item>
				<item id="title"><![CDATA[]]></item>
				<item id="classname"><![CDATA[sg-diy-block]]></item>
				<item id="summary"><![CDATA[]]></item>
				<item id="uid"><![CDATA[1]]></item>
				<item id="username"><![CDATA[admin]]></item>
				<item id="styleid"><![CDATA[0]]></item>
				<item id="blockstyle">
					<item id="name"><![CDATA[]]></item>
					<item id="blockclass"><![CDATA[member_member]]></item>
					<item id="makethumb"><![CDATA[0]]></item>
					<item id="getpic"><![CDATA[0]]></item>
					<item id="getsummary"><![CDATA[0]]></item>
					<item id="settarget"><![CDATA[1]]></item>
					<item id="moreurl"><![CDATA[0]]></item>
					<item id="fields">
						<item id="0"><![CDATA[url]]></item>
						<item id="1"><![CDATA[avatar_middle]]></item>
						<item id="2"><![CDATA[title]]></item>
						<item id="3"><![CDATA[threads]]></item>
						<item id="4"><![CDATA[posts]]></item>
						<item id="5"><![CDATA[credits]]></item>
					</item>
					<item id="template">
						<item id="raw"><![CDATA[<section class="sg-diy-card sg-diy-about">
<div class="sg-diy-card__title"><h2>关于我</h2></div>
[loop]
<a class="sg-diy-about__avatar" href="{url}"{target}><img src="{avatar_middle}" alt="{title}" /></a>
<h3><a href="{url}"{target}>{title}</a></h3>
<p class="sg-diy-about__bio">在时光里写下生活与思考，收藏每一段值得记住的日常。</p>
<div class="sg-diy-about__stats"><span><b>{threads}</b>主题</span><span><b>{posts}</b>帖子</span><span><b>{credits}</b>积分</span></div>
[/loop]
</section>]]></item>
						<item id="footer"><![CDATA[]]></item>
						<item id="header"><![CDATA[]]></item>
						<item id="indexplus">
						</item>
						<item id="index">
						</item>
						<item id="orderplus">
						</item>
						<item id="order">
						</item>
						<item id="loopplus">
						</item>
						<item id="loop"><![CDATA[<a class="sg-diy-about__avatar" href="{url}"{target}><img src="{avatar_middle}" alt="{title}" /></a>
<h3><a href="{url}"{target}>{title}</a></h3>
<p class="sg-diy-about__bio">在时光里写下生活与思考，收藏每一段值得记住的日常。</p>
<div class="sg-diy-about__stats"><span><b>{threads}</b>主题</span><span><b>{posts}</b>帖子</span><span><b>{credits}</b>积分</span></div>]]></item>
					</item>
					<item id="hash"><![CDATA[59fa82da]]></item>
				</item>
				<item id="picwidth"><![CDATA[0]]></item>
				<item id="picheight"><![CDATA[0]]></item>
				<item id="target"><![CDATA[blank]]></item>
				<item id="dateformat"><![CDATA[Y-m-d]]></item>
				<item id="dateuformat"><![CDATA[0]]></item>
				<item id="script"><![CDATA[memberspecified]]></item>
				<item id="param">
					<item id="uids"><![CDATA[1]]></item>
					<item id="special"><![CDATA[]]></item>
					<item id="items"><![CDATA[1]]></item>
				</item>
				<item id="shownum"><![CDATA[1]]></item>
				<item id="cachetime"><![CDATA[3600]]></item>
				<item id="cachetimerange"><![CDATA[]]></item>
				<item id="punctualupdate"><![CDATA[0]]></item>
				<item id="hidedisplay"><![CDATA[0]]></item>
				<item id="dateline"><![CDATA[1784255140]]></item>
				<item id="notinherited"><![CDATA[0]]></item>
				<item id="isblank"><![CDATA[0]]></item>
			</item>
			<item id="322">
				<item id="bid"><![CDATA[322]]></item>
				<item id="blockclass"><![CDATA[forum_thread]]></item>
				<item id="blocktype"><![CDATA[0]]></item>
				<item id="name"><![CDATA[拾光·热门文章]]></item>
				<item id="title"><![CDATA[]]></item>
				<item id="classname"><![CDATA[sg-diy-block]]></item>
				<item id="summary"><![CDATA[]]></item>
				<item id="uid"><![CDATA[1]]></item>
				<item id="username"><![CDATA[admin]]></item>
				<item id="styleid"><![CDATA[0]]></item>
				<item id="blockstyle">
					<item id="name"><![CDATA[]]></item>
					<item id="blockclass"><![CDATA[forum_thread]]></item>
					<item id="makethumb"><![CDATA[0]]></item>
					<item id="getpic"><![CDATA[0]]></item>
					<item id="getsummary"><![CDATA[0]]></item>
					<item id="settarget"><![CDATA[1]]></item>
					<item id="moreurl"><![CDATA[0]]></item>
					<item id="fields">
						<item id="0"><![CDATA[currentorder]]></item>
						<item id="1"><![CDATA[url]]></item>
						<item id="2"><![CDATA[title]]></item>
						<item id="3"><![CDATA[views]]></item>
					</item>
					<item id="template">
						<item id="raw"><![CDATA[<section class="sg-diy-card sg-diy-hot">
<div class="sg-diy-card__title"><h2>热门文章</h2></div>
<ol class="sg-diy-hot-list">
[loop]
<li><span class="sg-diy-hot-list__rank">{currentorder}</span><a href="{url}" title="{title}"{target}>{title}</a><em>{views} 阅读</em></li>
[/loop]
</ol>
</section>]]></item>
						<item id="footer"><![CDATA[]]></item>
						<item id="header"><![CDATA[]]></item>
						<item id="indexplus">
						</item>
						<item id="index">
						</item>
						<item id="orderplus">
						</item>
						<item id="order">
						</item>
						<item id="loopplus">
						</item>
						<item id="loop"><![CDATA[<li><span class="sg-diy-hot-list__rank">{currentorder}</span><a href="{url}" title="{title}"{target}>{title}</a><em>{views} 阅读</em></li>]]></item>
					</item>
					<item id="hash"><![CDATA[5b337e6a]]></item>
				</item>
				<item id="picwidth"><![CDATA[0]]></item>
				<item id="picheight"><![CDATA[0]]></item>
				<item id="target"><![CDATA[blank]]></item>
				<item id="dateformat"><![CDATA[Y-m-d]]></item>
				<item id="dateuformat"><![CDATA[0]]></item>
				<item id="script"><![CDATA[thread]]></item>
				<item id="param">
					<item id="tids"><![CDATA[]]></item>
					<item id="uids"><![CDATA[]]></item>
					<item id="keyword"><![CDATA[]]></item>
					<item id="tagkeyword"><![CDATA[]]></item>
					<item id="typeids"><![CDATA[]]></item>
					<item id="reply"><![CDATA[0]]></item>
					<item id="recommend"><![CDATA[0]]></item>
					<item id="viewmod"><![CDATA[0]]></item>
					<item id="rewardstatus"><![CDATA[0]]></item>
					<item id="picrequired"><![CDATA[0]]></item>
					<item id="orderby"><![CDATA[lastpost]]></item>
					<item id="postdateline"><![CDATA[0]]></item>
					<item id="lastpost"><![CDATA[0]]></item>
					<item id="highlight"><![CDATA[0]]></item>
					<item id="titlelength"><![CDATA[40]]></item>
					<item id="summarylength"><![CDATA[80]]></item>
					<item id="startrow"><![CDATA[0]]></item>
					<item id="items"><![CDATA[10]]></item>
				</item>
				<item id="shownum"><![CDATA[10]]></item>
				<item id="cachetime"><![CDATA[3600]]></item>
				<item id="cachetimerange"><![CDATA[]]></item>
				<item id="punctualupdate"><![CDATA[0]]></item>
				<item id="hidedisplay"><![CDATA[0]]></item>
				<item id="dateline"><![CDATA[1784255210]]></item>
				<item id="notinherited"><![CDATA[0]]></item>
				<item id="isblank"><![CDATA[0]]></item>
			</item>
			<item id="323">
				<item id="bid"><![CDATA[323]]></item>
				<item id="blockclass"><![CDATA[forum_forum]]></item>
				<item id="blocktype"><![CDATA[0]]></item>
				<item id="name"><![CDATA[拾光·文章分类]]></item>
				<item id="title"><![CDATA[]]></item>
				<item id="classname"><![CDATA[sg-diy-block]]></item>
				<item id="summary"><![CDATA[]]></item>
				<item id="uid"><![CDATA[1]]></item>
				<item id="username"><![CDATA[admin]]></item>
				<item id="styleid"><![CDATA[0]]></item>
				<item id="blockstyle">
					<item id="name"><![CDATA[]]></item>
					<item id="blockclass"><![CDATA[forum_forum]]></item>
					<item id="makethumb"><![CDATA[0]]></item>
					<item id="getpic"><![CDATA[0]]></item>
					<item id="getsummary"><![CDATA[0]]></item>
					<item id="settarget"><![CDATA[1]]></item>
					<item id="moreurl"><![CDATA[0]]></item>
					<item id="fields">
						<item id="0"><![CDATA[url]]></item>
						<item id="1"><![CDATA[title]]></item>
						<item id="2"><![CDATA[threads]]></item>
					</item>
					<item id="template">
						<item id="raw"><![CDATA[<section class="sg-diy-card sg-diy-category">
<div class="sg-diy-card__title"><h2>文章分类</h2></div>
<div class="sg-diy-category-grid">
[loop]
<a href="{url}"{target}><span>{title}</span><em>{threads}</em></a>
[/loop]
</div>
</section>]]></item>
						<item id="footer"><![CDATA[]]></item>
						<item id="header"><![CDATA[]]></item>
						<item id="indexplus">
						</item>
						<item id="index">
						</item>
						<item id="orderplus">
						</item>
						<item id="order">
						</item>
						<item id="loopplus">
						</item>
						<item id="loop"><![CDATA[<a href="{url}"{target}><span>{title}</span><em>{threads}</em></a>]]></item>
					</item>
					<item id="hash"><![CDATA[380711ad]]></item>
				</item>
				<item id="picwidth"><![CDATA[0]]></item>
				<item id="picheight"><![CDATA[0]]></item>
				<item id="target"><![CDATA[blank]]></item>
				<item id="dateformat"><![CDATA[Y-m-d]]></item>
				<item id="dateuformat"><![CDATA[0]]></item>
				<item id="script"><![CDATA[forum]]></item>
				<item id="param">
					<item id="fids"><![CDATA[]]></item>
					<item id="fups">
						<item id="0"><![CDATA[0]]></item>
					</item>
					<item id="viewtype"><![CDATA[]]></item>
					<item id="titlelength"><![CDATA[40]]></item>
					<item id="summarylength"><![CDATA[80]]></item>
					<item id="orderby"><![CDATA[displayorder]]></item>
					<item id="items"><![CDATA[10]]></item>
				</item>
				<item id="shownum"><![CDATA[10]]></item>
				<item id="cachetime"><![CDATA[3600]]></item>
				<item id="cachetimerange"><![CDATA[]]></item>
				<item id="punctualupdate"><![CDATA[0]]></item>
				<item id="hidedisplay"><![CDATA[0]]></item>
				<item id="dateline"><![CDATA[1784255259]]></item>
				<item id="notinherited"><![CDATA[0]]></item>
				<item id="isblank"><![CDATA[0]]></item>
			</item>
		</item>
		<item id="style">
		</item>
	</item>
</root>