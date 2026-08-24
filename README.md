# Commute Share — XAMPP + PHP + MySQL

This version converts the browser-only MVP into a database-backed application.

## Features
- Registration/login/logout with PHP sessions
- Password hashing
- MySQL users
- Create/search/cancel rides
- Transaction-safe seat booking
- MySQL booking records
- Individual and group conversations
- Database-backed messages
- 3-second chat polling
- Profile editing
- Responsive UI

## Install
1. Copy this folder to `C:\xampp\htdocs\commute_share_xampp`
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin: `http://localhost/phpmyadmin`
4. Import `database.sql`.
5. Check `config.php`:
   - host `127.0.0.1`
   - database `commute_share`
   - user `root`
   - password `''` for the common default XAMPP setup.
6. Open `http://localhost/commute_share_xampp/`

## Database
The schema creates:
- users
- rides
- bookings
- conversations
- conversation_members
- messages

## Important
This is a local development application. Before production, add CSRF protection, rate limiting, stronger authorization rules, HTTPS, email/phone verification, audit logging, input/content moderation, secure production database credentials, and real-time WebSockets.

## Maps
Google Maps can be added after the core database/auth flow is working. Store coordinates on rides/users and use a maps API for route display and pickup selection.


## Google Maps + GPS upgrade

### 1. Create a Google Cloud project
Create/select a Google Cloud project, enable billing, create an API key, and enable:
- Maps JavaScript API
- Places API (New)
- Routes API

Google's current documentation requires the Maps JavaScript API and Routes API for the Routes library, and the Places API (New) for the current Place Autocomplete widget. Advanced markers use a map ID; this project uses `DEMO_MAP_ID` for development. For production, create your own map ID. 

### 2. Configure the key
Open `config.php` and replace:
`YOUR_GOOGLE_MAPS_API_KEY`
with your key.

Restrict the browser key by HTTP referrer, for example:
`http://localhost/*`
and only allow the APIs this application needs.

### 3. Re-import database.sql
The upgrade adds:
- `from_lat`, `from_lng`
- `to_lat`, `to_lng`
- `ride_locations`

If your old database already exists, either import the upgrade statements manually or use a fresh `commute_share` database for development.

### 4. GPS
The browser uses HTML5 `navigator.geolocation` for device location. On a local XAMPP installation, use `http://localhost/...`; production GPS should be served over HTTPS and users must grant location permission.

### 5. Live tracking architecture
Driver browser:
GPS → `api/location.php?action=update` → MySQL `ride_locations`

Rider browser:
`api/location.php?action=get` every 5 seconds → map marker

The current implementation is polling-based, which is simple and XAMPP-friendly. For a production Rapido-like experience, replace polling with WebSockets or a push service and add explicit trip start/end states, stale-location handling, privacy controls, and tracking consent.

### 6. Route
Pickup and destination are selected using the current Place Autocomplete widget. The Maps JavaScript Routes library computes and draws the driving route. Google recommends the newer `Route.computeRoutes()` approach over the legacy Directions Service.

Official docs:
- Maps JavaScript API: https://developers.google.com/maps/documentation/javascript
- Place Autocomplete: https://developers.google.com/maps/documentation/javascript/place-autocomplete-new
- Routes library: https://developers.google.com/maps/documentation/javascript/routes/start
- Browser/device geolocation guidance: https://developers.google.com/maps/documentation/geolocation/overview
