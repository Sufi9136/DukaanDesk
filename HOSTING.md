# Put DukaanDesk online + make an Android app

Two parts: (A) host the site so it has a public link, (B) turn that link into an installable app / APK.
Do part A first, part B needs the https link from part A.

Keep the XAMPP version working on your laptop as a backup for the viva. Free hosts are slow sometimes.

---

## A. Host it online (free PHP + MySQL host)

Free hosts that give PHP with MySQL and no credit card: **InfinityFree**, **Byet.host**, **TinkerHost**.
The steps below are written for InfinityFree, the others are very similar. Menu names can be slightly different.

1. **Sign up** on the host and create a hosting account. Choose a free subdomain, for example `dukaandesk.rf.gd`.
2. **Create the database.** In the control panel open *MySQL Databases*, create a database (for example `dukaandesk`).
   The host adds a prefix to the names. Write down these four things, you need them in step 5:
   - MySQL host name (looks like `sql123.infinityfree.com`, it is *not* localhost)
   - database name (like `if0_12345678_dukaandesk`)
   - database username
   - database password
3. **Import the tables.** Open *phpMyAdmin* from the panel, click your database on the left, open the **Import** tab and
   choose **`sql/shop_billing_hosting.sql`** (the hosting file, not `shop_billing.sql`). Click Go.
4. **Upload the files.** Use the *File Manager* (or FileZilla with the FTP details from the panel).
   Open the `htdocs` folder and upload the **contents** of the DukaanDesk folder, so that
   `htdocs/login.php` and `htdocs/sw.js` exist (not `htdocs/DukaanDesk/login.php`).
5. **Edit `config/db.php`** (right click -> Edit in File Manager) and put your four values:
   ```php
   $db_host = 'sql123.infinityfree.com';
   $db_name = 'if0_12345678_dukaandesk';
   $db_user = 'if0_12345678';
   $db_pass = 'your-database-password';
   ```
   Also change `SHOP_NAME`, `SHOP_ADDRESS` and `SHOP_PHONE` to your shop.
6. **Turn on HTTPS.** Find *Free SSL Certificates* in the panel and enable it for your domain. Wait a few minutes.
   HTTPS is required for the install button and for the APK.
7. Open `https://your-subdomain/` and log in.
8. **Change the passwords immediately** (Profile page). The sample logins `admin / admin123`, `cashier / cashier123`
   and `stock / stock123` are public knowledge. Better still, create your own users in the Users page and delete the sample ones.

### If something does not work
- White page / "Something went wrong": open `config/db.php`, set `define('DEBUG', true);`, reload, read the message,
  then set it back to `false`.
- "Could not connect to the database": the host name is wrong. It must be the long `sqlXXX...` name, not `localhost`.
- Import fails with "access denied ... CREATE DATABASE": you imported the wrong file, use `shop_billing_hosting.sql`.
- Free hosts can show a one-time "browser check" page or be slow. Open the site once in Chrome before the demo.

---

## B. Install it like an app (works right now, no APK needed)

After part A, open the https link in **Chrome on your Android phone**:
menu (three dots) -> **Install app** (or *Add to Home screen*). DukaanDesk now opens full screen from its own icon,
without the browser bar. On a laptop, Chrome shows an install icon in the address bar.

## C. Get an Android APK file

An APK is just a wrapper around the same website, so the shop data stays on the server.

1. Open **https://www.pwabuilder.com** and paste your https link, press Start.
2. It reads `manifest.json` and `sw.js` from this project. Open **Package for stores -> Android**.
3. Enter a package id (for example `com.yourname.dukaandesk`), app name `DukaanDesk`, and download the package.
4. The zip contains an `.apk` you can copy to your phone and install (allow "install unknown apps").
   The `.aab` file is only for Google Play.

Note: if PWABuilder cannot read your site, the free host's browser check may be blocking it. Part B (Install app
from Chrome) still gives you an app icon, and you can try another free host for the APK.
