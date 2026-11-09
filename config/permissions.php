<?php
return [
    'admin' => ['*'],
    'sales' => ['dashboard','orders.view','orders.confirm','orders.complete','orders.cancel','customers.view'],
    'warehouse' => ['dashboard','orders.view','orders.prepare','orders.ship','inventory.view','inventory.manage'],
    'accountant' => ['dashboard','orders.view','payments.manage','invoices.manage','reports.view'],
    'support' => ['dashboard','orders.view','customers.view','support.manage'],
];
