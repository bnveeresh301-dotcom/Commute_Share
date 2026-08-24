<?php
require_once __DIR__ . '/../config.php'; $userId=require_login(); $action=$_GET['action']??'list';
try {
 if($action==='list'){
   $st=db()->prepare("SELECT c.id,c.type,c.name,c.created_at,
      CASE WHEN c.type='group' THEN c.name ELSE (SELECT u.name FROM conversation_members cm2 JOIN users u ON u.id=cm2.user_id WHERE cm2.conversation_id=c.id AND cm2.user_id<>? LIMIT 1) END display_name,
      (SELECT m.message FROM messages m WHERE m.conversation_id=c.id ORDER BY m.id DESC LIMIT 1) last_message,
      (SELECT m.created_at FROM messages m WHERE m.conversation_id=c.id ORDER BY m.id DESC LIMIT 1) last_time
      FROM conversations c JOIN conversation_members cm ON cm.conversation_id=c.id
      WHERE cm.user_id=? ORDER BY COALESCE(last_time,c.created_at) DESC");
   $st->execute([$userId,$userId]);$rows=$st->fetchAll();json_response(['ok'=>true,'chats'=>$rows]);
 }
 if($action==='messages'){
   $cid=(int)($_GET['conversation_id']??0);
   $chk=db()->prepare('SELECT 1 FROM conversation_members WHERE conversation_id=? AND user_id=?');$chk->execute([$cid,$userId]);if(!$chk->fetch())json_response(['ok'=>false,'error'=>'Access denied'],403);
   $st=db()->prepare('SELECT m.id,m.sender_id,m.message,m.created_at,u.name sender_name FROM messages m JOIN users u ON u.id=m.sender_id WHERE m.conversation_id=? ORDER BY m.id ASC LIMIT 500');$st->execute([$cid,$cid]);json_response(['ok'=>true,'messages'=>$st->fetchAll()]);
 }
 if($action==='send'){
   $d=body();$cid=(int)($d['conversation_id']??0);$msg=clean((string)($d['message']??''));if($msg==='')json_response(['ok'=>false,'error'=>'Message is empty'],422);
   $chk=db()->prepare('SELECT 1 FROM conversation_members WHERE conversation_id=? AND user_id=?');$chk->execute([$cid,$userId]);if(!$chk->fetch())json_response(['ok'=>false,'error'=>'Access denied'],403);
   $st=db()->prepare('INSERT INTO messages(conversation_id,sender_id,message) VALUES(?,?,?)');$st->execute([$cid,$userId,$msg]);json_response(['ok'=>true,'id'=>db()->lastInsertId()]);
 }
 if($action==='create_group'){
   $d=body();$name=clean((string)($d['name']??''));if($name==='')json_response(['ok'=>false,'error'=>'Group name required'],422);$pdo=db();$pdo->beginTransaction();
   $st=$pdo->prepare("INSERT INTO conversations(type,name,created_by) VALUES('group',?,?)");$st->execute([$name,$userId]);$cid=(int)$pdo->lastInsertId();
   $st=$pdo->prepare('INSERT INTO conversation_members(conversation_id,user_id) VALUES(?,?)');$st->execute([$cid,$userId]);$pdo->commit();json_response(['ok'=>true,'conversation_id'=>$cid]);
 }
 if($action==='direct'){
   $other=(int)(body()['user_id']??0);if($other<1||$other===$userId)json_response(['ok'=>false,'error'=>'Invalid user'],422);
   $st=db()->prepare("SELECT c.id FROM conversations c JOIN conversation_members a ON a.conversation_id=c.id JOIN conversation_members b ON b.conversation_id=c.id WHERE c.type='direct' AND a.user_id=? AND b.user_id=? LIMIT 1");$st->execute([$userId,$other]);$found=$st->fetch();
   if($found)json_response(['ok'=>true,'conversation_id'=>$found['id']]);
   $pdo=db();$pdo->beginTransaction();$st=$pdo->prepare("INSERT INTO conversations(type,created_by) VALUES('direct',?)");$st->execute([$userId]);$cid=(int)$pdo->lastInsertId();$st=$pdo->prepare('INSERT INTO conversation_members(conversation_id,user_id) VALUES(?,?),(?,?)');$st->execute([$cid,$userId,$cid,$other]);$pdo->commit();json_response(['ok'=>true,'conversation_id'=>$cid]);
 }
 json_response(['ok'=>false,'error'=>'Unknown chat action'],400);
} catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();json_response(['ok'=>false,'error'=>$e->getMessage()],500);}
