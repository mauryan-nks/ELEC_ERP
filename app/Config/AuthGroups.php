<?php

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    public string $defaultGroup = 'sales';

    public array $groups = [
        'owner'      => ['title' => 'Owner', 'description' => 'Full shop access'],
        'admin'      => ['title' => 'Admin', 'description' => 'Administrative access'],
        'manager'    => ['title' => 'Manager', 'description' => 'Sales, purchase, stock and reports'],
        'sales'      => ['title' => 'Sales', 'description' => 'Customers, invoices and sales'],
        'inventory'  => ['title' => 'Inventory', 'description' => 'Products, purchases and stock'],
        'accountant' => ['title' => 'Accountant', 'description' => 'Invoices, payments, expenses and reports'],
    ];

    public array $permissions = [
        'dashboard.view'    => 'View dashboard',
        'products.view'     => 'View products',
        'products.create'   => 'Create/edit product masters',
        'purchases.view'    => 'View purchases',
        'purchases.create'  => 'Create purchases and receive stock',
        'sales.view'        => 'View sales and invoices',
        'sales.create'      => 'Create sales and invoices',
        'customers.view'    => 'View customers',
        'customers.create'  => 'Create/edit customers',
        'expenses.manage'   => 'Manage expenses',
        'borrow.manage'     => 'Manage borrowed stock',
        'reports.view'      => 'View reports and profit',
        'whatsapp.manage'   => 'Connect WhatsApp and send reminders',
        'users.manage'      => 'Manage users and permissions',
        'settings.manage'   => 'Manage shop settings',
    ];

    public array $matrix = [
        'owner' => [
            'dashboard.view','products.view','products.create','purchases.view','purchases.create',
            'sales.view','sales.create','customers.view','customers.create','expenses.manage',
            'borrow.manage','reports.view','whatsapp.manage','users.manage','settings.manage',
        ],
        'admin' => [
            'dashboard.view','products.view','products.create','purchases.view','purchases.create',
            'sales.view','sales.create','customers.view','customers.create','expenses.manage',
            'borrow.manage','reports.view','whatsapp.manage','users.manage','settings.manage',
        ],
        'manager' => [
            'dashboard.view','products.view','products.create','purchases.view','purchases.create',
            'sales.view','sales.create','customers.view','customers.create','expenses.manage',
            'borrow.manage','reports.view','whatsapp.manage',
        ],
        'sales' => ['dashboard.view','products.view','sales.view','sales.create','customers.view','customers.create'],
        'inventory' => ['dashboard.view','products.view','products.create','purchases.view','purchases.create','borrow.manage'],
        'accountant' => ['dashboard.view','purchases.view','sales.view','customers.view','expenses.manage','reports.view'],
    ];
}
