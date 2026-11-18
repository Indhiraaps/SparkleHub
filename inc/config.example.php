<?php
/**
 * Database and shop settings.
 *
 * Copy this file to inc/config.php and put your own MySQL details in it.
 * inc/config.php is listed in .gitignore, so your password never reaches GitHub.
 */
return [
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'cracker_shop',
    'db_user' => 'root',
    'db_pass' => '',

    // Flat packing charge added to every order (in rupees)
    'packing_cost' => 20.00,
];
