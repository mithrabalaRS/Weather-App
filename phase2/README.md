# Wewo Weather App

This is a starter full-stack version of the weather app.

## Features
- User registration and login
- Password hashing with PHP
- MySQL database storage for users
- Weather searches saved per user
- Favorite cities per user
- Admin portal with account, login, search, and favorite activity
- Validation for all forms

## Setup
1. Create a MySQL database named `weather_phase2`.
2. Import the schema from `schema.sql`.
3. Update database credentials in `config/db.php`.
4. Start a local PHP server:
   `php -S localhost:8000`
5. Open `http://localhost:8000/index.php`

## Admin access
- Existing databases: select `weather_phase2` in phpMyAdmin and import `migrate_user_logins.sql` to enable login tracking.
- New databases: `schema.sql` creates the login tracking table.
- Public signups create user accounts. An existing admin can select Admin when creating an account.
- Select the matching User or Admin account type when signing in.

## Notes
- The OpenWeather API key is already set in the starter code.
- For production, move the API key to environment variables.
