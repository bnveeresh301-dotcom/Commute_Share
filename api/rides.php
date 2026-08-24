<?php
require_once __DIR__ . '/../config.php';
$userId=require_login(); $action=$_GET['action']??'list';
try {
 if($action==='list'){
   $from=clean((string)($_GET['from']??''));$to=clean((string)($_GET['to']??''));$date=clean((string)($_GET['date']??''));$seats=max(1,(int)($_GET['seats']??1));
   $sql="SELECT r.*,u.name driver,u.vehicle,u.vehicle_no,u.verified FROM rides r JOIN users u ON u.id=r.driver_id WHERE r.status='active' AND r.seats_available>=?";
   $args=[$seats];
   if($from!==''){ $sql.=" AND r.from_location LIKE ?";$args[]="%$from%"; }
   if($to!==''){ $sql.=" AND r.to_location LIKE ?";$args[]="%$to%"; }
   if($date!==''){ $sql.=" AND r.ride_date=?";$args[]=$date; }
   $sql.=" ORDER BY r.ride_date,r.departure_time LIMIT 100";
   $st=db()->prepare($sql);$st->execute($args);json_response(['ok'=>true,'rides'=>$st->fetchAll()]);
 }
 if($action==='trackable'){
   // A user may track rides they drive or rides they have a confirmed booking for.
   $st=db()->prepare("SELECT r.*,u.name driver,u.vehicle,u.vehicle_no,u.verified
     FROM rides r JOIN users u ON u.id=r.driver_id
     WHERE r.status='active' AND (r.driver_id=? OR EXISTS(
       SELECT 1 FROM bookings b WHERE b.ride_id=r.id AND b.rider_id=? AND b.status='confirmed'
     ))
     ORDER BY r.ride_date,r.departure_time LIMIT 100");
   $st->execute([$userId,$userId]);
   json_response(['ok'=>true,'rides'=>$st->fetchAll()]);
 }
 if($action==='create'){
   $d=body();$required=['from','to','date','time','seats','price'];
   foreach($required as $k) if(!isset($d[$k])||trim((string)$d[$k])==='') json_response(['ok'=>false,'error'=>"Missing $k"],422);
   $seats=(int)$d['seats'];$price=(float)$d['price'];if($seats<1||$seats>8||$price<0)json_response(['ok'=>false,'error'=>'Invalid seats or price'],422);
   $st=db()->prepare('INSERT INTO rides(driver_id,from_location,to_location,ride_date,departure_time,seats_total,seats_available,price,pickup_details,note) VALUES(?,?,?,?,?,?,?,?,?,?)');
   $st->execute([$userId,clean($d['from']),clean($d['to']),$d['date'],$d['time'],$seats,$seats,$price,clean((string)($d['pickup']??'')),clean((string)($d['note']??''))]);
   json_response(['ok'=>true,'id'=>db()->lastInsertId()]);
 }
 if($action==='mine'){
   $st=db()->prepare("SELECT r.*, (SELECT COUNT(*) FROM bookings b WHERE b.ride_id=r.id AND b.status='confirmed') booking_count FROM rides r WHERE r.driver_id=? ORDER BY r.created_at DESC");
   $st->execute([$userId]);json_response(['ok'=>true,'rides'=>$st->fetchAll()]);
 }
 if($action==='cancel'){
   $id=(int)(body()['ride_id']??0);$st=db()->prepare("UPDATE rides SET status='cancelled' WHERE id=? AND driver_id=?");$st->execute([$id,$userId]);json_response(['ok'=>true]);
 }
 if($action==='book'){
   $d=body();$rideId=(int)($d['ride_id']??0);$seats=max(1,(int)($d['seats']??1));$pdo=db();$pdo->beginTransaction();
   $st=$pdo->prepare("SELECT * FROM rides WHERE id=? AND status='active' FOR UPDATE");$st->execute([$rideId]);$ride=$st->fetch();
   if(!$ride)throw new Exception('Ride not found'); if((int)$ride['driver_id']===$userId)throw new Exception('You cannot book your own ride'); if((int)$ride['seats_available']<$seats)throw new Exception('Not enough seats');
   $chk=$pdo->prepare("SELECT id FROM bookings WHERE ride_id=? AND rider_id=? AND status='confirmed'");$chk->execute([$rideId,$userId]);if($chk->fetch())throw new Exception('You already booked this ride');
   $ins=$pdo->prepare("INSERT INTO bookings(ride_id,rider_id,seats,total_price) VALUES(?,?,?,?)");$ins->execute([$rideId,$userId,$seats,$seats*(float)$ride['price']]);
   $upd=$pdo->prepare("UPDATE rides SET seats_available=seats_available-? WHERE id=?");$upd->execute([$seats,$rideId]);$pdo->commit();
   json_response(['ok'=>true,'total'=>$seats*(float)$ride['price'],'driver_id'=>(int)$ride['driver_id']]);
 }
 json_response(['ok'=>false,'error'=>'Unknown ride action'],400);
} catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();json_response(['ok'=>false,'error'=>$e->getMessage()],500);}
