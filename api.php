<?php
declare(strict_types=1);
require __DIR__.'/lib.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
ini_set('display_errors','0');
session_name('borrowhub_session');
session_set_cookie_params(['lifetime'=>0,'path'=>rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'])),'/').'/','httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off']);
ini_set('session.use_strict_mode','1');session_start();
$_SESSION['csrf']??=bin2hex(random_bytes(32));
set_exception_handler(function(Throwable $e){
    if(!empty($GLOBALS['transaction']) && db()->inTransaction())db()->rollBack();
    error_log('BorrowHub: '.$e->getMessage());
    if($e instanceof PDOException && $e->getCode()==='23000')fail('This record already exists or is in use.',409);
    fail('The server could not complete this request. Check that MySQL is running and BorrowHub setup has been completed.',503);
});
$method=$_SERVER['REQUEST_METHOD'];$action=$_GET['action']??'session';
if(!in_array($method,['GET','POST'],true))fail('Method not allowed.',405);
if($method==='POST'){
    if((int)($_SERVER['CONTENT_LENGTH']??0)>6*1024*1024)fail('Request too large. Maximum image size is 5 MB.',413);
    if(!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??''))fail('Your session changed. Reload the page and try again.',403);
    $type=$_SERVER['CONTENT_TYPE']??'';
    if(str_starts_with($type,'application/json')){
        $d=json_decode(file_get_contents('php://input'),true);if(!is_array($d) || array_is_list($d) && $d!==[])fail('Invalid JSON request.');
    }elseif(str_starts_with($type,'multipart/form-data'))$d=$_POST;
    else fail('Unsupported request format.',415);
}else $d=[];
$writes=['register','login','logout','profile','password','reset','item_save','item_delete','book','rental_status','review','message_send','contact','admin_item','admin_user','admin_contact','admin_category'];
if(in_array($action,$writes,true) && $method!=='POST')fail('Use POST for this action.',405);
if(!in_array($action,$writes,true) && $method!=='GET')fail('Use GET for this action.',405);

switch($action){
case 'session':
    answer(['user'=>user(),'csrf'=>$_SESSION['csrf'],'today'=>date('Y-m-d')]);
case 'register':
    throttle('register',12);$name=field($d,'name',2,100);$email=email($d);$pass=password($d);$city=field($d,'city',2,100);
    sql('INSERT INTO users(name,email,password_hash,city) VALUES (?,?,?,?)',[$name,$email,password_hash($pass,PASSWORD_DEFAULT),$city]);
    session_regenerate_id(true);$_SESSION['uid']=(int)db()->lastInsertId();$_SESSION['version']=1;$_SESSION['csrf']=bin2hex(random_bytes(32));
    answer(['user'=>user(),'csrf'=>$_SESSION['csrf']],201);
case 'login':
    throttle('login',25);$e=email($d);$p=$d['password']??null;if(!is_string($p) || strlen($p)<1 || strlen($p)>72)fail('Enter your password.');$u=sql('SELECT * FROM users WHERE email=?',[$e])->fetch();
    if(!$u || !password_verify($p,$u['password_hash']) || !$u['active'])fail('Email or password is incorrect, or the account is unavailable.',401);
    session_regenerate_id(true);$_SESSION['uid']=$u['id'];$_SESSION['version']=$u['session_version'];$_SESSION['csrf']=bin2hex(random_bytes(32));answer(['user'=>user(),'csrf'=>$_SESSION['csrf']]);
case 'logout':
    $_SESSION=[];session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));answer(['csrf'=>$_SESSION['csrf']]);
case 'profile':
    $u=auth();$name=field($d,'name',2,100);$city=field($d,'city',2,100);$phone=field($d,'phone',0,30);
    if($phone!=='' && !preg_match('/^[+0-9 ()-]{7,30}$/',$phone))fail('Enter a valid phone number.');
    sql('UPDATE users SET name=?,city=?,phone=? WHERE id=?',[$name,$city,$phone,$u['id']]);answer(['user'=>user()]);
case 'password':
    $u=auth();throttle('password',20);$old=$d['current_password']??null;if(!is_string($old) || strlen($old)<1 || strlen($old)>72)fail('Enter your current password.');$new=password($d);
    if(!password_verify($old,sql('SELECT password_hash FROM users WHERE id=?',[$u['id']])->fetchColumn()))fail('Current password is incorrect.');
    sql('UPDATE users SET password_hash=?,session_version=session_version+1 WHERE id=?',[password_hash($new,PASSWORD_DEFAULT),$u['id']]);
    $_SESSION['version']++;session_regenerate_id(true);answer(['ok'=>true]);
case 'reset':
    throttle('reset',15);$token=field($d,'token',64,64);$new=password($d);begin();
    $r=sql('SELECT * FROM password_resets WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() FOR UPDATE',[hash('sha256',$token)])->fetch();
    if(!$r)fail('This reset link is invalid or has expired.');
    sql('UPDATE users SET password_hash=?,session_version=session_version+1 WHERE id=?',[password_hash($new,PASSWORD_DEFAULT),$r['user_id']]);
    sql('UPDATE password_resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL',[$r['user_id']]);commit();answer(['ok'=>true]);
case 'categories':
    answer(sql('SELECT * FROM categories WHERE active=1 ORDER BY id')->fetchAll());
case 'items':
    $params=[];$where=['i.deleted_at IS NULL','i.hidden=0','u.active=1','c.active=1'];
    if(!empty($_GET['mine'])){$u=auth();$where=['i.deleted_at IS NULL','i.owner_id=?'];$params[]=$u['id'];}
    else {
        if(!empty($_GET['q'])){$q=field($_GET,'q',1,120);$where[]='(i.title LIKE ? OR i.description LIKE ?)';$params[]='%'.$q.'%';$params[]='%'.$q.'%';}
        if(!empty($_GET['category'])){$where[]='i.category_id=?';$params[]=id($_GET['category']);}
        if(!empty($_GET['city'])){$where[]='i.city LIKE ?';$params[]='%'.field($_GET,'city',1,100).'%';}
        if(isset($_GET['max']) && $_GET['max']!==''){$where[]='i.daily_rate<=?';$params[]=money($_GET,'max');}
        if(!empty($_GET['available'])){$where[]='i.available=1';$where[]="NOT EXISTS (SELECT 1 FROM rentals b WHERE b.item_id=i.id AND b.status IN ('accepted','active','return_requested') AND b.start_date<=? AND b.end_date>=?)";$params[]=date('Y-m-d');$params[]=date('Y-m-d');}
    }
    $page=max(1,min(100000,(int)($_GET['page']??1)));$limit=12;$offset=($page-1)*$limit;
    $order=['newest'=>'i.id DESC','price_low'=>'i.daily_rate ASC,i.id DESC','price_high'=>'i.daily_rate DESC,i.id DESC'][$_GET['sort']??'newest']??'i.id DESC';
    $from=' FROM items i JOIN users u ON u.id=i.owner_id JOIN categories c ON c.id=i.category_id WHERE '.implode(' AND ',$where);
    $count=(int)sql('SELECT COUNT(*)'.$from,$params)->fetchColumn();
    $rows=sql("SELECT i.*,u.name owner_name,c.name category,c.icon,(SELECT ROUND(AVG(v.rating),1) FROM reviews v JOIN rentals r ON r.id=v.rental_id WHERE r.item_id=i.id) rating,(SELECT COUNT(*) FROM reviews v JOIN rentals r ON r.id=v.rental_id WHERE r.item_id=i.id) review_count".$from." ORDER BY $order LIMIT $limit OFFSET $offset",$params)->fetchAll();
    answer(['items'=>$rows,'total'=>$count,'page'=>$page,'pages'=>(int)ceil($count/$limit)]);
case 'item':
    $item=sql('SELECT i.*,u.name owner_name,u.created_at owner_since,u.active owner_active,c.name category,c.icon,c.active category_active FROM items i JOIN users u ON u.id=i.owner_id JOIN categories c ON c.id=i.category_id WHERE i.id=? AND i.deleted_at IS NULL',[id($_GET['id']??null)])->fetch();
    if(!$item)fail('Item not found.',404);$u=user();
    if(($item['hidden'] || !$item['owner_active'] || !$item['category_active']) && (!$u || $u['id']!=$item['owner_id']))fail('Item is unavailable.',404);
    $item['reviews']=sql('SELECT v.rating,v.comment,v.created_at,u.name FROM reviews v JOIN rentals r ON r.id=v.rental_id JOIN users u ON u.id=r.renter_id WHERE r.item_id=? ORDER BY v.id DESC LIMIT 50',[$item['id']])->fetchAll();
    $item['booked_dates']=sql("SELECT start_date,end_date FROM rentals WHERE item_id=? AND status IN ('accepted','active','return_requested') AND end_date>=? ORDER BY start_date",[$item['id'],date('Y-m-d')])->fetchAll();answer($item);
case 'item_save':
    $u=auth();$title=field($d,'title',3,120);$desc=field($d,'description',20,5000);$cat=id($d['category_id']??null);$city=field($d,'city',2,100);
    $rate=money($d,'daily_rate',1);$deposit=money($d,'deposit');$condition=field($d,'item_condition',4,20);
    if(!in_array($condition,['Like new','Good','Fair'],true))fail('Choose a valid item condition.');
    if(!sql('SELECT id FROM categories WHERE id=? AND active=1',[$cat])->fetch())fail('Choose an active category.');
    $available=($d['available']??'1')==='0'?0:1;
    begin();$existing=null;
    if(!empty($d['id'])){$existing=sql('SELECT * FROM items WHERE id=? AND deleted_at IS NULL FOR UPDATE',[id($d['id'])])->fetch();if(!$existing || $existing['owner_id']!=$u['id'])fail('You cannot edit this listing.',403);}
    $img=upload();
    if($existing){sql('UPDATE items SET title=?,description=?,category_id=?,daily_rate=?,deposit=?,city=?,item_condition=?,available=?,image=? WHERE id=?',[$title,$desc,$cat,$rate,$deposit,$city,$condition,$available,$img??$existing['image'],$existing['id']]);$itemId=$existing['id'];}
    else{sql('INSERT INTO items(owner_id,title,description,category_id,daily_rate,deposit,city,item_condition,image,available) VALUES (?,?,?,?,?,?,?,?,?,?)',[$u['id'],$title,$desc,$cat,$rate,$deposit,$city,$condition,$img,$available]);$itemId=db()->lastInsertId();}
    commit();answer(['id'=>$itemId]);
case 'item_delete':
    $u=auth();$itemId=id($d['id']??null);begin();$i=sql('SELECT * FROM items WHERE id=? FOR UPDATE',[$itemId])->fetch();
    if(!$i || $i['owner_id']!=$u['id'])fail('You cannot remove this listing.',403);
    if(sql("SELECT id FROM rentals WHERE item_id=? AND status IN ('accepted','active','return_requested') LIMIT 1",[$itemId])->fetch())fail('Finish or cancel confirmed rentals before removing this item.',409);
    sql('UPDATE items SET deleted_at=NOW() WHERE id=?',[$itemId]);sql("UPDATE rentals SET status='rejected' WHERE item_id=? AND status='pending'",[$itemId]);commit();answer(['ok'=>true]);
case 'book':
    $u=auth();throttle('book',50);$itemId=id($d['item_id']??null);$start=date_input($d['start_date']??null);$end=date_input($d['end_date']??null);$note=field($d,'note',0,1000);
    $days=(int)(new DateTimeImmutable($start))->diff(new DateTimeImmutable($end))->format('%r%a')+1;
    if($start<date('Y-m-d') || $days<1 || $days>90 || $start>date('Y-m-d',strtotime('+1 year')))fail('Rent for 1–90 days, starting today or within the next year.');
    begin();$i=sql('SELECT i.*,u.active owner_active,c.active category_active FROM items i JOIN users u ON u.id=i.owner_id JOIN categories c ON c.id=i.category_id WHERE i.id=? FOR UPDATE',[$itemId])->fetch();
    if(!$i || $i['hidden'] || $i['deleted_at'] || !$i['available'] || !$i['owner_active'] || !$i['category_active'])fail('This item is not available.',409);
    if($i['owner_id']==$u['id'])fail('You cannot rent your own item.');
    if(sql("SELECT id FROM rentals WHERE item_id=? AND status IN ('accepted','active','return_requested') AND start_date<=? AND end_date>=? LIMIT 1",[$itemId,$end,$start])->fetch())fail('This item is already booked for those dates.',409);
    if(sql("SELECT id FROM rentals WHERE item_id=? AND renter_id=? AND status='pending' AND start_date<=? AND end_date>=?",[$itemId,$u['id'],$end,$start])->fetch())fail('You already have a pending request for these dates.',409);
    sql('INSERT INTO rentals(item_id,renter_id,start_date,end_date,days,daily_rate,total,deposit,note) VALUES (?,?,?,?,?,?,?,?,?)',[$itemId,$u['id'],$start,$end,$days,$i['daily_rate'],number_format($days*(float)$i['daily_rate'],2,'.',''),$i['deposit'],$note]);
    $newId=db()->lastInsertId();commit();answer(['id'=>$newId],201);
case 'rentals':
    $u=auth();answer(sql('SELECT r.*,i.title,i.image,c.icon,i.owner_id,o.name owner_name,b.name renter_name,v.rating,v.comment FROM rentals r JOIN items i ON i.id=r.item_id JOIN categories c ON c.id=i.category_id JOIN users o ON o.id=i.owner_id JOIN users b ON b.id=r.renter_id LEFT JOIN reviews v ON v.rental_id=r.id WHERE r.renter_id=? OR i.owner_id=? ORDER BY r.id DESC',[$u['id'],$u['id']])->fetchAll());
case 'rental_status':
    $u=auth();$r=rental(id($d['id']??null),$u);$next=field($d,'status',3,30);begin();
    // Every booking decision locks the item first, serializing overlapping approvals.
    $i=sql('SELECT * FROM items WHERE id=? FOR UPDATE',[$r['item_id']])->fetch();$r=sql('SELECT * FROM rentals WHERE id=? FOR UPDATE',[$r['id']])->fetch();
    $owner=$u['id']==$i['owner_id'];$allowed=$owner?['pending'=>['accepted','rejected'],'accepted'=>['active','cancelled'],'return_requested'=>['completed']]:['pending'=>['cancelled'],'accepted'=>['cancelled'],'active'=>['return_requested']];
    if(!in_array($next,$allowed[$r['status']]??[],true))fail('That status change is not permitted.',409);
    if($next==='accepted'){
        if($i['hidden'] || $i['deleted_at'] || !$i['available'] || $r['start_date']<date('Y-m-d'))fail('The listing or requested dates are no longer available.',409);
        if(!sql('SELECT id FROM users WHERE id=? AND active=1',[$r['renter_id']])->fetch() || !sql('SELECT id FROM categories WHERE id=? AND active=1',[$i['category_id']])->fetch())fail('The renter or category is unavailable.',409);
        if(sql("SELECT id FROM rentals WHERE item_id=? AND id<>? AND status IN ('accepted','active','return_requested') AND start_date<=? AND end_date>=? LIMIT 1",[$i['id'],$r['id'],$r['end_date'],$r['start_date']])->fetch())fail('Another confirmed booking overlaps these dates.',409);
        sql("UPDATE rentals SET status='rejected' WHERE item_id=? AND id<>? AND status='pending' AND start_date<=? AND end_date>=?",[$i['id'],$r['id'],$r['end_date'],$r['start_date']]);
    }
    if($next==='active' && ($r['start_date']>date('Y-m-d') || $r['end_date']<date('Y-m-d')))fail('Confirm pickup during the booked rental period.');
    sql('UPDATE rentals SET status=? WHERE id=?',[$next,$r['id']]);commit();answer(['ok'=>true]);
case 'review':
    $u=auth();$r=rental(id($d['id']??null),$u);$rating=id($d['rating']??null);$comment=field($d,'comment',0,1000);
    if($r['renter_id']!=$u['id'] || $r['status']!=='completed')fail('Only the renter can review a completed rental.',403);
    if($rating>5)fail('Choose a rating from 1 to 5.');sql('INSERT INTO reviews(rental_id,rating,comment) VALUES (?,?,?)',[$r['id'],$rating,$comment]);answer(['ok'=>true],201);
case 'messages':
    $u=auth();$r=rental(id($_GET['id']??null),$u);answer(sql('SELECT m.*,u.name FROM messages m JOIN users u ON u.id=m.sender_id WHERE m.rental_id=? ORDER BY m.id',[$r['id']])->fetchAll());
case 'message_send':
    $u=auth();throttle('messages',100);$r=rental(id($d['id']??null),$u);$body=field($d,'body',1,2000);
    sql('INSERT INTO messages(rental_id,sender_id,body) VALUES (?,?,?)',[$r['id'],$u['id'],$body]);answer(['ok'=>true],201);
case 'contact':
    throttle('contact',10);sql('INSERT INTO contacts(name,email,subject,body) VALUES (?,?,?,?)',[field($d,'name',2,100),email($d),field($d,'subject',3,150),field($d,'body',10,5000)]);answer(['ok'=>true],201);
case 'admin':
    auth(true);answer([
        'stats'=>['users'=>(int)sql('SELECT COUNT(*) FROM users')->fetchColumn(),'items'=>(int)sql('SELECT COUNT(*) FROM items WHERE deleted_at IS NULL')->fetchColumn(),'rentals'=>(int)sql('SELECT COUNT(*) FROM rentals')->fetchColumn(),'revenue'=>sql("SELECT COALESCE(SUM(total),0) FROM rentals WHERE status='completed'")->fetchColumn()],
        'items'=>sql('SELECT i.*,u.name owner_name FROM items i JOIN users u ON u.id=i.owner_id WHERE deleted_at IS NULL ORDER BY i.id DESC LIMIT 200')->fetchAll(),
        'users'=>sql('SELECT id,name,email,role,active,city FROM users ORDER BY id DESC LIMIT 200')->fetchAll(),
        'contacts'=>sql('SELECT * FROM contacts ORDER BY resolved,id DESC LIMIT 200')->fetchAll(),
        'categories'=>sql('SELECT * FROM categories ORDER BY id')->fetchAll(),
        'rentals'=>sql('SELECT r.*,i.title,u.name renter_name FROM rentals r JOIN items i ON i.id=r.item_id JOIN users u ON u.id=r.renter_id ORDER BY r.id DESC LIMIT 200')->fetchAll()
    ]);
case 'admin_item':
    auth(true);$v=filter_var($d['hidden']??null,FILTER_VALIDATE_INT);if(!in_array($v,[0,1],true))fail('Invalid visibility.');sql('UPDATE items SET hidden=? WHERE id=?',[$v,id($d['id']??null)]);answer(['ok'=>true]);
case 'admin_user':
    $u=auth(true);$target=id($d['id']??null);$v=filter_var($d['active']??null,FILTER_VALIDATE_INT);if(!in_array($v,[0,1],true))fail('Invalid account status.');
    begin();$t=sql('SELECT id,role FROM users WHERE id=? FOR UPDATE',[$target])->fetch();if(!$t)fail('Account not found.',404);if($t['role']==='admin')fail('Administrator accounts cannot be suspended here.',403);
    if(!$v && sql("SELECT r.id FROM rentals r JOIN items i ON i.id=r.item_id WHERE (r.renter_id=? OR i.owner_id=?) AND r.status IN ('accepted','active','return_requested') LIMIT 1",[$target,$target])->fetch())fail('Resolve this member’s confirmed rentals before suspending the account.',409);
    sql('UPDATE users SET active=?,session_version=session_version+1 WHERE id=?',[$v,$target]);commit();answer(['ok'=>true]);
case 'admin_contact':
    auth(true);sql('UPDATE contacts SET resolved=1 WHERE id=?',[id($d['id']??null)]);answer(['ok'=>true]);
case 'admin_category':
    auth(true);if(!empty($d['id'])){$v=filter_var($d['active']??null,FILTER_VALIDATE_INT);if(!in_array($v,[0,1],true))fail('Invalid category status.');sql('UPDATE categories SET active=? WHERE id=?',[$v,id($d['id'])]);}
    else sql("INSERT INTO categories(name,icon) VALUES (?,'box')",[field($d,'name',2,80)]);answer(['ok'=>true]);
default:fail('Endpoint not found.',404);
}
