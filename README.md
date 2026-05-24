# 🎬 Movie Review System — IT8415 Database Programming 2 Group Project

A data-driven PHP + MySQL web application built for the IT8415 group project.
Theme: **Movie Review System** (tutor approved).

---

## What this app does
Users browse and search movies, read details, rate (1–5 stars) and comment.
Three roles control access:

| Role        | Can do |
|-------------|--------|
| **Viewer**  | Browse, search, view details, see comments/ratings. (Register to comment/rate.) |
| **Creator** | Everything a viewer can, plus add/edit/publish their own movies + upload media. |
| **Admin**   | Everything, plus manage users, manage/remove all content & comments, run reports. |

---

## Tech & deployment
- **Server-side:** PHP (mysqli, prepared statements)
- **Database:** MySQL (phpMyAdmin), all tables prefixed `dbProj_`
- **IDE:** NetBeans 8.2
- **Deployment:** uni server over **SFTP** (host `20.74.143.233`)

---

## First-time setup (do this once)

### 1. Get the code
Clone the GitHub repo into your NetBeans projects folder.

### 2. Configure the database connection
Open `DBconn.php` and replace the placeholders with your phpMyAdmin details:
```php
$username = "u<studentID>";   // your MySQL user
$password = "...";            // your phpMyAdmin password
$database = "<dbname>";       // the SHARED project database
```

### 3. Import the database
In phpMyAdmin, select the shared project database and **import** `sql/schema.sql`.
This creates all tables, the stored procedures, the trigger, the full-text index,
and inserts test data.

### 4. Set the base path
In **both** `header.php` and `auth_guard.php`, set:
```php
define('BASE_URL', '/movie');   // change to your deployed project root path
```

### 5. NetBeans SFTP
Set up the remote connection as **SFTP (not FTP)**:
- Host IP: `20.74.143.233`
- Username: `u<studentID>`  (e.g. `u123456789`)
- Password: `dbpr2group`

---

## Test accounts (after importing schema.sql)
| Role    | Username  | Password    |
|---------|-----------|-------------|
| Admin   | admin     | Admin123    |
| Creator | creator1  | Creator123  |
| Creator | creator2  | Creator123  |
| Viewer  | viewer1   | Viewer123   |

---

## Project structure
```
/                     root (home page, shared layout, DB connection)
  index.php           home page (movie listing)
  DBconn.php          single DB connection function
  header.php          shared header + role-aware nav (include)
  footer.php          shared footer (include)
  auth_guard.php      require_login() / require_role() helpers
  README.md           this file
  plan.md             full task list & progress tracker
/auth                 login.php, register.php, logout.php, Users.php, LoginClass.php
/creator              creator panel (add/edit/publish movies)
/admin                admin panel (users, content, reports)
/search               search.php (full-text + filters)
/css                  style.css
/images               uploaded posters (+ placeholder.jpg)
/sql                  schema.sql
```

---

## Features mapped to the brief
- **Auth & roles** — sessions, encrypted passwords (`password_hash`), 3 roles
- **Security** — prepared statements everywhere, XSS cleaning (`htmlentities`/`strip_tags`)
- **Search** — FULLTEXT index on movies (`MATCH ... AGAINST`), plus date/creator/popularity
- **Database** — stored procedures (reports), trigger (rating validation), `dbProj_` prefix
- **Advanced features** — prepared statements, triggers, stored procedures, AJAX, jQuery
  (more than the required 2)

See **`plan.md`** for the live task checklist and what's left to build.

---

## Team workflow
- Work on the tasks listed in `plan.md`; tick items off as you go.
- Commit often with clear messages.
- Don't commit real DB passwords to GitHub if the repo is public — keep `DBconn.php`
  credentials only on the server / share privately.

---

## Status
Auth system + database schema + shared layout are **done**.
Content (movies), search, creator/admin panels, reports, and AJAX/jQuery are **next**.
Track everything in `plan.md`.