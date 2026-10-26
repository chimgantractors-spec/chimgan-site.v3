<?php
require __DIR__.'/core.php';
$path=rtrim(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),'/')?:'/';
if($path=='/lead'&&$_SERVER['REQUEST_METHOD']=='POST'){
 chk();$f=fn($k,$l)=>mb_substr(trim($_POST[$k]??''),0,$l);
 [$n,$p,$m,$t]=[$f('name',100),$f('phone',40),$f('msg',1000),$f('tractor',100)];
 if($n&&$p&&!$f('site',5)){
  $db->prepare('INSERT INTO leads(name,phone,msg,tractor,created) VALUES(?,?,?,?,?)')->execute([$n,$p,$m,$t,date('Y-m-d H:i')]);
  @mail(MAIL,'=?UTF-8?B?'.base64_encode('Заявка с сайта Chimgan').'?=',"Имя: $n\nТелефон: $p\nМодель: $t\nСообщение: $m","Content-Type: text/plain; charset=UTF-8\r\nFrom: noreply@".preg_replace('/[^a-z0-9.-]|^www\./i','',$_SERVER['HTTP_HOST']));
 }
 header('Location: '.(parse_url($_SERVER['HTTP_REFERER']??'',PHP_URL_PATH)?:'/').'?sent=1#form');exit;
}
if($path=='/admin'){header('Location: /admin.php');exit;}
$all=$db->query('SELECT * FROM tractors WHERE hidden=0 ORDER BY id')->fetchAll();
function opts($c){global $db;$o='<option value="">'.e(t('all')).'</option>';foreach($db->query("SELECT DISTINCT $c FROM tractors WHERE hidden=0 AND $c<>'' ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN) as $v)$o.='<option value="'.e($v).'"'.(($_GET[$c]??'')===$v?' selected':'').'>'.e($c=='class'?cls($v):$v).'</option>';return $o;}

if($path=='/'){head(t('home_title'),t('home_desc'));?>
<section class="hero"><div class="w"><h1><?=t('hero_h1')?></h1><p><?=t('hero_p')?></p><a class="btn o" href="/catalog"><?=t('nav_catalog')?></a><a class="btn l" href="/installment"><?=t('calc_btn')?></a></div></section>
<div class="w facts rv"><span><b><?=count($all)?></b> <?=t('models_in_catalog')?></span><span><b><?=t('from_pct',DOWN_MIN)?></b> <?=t('down_label')?></span><span><b><?=t('months_short',MONTHS)?></b> <?=t('term_label')?></span><span><b><?=t('hours_days')?></b> 9:00–18:00</span></div>
<section class="sec"><div class="w"><h2 class="rv"><?=t('popular')?></h2><div class="g"><?php foreach(array_slice($all,0,4) as $t)card($t);?></div></div></section>
<section class="sec alt"><div class="w cols4">
<div class="rv"><h3><?=t('adv1_h')?></h3><p><?=t('adv1_p',DOWN_MIN,MONTHS)?></p></div>
<div class="rv"><h3><?=t('adv2_h')?></h3><p><?=t('adv2_p')?></p></div>
<div class="rv"><h3><?=t('adv3_h')?></h3><p><?=t('adv3_p')?></p></div>
<div class="rv"><h3><?=t('adv4_h')?></h3><p><?=t('adv4_p')?></p></div></div></section>
<section class="sec"><div class="w band rv"><div><h2><?=t('band_h')?></h2><p><?=t('band_p')?></p></div><a class="btn o" href="/installment"><?=t('calc_btn')?></a></div></section>
<section class="sec alt"><div class="w"><h2 class="rv"><?=t('reviews')?></h2><div class="cols4"><?php for($i=0;$i<3;$i++):?><blockquote class="rv"><p><?=t('review_text')?></p><cite><?=t('review_cite')?></cite></blockquote><?php endfor?></div></div></section>
<section class="sec" id="form"><div class="w cols2"><div><h2><?=t('lead_h')?></h2><p><?=t('lead_p')?></p></div><div><?php leadform()?></div></div></section>
<?php foot();exit;}

if($path=='/catalog'){
 $g=fn($k)=>trim($_GET[$k]??'');$w=['hidden=0'];$a=[];
 foreach(['brand','class','drive'] as $k)if($g($k)!==''){$w[]="$k=?";$a[]=$g($k);}
 foreach(['pmin'=>'power>=?','pmax'=>'power<=?','rmin'=>'price>=?','rmax'=>'price<=?'] as $k=>$c)if($g($k)!==''){$w[]=$c;$a[]=(int)$g($k);}
 $o=['p1'=>'price ASC','p2'=>'price DESC','w1'=>'power ASC','w2'=>'power DESC'][$g('s')]??'id';
 $s=$db->prepare('SELECT * FROM tractors WHERE '.implode(' AND ',$w)." ORDER BY $o");$s->execute($a);
 $rows=array_filter($s->fetchAll(),fn($t)=>$g('q')===''||mb_stripos($t['name'].' '.$t['brand'],$g('q'))!==false);
 head(t('cat_title'),t('cat_desc'));?>
<section class="sec"><div class="w"><h1><?=t('nav_catalog')?></h1>
<form class="filters" method="get"><label><?=t('search')?><input name="q" type="search" placeholder="<?=e(t('model_name'))?>" value="<?=e($g('q'))?>"></label>
<label><?=t('brand')?><select name="brand"><?=opts('brand')?></select></label><label><?=t('class')?><select name="class"><?=opts('class')?></select></label><label><?=t('drive')?><select name="drive"><?=opts('drive')?></select></label>
<label><?=t('power_hp')?><span><input name="pmin" type="number" min="0" placeholder="<?=e(t('from'))?>" value="<?=e($g('pmin'))?>"><input name="pmax" type="number" min="0" placeholder="<?=e(t('to'))?>" value="<?=e($g('pmax'))?>"></span></label>
<label><?=t('price_usd')?><span><input name="rmin" type="number" min="0" placeholder="<?=e(t('from'))?>" value="<?=e($g('rmin'))?>"><input name="rmax" type="number" min="0" placeholder="<?=e(t('to'))?>" value="<?=e($g('rmax'))?>"></span></label>
<label><?=t('sort')?><select name="s"><option value=""><?=t('sort_default')?></option><?php foreach(['p1','p2','w1','w2'] as $k)echo "<option value=$k".($g('s')==$k?' selected':'').'>'.e(t("sort_$k")).'</option>'?></select></label>
<button class="btn o"><?=t('show')?></button></form>
<p class="cnt"><?=t('found')?> <b><?=count($rows)?></b></p>
<?php if(!$rows)echo '<p>'.e(t('nothing')).'</p>'?><div class="g"><?php foreach($rows as $t)card($t);?></div></div></section>
<?php foot();exit;}

if(preg_match('#^/tractor/([a-z0-9-]+)$#',$path,$m)){
 $s=$db->prepare('SELECT * FROM tractors WHERE slug=? AND hidden=0');$s->execute([$m[1]]);
 if($t=$s->fetch()){
  $p=photos($t);$host='https://'.$_SERVER['HTTP_HOST'];$ds=descr($t);
  $ld='<script type="application/ld+json">'.json_encode(['@context'=>'https://schema.org','@type'=>'Product','name'=>$t['name'],'brand'=>$t['brand'],'description'=>$ds,'image'=>array_map(fn($f)=>"$host/uploads/$f",$p),'offers'=>['@type'=>'Offer','price'=>(int)$t['price'],'priceCurrency'=>'USD','availability'=>'https://schema.org/InStock','url'=>"$host/tractor/{$t['slug']}"]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG).'</script>';
  head($t['name'].t('price_specs'),mb_substr(strip_tags($ds),0,150),$ld);?>
<section class="sec"><div class="w cols2 top">
<div class="rv"><?php if($p):?><img id="g" class="main" src="/uploads/<?=e($p[0])?>" alt="<?=e($t['name'])?>"><div class="th"><?php foreach($p as $f):?><img src="/uploads/<?=e($f)?>" data-s="/uploads/<?=e($f)?>" alt="" loading="lazy"><?php endforeach?></div><?php else:?><div class="im ph main"><?=e($t['brand'])?></div><?php endif?></div>
<div class="rv"><p class="m"><?=e($t['brand'])?>, <?=e(cls($t['class']))?></p><h1><?=e($t['name'])?></h1><p class="price"><?=money($t['price'])?></p><a class="btn o" href="#form"><?=t('leave_req')?></a><a class="btn l" href="/installment?m=<?=e($t['slug'])?>"><?=t('calc_inst')?></a>
<table><?php foreach(array_merge([[t('brand'),$t['brand']],[t('class'),cls($t['class'])],[t('power'),(int)$t['power'].' '.t('hp')],[t('drive'),$t['drive']]],specs($t['specs'])) as [$k,$v]):?><tr><th><?=e($k)?></th><td><?=e($v)?></td></tr><?php endforeach?></table></div></div></section>
<section class="sec alt"><div class="w cols2"><div class="rv"><h2><?=t('descr_h')?></h2><p><?=nl2br(e($ds))?></p></div><?php if($eq=lines($t['equip'])):?><div class="rv"><h2><?=t('equip_h')?></h2><ul><?php foreach($eq as $l):?><li><?=e($l)?></li><?php endforeach?></ul></div><?php endif?></div></section>
<section class="sec" id="form"><div class="w cols2"><div><h2><?=t('req_for',e($t['name']))?></h2></div><div><?php leadform($t['name'])?></div></div></section>
<?php $s=$db->prepare('SELECT * FROM tractors WHERE hidden=0 AND id<>? AND (brand=? OR class=?) LIMIT 3');$s->execute([$t['id'],$t['brand'],$t['class']]);
  if($sim=$s->fetchAll()){?><section class="sec alt"><div class="w"><h2><?=t('similar')?></h2><div class="g"><?php foreach($sim as $x)card($x);?></div></div></section><?php }
  foot();exit;}
}

if($path=='/installment'){head(t('inst_title'),t('inst_desc',DOWN_MIN,MONTHS));?>
<section class="sec"><div class="w cols2 top"><div class="rv"><h1><?=t('inst_h1')?></h1><p><?=t('inst_p1',DOWN_MIN,MONTHS)?></p><p><?=t(RATE==0?'inst_p2_free':'inst_p2_rate',RATE)?></p>
<h2 class="gap"><?=t('how_h')?></h2><ol><li><?=t('step1')?></li><li><?=t('step2')?></li><li><?=t('step3')?></li><li><?=t('step4')?></li></ol></div>
<form id="calc" class="calc rv"><label><?=t('model')?><select name="m"><?php foreach($all as $t):?><option value="<?=e($t['slug'])?>" data-p="<?=(int)$t['price']?>"<?=($_GET['m']??'')==$t['slug']?' selected':''?>><?=e($t['name'])?>, <?=money($t['price'])?></option><?php endforeach?></select></label>
<label><?=t('down_pay')?> <b id="od"></b><input name="d" type="range" min="<?=DOWN_MIN?>" max="<?=DOWN_MAX?>" step="5" value="<?=DOWN_MIN?>"></label>
<label><?=t('term')?><select name="n"><?php for($i=1;$i<=MONTHS;$i++)echo "<option value=$i>".e(t('mo',$i)).'</option>'?></select></label>
<label><?=t('rate_label')?><input name="r" value="<?=RATE?>" readonly></label>
<div class="res"><span><?=t('monthly')?><b id="o1"></b></span><span><?=t('overpay')?><b id="o2"></b></span><span><?=t('total')?><b id="o3"></b></span></div>
<a class="btn o" id="ap" href="/contacts#form"><?=t('leave_req')?></a></form></div></section>
<section class="sec alt"><div class="w"><h2><?=t('faq_h')?></h2>
<details><summary><?=t('q1')?></summary><p><?=t('a1',DOWN_MIN)?></p></details>
<details><summary><?=t('q2')?></summary><p><?=t('a2',MONTHS)?></p></details>
<details><summary><?=t('q3')?></summary><p><?=t('a3')?></p></details>
<details><summary><?=t('q4')?></summary><p><?=t('a4')?></p></details></div></section>
<?php foot();exit;}

if($path=='/about'){head(t('about_h1'),t('about_desc'));?>
<section class="sec"><div class="w cols2 top"><div class="rv"><h1><?=t('about_h1')?></h1><p><?=t('about_p')?></p></div><div class="rv"><h2><?=t('history')?></h2><p><?=t('history_p')?></p></div></div></section>
<section class="sec alt"><div class="w"><h2 class="rv"><?=t('team')?></h2><div class="cols4"><?php for($i=0;$i<3;$i++):?><div class="rv"><h3><?=t('full_name')?></h3><p class="m"><?=t('position')?></p></div><?php endfor?></div></div></section>
<section class="sec"><div class="w cols4"><div class="rv"><h3><?=t('wh_h')?></h3><p><?=t('wh_p')?></p></div><div class="rv"><h3><?=t('wr_h')?></h3><p><?=t('wr_p')?></p></div><div class="rv"><h3><?=t('sv_h')?></h3><p><?=t('sv_p')?></p></div></div></section>
<?php foot();exit;}

if($path=='/contacts'){head(t('nav_contacts'),t('contacts_desc',PHONE,MAIL,ADDR,t('hours')));?>
<section class="sec"><div class="w cols2 top"><div class="rv"><h1><?=t('nav_contacts')?></h1><table><tr><th><?=t('phone')?></th><td><a href="tel:<?=TEL?>"><?=PHONE?></a></td></tr><tr><th><?=t('c_mail')?></th><td><a href="mailto:<?=MAIL?>"><?=MAIL?></a></td></tr><tr><th><?=t('c_addr')?></th><td><?=ADDR?></td></tr><tr><th><?=t('c_hours')?></th><td><?=t('hours')?></td></tr></table></div>
<iframe class="map rv" title="<?=e(t('map'))?>" loading="lazy" src="https://maps.google.com/maps?q=<?=urlencode(ADDR)?>&output=embed&hl=<?=$L?>"></iframe></div></section>
<section class="sec alt" id="form"><div class="w cols2"><div><h2><?=t('write_us')?></h2><p><?=t('write_p')?></p></div><div><?php leadform($_GET['m']??'')?></div></div></section>
<?php foot();exit;}

http_response_code(404);head(t('nf'),'');?>
<section class="sec"><div class="w"><h1><?=t('nf')?></h1><a class="btn o" href="/"><?=t('to_home')?></a></div></section>
<?php foot();
