-- DukaanDesk - Inventory & Billing Management System for Local Shops
-- HOSTING VERSION: first create a database in your hosting panel, select it in phpMyAdmin, then Import this file.


SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS stock_log;
DROP TABLE IF EXISTS bill_items;
DROP TABLE IF EXISTS bills;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','cashier','stock_manager') NOT NULL DEFAULT 'cashier',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255),
    category_id INT NULL,
    supplier_id INT NULL,
    cost_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    min_stock INT NOT NULL DEFAULT 5,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_no VARCHAR(20) NOT NULL UNIQUE,
    customer_id INT NULL,
    user_id INT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0,
    gst_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    gst_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    pay_mode VARCHAR(10) NOT NULL DEFAULT 'Cash',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- item_name and prices are copied here so old bills stay correct even if the product changes later
CREATE TABLE bill_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    product_id INT NULL,
    item_name VARCHAR(120) NOT NULL,
    qty INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE stock_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    change_qty INT NOT NULL,
    reason VARCHAR(30) NOT NULL,
    note VARCHAR(255),
    user_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- sample data ----------
-- logins:  admin / admin123    cashier / cashier123    stock / stock123
INSERT INTO users (full_name, username, password, role) VALUES
('Shop Owner','admin','$2y$10$1k/CZam3zhVJhAyB5IhWMucRqEoW4wQzvCW14RptZYpwUUUAHZa.q','admin'),
('Ramesh Cashier','cashier','$2y$10$UCHzffn9ZsvKV74buvq7o.8JGe2CYh/hSMhlozrcvwcSqcJ11Jv/e','cashier'),
('Sunita Stock','stock','$2y$10$Mj3OqDJ8Te8wc2Q.mL.LtOLpwfwrCUZGIgkJE05FlukSNUT1NP.be','stock_manager');

INSERT INTO categories (name) VALUES ('Grocery'),('Stationery'),('Personal Care'),('Household'),('Snacks'),('Beverages');

INSERT INTO suppliers (name, phone, email, address) VALUES
('Sharma Wholesale','9876500001','sharma@example.com','Main Market, Sector 4'),
('City Stationery Hub','9876500002','cityhub@example.com','Station Road'),
('Fresh Mart Distributors','9876500003','freshmart@example.com','Industrial Area, Phase 2'),
('Gupta Traders','9876500004','gupta@example.com','Old Bazaar');

INSERT INTO customers (name, phone, email, address) VALUES
('Rahul Verma','9811100001','rahul@example.com','Green Colony'),
('Priya Singh','9811100002','priya@example.com','Lake View Apartments'),
('Amit Patel','9811100003','','Shanti Nagar'),
('Neha Joshi','9811100004','neha@example.com','Model Town'),
('Suresh Kumar','9811100005','','Gandhi Road'),
('Anita Desai','9811100006','anita@example.com','Rose Garden Society');

INSERT INTO products (name, description, category_id, supplier_id, cost_price, price, stock, min_stock) VALUES
('Basmati Rice 1kg','Long grain basmati rice',1,1,78,95,40,10),
('Sugar 1kg','Refined sugar',1,1,38,45,60,15),
('Tea Powder 250g','Strong assam tea',6,1,95,120,3,8),
('Toor Dal 1kg','Unpolished toor dal',1,1,118,140,25,10),
('Sunflower Oil 1L','Refined sunflower oil pouch',1,3,120,145,18,8),
('Wheat Atta 5kg','Chakki fresh atta',1,3,215,250,22,6),
('Iodized Salt 1kg',NULL,1,1,16,22,70,15),
('Notebook 200 pages','Ruled notebook',2,2,32,45,35,10),
('Blue Ball Pen','Pack of single pens',2,2,6,10,150,40),
('Pencil Box','Plastic pencil box with sharpener',2,2,42,60,4,5),
('Bath Soap','Moisturising bath soap',3,3,26,35,55,12),
('Toothpaste 150g','Fluoride toothpaste',3,3,68,85,0,8),
('Detergent Powder 1kg',NULL,4,3,85,105,20,8),
('Biscuits Pack','Glucose biscuits',5,4,8,10,120,30),
('Instant Noodles','Masala noodles',5,4,11,14,90,25),
('Water Bottle 1L','Packaged drinking water',6,4,12,20,48,12),
('Cold Drink 750ml','Cola 750ml bottle',6,4,30,40,30,10),
('Dish Wash Liquid',NULL,4,3,68,85,14,6);

-- sample bills
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00001',NULL,2,240,0,0,0.0,240.0,'Cash',(NOW() - INTERVAL 7831 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (1,10,'Pencil Box',2,60,42),(1,3,'Tea Powder 250g',1,120,95);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00002',3,2,489,0,5,24.45,513.45,'UPI',(NOW() - INTERVAL 7646 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (2,9,'Blue Ball Pen',1,10,6),(2,17,'Cold Drink 750ml',1,40,30),(2,12,'Toothpaste 150g',5,85,68),(2,15,'Instant Noodles',1,14,11);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00003',5,1,740,0,0,0.0,740.0,'Card',(NOW() - INTERVAL 7616 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (3,3,'Tea Powder 250g',5,120,95),(3,16,'Water Bottle 1L',1,20,12),(3,10,'Pencil Box',2,60,42);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00004',3,2,134,0,0,0.0,134.0,'Cash',(NOW() - INTERVAL 7590 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (4,7,'Iodized Salt 1kg',2,22,16),(4,9,'Blue Ball Pen',2,10,6),(4,15,'Instant Noodles',5,14,11);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00005',3,1,610,0,0,0.0,610.0,'Cash',(NOW() - INTERVAL 7381 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (5,13,'Detergent Powder 1kg',1,105,85),(5,11,'Bath Soap',3,35,26),(5,16,'Water Bottle 1L',2,20,12),(5,3,'Tea Powder 250g',3,120,95);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00006',4,2,760,0,0,0.0,760.0,'Cash',(NOW() - INTERVAL 7329 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (6,6,'Wheat Atta 5kg',2,250,215),(6,8,'Notebook 200 pages',2,45,32),(6,18,'Dish Wash Liquid',2,85,68);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00007',5,2,267,0,0,0.0,267.0,'Card',(NOW() - INTERVAL 7301 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (7,2,'Sugar 1kg',2,45,38),(7,15,'Instant Noodles',2,14,11),(7,13,'Detergent Powder 1kg',1,105,85),(7,7,'Iodized Salt 1kg',2,22,16);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00008',NULL,2,160,0,5,8.0,168.0,'Cash',(NOW() - INTERVAL 7186 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (8,4,'Toor Dal 1kg',1,140,118),(8,16,'Water Bottle 1L',1,20,12);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00009',1,2,240,0,0,0.0,240.0,'UPI',(NOW() - INTERVAL 6804 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (9,3,'Tea Powder 250g',2,120,95);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00010',1,2,885,0,5,44.25,929.25,'Cash',(NOW() - INTERVAL 6373 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (10,1,'Basmati Rice 1kg',1,95,78),(10,5,'Sunflower Oil 1L',2,145,120),(10,6,'Wheat Atta 5kg',2,250,215);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00011',3,2,165,0,0,0.0,165.0,'UPI',(NOW() - INTERVAL 6341 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (11,8,'Notebook 200 pages',1,45,32),(11,10,'Pencil Box',2,60,42);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00012',3,2,495,10,5,24.25,509.25,'UPI',(NOW() - INTERVAL 5747 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (12,5,'Sunflower Oil 1L',3,145,120),(12,17,'Cold Drink 750ml',1,40,30),(12,16,'Water Bottle 1L',1,20,12);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00013',3,2,84,0,0,0.0,84.0,'UPI',(NOW() - INTERVAL 5740 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (13,7,'Iodized Salt 1kg',2,22,16),(13,17,'Cold Drink 750ml',1,40,30);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00014',NULL,2,355,10,0,0.0,345.0,'Cash',(NOW() - INTERVAL 5605 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (14,16,'Water Bottle 1L',2,20,12),(14,12,'Toothpaste 150g',2,85,68),(14,5,'Sunflower Oil 1L',1,145,120);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00015',2,2,405,0,0,0.0,405.0,'Card',(NOW() - INTERVAL 5340 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (15,6,'Wheat Atta 5kg',1,250,215),(15,14,'Biscuits Pack',5,10,8),(15,11,'Bath Soap',3,35,26);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00016',6,2,530,5,0,0.0,525.0,'UPI',(NOW() - INTERVAL 5294 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (16,3,'Tea Powder 250g',2,120,95),(16,5,'Sunflower Oil 1L',2,145,120);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00017',2,1,499,0,0,0.0,499.0,'Cash',(NOW() - INTERVAL 5288 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (17,15,'Instant Noodles',1,14,11),(17,12,'Toothpaste 150g',5,85,68),(17,10,'Pencil Box',1,60,42);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00018',NULL,1,505,0,0,0.0,505.0,'UPI',(NOW() - INTERVAL 5239 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (18,18,'Dish Wash Liquid',2,85,68),(18,1,'Basmati Rice 1kg',1,95,78),(18,3,'Tea Powder 250g',2,120,95);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00019',2,2,489,0,0,0.0,489.0,'Cash',(NOW() - INTERVAL 5197 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (19,8,'Notebook 200 pages',5,45,32),(19,13,'Detergent Powder 1kg',2,105,85),(19,17,'Cold Drink 750ml',1,40,30),(19,15,'Instant Noodles',1,14,11);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00020',5,2,50,0,0,0.0,50.0,'UPI',(NOW() - INTERVAL 4791 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (20,16,'Water Bottle 1L',1,20,12),(20,9,'Blue Ball Pen',3,10,6);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00021',5,2,105,5,0,0.0,100.0,'UPI',(NOW() - INTERVAL 4735 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (21,11,'Bath Soap',3,35,26);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00022',3,2,120,0,0,0.0,120.0,'Card',(NOW() - INTERVAL 4476 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (22,10,'Pencil Box',2,60,42);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00023',2,2,455,0,5,22.75,477.75,'Cash',(NOW() - INTERVAL 4087 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (23,17,'Cold Drink 750ml',2,40,30),(23,5,'Sunflower Oil 1L',2,145,120),(23,18,'Dish Wash Liquid',1,85,68);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00024',5,2,187,0,0,0.0,187.0,'UPI',(NOW() - INTERVAL 4069 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (24,5,'Sunflower Oil 1L',1,145,120),(24,15,'Instant Noodles',3,14,11);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00025',1,2,420,0,0,0.0,420.0,'Cash',(NOW() - INTERVAL 3965 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (25,18,'Dish Wash Liquid',3,85,68),(25,13,'Detergent Powder 1kg',1,105,85),(25,10,'Pencil Box',1,60,42);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00026',6,1,262,10,0,0.0,252.0,'Cash',(NOW() - INTERVAL 3831 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (26,3,'Tea Powder 250g',2,120,95),(26,7,'Iodized Salt 1kg',1,22,16);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00027',6,1,45,0,0,0.0,45.0,'UPI',(NOW() - INTERVAL 3827 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (27,8,'Notebook 200 pages',1,45,32);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00028',1,2,145,0,0,0.0,145.0,'Cash',(NOW() - INTERVAL 3804 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (28,5,'Sunflower Oil 1L',1,145,120);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00029',6,2,420,10,0,0.0,410.0,'Cash',(NOW() - INTERVAL 3759 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (29,13,'Detergent Powder 1kg',1,105,85),(29,2,'Sugar 1kg',2,45,38),(29,8,'Notebook 200 pages',5,45,32);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00030',1,2,965,0,0,0.0,965.0,'Cash',(NOW() - INTERVAL 3731 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (30,6,'Wheat Atta 5kg',2,250,215),(30,2,'Sugar 1kg',5,45,38),(30,3,'Tea Powder 250g',2,120,95);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00031',3,2,700,0,5,35.0,735.0,'Cash',(NOW() - INTERVAL 3727 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (31,4,'Toor Dal 1kg',5,140,118);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00032',5,1,290,0,5,14.5,304.5,'Cash',(NOW() - INTERVAL 3538 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (32,12,'Toothpaste 150g',1,85,68),(32,18,'Dish Wash Liquid',1,85,68),(32,3,'Tea Powder 250g',1,120,95);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00033',2,2,525,0,0,0.0,525.0,'UPI',(NOW() - INTERVAL 3438 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (33,3,'Tea Powder 250g',2,120,95),(33,18,'Dish Wash Liquid',3,85,68),(33,9,'Blue Ball Pen',3,10,6);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00034',NULL,2,265,0,5,13.25,278.25,'Cash',(NOW() - INTERVAL 3371 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (34,14,'Biscuits Pack',3,10,8),(34,5,'Sunflower Oil 1L',1,145,120),(34,2,'Sugar 1kg',2,45,38);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00035',3,2,625,0,0,0.0,625.0,'UPI',(NOW() - INTERVAL 3325 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (35,17,'Cold Drink 750ml',2,40,30),(35,9,'Blue Ball Pen',5,10,6),(35,12,'Toothpaste 150g',3,85,68),(35,3,'Tea Powder 250g',2,120,95);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00036',2,1,520,0,0,0.0,520.0,'Card',(NOW() - INTERVAL 3207 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (36,8,'Notebook 200 pages',1,45,32),(36,3,'Tea Powder 250g',3,120,95),(36,1,'Basmati Rice 1kg',1,95,78),(36,16,'Water Bottle 1L',1,20,12);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00037',3,1,170,0,0,0.0,170.0,'Cash',(NOW() - INTERVAL 3199 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (37,12,'Toothpaste 150g',2,85,68);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00038',5,2,162,0,0,0.0,162.0,'Cash',(NOW() - INTERVAL 3125 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (38,4,'Toor Dal 1kg',1,140,118),(38,7,'Iodized Salt 1kg',1,22,16);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00039',5,1,219,0,5,10.95,229.95,'UPI',(NOW() - INTERVAL 3014 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (39,2,'Sugar 1kg',3,45,38),(39,7,'Iodized Salt 1kg',2,22,16),(39,16,'Water Bottle 1L',2,20,12);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00040',4,2,247,0,0,0.0,247.0,'UPI',(NOW() - INTERVAL 2887 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (40,8,'Notebook 200 pages',5,45,32),(40,7,'Iodized Salt 1kg',1,22,16);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00041',2,2,230,10,0,0.0,220.0,'Cash',(NOW() - INTERVAL 2767 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (41,4,'Toor Dal 1kg',1,140,118),(41,2,'Sugar 1kg',1,45,38),(41,8,'Notebook 200 pages',1,45,32);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00042',NULL,2,225,10,5,10.75,225.75,'Cash',(NOW() - INTERVAL 2652 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (42,16,'Water Bottle 1L',1,20,12),(42,8,'Notebook 200 pages',3,45,32),(42,15,'Instant Noodles',5,14,11);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00043',2,2,493,0,0,0.0,493.0,'Cash',(NOW() - INTERVAL 2578 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (43,15,'Instant Noodles',2,14,11),(43,10,'Pencil Box',2,60,42),(43,3,'Tea Powder 250g',1,120,95),(43,2,'Sugar 1kg',5,45,38);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00044',3,2,144,0,0,0.0,144.0,'Cash',(NOW() - INTERVAL 2436 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (44,17,'Cold Drink 750ml',2,40,30),(44,9,'Blue Ball Pen',1,10,6),(44,7,'Iodized Salt 1kg',2,22,16),(44,14,'Biscuits Pack',1,10,8);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00045',1,2,74,0,0,0.0,74.0,'Cash',(NOW() - INTERVAL 2390 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (45,15,'Instant Noodles',1,14,11),(45,16,'Water Bottle 1L',1,20,12),(45,17,'Cold Drink 750ml',1,40,30);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00046',NULL,1,325,0,5,16.25,341.25,'Card',(NOW() - INTERVAL 2362 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (46,16,'Water Bottle 1L',2,20,12),(46,1,'Basmati Rice 1kg',3,95,78);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00047',2,2,870,0,0,0.0,870.0,'Cash',(NOW() - INTERVAL 2270 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (47,17,'Cold Drink 750ml',3,40,30),(47,3,'Tea Powder 250g',2,120,95),(47,18,'Dish Wash Liquid',1,85,68),(47,12,'Toothpaste 150g',5,85,68);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00048',NULL,1,745,0,0,0.0,745.0,'Cash',(NOW() - INTERVAL 2191 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (48,5,'Sunflower Oil 1L',5,145,120),(48,14,'Biscuits Pack',2,10,8);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00049',4,1,170,0,0,0.0,170.0,'Cash',(NOW() - INTERVAL 1956 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (49,18,'Dish Wash Liquid',2,85,68);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00050',2,2,190,0,0,0.0,190.0,'Cash',(NOW() - INTERVAL 1857 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (50,12,'Toothpaste 150g',1,85,68),(50,11,'Bath Soap',3,35,26);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00051',6,2,620,5,5,30.75,645.75,'Cash',(NOW() - INTERVAL 1785 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (51,3,'Tea Powder 250g',1,120,95),(51,6,'Wheat Atta 5kg',1,250,215),(51,17,'Cold Drink 750ml',2,40,30),(51,18,'Dish Wash Liquid',2,85,68);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00052',5,2,90,0,5,4.5,94.5,'UPI',(NOW() - INTERVAL 1762 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (52,8,'Notebook 200 pages',2,45,32);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00053',1,1,84,0,0,0.0,84.0,'Cash',(NOW() - INTERVAL 1688 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (53,17,'Cold Drink 750ml',1,40,30),(53,7,'Iodized Salt 1kg',2,22,16);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00054',NULL,2,230,0,0,0.0,230.0,'UPI',(NOW() - INTERVAL 1664 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (54,2,'Sugar 1kg',2,45,38),(54,1,'Basmati Rice 1kg',1,95,78),(54,8,'Notebook 200 pages',1,45,32);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00055',2,2,303,0,5,15.15,318.15,'UPI',(NOW() - INTERVAL 1594 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (55,15,'Instant Noodles',2,14,11),(55,18,'Dish Wash Liquid',1,85,68),(55,1,'Basmati Rice 1kg',2,95,78);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00056',NULL,2,440,0,0,0.0,440.0,'Cash',(NOW() - INTERVAL 1424 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (56,2,'Sugar 1kg',1,45,38),(56,3,'Tea Powder 250g',3,120,95),(56,11,'Bath Soap',1,35,26);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00057',4,2,280,0,0,0.0,280.0,'Card',(NOW() - INTERVAL 1421 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (57,4,'Toor Dal 1kg',2,140,118);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00058',4,1,1010,0,0,0.0,1010.0,'UPI',(NOW() - INTERVAL 1420 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (58,5,'Sunflower Oil 1L',5,145,120),(58,13,'Detergent Powder 1kg',1,105,85),(58,12,'Toothpaste 150g',1,85,68),(58,1,'Basmati Rice 1kg',1,95,78);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00059',2,1,425,10,0,0.0,415.0,'UPI',(NOW() - INTERVAL 1309 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (59,2,'Sugar 1kg',1,45,38),(59,3,'Tea Powder 250g',2,120,95),(59,4,'Toor Dal 1kg',1,140,118);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00060',6,1,175,0,0,0.0,175.0,'Cash',(NOW() - INTERVAL 1258 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (60,11,'Bath Soap',5,35,26);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00061',NULL,2,129,0,0,0.0,129.0,'Card',(NOW() - INTERVAL 1094 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (61,15,'Instant Noodles',1,14,11),(61,9,'Blue Ball Pen',1,10,6),(61,13,'Detergent Powder 1kg',1,105,85);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00062',4,1,294,0,0,0.0,294.0,'Card',(NOW() - INTERVAL 1022 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (62,12,'Toothpaste 150g',2,85,68),(62,8,'Notebook 200 pages',2,45,32),(62,16,'Water Bottle 1L',1,20,12),(62,15,'Instant Noodles',1,14,11);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00063',4,2,150,0,5,7.5,157.5,'UPI',(NOW() - INTERVAL 1017 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (63,13,'Detergent Powder 1kg',1,105,85),(63,8,'Notebook 200 pages',1,45,32);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00064',NULL,1,130,0,5,6.5,136.5,'Cash',(NOW() - INTERVAL 1012 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (64,14,'Biscuits Pack',1,10,8),(64,3,'Tea Powder 250g',1,120,95);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00065',6,2,330,0,0,0.0,330.0,'UPI',(NOW() - INTERVAL 975 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (65,2,'Sugar 1kg',1,45,38),(65,1,'Basmati Rice 1kg',3,95,78);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00066',1,1,175,0,0,0.0,175.0,'UPI',(NOW() - INTERVAL 950 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (66,1,'Basmati Rice 1kg',1,95,78),(66,11,'Bath Soap',2,35,26),(66,14,'Biscuits Pack',1,10,8);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00067',3,2,580,0,5,29.0,609.0,'Cash',(NOW() - INTERVAL 868 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (67,10,'Pencil Box',1,60,42),(67,5,'Sunflower Oil 1L',2,145,120),(67,1,'Basmati Rice 1kg',1,95,78),(67,8,'Notebook 200 pages',3,45,32);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00068',4,2,335,0,0,0.0,335.0,'UPI',(NOW() - INTERVAL 827 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (68,12,'Toothpaste 150g',1,85,68),(68,6,'Wheat Atta 5kg',1,250,215);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00069',NULL,1,10,0,5,0.5,10.5,'Card',(NOW() - INTERVAL 758 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (69,9,'Blue Ball Pen',1,10,6);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00070',2,1,260,0,0,0.0,260.0,'UPI',(NOW() - INTERVAL 659 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (70,10,'Pencil Box',1,60,42),(70,17,'Cold Drink 750ml',5,40,30);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00071',6,2,200,10,0,0.0,190.0,'Card',(NOW() - INTERVAL 539 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (71,1,'Basmati Rice 1kg',1,95,78),(71,9,'Blue Ball Pen',2,10,6),(71,12,'Toothpaste 150g',1,85,68);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00072',5,1,411,0,0,0.0,411.0,'Cash',(NOW() - INTERVAL 515 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (72,10,'Pencil Box',2,60,42),(72,17,'Cold Drink 750ml',2,40,30),(72,7,'Iodized Salt 1kg',3,22,16),(72,5,'Sunflower Oil 1L',1,145,120);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00073',NULL,1,215,0,5,10.75,225.75,'Cash',(NOW() - INTERVAL 492 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (73,8,'Notebook 200 pages',2,45,32),(73,1,'Basmati Rice 1kg',1,95,78),(73,16,'Water Bottle 1L',1,20,12),(73,14,'Biscuits Pack',1,10,8);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00074',6,1,40,0,0,0.0,40.0,'UPI',(NOW() - INTERVAL 367 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (74,17,'Cold Drink 750ml',1,40,30);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00075',5,2,510,0,0,0.0,510.0,'UPI',(NOW() - INTERVAL 340 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (75,14,'Biscuits Pack',2,10,8),(75,17,'Cold Drink 750ml',5,40,30),(75,5,'Sunflower Oil 1L',2,145,120);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00076',NULL,2,310,10,0,0.0,300.0,'UPI',(NOW() - INTERVAL 321 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (76,18,'Dish Wash Liquid',2,85,68),(76,4,'Toor Dal 1kg',1,140,118);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00077',3,2,140,0,5,7.0,147.0,'UPI',(NOW() - INTERVAL 222 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (77,4,'Toor Dal 1kg',1,140,118);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00078',2,1,405,10,0,0.0,395.0,'UPI',(NOW() - INTERVAL 204 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (78,16,'Water Bottle 1L',2,20,12),(78,10,'Pencil Box',2,60,42),(78,17,'Cold Drink 750ml',5,40,30),(78,8,'Notebook 200 pages',1,45,32);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00079',2,2,615,0,0,0.0,615.0,'Cash',(NOW() - INTERVAL 202 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (79,9,'Blue Ball Pen',1,10,6),(79,16,'Water Bottle 1L',1,20,12),(79,6,'Wheat Atta 5kg',2,250,215),(79,18,'Dish Wash Liquid',1,85,68);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00080',4,2,201,5,0,0.0,196.0,'Cash',(NOW() - INTERVAL 131 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (80,7,'Iodized Salt 1kg',3,22,16),(80,8,'Notebook 200 pages',3,45,32);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00081',2,2,855,0,0,0.0,855.0,'Cash',(NOW() - INTERVAL 125 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (81,4,'Toor Dal 1kg',3,140,118),(81,5,'Sunflower Oil 1L',3,145,120);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00082',NULL,2,332,0,0,0.0,332.0,'Cash',(NOW() - INTERVAL 100 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (82,10,'Pencil Box',2,60,42),(82,15,'Instant Noodles',3,14,11),(82,18,'Dish Wash Liquid',1,85,68),(82,12,'Toothpaste 150g',1,85,68);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00083',4,2,135,0,0,0.0,135.0,'Cash',(NOW() - INTERVAL 78 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (83,8,'Notebook 200 pages',3,45,32);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00084',NULL,2,150,0,0,0.0,150.0,'Cash',(NOW() - INTERVAL 63 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (84,10,'Pencil Box',1,60,42),(84,8,'Notebook 200 pages',2,45,32);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00085',NULL,2,390,5,0,0.0,385.0,'UPI',(NOW() - INTERVAL 52 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (85,4,'Toor Dal 1kg',1,140,118),(85,6,'Wheat Atta 5kg',1,250,215);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00086',3,1,755,0,0,0.0,755.0,'Cash',(NOW() - INTERVAL 37 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (86,5,'Sunflower Oil 1L',5,145,120),(86,14,'Biscuits Pack',3,10,8);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00087',4,1,405,10,5,19.75,414.75,'Cash',(NOW() - INTERVAL 12 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (87,13,'Detergent Powder 1kg',1,105,85),(87,5,'Sunflower Oil 1L',2,145,120),(87,9,'Blue Ball Pen',1,10,6);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00088',1,1,720,0,0,0.0,720.0,'Cash',(NOW() - INTERVAL 9 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (88,12,'Toothpaste 150g',3,85,68),(88,11,'Bath Soap',5,35,26),(88,5,'Sunflower Oil 1L',2,145,120);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00089',2,2,454,0,0,0.0,454.0,'Cash',(NOW() - INTERVAL 8 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (89,15,'Instant Noodles',1,14,11),(89,13,'Detergent Powder 1kg',2,105,85),(89,10,'Pencil Box',1,60,42),(89,12,'Toothpaste 150g',2,85,68);
INSERT INTO bills (bill_no, customer_id, user_id, subtotal, discount, gst_percent, gst_amount, total, pay_mode, created_at) VALUES ('INV00090',4,2,1575,0,5,78.75,1653.75,'Cash',(NOW() - INTERVAL 4 HOUR));
INSERT INTO bill_items (bill_id, product_id, item_name, qty, price, cost_price) VALUES (90,4,'Toor Dal 1kg',5,140,118),(90,17,'Cold Drink 750ml',1,40,30),(90,5,'Sunflower Oil 1L',5,145,120),(90,7,'Iodized Salt 1kg',5,22,16);

-- opening stock entries
INSERT INTO stock_log (product_id, change_qty, reason, note, user_id) SELECT id, stock, 'Opening stock', 'Sample data', 1 FROM products WHERE stock > 0;
