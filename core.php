<?php
const PHONE='+998 71 210 90 90',TEL='+998712109090',MAIL='chimgan.tractors@gmail.com',ADDR='Zangiota tumani, M39 BUT5, Erkin post',HOURS='Пн–Пт, 9:00–18:00',DOWN_MIN=60,DOWN_MAX=90,MONTHS=6,RATE=0;
session_start();
const LANGS=['ru'=>'RU','uz'=>'UZ','en'=>'EN'];
$TR=json_decode(file_get_contents(__DIR__.'/lang.json'),true)?:[];
$L=$_COOKIE['lang']??'ru';if(!isset(LANGS[$L]))$L='ru';
if(isset($_GET['lang'])&&$_SERVER['REQUEST_METHOD']=='GET'){
 if(isset(LANGS[$_GET['lang']]))setcookie('lang',$_GET['lang'],['expires'=>time()+31536000,'path'=>'/','samesite'=>'Lax']);
 $q=$_GET;unset($q['lang']);header('Location: '.strtok($_SERVER['REQUEST_URI'],'?').($q?'?'.http_build_query($q):''));exit;
}
function t($k,...$a){global $TR,$L;$s=$TR[$k][$L]??$TR[$k]['ru']??$k;foreach($a as $i=>$v)$s=str_replace('{'.$i.'}',(string)$v,$s);return $s;}
function cls($v){global $TR,$L;return $TR['cls.'.$v][$L]??$v;}
function langurl($l){$q=$_GET;$q['lang']=$l;return strtok($_SERVER['REQUEST_URI'],'?').'?'.http_build_query($q);}
date_default_timezone_set('Asia/Tashkent');
foreach(['data','uploads'] as $d)is_dir(__DIR__."/$d")||mkdir(__DIR__."/$d",0755);
is_file(__DIR__.'/data/.htaccess')||file_put_contents(__DIR__.'/data/.htaccess',"<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
$db=new PDO('sqlite:'.__DIR__.'/data/db.sqlite',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY,login TEXT UNIQUE,hash TEXT,role TEXT);
CREATE TABLE IF NOT EXISTS tractors(id INTEGER PRIMARY KEY,slug TEXT UNIQUE,name TEXT,brand TEXT,class TEXT,power INT,drive TEXT,price INT,descr TEXT,specs TEXT,equip TEXT,photos TEXT DEFAULT '[]',hidden INT DEFAULT 0);
CREATE TABLE IF NOT EXISTS leads(id INTEGER PRIMARY KEY,name TEXT,phone TEXT,msg TEXT,tractor TEXT,status TEXT DEFAULT 'new',created TEXT)");
if(!$db->query('SELECT 1 FROM users')->fetch()&&!$db->query('SELECT 1 FROM tractors')->fetch())
foreach([['gd-304','GD 304','Мини',30,8500,'gd-304.jpg'],['gd-404','GD 404','Мини',40,9700,'gd-404.jpg'],['gd-504','GD 504','Универсальный',50,10700,'gd-504.jpg'],['gd-404-2','GD 404','Мини',40,9000,'gd-404-2.jpg'],['gd-504-2','GD 504','Универсальный',50,9700,'gd-504-2.jpg'],['gd-504-3','GD 504','Универсальный',50,11000,'gd-504-3.jpg'],['gd-704','GD 704','Универсальный',70,15800,'gd-704.jpg'],['sf-504','SF 504','Универсальный',50,14500,'sf-504.jpg']] as $r)
 $db->prepare('INSERT INTO tractors(slug,name,brand,class,power,drive,price,descr,specs,equip,photos) VALUES(?,?,?,?,?,?,?,?,?,?,?)')->execute([$r[0],$r[1],'CHIMGAN',$r[2],$r[3],'4x4',$r[4],'','','',json_encode([$r[5]])]);
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES);}
function money($n){return number_format((int)$n,0,'',' ').' $';}
function photos($t){return json_decode($t['photos']?:'[]',true)?:[];}
function csrf(){return $_SESSION['c']??=bin2hex(random_bytes(16));}
function chk(){hash_equals($_SESSION['c']??'',$_POST['c']??'')||exit('Ошибка формы. Обновите страницу.');}
function slug($s){$s=function_exists('transliterator_transliterate')?transliterator_transliterate('Any-Latin;Latin-ASCII;Lower()',$s):strtolower($s);return trim(preg_replace('/[^a-z0-9]+/','-',$s),'-')?:'model';}
function descr($t){$d=trim((string)$t['descr']);return($d===''||preg_match('/^Трактор CHIMGAN .*В наличии\.$/u',$d))?t('default_descr',$t['brand'],$t['name'],(int)$t['power'],$t['drive']):$d;}
function lines($t){return array_filter(array_map('trim',explode("\n",$t)));}
function specs($t){$o=[];foreach(lines($t) as $l)if(strpos($l,':'))$o[]=array_map('trim',explode(':',$l,2));return $o;}
function card($t){$p=photos($t);echo '<a class="card rv" href="/tractor/'.e($t['slug']).'"><div class="im'.($p?'':' ph').'">'.($p?'<img src="/uploads/'.e($p[0]).'" alt="'.e($t['name']).'" loading="lazy">':e($t['brand'])).'</div><div class="bd"><small>'.e($t['brand']).', '.e(cls($t['class'])).'</small><h3>'.e($t['name']).'</h3><p>'.(int)$t['power'].' '.t('hp').', '.e($t['drive']).'</p><b>'.money($t['price']).'</b></div></a>';}
function leadform($m=''){if(isset($_GET['sent']))echo '<p class="ok">'.e(t('lead_ok')).'</p>';?><form class="form" method="post" action="/lead"><input type="hidden" name="c" value="<?=csrf()?>"><input type="hidden" name="tractor" value="<?=e($m)?>"><input class="hp" name="site" tabindex="-1" autocomplete="off"><label><?=t('name')?><input name="name" required></label><label><?=t('phone')?><input name="phone" type="tel" placeholder="+998" required></label><label><?=t('comment')?><textarea name="msg" rows="3"></textarea></label><button class="btn o"><?=t('send')?></button></form><?php }
function head($t,$d,$x=''){global $L;?><!doctype html><html lang="<?=$L?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($t)?> — Chimgan</title><meta name="description" content="<?=e($d)?>"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;600&family=Unbounded:wght@600;700&display=swap"><link rel="stylesheet" href="/assets/style.css?v=<?=filemtime(__DIR__.'/assets/style.css')?>"><?=$x?></head><body><header><div class="w nav"><a class="logo" href="/">Chimgan</a><nav><a href="/catalog"><?=t('nav_catalog')?></a><a href="/installment"><?=t('nav_installment')?></a><a href="/about"><?=t('nav_about')?></a><a href="/contacts"><?=t('nav_contacts')?></a></nav><a class="tel" href="tel:<?=TEL?>"><?=PHONE?></a><div class="lang"><?php foreach(LANGS as $k=>$n):?><a href="<?=e(langurl($k))?>" hreflang="<?=$k?>" lang="<?=$k?>"<?=$k==$L?' class="on" aria-current="true"':''?>><?=$n?></a><?php endforeach?></div></div></header><main><?php }
function foot(){?></main><footer><div class="w"><b>Chimgan</b><span><?=ADDR?></span><span><?=t('hours')?></span><a href="tel:<?=TEL?>"><?=PHONE?></a><a href="mailto:<?=MAIL?>"><?=MAIL?></a></div></footer><script src="/assets/app.js?v=<?=filemtime(__DIR__.'/assets/app.js')?>" defer></script></body></html><?php }
