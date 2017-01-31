<?php

return [
    // Database
    'database' => [
        'engine' => 'mysql',
        'host' => 'localhost',
        'database' => 'tdphp2017',
        'user' => 'root',
        'password' => ''
    ],

    // Administrator auth
    //'admin' => ['admin', 'password']
    'admin' => [['admin', 'password'],['admin1', 'password1'],['admin2', 'password2']]

];
