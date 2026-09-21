Chef Janno's Chill & Grill - XAMPP setup
1. Copy the chef_jannos folder to C:\xampp\htdocs\
2. Start Apache + MySQL. New install: import database.sql in phpMyAdmin.
   Existing install: just load any page - the database upgrades automatically.
3. Open http://localhost/chef_jannos/
   Logins: 
   admin@chefjannos.com/admin123 
   kitchen@chefjannos.com/kitchen123 
   cashier@chefjannos.com/cashier123
   julia@customer.com/julia123
DB settings: config.php line 4 (port 3307 - change to 3306 if needed).
