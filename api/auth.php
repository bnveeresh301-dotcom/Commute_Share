<?php
require_once __DIR__ . '/../config.php';
$action = $_GET['action'] ?? '';
try {
    if ($action === 'register') {
        $d=body(); $name=clean((string)($d['name']??'')); $email=strtolower(clean((string)($d['email']??''))); $phone=clean((string)($d['phone']??'')); $pass=(string)($d['password']??'');
        if ($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($pass)<6) json_response(['ok'=>false,'error'=>'Enter a valid name, email and password of at least 6 characters'],422);
        $hash=password_hash($pass,PASSWORD_DEFAULT);
        $st=db()->prepare('INSERT INTO users(name,email,password_hash,phone) VALUES(?,?,?,?)'); $st->execute([$name,$email,$hash,$phone]);
        $newUserId=(int)db()->lastInsertId();
        // Do not create a login session during registration.
        // The frontend will redirect the user to the login page.
        json_response(['ok'=>true,'user'=>user_by_id($newUserId)]);
    }
    if ($action === 'login') {
        $d=body(); $email=strtolower(clean((string)($d['email']??''))); $pass=(string)($d['password']??'');
        $st=db()->prepare('SELECT * FROM users WHERE email=?');$st->execute([$email]);$u=$st->fetch();
        if(!$u || !password_verify($pass,$u['password_hash'])) json_response(['ok'=>false,'error'=>'Invalid email or password'],401);
        $_SESSION['user_id']=(int)$u['id']; json_response(['ok'=>true,'user'=>user_by_id($_SESSION['user_id'])]);
    }
    if ($action === 'logout') { session_destroy(); json_response(['ok'=>true]); }
    if ($action === 'me') {
        if(empty($_SESSION['user_id'])) json_response(['ok'=>true,'user'=>null]);
        json_response(['ok'=>true,'user'=>user_by_id((int)$_SESSION['user_id'])]);
    }
    json_response(['ok'=>false,'error'=>'Unknown auth action'],400);
} catch(Throwable $e) { json_response(['ok'=>false,'error'=>$e->getMessage()],500); }
