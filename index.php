<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Commute Share</title>
<link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<header class="topbar">
  <div class="brand" onclick="showPage('home')"><div class="logo">CS</div><div><strong>Commute Share</strong><small>Ride together. Save together.</small></div></div>
  <nav id="nav"></nav>
  <button id="mobileMenu" class="icon-btn">☰</button>
</header>
<div id="mobileNav" class="mobile-nav"></div>

<main>
<section id="homePage" class="page active">
  <div class="hero"><div><span class="eyebrow">SMART COMMUTING</span><h1>Go farther.<br><span>Share the ride.</span></h1><p>Find rides, offer empty seats, book trips and chat with your commute community.</p><div class="hero-actions"><button class="primary" onclick="showPage('rides')">Find a Ride</button><button class="secondary" onclick="showPage('offer')">Offer a Ride</button></div><div class="trust">✓ Verified profiles &nbsp; ✓ Ride booking &nbsp; ✓ Individual & group chat</div></div><div class="hero-card"><div class="route-box"><div class="route-top">TODAY'S POPULAR ROUTE <b>● LIVE</b></div><div class="route"><span>●</span><div><small>FROM</small><strong>Electronic City</strong></div><i>↓</i><span>●</span><div><small>TO</small><strong>Whitefield</strong></div></div><div class="route-foot">🚗 18 rides &nbsp; · &nbsp; 👥 41 seats &nbsp; · &nbsp; ₹85 avg.</div></div></div></div>
  <div class="cards4"><div class="quick" onclick="showPage('rides')">🔎<h3>Find a ride</h3><p>Search by pickup, destination and date.</p></div><div class="quick" onclick="showPage('offer')">🚘<h3>Offer a ride</h3><p>Publish your route and share your seats.</p></div><div class="quick" onclick="showPage('chats')">💬<h3>Chat</h3><p>Message riders individually or in groups.</p></div><div class="quick" onclick="showPage('profile')">🛡️<h3>Profile</h3><p>Manage your vehicle and preferences.</p></div></div>
</section>

<section id="mapPage" class="page">
  <div class="page-head"><div><span class="eyebrow">LIVE MAP</span><h1>Map & Live Tracking</h1><p>Use GPS, choose places and follow a booked ride.</p></div><div class="map-actions"><button class="secondary" onclick="locateMe()">📍 My Location</button><button class="primary" onclick="openTracking()">🚗 Track Ride</button></div></div>
  <div class="map-layout">
    <div id="map" class="map"></div>
    <aside class="map-panel">
      <h3>Choose locations</h3>
      <label>Pickup</label><div id="pickupAutocomplete" class="autocomplete-host"></div>
      <label>Destination</label><div id="destinationAutocomplete" class="autocomplete-host"></div>
      <button class="primary full" onclick="previewRoute()">Show Route</button>
      <div id="mapStatus" class="map-status">Allow location access to show your position.</div>
      <hr>
      <h3>Live ride tracking</h3>
      <select id="trackRideSelect"><option value="">Select a booked/owned ride</option></select>
      <button class="secondary full" onclick="startTracking()">Start Tracking</button>
      <button class="secondary full" onclick="stopTracking()">Stop Tracking</button>
      <div id="trackingInfo" class="map-status">Tracking is off.</div>
    </aside>
  </div>
</section>

<section id="ridesPage" class="page">
  <div class="page-head"><div><span class="eyebrow">RIDE DISCOVERY</span><h1>Find a ride</h1><p>Search active rides from the database.</p></div></div>
  <form id="searchForm" class="panel search-grid"><input id="from" placeholder="From"><input id="to" placeholder="To"><input id="date" type="date"><input id="seats" type="number" min="1" value="1"><button class="primary">Search</button></form>
  <div class="row-head"><h2>Available rides</h2><span id="rideCount"></span></div><div id="rideList" class="list"></div>
</section>

<section id="offerPage" class="page">
  <div class="page-head"><div><span class="eyebrow">DRIVER MODE</span><h1>Offer a ride</h1><p>Publish a real database-backed ride.</p></div></div>
  <form id="offerForm" class="panel form-grid"><div class="place-wrap"><input id="ofrom" required placeholder="From"><button type="button" class="locate-input" onclick="useCurrentFor('from')">📍</button></div><div class="place-wrap"><input id="oto" required placeholder="To"></div><input id="odate" type="date" required><input id="otime" type="time" required><input id="oseats" type="number" min="1" max="8" value="3" required><input id="oprice" type="number" min="0" value="80" required><input id="opickup" class="wide" placeholder="Pickup details"><textarea id="onote" class="wide" placeholder="Ride note"></textarea><input type="hidden" id="ofromLat"><input type="hidden" id="ofromLng"><input type="hidden" id="otoLat"><input type="hidden" id="otoLng"><button class="primary wide">Publish Ride</button></form>
  <h2 class="subhead">Your offered rides</h2><div id="myRides" class="list"></div>
</section>

<section id="chatsPage" class="page">
  <div class="page-head"><div><span class="eyebrow">COMMUNICATION</span><h1>Messages</h1><p>Individual and group conversations.</p></div><button class="secondary" onclick="openGroup()">＋ New Group</button></div>
  <div class="chat-layout"><aside><div class="chat-filter"><button data-filter="all" class="active">All</button><button data-filter="direct">Direct</button><button data-filter="group">Groups</button></div><div id="chatList"></div></aside><section id="chatWindow" class="chat-window"><div class="empty">💬<h3>Select a conversation</h3><p>Your messages appear here.</p></div></section></div>
</section>

<section id="profilePage" class="page">
  <div class="page-head"><div><span class="eyebrow">ACCOUNT</span><h1>Your profile</h1><p>Update information stored in MySQL.</p></div></div>
  <form id="profileForm" class="panel form-grid"><input id="pname" required placeholder="Full name"><input id="pemail" required type="email" placeholder="Email"><input id="pphone" placeholder="Phone"><input id="pvehicle" placeholder="Vehicle"><input id="pvehicleNo" placeholder="Vehicle number"><input id="pcity" placeholder="City"><button class="primary wide">Save Profile</button></form>
</section>

<section id="loginPage" class="page auth-page">
  <div class="auth-card"><div class="logo big">CS</div><h1>Welcome Back! 👋</h1><p>Login to Commute Share.</p><form id="loginForm"><input id="loginEmail" required type="email" placeholder="Email"><input id="loginPassword" required type="password" placeholder="Password"><button class="primary">LOGIN</button></form><p>Don't have an account? <button class="link" onclick="showPage('register')">CREATE ACCOUNT</button></p></div>
</section>

<section id="registerPage" class="page auth-page">
  <div class="auth-card"><div class="logo big">CS</div><h1>Create Account</h1><p>Start sharing your commute.</p><form id="registerForm"><input id="regName" required placeholder="Full name"><input id="regEmail" required type="email" placeholder="Email"><input id="regPhone" placeholder="Phone"><input id="regPassword" required type="password" minlength="6" placeholder="Password"><button class="primary">CREATE ACCOUNT</button></form><p>Already have an account? <button class="link" onclick="showPage('login')">LOGIN</button></p></div>
</section>
</main>

<div id="modal" class="modal"></div>
<div id="toast" class="toast"></div>
<script src="assets/app.js"></script>
</body></html>
