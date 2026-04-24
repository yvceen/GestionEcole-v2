# Run Local (Windows / XAMPP)

## `php artisan db:show` and `intl` extension

`php artisan db:show` requires the PHP `intl` extension.
If `intl` is disabled, the command can fail.

### Enable `intl` on Windows XAMPP

1. Open `C:\xampp\php\php.ini`
2. Find: `;extension=intl`
3. Remove the leading semicolon so it becomes: `extension=intl`
4. Save the file
5. Restart Apache (and any running PHP CLI/server processes)

## Safe alternatives when `intl` is not enabled

- `php artisan migrate:status`
- `php artisan schema:dump`
- `php artisan tinker` then run:
  - `DB::select('SHOW TABLES');`
- `php artisan db:wipe` (warning: destructive, removes all tables in the configured database)

## Contact form SMTP setup

The landing page contact form posts to `POST /contact` and sends an email server-side.

### Required `.env` keys

Set at least:

- `CONTACT_TO=yassine@myedu.school`
- `MAIL_MAILER=smtp`
- `MAIL_HOST=...`
- `MAIL_PORT=587`
- `MAIL_USERNAME=...`
- `MAIL_PASSWORD=...`
- `MAIL_FROM_ADDRESS=no-reply@myedu.school`
- `MAIL_FROM_NAME="MyEdu"`

Optional aliases are also supported:

- `SMTP_HOST`
- `SMTP_PORT`
- `SMTP_USER`
- `SMTP_PASS`
- `SMTP_FROM` (example: `MyEdu <no-reply@myedu.school>`)

When `SMTP_*` values are provided, they are used by the SMTP mailer config.

### Quick local test

1. Configure SMTP values in `.env`.
2. Run `php artisan optimize:clear`.
3. Open the landing page and submit the contact form.
4. Verify:
   - success message: `Message envoye`
   - email delivered to `CONTACT_TO`
   - if SMTP is down, a friendly error is shown on the form.
