# 🎬 Movie Review System — IT8415 Database Programming 2

PHP + MySQL web app. Theme: **Movie Review System** (tutor approved).
Roles: **Viewer** (browse/search), **Creator** (add/edit/publish movies), **Admin** (manage everything + reports).

---

## Setup (do this once)

### 1. Get the code
Clone the repo into your NetBeans projects folder.

### 2. Database connection — `DBconn.php`
Put your phpMyAdmin details:
```php
$username = "u<studentID>";   // your MySQL user
$password = "...";            // your phpMyAdmin password
$database = "MovieReview";    // the shared project database
```

### 3. Import the database
In phpMyAdmin: select the `MovieReview` database → **Import** → choose `sql/schema.sql` → Go.
This creates all tables, stored procedures, the trigger, the full-text index, and test data.

### 4. Set the base path — VERY IMPORTANT
Look at the URL when you open the site, then set the matching path in **BOTH**
`header.php` and `auth_guard.php`:
```php
define('BASE_URL', '/~u202304108/MovieReview');   // <-- match YOUR url exactly (tilde + capital letters matter)
```
If CSS/images don't load, this value is wrong.

### 5. NetBeans deploy — **SFTP (not FTP)**
- Host IP: `20.74.143.233`
- Username: `u<studentID>`
- Password: `dbpr2group`

---

## Test accounts
| Role    | Username  | Password    |
|---------|-----------|-------------|
| Admin   | admin     | Admin123    |
| Creator | creator1  | Creator123  |
| Viewer  | viewer1   | Viewer123   |

---

## What works now
- **Home** (`index.php`) — newest movies, pagination.
- **Movie details** (`movie_view.php`) — info, trailer, comments, rating display.
- **Search** (`search/search.php`) — by title (full-text), date range, creator, popularity.
- **Auth** (`auth/`) — register, login, logout, 3 roles, encrypted passwords.

## Still to build
Creator panel, admin panel, reports, comment/rating submission (AJAX/jQuery).
See **`plan.md`** for the full task list.

---

## Project structure
```
/            index.php, movie_view.php, Movie.php, DBconn.php, header.php, footer.php, auth_guard.php
/auth        login, register, logout, Users.php, LoginClass.php
/search      search.php
/creator     (creator panel — TODO)
/admin       (admin panel — TODO)
/css         style.css
/images      placeholder.jpg + uploaded posters
/sql         schema.sql
```

---

## Team workflow
1. `git pull` before you start.
2. Work on a task from `plan.md`, tick it off.
3. `git add .` → `git commit -m "what you did"` → `git push`.
4. Don't commit real DB passwords if the repo is public.
