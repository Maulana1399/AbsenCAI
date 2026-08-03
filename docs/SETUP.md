# Setup

## Database

### Primary: MariaDB

The default database connection is **MariaDB** (see `.env` / `config/database.php`).

Example `.env`:

```
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kja_event_manager
DB_USERNAME=kja
DB_PASSWORD=your_password
```

Setup steps:

1. Create the database and a user:
   ```sql
   CREATE DATABASE kja_event_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE DATABASE kja_event_manager_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'kja'@'localhost' IDENTIFIED BY 'your_password';
   GRANT ALL PRIVILEGES ON kja_event_manager.* TO 'kja'@'localhost';
   GRANT ALL PRIVILEGES ON kja_event_manager_test.* TO 'kja'@'localhost';
   FLUSH PRIVILEGES;
   ```
2. Run migrations and seeders:
   ```sh
   php artisan migrate:fresh --seed
   ```
3. Verify: `php artisan migrate:status` shows all migrations applied.

`migrate:fresh --seed`, full `migrate:rollback` + re-migrate, and re-running seeders are all idempotent on MariaDB.

### Tests

`phpunit.xml` pins the committed test baseline to SQLite `:memory:`:

```sh
vendor/bin/pest
```

To run the suite against MariaDB (test database must exist — see above):

```sh
DB_CONNECTION=mariadb DB_HOST=127.0.0.1 DB_PORT=3306 \
DB_DATABASE=kja_event_manager_test DB_USERNAME=kja DB_PASSWORD=your_password \
vendor/bin/pest
```

Both drivers pass the full suite: **1792 tests / 4219 assertions / 0 failures**.

## Frontend

```sh
npm install
npm run dev
```

## PHP

- PHP 8.3+, extensions: `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `gd`
