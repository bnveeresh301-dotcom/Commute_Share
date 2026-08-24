let me=null, currentChat=null, chatFilter='all', poll=null, mapsReady=false, map=null, myMarker=null, selectedPickup=null, selectedDestination=null, trackingTimer=null, trackingWatch=null, trackingMarker=null, routePolylines=[];
const $=s=>document.querySelector(s), $$=s=>document.querySelectorAll(s);
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money=n=>'₹'+Number(n).toLocaleString('en-IN');
const today=new Date().toISOString().slice(0,10);

async function api(url,options={}){const r=await fetch(url,{headers:{'Content-Type':'application/json',...(options.headers||{})},...options});const d=await r.json().catch(()=>({ok:false,error:'Invalid server response'}));if(!r.ok||d.ok===false)throw new Error(d.error||'Request failed');return d}
function toast(s){let t=$('#toast');t.textContent=s;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2500)}
function showPage(page){
  if(['rides','offer','map','chats','profile'].includes(page)&&!me){page='login';toast('Please login first.')}
  $$('.page').forEach(x=>x.classList.remove('active'));$(`#${page}Page`).classList.add('active');
  $('#mobileNav').classList.remove('open');
  if(page==='rides')loadRides();if(page==='offer')loadMyRides();if(page==='map')initMapPage();if(page==='chats')loadChats();if(page==='profile')loadProfile();
  if(page==='home')stopPolling();
}
function nav(){
  const html=me?`<button onclick="showPage('home')">Home</button><button onclick="showPage('rides')">Find Rides</button><button onclick="showPage('offer')">Offer Ride</button><button onclick="showPage('map')">Map</button><button onclick="showPage('chats')">Chats</button><button onclick="showPage('profile')">Profile</button><button onclick="logout()">Logout</button>`:`<button onclick="showPage('home')">Home</button><button onclick="showPage('login')">Login</button><button onclick="showPage('register')">Create Account</button>`;
  $('#nav').innerHTML=html;$('#mobileNav').innerHTML=html;
}
$('#mobileMenu').onclick=()=>$('#mobileNav').classList.toggle('open');

async function check(){try{const d=await api('api/auth.php?action=me');me=d.user;nav();if(me)showPage('home');else showPage('home')}catch(e){toast(e.message)}}
async function logout(){await api('api/auth.php?action=logout',{method:'POST'});me=null;nav();showPage('home');toast('Logged out')}
$('#loginForm').onsubmit=async e=>{e.preventDefault();try{const d=await api('api/auth.php?action=login',{method:'POST',body:JSON.stringify({email:$('#loginEmail').value,password:$('#loginPassword').value})});me=d.user;nav();showPage('home');toast('Welcome back!')}catch(x){toast(x.message)}};
$('#registerForm').onsubmit=async e=>{
  e.preventDefault();
  const form=e.currentTarget;
  const email=$('#regEmail').value.trim();
  const password=$('#regPassword').value;
  try{
    await api('api/auth.php?action=register',{method:'POST',body:JSON.stringify({name:$('#regName').value.trim(),email,phone:$('#regPhone').value.trim(),password})});
    // Registration must not automatically log the user in.
    // Send the user to the login page after successful registration.
    me=null;
    nav();
    $('#loginEmail').value=email;
    $('#loginPassword').value='';
    form.reset();
    showPage('login');
    toast('Registration successful! Please login to continue.');
  }catch(x){toast(x.message)}
};

async function loadRides(){
  try{
    const q=new URLSearchParams({from:$('#from')?.value||'',to:$('#to')?.value||'',date:$('#date')?.value||'',seats:$('#seats')?.value||1});
    const d=await api('api/rides.php?action=list&'+q);$('#rideCount').textContent=d.rides.length+' rides';
    $('#rideList').innerHTML=d.rides.length?d.rides.map(r=>`<article class="ride"><div><div class="ride-route">${esc(r.from_location)} → ${esc(r.to_location)}</div><div class="ride-meta"><span>🕐 ${esc(r.departure_time.slice(0,5))}</span><span>📅 ${esc(r.ride_date)}</span><span>👤 ${esc(r.driver)}</span><span>🚘 ${esc(r.vehicle||'Vehicle')}</span><span>💺 ${r.seats_available} seats</span><span>📍 ${esc(r.pickup_details||'Pickup to confirm')}</span></div><p style="color:#777;font-size:12px">${esc(r.note||'')}</p></div><div class="ride-right"><strong>${money(r.price)}</strong><small>per seat</small><button class="primary" onclick="book(${r.id},${r.seats_available},${r.price},'${esc(r.from_location)}','${esc(r.to_location)}')">Book Seat</button></div></article>`).join(''):'<div class="panel"><b>No rides found.</b><p>Try a different route or offer your own ride.</p></div>';
  }catch(e){toast(e.message)}
}
$('#searchForm').onsubmit=e=>{e.preventDefault();loadRides()};

async function book(id,max,price,from,to){
  let seats=prompt(`Seats to book (1-${max})`,1);seats=Number(seats);if(!Number.isInteger(seats)||seats<1||seats>max)return;
  try{const d=await api('api/rides.php?action=book',{method:'POST',body:JSON.stringify({ride_id:id,seats})});toast(`Booking confirmed: ${money(d.total)}`);loadRides();showPage('chats')}catch(e){toast(e.message)}
}

$('#offerForm').onsubmit=async e=>{e.preventDefault();try{await api('api/rides.php?action=create',{method:'POST',body:JSON.stringify({from:$('#ofrom').value,to:$('#oto').value,date:$('#odate').value,time:$('#otime').value,seats:$('#oseats').value,price:$('#oprice').value,pickup:$('#opickup').value,note:$('#onote').value,from_lat:$('#ofromLat').value||null,from_lng:$('#ofromLng').value||null,to_lat:$('#otoLat').value||null,to_lng:$('#otoLng').value||null})});e.target.reset();$('#odate').value=today;toast('Ride published!');loadMyRides()}catch(x){toast(x.message)}};
async function loadMyRides(){try{const d=await api('api/rides.php?action=mine');$('#myRides').innerHTML=d.rides.length?d.rides.map(r=>`<div class="ride"><div><b>${esc(r.from_location)} → ${esc(r.to_location)}</b><div class="ride-meta">${r.ride_date} · ${r.departure_time.slice(0,5)} · ${r.seats_available}/${r.seats_total} seats · ${money(r.price)}</div></div><button class="secondary" onclick="cancelRide(${r.id})">Cancel</button></div>`).join(''):'<div class="panel">No offered rides yet.</div>'}catch(e){toast(e.message)}}
async function cancelRide(id){if(!confirm('Cancel this ride?'))return;try{await api('api/rides.php?action=cancel',{method:'POST',body:JSON.stringify({ride_id:id})});loadMyRides();toast('Ride cancelled')}catch(e){toast(e.message)}}

async function loadChats(){
 try{const d=await api('api/chats.php?action=list');const chats=d.chats.filter(c=>chatFilter==='all'||c.type===chatFilter);
 $('#chatList').innerHTML=chats.map(c=>`<div class="chat-item" onclick="openChat(${c.id})"><div class="avatar">${esc((c.display_name||'Chat').split(/\s+/).map(x=>x[0]).join('').slice(0,2).toUpperCase())}</div><div><strong>${esc(c.display_name||'Chat')}</strong><p>${esc(c.last_message||'No messages yet')}</p></div></div>`).join('')||'<div style="padding:20px;color:#777">No conversations yet.</div>';
 if(currentChat)openChat(currentChat);
 }catch(e){toast(e.message)}
}
$$('[data-filter]').forEach(b=>b.onclick=()=>{$$('[data-filter]').forEach(x=>x.classList.remove('active'));b.classList.add('active');chatFilter=b.dataset.filter;loadChats()});
async function openChat(id){
 currentChat=id;try{const d=await api('api/chats.php?action=messages&conversation_id='+id);const info=(await api('api/chats.php?action=list')).chats.find(x=>Number(x.id)===Number(id));$('#chatWindow').innerHTML=`<div class="fullchat"><div class="chat-head"><div class="avatar">${esc((info?.display_name||'Chat').split(/\s+/).map(x=>x[0]).join('').slice(0,2).toUpperCase())}</div><div><b>${esc(info?.display_name||'Chat')}</b><small>${info?.type==='group'?'Group conversation':'Direct conversation'}</small></div></div><div id="messages" class="messages">${d.messages.map(m=>`<div class="bubble ${Number(m.sender_id)===Number(me.id)?'mine':''}">${m.sender_id!==me.id?`<b>${esc(m.sender_name)}</b><br>`:''}${esc(m.message)}<small>${esc(m.created_at)}</small></div>`).join('')}</div><form id="sendForm" class="send"><input id="msg" autocomplete="off" placeholder="Type a message..."><button class="primary">➤</button></form></div>`;
 $('#sendForm').onsubmit=sendMessage;$('#messages').scrollTop=$('#messages').scrollHeight;startPolling();
 }catch(e){toast(e.message)}
}
async function sendMessage(e){e.preventDefault();const msg=$('#msg').value.trim();if(!msg)return;try{await api('api/chats.php?action=send',{method:'POST',body:JSON.stringify({conversation_id:currentChat,message:msg})});$('#msg').value='';openChat(currentChat)}catch(x){toast(x.message)}}
function startPolling(){stopPolling();poll=setInterval(()=>{if(currentChat)openChat(currentChat)},3000)}
function stopPolling(){if(poll)clearInterval(poll);poll=null}
async function openGroup(){const name=prompt('Group name');if(!name)return;try{const d=await api('api/chats.php?action=create_group',{method:'POST',body:JSON.stringify({name})});toast('Group created');loadChats();openChat(d.conversation_id)}catch(e){toast(e.message)}}

async function loadProfile(){try{const d=await api('api/profile.php');const u=d.user;$('#pname').value=u.name;$('#pemail').value=u.email;$('#pphone').value=u.phone||'';$('#pvehicle').value=u.vehicle||'';$('#pvehicleNo').value=u.vehicle_no||'';$('#pcity').value=u.city||''}catch(e){toast(e.message)}}
$('#profileForm').onsubmit=async e=>{e.preventDefault();try{const d=await api('api/profile.php',{method:'POST',body:JSON.stringify({name:$('#pname').value,email:$('#pemail').value,phone:$('#pphone').value,vehicle:$('#pvehicle').value,vehicle_no:$('#pvehicleNo').value,city:$('#pcity').value})});me=d.user;nav();toast('Profile saved')}catch(x){toast(x.message)}};


async function loadMapsScript(){
  if(window.google?.maps){mapsReady=true;return;}
  const d=await api('api/maps.php');
  if(!d.api_key || d.api_key==='YOUR_GOOGLE_MAPS_API_KEY') throw new Error('Google Maps API key is missing. Add it to config.php.');
  await new Promise((resolve,reject)=>{
    const existing=document.getElementById('googleMapsScript');
    if(existing){existing.addEventListener('load',resolve,{once:true});existing.addEventListener('error',()=>reject(new Error('Google Maps failed to load. Check API key, billing and enabled APIs.')),{once:true});return;}
    const s=document.createElement('script');s.id='googleMapsScript';
    s.src='https://maps.googleapis.com/maps/api/js?key='+encodeURIComponent(d.api_key)+'&v=weekly&loading=async&libraries=places';
    s.async=true;s.defer=true;s.onload=resolve;s.onerror=()=>reject(new Error('Google Maps failed to load. Check API key, billing and enabled APIs.'));
    document.head.appendChild(s);
  });
  if(!window.google?.maps) throw new Error('Google Maps JavaScript API did not initialize.');
  mapsReady=true;
}
async function initMapPage(){
  const status=$('#mapStatus');
  try{
    status.textContent='Loading Google Maps...';
    await loadMapsScript();
    const {Map}=await google.maps.importLibrary('maps');
    if(!map) map=new Map($('#map'),{center:{lat:12.9716,lng:77.5946},zoom:12,mapId:'DEMO_MAP_ID',mapTypeControl:false,streetViewControl:false,fullscreenControl:true});
    if(!$('#pickupAutocomplete').children.length) await setupPlaceAutocomplete();
    await populateTrackRides();
    status.textContent='Map ready. Allow location access or choose pickup and destination.';
  }catch(e){console.error('Map initialization error:',e);status.textContent=e.message;toast(e.message)}
}
async function setupPlaceAutocomplete(){
  const {PlaceAutocompleteElement}=await google.maps.importLibrary('places');
  const pickup=new PlaceAutocompleteElement();pickup.placeholder='Search pickup location';pickup.style.width='100%';$('#pickupAutocomplete').appendChild(pickup);
  const destination=new PlaceAutocompleteElement();destination.placeholder='Search destination location';destination.style.width='100%';$('#destinationAutocomplete').appendChild(destination);
  pickup.addEventListener('gmp-select',async ev=>{try{const place=ev.placePrediction.toPlace();await place.fetchFields({fields:['displayName','formattedAddress','location','id']});selectedPickup=place;if(place.location){map.setCenter(place.location);map.setZoom(15);await setMapMarker(place.location,'Pickup')}$('#mapStatus').textContent='Pickup selected. Now choose your destination.'}catch(e){toast('Pickup selection error: '+e.message)}});
  destination.addEventListener('gmp-select',async ev=>{try{const place=ev.placePrediction.toPlace();await place.fetchFields({fields:['displayName','formattedAddress','location','id']});selectedDestination=place;if(place.location)await setMapMarker(place.location,'Destination');$('#mapStatus').textContent='Destination selected. Click Show Route.'}catch(e){toast('Destination selection error: '+e.message)}});
}
async function setMapMarker(position,title){const {AdvancedMarkerElement}=await google.maps.importLibrary('marker');return new AdvancedMarkerElement({map,position,title});}
function locateMe(){
  if(!map){toast('Please wait for Google Maps to finish loading.');return}
  if(!navigator.geolocation){toast('Geolocation is not supported by this browser.');return}
  $('#mapStatus').textContent='Requesting your GPS location...';
  navigator.geolocation.getCurrentPosition(async pos=>{const p={lat:pos.coords.latitude,lng:pos.coords.longitude};try{map.setCenter(p);map.setZoom(16);const {AdvancedMarkerElement}=await google.maps.importLibrary('marker');if(myMarker)myMarker.map=null;myMarker=new AdvancedMarkerElement({map,position:p,title:'Your location'});$('#mapStatus').textContent=`Your location: ${p.lat.toFixed(6)}, ${p.lng.toFixed(6)} (±${Math.round(pos.coords.accuracy||0)}m)`}catch(e){toast('Could not place GPS marker: '+e.message)}},err=>{const msg=err.code===1?'Location permission was denied. Chrome: lock icon → Site settings → Location → Allow.':err.code===2?'Your location is currently unavailable.':'GPS request timed out.';$('#mapStatus').textContent=msg;toast(msg)},{enableHighAccuracy:true,maximumAge:0,timeout:15000});
}
function useCurrentFor(which){
  if(!navigator.geolocation){toast('Geolocation unavailable');return}
  navigator.geolocation.getCurrentPosition(async pos=>{const p={lat:pos.coords.latitude,lng:pos.coords.longitude};try{await loadMapsScript();const {Geocoder}=await google.maps.importLibrary('geocoding');const res=await new Geocoder().geocode({location:p});const address=res.results?.[0]?.formatted_address||`${p.lat}, ${p.lng}`;if(which==='from'){$('#ofrom').value=address;$('#ofromLat').value=p.lat;$('#ofromLng').value=p.lng}else{$('#oto').value=address;$('#otoLat').value=p.lat;$('#otoLng').value=p.lng}toast('Current location added')}catch(e){toast('Location address error: '+e.message)}},()=>toast('Location permission denied'),{enableHighAccuracy:true,maximumAge:0,timeout:15000});
}
async function previewRoute(){
  if(!map){toast('Please wait for Google Maps to finish loading.');return}
  if(!selectedPickup?.location||!selectedDestination?.location){toast('Select pickup and destination from the Google suggestions first.');return}
  try{
    $('#mapStatus').textContent='Calculating route...';
    const {Route}=await google.maps.importLibrary('routes');
    routePolylines.forEach(x=>x.setMap(null));
    routePolylines=[];
    // Google Routes accepts a Place instance directly. Do not wrap a Place ID
    // as {placeId: ...}; that object shape caused the error in the previous build.
    const result=await Route.computeRoutes({
      origin:selectedPickup,
      destination:selectedDestination,
      travelMode:'DRIVING',
      fields:['path','distanceMeters','durationMillis','staticDurationMillis','localizedValues','viewport']
    });
    if(!result.routes?.length){$('#mapStatus').textContent='No driving route found.';toast('No driving route found.');return}
    const r=result.routes[0];
    routePolylines=r.createPolylines();
    routePolylines.forEach(x=>x.setMap(map));
    if(r.viewport) map.fitBounds(r.viewport,50);

    // Current Maps JS Routes API returns the numeric duration as durationMillis.
    // Use localizedValues.duration as a fallback so the UI never shows 0 when
    // the localized duration is available.
    let durationMs=Number(r.durationMillis||0);
    if(!durationMs) durationMs=Number(r.staticDurationMillis||0);
    if(!durationMs && r.localizedValues?.duration){
      const text=String(r.localizedValues.duration).toLowerCase();
      let mins=0;
      const h=text.match(/([0-9]+(?:\.[0-9]+)?)\s*(?:hour|hours|hr|hrs|h)/);
      const m=text.match(/([0-9]+(?:\.[0-9]+)?)\s*(?:minute|minutes|min|mins|m)(?!i)/);
      if(h) mins+=Number(h[1])*60;
      if(m) mins+=Number(m[1]);
      durationMs=mins*60000;
    }
    const minutes=Math.max(1,Math.round(durationMs/60000));
    $('#mapStatus').innerHTML=`<div class="route-summary"><span>📏 ${(Number(r.distanceMeters||0)/1000).toFixed(1)} km</span><span>⏱️ ${minutes} min</span></div>`;
  }catch(e){
    console.error('Route error:',e);
    $('#mapStatus').textContent='Could not calculate the route. Check that Routes API is enabled for your Google Cloud project.';
    toast('Route error: '+e.message);
  }
}
async function populateTrackRides(){try{const d=await api('api/rides.php?action=trackable');const rides=d.rides||[];$('#trackRideSelect').innerHTML=rides.length?'<option value="">Select a ride</option>'+rides.map(r=>`<option value="${r.id}">${esc(r.from_location)} → ${esc(r.to_location)} · ${esc(r.driver)}${Number(r.driver_id)===Number(me?.id)?' · My ride':''}</option>`).join(''):'<option value="">No trackable rides. Offer a ride or book one first.</option>'}catch(e){console.error(e);$('#trackRideSelect').innerHTML='<option value="">Unable to load rides</option>';toast(e.message)}}
async function startTracking(){
  if(!map){toast('Please wait for Google Maps to finish loading.');return}
  const id=$('#trackRideSelect').value;if(!id){toast('Select a ride first.');return}
  try{const d=await api('api/location.php?action=get&ride_id='+encodeURIComponent(id));if(d.location?.lat)await renderLiveLocation(d.location);if(trackingTimer)clearInterval(trackingTimer);trackingTimer=setInterval(()=>refreshTracking(id),5000);if(!trackingWatch&&d.location&&Number(d.location.driver_id)===Number(me.id)){trackingWatch=navigator.geolocation.watchPosition(pos=>sendDriverLocation(id,pos),err=>toast('GPS tracking error: '+err.message),{enableHighAccuracy:true,maximumAge:3000,timeout:15000})}$('#trackingInfo').textContent='Live tracking active. Location refreshes every 5 seconds.'}catch(e){toast(e.message)}
}
async function refreshTracking(id){try{const d=await api('api/location.php?action=get&ride_id='+encodeURIComponent(id));if(d.location?.lat)await renderLiveLocation(d.location)}catch(e){$('#trackingInfo').textContent=e.message}}
async function sendDriverLocation(id,pos){try{await api('api/location.php?action=update',{method:'POST',body:JSON.stringify({ride_id:id,lat:pos.coords.latitude,lng:pos.coords.longitude,accuracy:pos.coords.accuracy,heading:pos.coords.heading,speed:pos.coords.speed})});await renderLiveLocation({lat:pos.coords.latitude,lng:pos.coords.longitude,accuracy:pos.coords.accuracy,updated_at:new Date().toISOString()})}catch(e){toast(e.message);stopTracking()}}
async function renderLiveLocation(l){if(l?.lat===undefined||l?.lng===undefined)return;const p={lat:Number(l.lat),lng:Number(l.lng)};map.setCenter(p);map.setZoom(Math.max(map.getZoom(),15));const {AdvancedMarkerElement,PinElement}=await google.maps.importLibrary('marker');if(trackingMarker)trackingMarker.map=null;const pin=new PinElement({glyph:'🚗',scale:1.25});trackingMarker=new AdvancedMarkerElement({map,position:p,title:'Live vehicle',content:pin.element});$('#trackingInfo').textContent=`Vehicle: ${p.lat.toFixed(6)}, ${p.lng.toFixed(6)} · accuracy ±${Math.round(Number(l.accuracy||0))}m · updated ${l.updated_at||'now'}`}
function stopTracking(){if(trackingTimer)clearInterval(trackingTimer);trackingTimer=null;if(trackingWatch!==null){navigator.geolocation.clearWatch(trackingWatch);trackingWatch=null}$('#trackingInfo').textContent='Tracking is off.'}
function openTracking(){showPage('map');setTimeout(startTracking,700)}

$('#odate').value=today;$('#date').value=today;nav();check();
