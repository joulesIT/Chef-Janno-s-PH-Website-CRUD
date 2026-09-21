<?php
require '../config.php';
need_role('cashier', 'kitchen');
head('Account Settings', true);
profile_form();
foot();
