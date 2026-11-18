# Sparkle Hub 🎆

An online fireworks & crackers store built with **PHP and MySQL**. Customers browse products with automatic slab discounts, manage a cart, check out with Cash on Delivery, and track or cancel their orders. Store owners get an admin panel for products, categories, discounts, orders and customer messages.

> Academic project built to practise full-stack web development. It is not a real store.

## Features

**Customer side**
- Product catalogue grouped by category, with live search by product or category name
- Automatic discounts by price slab (for example 15% off products priced ₹100–₹250), applied the same way on the product page, cart and checkout
- Cart with editable quantities and live stock checks
- Checkout with form validation (10-digit mobile number, email, state list)
- Order confirmation page with a status tracker (Placed → Packing → Shipped → Delivered)
- **My Orders**: see your orders, cancel before shipping (stock is returned automatically), or track an order from any device with order number + phone number
- Contact form that saves messages for the store owner

**Admin panel** (`/admin`)
- Dashboard with totals, pending orders, low-stock alert and unread messages
- Add, edit and delete products (with image upload), categories and discount slabs
- View orders and update their status; cancelling restores stock
- Read and manage customer messages

**Security**
- Passwords stored as bcrypt hashes (`password_hash` / `password_verify`)
- All database queries use PDO prepared statements (no SQL injection)
- All output is HTML-escaped (no XSS)
- Image uploads are checked to be real images and renamed, so scripts can't be uploaded
- Orders are private to the customer who placed them
- Form submissions from other websites are blocked (basic CSRF protection)
- Database password kept out of Git in `inc/config.php`

## Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP 8 (PDO) |
| Database | MySQL / MariaDB |
| Frontend | HTML, CSS, JavaScript, Bootstrap 5, jQuery, Font Awesome |

## Run it locally (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL**.
2. Copy this folder into `C:\xampp\htdocs\sparkle-hub`.
3. Open **phpMyAdmin** (http://localhost/phpmyadmin), click **Import**, and import `database/sparkle_hub.sql`.
   It creates the `cracker_shop` database with demo products.
4. Copy `inc/config.example.php` to `inc/config.php` and enter your MySQL username and password
   (XAMPP default: user `root`, empty password).
5. Open http://localhost/sparkle-hub/

**Admin panel:** http://localhost/sparkle-hub/admin/
Demo login: `admin` / `admin123`

> Already have the old database? Run `database/upgrade_existing_db.sql` instead of re-importing, to keep your data.

## Project structure

```
├── index.php            Product catalogue and search
├── cart.php             Shopping cart
├── cart_action.php      Add / update / remove cart items (JSON API)
├── checkout.php         Shipping details and order placement
├── order_detail.php     Order confirmation and status tracker
├── view_orders.php      My Orders, cancel and track orders
├── about.php            About page
├── contact.php          Contact form
├── admin/               Admin panel (login, dashboard, products, categories,
│                        discounts, orders, messages)
├── inc/
│   ├── db.php           Database connection
│   ├── functions.php    Shared helpers (discounts, cart, order status, security)
│   └── config.example.php  Settings template (copy to config.php)
├── database/
│   ├── sparkle_hub.sql          Full database with demo data
│   └── upgrade_existing_db.sql  Upgrade script for older installs
└── images/              Product images
```

## Database

| Table | Purpose |
|---|---|
| `product` | Products with price (MRP) and stock |
| `category` | Product categories |
| `discount` | Discount slabs by price range |
| `cart` | Items in each visitor's cart |
| `orders` / `order_items` | Placed orders and their items |
| `contact_messages` | Messages from the contact form |
| `login` | Admin users (hashed passwords) |

## Screenshots

_Add screenshots of the home page, cart, order tracker and admin dashboard to a `screenshots/` folder and link them here._

## Future improvements

- Customer accounts with login
- Online payment gateway (e.g. Razorpay test mode)
- Email confirmation when an order is placed or shipped
