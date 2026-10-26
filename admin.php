<?php
require __DIR__.'/core.php';
function upload($tmp,$err){if($err||!is_uploaded_file($tmp)||!function_exists('imagecreatefromstring'))return;$im=@imagecreatefromstring(file_get_contents($tmp));if(!$im)return;if(imagesx($im)>1600)$im=imagescale($im,1600);$n=bin2hex(random_bytes(6)).'.jpg';imagejpeg($im,__DIR__."/uploads/$n",82);return $n;}
function pf($a,$id,$l){return '<form method="post" class="inl"'.(strpos($a,'del')!==false?' onsubmit="return confirm(\'Удалить?\')"':'').'><input type="hidden" name="c" value="'.csrf().'"><input type="hidden" name="a" value="'.$a.'"><input type="hidden" name="id" value="'.$id.'"><button class="btn l s">'.$l.'</button></form>';}
$ST=['new'=>'Новая','work'=>'В работе','done'=>'Закрыта'];
$U=$_SESSION['u']??null;
if($U){$s=$db->prepare('SELECT * FROM users WHERE id=?');$s->execute([$U['id']]);if(!$U=$s->fetch())unset($_SESSION['u']);}
$adm=$U&&$U['role']=='admin';$users=$db->query('SELECT COUNT(*) FROM users')->fetchColumn();$err='';
if(isset($_GET['out'])){session_destroy();header('Location: admin.php');exit;}
if($_SERVER['REQUEST_METHOD']=='POST'){chk();$a=$_POST['a']??'';$id=(int)($_POST['id']??0);
 if($a=='setup'&&!$users){if(strlen($_POST['pass'])<8)$err='Пароль — не короче 8 символов.';else{$db->prepare('INSERT INTO users(login,hash,role) VALUES(?,?,?)')->execute([trim($_POST['login']),password_hash($_POST['pass'],PASSWORD_DEFAULT),'admin']);header('Location: admin.php');exit;}}
 elseif($a=='login'){$s=$db->prepare('SELECT * FROM users WHERE login=?');$s->execute([trim($_POST['login'])]);$u=$s->fetch();
  if($u&&password_verify($_POST['pass'],$u['hash'])){session_regenerate_id(true);$_SESSION['u']=$u;header('Location: admin.php');exit;}$err='Неверный логин или пароль.';}
 elseif($U){
  if($a=='tog')$db->prepare('UPDATE tractors SET hidden=1-hidden WHERE id=?')->execute([$id]);
  if($a=='del'){$s=$db->prepare('SELECT photos FROM tractors WHERE id=?');$s->execute([$id]);foreach(json_decode($s->fetchColumn()?:'[]',true) as $f)@unlink(__DIR__.'/uploads/'.basename($f));$db->prepare('DELETE FROM tractors WHERE id=?')->execute([$id]);}
  if($a=='ls'&&isset($ST[$_POST['st']??'']))$db->prepare('UPDATE leads SET status=? WHERE id=?')->execute([$_POST['st'],$id]);
  if($a=='uadd'&&$adm&&strlen($_POST['pass'])>=8)try{$db->prepare('INSERT INTO users(login,hash,role) VALUES(?,?,?)')->execute([trim($_POST['login']),password_hash($_POST['pass'],PASSWORD_DEFAULT),$_POST['role']=='admin'?'admin':'manager']);}catch(Exception $x){}
  if($a=='udel'&&$adm&&$id!=$U['id'])$db->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
  if($a=='save'){$old=[];if($id){$s=$db->prepare('SELECT photos FROM tractors WHERE id=?');$s->execute([$id]);$old=json_decode($s->fetchColumn()?:'[]',true);}
   $ord=$_POST['ord']??[];$rm=$_POST['rm']??[];
   foreach(array_intersect($old,$rm) as $f)@unlink(__DIR__.'/uploads/'.basename($f));
   $ph=array_values(array_diff($old,$rm));usort($ph,fn($x,$y)=>($ord[$x]??0)<=>($ord[$y]??0));
   foreach($_FILES['ph']['tmp_name']??[] as $i=>$tmp)if($n=upload($tmp,$_FILES['ph']['error'][$i]))$ph[]=$n;
   $v=[trim($_POST['name']),trim($_POST['brand']),trim($_POST['class']),(int)$_POST['power'],$_POST['drive'],(int)$_POST['price'],$_POST['descr'],$_POST['specs'],$_POST['equip'],json_encode($ph)];
   if($id)$db->prepare('UPDATE tractors SET name=?,brand=?,class=?,power=?,drive=?,price=?,descr=?,specs=?,equip=?,photos=? WHERE id=?')->execute([...$v,$id]);
   else{$db->prepare('INSERT INTO tractors(name,brand,class,power,drive,price,descr,specs,equip,photos,slug) VALUES(?,?,?,?,?,?,?,?,?,?,?)')->execute([...$v,uniqid()]);$id=$db->lastInsertId();
    $sl=slug($v[0]);$c=$db->prepare('SELECT 1 FROM tractors WHERE slug=?');$c->execute([$sl]);if($c->fetch())$sl.="-$id";
    $db->prepare('UPDATE tractors SET slug=? WHERE id=?')->execute([$sl,$id]);}}
  header('Location: admin.php'.($a=='ls'?'?v=leads':(in_array($a,['uadd','udel'])?'?v=users':'')));exit;
 }
}
?><!doctype html><html lang="ru"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Админ-панель Chimgan</title><link rel="stylesheet" href="/assets/style.css"><body class="adm"><div class="w sec">
<?php if(!$U){?><form class="form narrow" method="post"><h1><?=$users?'Вход':'Создайте администратора'?></h1><?=$err?'<p class="er">'.e($err).'</p>':''?><input type="hidden" name="c" value="<?=csrf()?>"><input type="hidden" name="a" value="<?=$users?'login':'setup'?>"><label>Логин<input name="login" required autofocus></label><label>Пароль<input name="pass" type="password" required></label><button class="btn o"><?=$users?'Войти':'Создать'?></button></form></div><?php exit;}
$v=$_GET['v']??'tractors';?>
<div class="an"><a href="admin.php">Тракторы</a><a href="?v=leads">Заявки</a><?php if($adm):?><a href="?v=users">Менеджеры</a><?php endif?><a href="/" target="_blank">Сайт</a><span class="m"><?=e($U['login'])?>, <?=$adm?'администратор':'менеджер'?></span><a href="?out=1">Выйти</a></div>
<?php if($v=='tractors'){$rows=$db->query('SELECT * FROM tractors ORDER BY id DESC')->fetchAll();?>
<h1>Тракторы</h1><a class="btn o" href="?v=edit">Добавить трактор</a>
<table><?php foreach($rows as $t):?><tr><td><b><?=e($t['name'])?></b><br><small><?=e($t['brand'])?></small></td><td><?=money($t['price'])?></td><td><?=$t['hidden']?'Скрыт':'На сайте'?></td><td class="ac"><a class="btn l s" href="?v=edit&id=<?=$t['id']?>">Изменить</a><?=pf('tog',$t['id'],$t['hidden']?'Показать':'Скрыть')?><?=pf('del',$t['id'],'Удалить')?></td></tr><?php endforeach?></table>
<?php }elseif($v=='edit'){$id=(int)($_GET['id']??0);$s=$db->prepare('SELECT * FROM tractors WHERE id=?');$s->execute([$id]);
 $t=$s->fetch()?:['name'=>'','brand'=>'','class'=>'','power'=>'','drive'=>'4x4','price'=>'','descr'=>'','specs'=>'','equip'=>'','photos'=>'[]'];
 $dl=fn($c)=>implode('',array_map(fn($x)=>'<option value="'.e($x).'">',$db->query("SELECT DISTINCT $c FROM tractors WHERE $c<>''")->fetchAll(PDO::FETCH_COLUMN)));?>
<h1><?=$id?'Изменить трактор':'Новый трактор'?></h1>
<form class="form" method="post" enctype="multipart/form-data"><input type="hidden" name="c" value="<?=csrf()?>"><input type="hidden" name="a" value="save"><input type="hidden" name="id" value="<?=$id?>">
<label>Название<input name="name" value="<?=e($t['name'])?>" required></label>
<label>Марка<input name="brand" list="b" value="<?=e($t['brand'])?>"></label><datalist id="b"><?=$dl('brand')?></datalist>
<label>Класс (категория)<input name="class" list="k" value="<?=e($t['class'])?>"></label><datalist id="k"><?=$dl('class')?></datalist>
<label>Мощность, л.с.<input name="power" type="number" value="<?=e($t['power'])?>"></label>
<label>Привод<select name="drive"><?php foreach(['4x2','4x4'] as $d):?><option<?=$t['drive']==$d?' selected':''?>><?=$d?></option><?php endforeach?></select></label>
<label>Цена, $<input name="price" type="number" value="<?=e($t['price'])?>" required></label>
<label>Описание<textarea name="descr" rows="4"><?=e($t['descr'])?></textarea></label>
<label>Характеристики (каждая с новой строки: Название: значение)<textarea name="specs" rows="6"><?=e($t['specs'])?></textarea></label>
<label>Навесное оборудование (каждое с новой строки)<textarea name="equip" rows="4"><?=e($t['equip'])?></textarea></label>
<div><b>Фото.</b> Порядок задаёт номер, для удаления поставьте галочку.<div class="phl"><?php foreach(photos($t) as $i=>$f):?><label><img src="/uploads/<?=e($f)?>" alt=""><input name="ord[<?=e($f)?>]" type="number" value="<?=$i+1?>"><span><input type="checkbox" name="rm[]" value="<?=e($f)?>"> удалить</span></label><?php endforeach?></div><input type="file" name="ph[]" accept="image/*" multiple></div>
<button class="btn o">Сохранить</button></form>
<?php }elseif($v=='leads'){$rows=$db->query('SELECT * FROM leads ORDER BY id DESC')->fetchAll();?>
<h1>Заявки</h1><?php if(!$rows)echo '<p>Заявок пока нет.</p>'?>
<table><?php foreach($rows as $l):?><tr><td><?=e($l['created'])?></td><td><b><?=e($l['name'])?></b><br><a href="tel:<?=e($l['phone'])?>"><?=e($l['phone'])?></a></td><td><?=e($l['tractor'])?><br><small><?=e($l['msg'])?></small></td><td><form method="post"><input type="hidden" name="c" value="<?=csrf()?>"><input type="hidden" name="a" value="ls"><input type="hidden" name="id" value="<?=$l['id']?>"><select name="st" onchange="this.form.submit()"><?php foreach($ST as $k=>$n):?><option value="<?=$k?>"<?=$l['status']==$k?' selected':''?>><?=$n?></option><?php endforeach?></select></form></td></tr><?php endforeach?></table>
<?php }elseif($v=='users'&&$adm){$rows=$db->query('SELECT * FROM users ORDER BY id')->fetchAll();?>
<h1>Менеджеры</h1>
<table><?php foreach($rows as $u):?><tr><td><?=e($u['login'])?></td><td><?=$u['role']=='admin'?'Администратор':'Менеджер'?></td><td><?=$u['id']!=$U['id']?pf('udel',$u['id'],'Удалить'):''?></td></tr><?php endforeach?></table>
<form class="form narrow gap" method="post"><h2>Новый аккаунт</h2><input type="hidden" name="c" value="<?=csrf()?>"><input type="hidden" name="a" value="uadd"><label>Логин<input name="login" required></label><label>Пароль (от 8 символов)<input name="pass" type="password" minlength="8" required></label><label>Роль<select name="role"><option value="manager">Менеджер</option><option value="admin">Администратор</option></select></label><button class="btn o">Создать</button></form>
<?php }?></div>
