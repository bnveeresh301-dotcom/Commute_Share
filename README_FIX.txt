MAP/GPS/LIVE TRACKING FIXES

1. Route calculation now passes the selected Google Place objects directly to Route.computeRoutes().
2. Live tracking ride list now contains the user's own active rides and rides with confirmed bookings, regardless of today's date.
3. Driver GPS updates are still stored in ride_locations and riders with confirmed bookings can read them.

Do not re-import database.sql if your existing commute_share database already works; this update changes PHP/JS only.
