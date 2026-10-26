# DukaanDesk - Inventory & Billing Management System for Local Shops

A web based system for small shops to manage products, stock, billing, customers and suppliers,
and to see sales reports.

**Tech used:** HTML5, CSS3, JavaScript, Bootstrap 5, PHP (PDO), MySQL. Runs on XAMPP.

## How to run

1. Install XAMPP and start **Apache** and **MySQL** from the control panel.
2. Copy the `DukaanDesk` folder to `C:\xampp\htdocs\`
   (so that `C:\xampp\htdocs\DukaanDesk\login.php` exists).
3. Open `http://localhost/phpmyadmin`, click **Import**, choose `sql/shop_billing.sql`, click **Go**.
   This creates the database `shop_billing_db` with sample data.
4. Open `http://localhost/DukaanDesk/` in the browser.

Internet is needed on the first load because Bootstrap, Chart.js and DataTables are loaded from CDN.

To put it online or make an Android app, read **HOSTING.md**.

The software name (DukaanDesk) and the shop name, address and phone (printed on the invoice) are at the top of `config/db.php`.
If your MySQL has a password, change it in the same file.

## Logins

| Role | Username | Password | Can use |
|---|---|---|---|
| Admin | admin | admin123 | everything |
| Cashier | cashier | cashier123 | dashboard, billing, bills, customers |
| Stock Manager | stock | stock123 | products, categories, suppliers, stock update, reports |

## Features

- Login with three roles, passwords stored hashed, logout confirmation
- Dashboard: today / month sales, low stock and out of stock counts, 12 month sales chart, top 5 products, recent bills
- Products: add, edit, delete, search, category, supplier, cost price, selling price, low stock alert level
- Categories and Suppliers management
- **New Bill:** search or filter products, click to add, change quantity, discount, GST %, payment mode (Cash / UPI / Card),
  pick a customer or add a new one while billing, live total
- Stock is reduced automatically when a bill is saved
- Printable invoice
- All Bills: date filter, details popup, print, cancel bill (admin only, stock goes back)
- Customers with total purchases
- Stock Update: add new stock or remove damaged stock, with a movement history
- Reports: date range, sales per day chart, payment split, top products, estimated profit, inventory value,
  export to **PDF** and **CSV**
- User management (admin) and profile page (change name / password)

## Folder structure

```
config/db.php        database + shop settings
inc/                 header, footer, login check (auth.php), helper functions
ajax/                bill_details.php (popup content)
assets/css, assets/js   styles and javascript (billing.js is the bill page logic)
sql/shop_billing.sql database tables + sample data
*.php                one file for each page
```

## Database tables

users, categories, suppliers, customers, products, bills, bill_items, stock_log

## How the main part works (for viva)

1. Cashier clicks products, the browser keeps them in a JavaScript object and shows the total.
2. On "Generate Bill" only the product id and quantity are sent to the server.
3. `billing.php` starts a **transaction**, reads each product again with `SELECT ... FOR UPDATE`
   (so two bills cannot sell the same last item), checks stock, takes the price from the database,
   saves the bill and the items, reduces stock, writes the stock log and commits.
4. If anything fails the transaction is rolled back, so there are no half saved bills.
5. Item name and prices are copied into `bill_items`, so old invoices do not change when a product price changes later.

**Security:** prepared statements (SQL injection), `htmlspecialchars` on output (XSS), `password_hash`
(passwords), CSRF token in every form, role check on every page, session id renewed on login.

**Profit** = (selling price - cost price) x quantity for the items sold, minus the discounts given.

## Future scope

Barcode scanner, WhatsApp / SMS bills, purchase orders to suppliers, cloud backup, mobile app, multiple branches.
