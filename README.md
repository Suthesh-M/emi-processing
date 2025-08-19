# EMI Processing - Upfinzo Assignment

**Role:** Junior Laravel Developer

This repository implements the EMI processing task described in the assignment PDF. It is a small Laravel application that:

* Reads `loan_details` rows
* Dynamically creates an `emi_details` table (raw SQL) with one column per month in the loans' date range
* Inserts EMI amounts month-wise per `clientid` such that the sum of monthly EMIs equals the original `loan_amount` (rounding adjusted to the last payment)

---

## Table of Contents

* [Prerequisites](#prerequisites)
* [Installation](#installation)
* [Configuration](#configuration)
* [Database](#database)
* [Run the application](#run-the-application)
* [Usage](#usage)
* [Implementation notes](#implementation-notes)
* [Verification / SQL checks](#verification--sql-checks)
* [Testing](#testing)
* [Troubleshooting](#troubleshooting)
* [Improvement ideas](#improvement-ideas)
* [Project layout](#project-layout)
* [License & Contact](#license--contact)

---

## Prerequisites

* PHP 8.1+ (or Laravel 10 compatible PHP)
* Composer
* Node.js & npm (for frontend assets)
* MySQL (or compatible DB)
* Git

---

## Installation

```bash
# 1. Clone the repository
git clone git@github.com:<your-username>/<repo>.git
cd <repo>

# 2. Install PHP dependencies
composer install

# 3. Copy env and generate app key
cp .env.example .env
php artisan key:generate

# 4. Install JS dependencies and build assets
npm install
npm run dev

# 5. Create database & configure .env (see Configuration section)
# 6. Run migrations and seeders
php artisan migrate --seed

# 7. Serve locally
php artisan serve
# App will be available at http://127.0.0.1:8000
```

---

## Configuration

Edit `.env` and set DB credentials and other environment variables. Example minimal settings:

```
APP_NAME="EMI Processing"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=emi_db
DB_USERNAME=root
DB_PASSWORD=secret
```

> **Important:** Do NOT commit your `.env` to GitHub. Push a `.env.example` with placeholder values only.

---

## Database

The project includes migrations and seeders:

* `loan_details` migration (fields: `entity_id` (PK), `clientid`, `num_of_payment`, `first_payment_date`, `last_payment_date`, `loan_amount`)
* User seeder (creates a developer user for login)
* LoanDetails seeder (sample loans to test the app)

Seeders are wired in `DatabaseSeeder` and will run with `php artisan migrate --seed`.

**Seeded login:**

* Email: `developer@example.com`
* Password: `Test@Password123#`

---

## Run the application

1. Start the dev server:

```bash
php artisan serve
```

2. Open `http://127.0.0.1:8000` and login with the seeded credentials.
3. Navigate to `/admin` to view loan records and to run the EMI processing.

---

## Usage

1. On the admin page, you'll see the current `loan_details` rows.
2. Click **Process Data** to:

   * Determine the minimum `first_payment_date` and maximum `last_payment_date` across all loans
   * Build a months list covering this inclusive range
   * `DROP TABLE IF EXISTS emi_details;` and then `CREATE TABLE emi_details (...)` with one column for each month (column names formatted like `2019_Feb`)
   * Insert one row per `clientid` with EMI amounts month-wise

Processing is implemented in `app/Services/EmiProcessingService.php` and is triggered by `AdminController@processData`.

---

## Implementation notes

* **Raw SQL for DDL**: The `emi_details` table is created using raw SQL (`DB::unprepared()`), as required by the assignment.
* **Dynamic columns**: Column names are formatted as `YYYY_MMM` (e.g. `2019_Feb`). Column identifiers are wrapped in backticks when building SQL to avoid syntax errors (because names start with digits).
* **Rounding logic**:

  * `base_emi = round(loan_amount / num_of_payment, 2)`
  * `sum_base = base_emi * num_of_payment`
  * `remainder = round(loan_amount - sum_base, 2)`
  * The remainder (positive or negative) is added to the **last EMI** so that the sum of monthly values equals `loan_amount` exactly.
* **Insertion**: Each `clientid` row is inserted using parameterized raw insert (`DB::insert($sql, $bindings)`) to avoid SQL injection.
* **Re-run safe**: Re-processing the data will drop and re-create `emi_details`, and re-insert values.

---

## Verification / SQL checks

After processing, verify that sum of month columns equals loan\_amount. Example (generic approach):

1. Inspect the `emi_details` table structure to find the month columns (or copy/paste the header in a spreadsheet).
2. Or run a manual check per client in SQL (replace month columns with actual columns produced):

```sql
SELECT clientid,
       (`2019_Feb` + `2019_Mar` + `2019_Apr` /* + ... other months */) AS total
FROM emi_details
WHERE clientid = 1001;
```

3. Alternatively export `emi_details` and use Excel/Google Sheets to compute row sums and compare with `loan_details`.

**Quick DB-level sum check (programmatic)**: You can write a small PHP or MySQL script to fetch column names and compute sums for each row.

---

## Testing

* There are no unit tests included by default. Recommended tests to add:

  * Unit tests for `EmiProcessingService` verifying month generation and rounding behavior for multiple cases (even/odd num\_of\_payment, small/large loan\_amount, first payments that start near year-end).
  * Integration test for the admin flow (authenticated user can trigger processing).

Run tests (if you add them):

```bash
php artisan test
```

---

## Troubleshooting

* **`DB::unprepared()` fails**: Check for SQL syntax errors; log the generated SQL in the service or run it manually in a MySQL client to get the exact error message.
* **Permissions issues**: Ensure the DB user has privileges to `DROP` and `CREATE` tables in the database.
* **Column name collisions**: If you run the process with different date ranges, the table is dropped first so collisions should not occur.

---

## Improvement ideas

* Add **unit tests** covering edge cases and rounding behavior.
* Add an endpoint to **export `emi_details` to CSV**.
* Make `emi_details` table name configurable via `.env`.
* Add a small **audit log** table to store when the last processing ran and which rows were changed.
* Add RBAC (roles) and limit processing to admin users only.

---

## Project layout (important files)

* `app/Services/EmiProcessingService.php` — main logic which generates months, creates `emi_details` via raw SQL, and inserts rows
* `app/Repositories/EloquentLoanRepository.php` — loan data access
* `app/Http/Controllers/AdminController.php` — UI endpoints to trigger processing and view results
* `database/migrations/*` — migrations for `loan_details` and default Laravel tables
* `database/seeders/*` — seeders for `user` and `loan_details`
* `resources/views/admin/loan_details.blade.php` — admin UI

---

## Security & Notes

* Never commit `.env` or secrets.
* The service runs `DROP TABLE IF EXISTS` — make sure the database used for testing is not production.
* Parameterize inserts and avoid concatenating user input into DDL; the app uses backticks for generated column names and parameterized `DB::insert` for values.

---

## License & Contact

This sample project is provided for the Upfinzo assignment. Use and modify it freely to complete your task.

If you need changes (CSV export, unit tests, README tweaks, or a ready-to-push repo structure), tell me and I will prepare them.

---
