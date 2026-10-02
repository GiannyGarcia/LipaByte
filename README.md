# LipaByte Web App

Peer-to-peer tech equipment rental hub for IT students.  
PHP + MySQL — built for **InfinityFree** hosting and **phpMyAdmin**.

## Phase 1 (current): Accounts

- Student registration & login (bcrypt)
- Institutional email validation
- Admin verification workflow
- User profile & password settings
- Admin panel: users, campuses, categories

## Quick setup on InfinityFree

### 1. Create MySQL database

1. Log in to [InfinityFree](https://infinityfree.net/) control panel
2. Go to **MySQL Databases** → create a database (e.g. `lipabyte`)
3. Note the **hostname**, **database name**, **username**, and **password**

### 2. Import or upgrade database

**New database:** Import `sql/lipabyte_db_infinityfree.sql` in phpMyAdmin.

**Existing database (old schema):** Run `sql/upgrade_schema_v2.sql` in phpMyAdmin. This updates the `users` table (first/last name, no campus) and creates the default admin account.

### 3. Configure the app

1. Copy `config/database.example.php` to `config/database.local.php`
2. Fill in your InfinityFree MySQL credentials:

```php
return [
    'host' => 'sql123.infinityfree.com',
    'dbname' => 'epiz_12345678_lipabyte',
    'username' => 'epiz_12345678',
    'password' => 'your_mysql_password',
];
```

### 4. Upload files

Upload **all contents** of the `lipabyte/` folder to your `htdocs` directory (or a subfolder).

- **`index.html`** — public landing page (main entry point)
- **`home.php`** — logged-in marketplace home

### 5. Set folder permissions

Ensure `uploads/listings/` is writable (chmod 755 or 775).

## Default login credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@lipabyte.edu.ph | Admin@123 |
| Student | juan.mitra@university.edu.ph | Student@123 |
| Student | maria.garcia@university.edu.ph | Student@123 |

Change these passwords after first login in production.

## Project structure

```
lipabyte/
├── admin/           Admin dashboard & management
├── account/         User profile & settings
├── auth/            Login, register, logout
├── assets/          CSS & JS
├── config/          App & database config
├── includes/        Core PHP (auth, DB, layout)
├── sql/             Database import files
└── uploads/         Listing images (future)
```

## Student registration flow

1. Student registers with first name, last name, and school email (`*@schoolname.edu.ph`, e.g. `2120751@university.edu.ph`)
2. Account is created as **unverified**
3. Admin verifies the account in **Admin → Users**
4. Verified students can access marketplace features (next phases)

## Security notes

- Passwords hashed with bcrypt
- CSRF protection on all POST forms
- Session-based authentication
- `config/database.local.php` should not be shared publicly

## Next build phases

- Marketplace browse & search
- Listing creation with image upload
- Rental request workflow
- Reviews & ratings
