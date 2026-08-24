<?php
require_once __DIR__ . '/../config.php'; $userId=require_login();
try {
 if($_SERVER['REQUEST_METHOD']==='GET') json_response(['ok'=>true,'user'=>user_by_id($userId)]);
 $d=body();$name=clean((string)($d['name']??''));$email=strtolower(clean((string)($d['email']??'')));
 if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL))json_response(['ok'=>false,'error'=>'Name and valid email are required'],422);
 $st=db()->prepare('UPDATE users SET name=?,email=?,phone=?,vehicle=?,vehicle_no=?,city=? WHERE id=?');
 $st->execute([$name,$email,clean((string)($d['phone']??'')),clean((string)($d['vehicle']??'')),clean((string)($d['vehicle_no']??'')),clean((string)($d['city']??'')),$userId]);
 json_response(['ok'=>true,'user'=>user_by_id($userId)]);
} catch(Throwable $e){json_response(['ok'=>false,'error'=>$e->getMessage()],500);}
