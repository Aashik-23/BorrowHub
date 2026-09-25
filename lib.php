<?php
declare(strict_types=1);
$config=require __DIR__.'/config.php';
date_default_timezone_set($config['timezone']);
function db(): PDO {
    static $pdo; global $config;
    if (!$pdo) $pdo=new PDO('mysql:host='.$config['host'].';port='.$config['port'].';dbname='.$config['database'].';charset=utf8mb4',$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    return $pdo;
}
function sql(string $q,array $p=[]): PDOStatement {$s=db()->prepare($q);$s->execute($p);return $s;}
function fail(string $message,int $status=400): never {
    if (!empty($GLOBALS['transaction']) && db()->inTransaction()) db()->rollBack();
    http_response_code($status);echo json_encode(['error'=>$message]);exit;
}
function answer(mixed $data,int $status=200): never {http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
function field(array $d,string $k,int $min,int $max): string {
    if (!isset($d[$k]) || !is_string($d[$k])) fail('Please enter a valid '.str_replace('_',' ',$k).'.');
    $v=trim($d[$k]);if(mb_strlen($v)<$min || mb_strlen($v)>$max) fail(ucfirst(str_replace('_',' ',$k)).' must be '.$min.'–'.$max.' characters.');return $v;
}
function id(mixed $v): int {if(filter_var($v,FILTER_VALIDATE_INT)===false || (int)$v<1) fail('Invalid record ID.');return (int)$v;}
function money(array $d,string $k,float $min=0): string {
    if(!isset($d[$k]) || !is_scalar($d[$k]) || !preg_match('/^\d{1,7}(\.\d{1,2})?$/',(string)$d[$k]) || (float)$d[$k]<$min) fail('Enter a valid '.str_replace('_',' ',$k).'.');return number_format((float)$d[$k],2,'.','');
}
function password(array $d,string $key='password'): string {
    $v=$d[$key]??null;if(!is_string($v) || strlen($v)<10 || strlen($v)>72) fail('Password must be 10–72 bytes long.');return $v;
}
function email(array $d): string {$v=strtolower(field($d,'email',3,190));if(!filter_var($v,FILTER_VALIDATE_EMAIL)) fail('Enter a valid email address.');return $v;}
function user(): ?array {
    if(empty($_SESSION['uid'])) return null;
    $u=sql('SELECT id,name,email,city,phone,role,active,session_version,created_at FROM users WHERE id=?',[$_SESSION['uid']])->fetch();
    return $u && $u['active'] && $u['session_version']===($_SESSION['version']??null) ? $u : null;
}
function auth(bool $admin=false): array {$u=user();if(!$u)fail('Please sign in to continue.',401);if($admin && $u['role']!=='admin')fail('Administrator access required.',403);return $u;}
function throttle(string $bucket,int $limit,int $seconds=900): void {
    $f=fopen(sys_get_temp_dir().'/bh_'.hash('sha256',__DIR__.$bucket.($_SERVER['REMOTE_ADDR']??'cli')),'c+');
    if(!$f)throw new RuntimeException('Throttle storage unavailable');flock($f,LOCK_EX);
    $hits=json_decode(stream_get_contents($f)?:'[]',true)?:[];$hits=array_values(array_filter($hits,fn($t)=>$t>time()-$seconds));
    if(count($hits)>=$limit){flock($f,LOCK_UN);fclose($f);fail('Too many attempts. Please try again later.',429);}
    $hits[]=time();ftruncate($f,0);rewind($f);fwrite($f,json_encode($hits));flock($f,LOCK_UN);fclose($f);
}
function date_input(mixed $v): string {
    if(!is_string($v))fail('Choose valid rental dates.');$dt=DateTimeImmutable::createFromFormat('!Y-m-d',$v);
    if(!$dt || $dt->format('Y-m-d')!==$v)fail('Choose valid rental dates.');return $v;
}
function begin(): void {db()->beginTransaction();$GLOBALS['transaction']=true;}
function commit(): void {db()->commit();$GLOBALS['transaction']=false;}
function rental(int $id,array $u): array {
    $r=sql('SELECT r.*,i.owner_id,i.title FROM rentals r JOIN items i ON i.id=r.item_id WHERE r.id=?',[$id])->fetch();
    if(!$r)fail('Rental not found.',404);if($u['id']!=$r['owner_id'] && $u['id']!=$r['renter_id'])fail('This rental is private.',403);return $r;
}
function upload(): ?string {
    if(empty($_FILES['image']) || $_FILES['image']['error']===UPLOAD_ERR_NO_FILE)return null;$f=$_FILES['image'];
    if($f['error']!==UPLOAD_ERR_OK || $f['size']>5*1024*1024)fail('Choose a JPG, PNG or WebP image under 5 MB.');
    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name'])]??null;
    $size=@getimagesize($f['tmp_name']);if(!$ext || !$size || $size[0]>8000 || $size[1]>8000)fail('Invalid image. Maximum dimensions are 8000 × 8000.');
    $name=bin2hex(random_bytes(18)).'.'.$ext;if(!move_uploaded_file($f['tmp_name'],__DIR__.'/uploads/'.$name))throw new RuntimeException('Upload failed');return $name;
}
