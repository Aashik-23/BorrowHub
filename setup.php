<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/lib.php';
if(!preg_match('/^[a-zA-Z0-9_]+$/',$config['database']))throw new RuntimeException('Invalid database name');
$server=new PDO('mysql:host='.$config['host'].';port='.$config['port'].';charset=utf8mb4',$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$server->exec('CREATE DATABASE IF NOT EXISTS `'.$config['database'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
foreach(explode(';',file_get_contents(__DIR__.'/database/schema.sql')) as $statement)if(trim($statement))db()->exec($statement);
foreach([['Electronics','laptop'],['Tools & equipment','tool'],['Photography','camera'],['Sports & outdoors','tent'],['Study essentials','book'],['Home & events','speaker']] as $c)sql('INSERT IGNORE INTO categories(name,icon) VALUES (?,?)',$c);
if(getenv('BH_ADMIN_EMAIL') && getenv('BH_ADMIN_PASSWORD')){
    $e=strtolower(getenv('BH_ADMIN_EMAIL'));$p=getenv('BH_ADMIN_PASSWORD');
    if(!filter_var($e,FILTER_VALIDATE_EMAIL) || strlen($p)<10 || strlen($p)>72)throw new RuntimeException('Valid admin email and password of 10–72 bytes required.');
    if(!sql('SELECT id FROM users WHERE email=?',[$e])->fetch()){
        sql("INSERT INTO users(name,email,password_hash,city,role) VALUES (?,?,?,'Colombo','admin')",[getenv('BH_ADMIN_NAME')?:'BorrowHub Admin',$e,password_hash($p,PASSWORD_DEFAULT)]);echo "Administrator created.\n";
    }else echo "Existing account preserved; role and password were not changed.\n";
}
if(in_array('--demo',$argv,true)){
    $demoPass=getenv('BH_DEMO_PASSWORD');if(!$demoPass || strlen($demoPass)<10 || strlen($demoPass)>72)throw new RuntimeException('Set BH_DEMO_PASSWORD (10–72 bytes) to add demo data.');
    foreach([['Nimal Perera','nimal@example.test','Colombo'],['Amaya Silva','amaya@example.test','Kandy']] as $d){
        sql('INSERT IGNORE INTO users(name,email,city,password_hash) VALUES (?,?,?,?)',[$d[0],$d[1],$d[2],password_hash($demoPass,PASSWORD_DEFAULT)]);
    }
    $owner=sql('SELECT id FROM users WHERE email=?',['nimal@example.test'])->fetchColumn();
    $samples=[['Canon EOS 2000D camera','Photography',1800,5000,'camera'],['Cordless drill kit','Tools & equipment',650,1500,'tool'],['Weekend camping tent','Sports & outdoors',1200,2500,'tent'],['Portable party speaker','Home & events',950,2000,'speaker'],['Study laptop','Electronics',1500,5000,'laptop'],['Scientific calculator','Study essentials',200,500,'book']];
    foreach($samples as [$title,$category,$rate,$deposit,$art]){
        if(sql('SELECT id FROM items WHERE owner_id=? AND title=?',[$owner,$title])->fetch())continue;
        $cat=sql('SELECT id FROM categories WHERE name=?',[$category])->fetchColumn();
        sql('INSERT INTO items(owner_id,category_id,title,description,daily_rate,deposit,city,item_condition,image) VALUES (?,?,?,?,?,?,?,?,?)',[$owner,$cat,$title,'Demonstration listing for the BorrowHub coursework project. Clean, well cared for and ready for your next project. Arrange collection with the owner through your rental messages. Please return all accessories in the same condition.',$rate,$deposit,'Colombo','Good','demo:'.$art]);
    }
    echo "Demo members and listings added. They are fictional.\n";
}
echo "BorrowHub database ready: ".$config['database']."\n";
