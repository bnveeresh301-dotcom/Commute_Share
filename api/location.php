<?php
require_once __DIR__ . '/../config.php';
$userId=require_login();
$action=$_GET['action']??'';
try {
  if($action==='update'){
    $d=body(); $rideId=(int)($d['ride_id']??0);
    $lat=(float)($d['lat']??0); $lng=(float)($d['lng']??0);
    if(!$rideId || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) json_response(['ok'=>false,'error'=>'Invalid location'],422);
    $chk=db()->prepare("SELECT id FROM rides WHERE id=? AND driver_id=? AND status='active'");
    $chk->execute([$rideId,$userId]); if(!$chk->fetch()) json_response(['ok'=>false,'error'=>'You are not the driver of this active ride'],403);
    $st=db()->prepare("INSERT INTO ride_locations(ride_id,driver_id,lat,lng,accuracy,heading,speed) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE lat=VALUES(lat),lng=VALUES(lng),accuracy=VALUES(accuracy),heading=VALUES(heading),speed=VALUES(speed),updated_at=CURRENT_TIMESTAMP");
    $st->execute([$rideId,$userId,$lat,$lng,$d['accuracy']??null,$d['heading']??null,$d['speed']??null]);
    json_response(['ok'=>true]);
  }
  if($action==='get'){
    $rideId=(int)($_GET['ride_id']??0);
    $st=db()->prepare("SELECT r.id,r.driver_id,r.from_location,r.to_location,r.from_lat,r.from_lng,r.to_lat,r.to_lng,rl.lat,rl.lng,rl.accuracy,rl.heading,rl.speed,rl.updated_at FROM rides r LEFT JOIN ride_locations rl ON rl.ride_id=r.id WHERE r.id=?");
    $st->execute([$rideId]); $row=$st->fetch();
    if(!$row) json_response(['ok'=>false,'error'=>'Ride not found'],404);
    // Only driver or confirmed rider may see live location.
    if((int)$row['driver_id']!==$userId){
      $chk=db()->prepare("SELECT id FROM bookings WHERE ride_id=? AND rider_id=? AND status='confirmed'");
      $chk->execute([$rideId,$userId]); if(!$chk->fetch()) json_response(['ok'=>false,'error'=>'You must have a confirmed booking to track this ride'],403);
    }
    json_response(['ok'=>true,'location'=>$row]);
  }
  json_response(['ok'=>false,'error'=>'Unknown location action'],400);
} catch(Throwable $e){json_response(['ok'=>false,'error'=>$e->getMessage()],500);}
