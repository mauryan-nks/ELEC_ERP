<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
service('auth')->routes($routes);

$routes->get('/', 'Dashboard::index', ['filter' => 'session']);

$routes->group('', ['filter' => 'session'], static function (RouteCollection $routes): void {
    $routes->get('dashboard', 'Dashboard::index');

    $routes->get('products', 'Products::index', ['filter' => 'permission:products.view']);
    $routes->post('products', 'Products::store', ['filter' => 'permission:products.create']);
    $routes->post('products/(:num)', 'Products::update/$1', ['filter' => 'permission:products.create']);
    $routes->post('products/(:num)/status', 'Products::status/$1', ['filter' => 'permission:products.create']);
    $routes->post('products/quick-master', 'Products::quickMaster', ['filter' => 'permission:products.create']);

    $routes->get('customers', 'Customers::index', ['filter' => 'permission:customers.view']);
    $routes->post('customers', 'Customers::store', ['filter' => 'permission:customers.create']);
    $routes->post('customers/(:num)', 'Customers::update/$1', ['filter' => 'permission:customers.create']);
    $routes->post('customers/quick', 'Customers::quickCreate', ['filter' => 'permission:customers.create']);

    $routes->get('suppliers', 'Suppliers::index', ['filter' => 'permission:purchases.create']);
    $routes->post('suppliers', 'Suppliers::store', ['filter' => 'permission:purchases.create']);
    $routes->post('suppliers/(:num)', 'Suppliers::update/$1', ['filter' => 'permission:purchases.create']);
    $routes->post('suppliers/quick', 'Suppliers::quickCreate', ['filter' => 'permission:purchases.create']);

    $routes->get('purchases', 'Purchases::index', ['filter' => 'permission:purchases.view']);
    $routes->get('purchases/new', 'Purchases::create', ['filter' => 'permission:purchases.create']);
    $routes->post('purchases', 'Purchases::store', ['filter' => 'permission:purchases.create']);
    $routes->get('purchases/(:num)', 'Purchases::show/$1', ['filter' => 'permission:purchases.view']);
    $routes->post('purchases/(:num)/payments', 'Purchases::addPayment/$1', ['filter' => 'permission:purchases.create']);

    $routes->get('sales', 'Sales::index', ['filter' => 'permission:sales.view']);
    $routes->get('sales/new', 'Sales::create', ['filter' => 'permission:sales.create']);
    $routes->post('sales', 'Sales::store', ['filter' => 'permission:sales.create']);
    $routes->get('sales/(:num)/invoice', 'Sales::invoice/$1', ['filter' => 'permission:sales.view']);
    $routes->post('sales/(:num)/payments', 'Sales::addPayment/$1', ['filter' => 'permission:sales.view']);
    $routes->get('inventory/available/(:num)', 'Sales::availableUnits/$1', ['filter' => 'permission:sales.create']);

    $routes->get('inventory', 'Inventory::index', ['filter' => 'permission:products.view']);
    $routes->post('inventory/units/(:num)', 'Inventory::updateUnit/$1', ['filter' => 'permission:products.create']);
    $routes->post('inventory/adjust', 'Inventory::adjust', ['filter' => 'permission:products.create']);

    $routes->get('expenses', 'Expenses::index', ['filter' => 'permission:expenses.manage']);
    $routes->post('expenses', 'Expenses::store', ['filter' => 'permission:expenses.manage']);
    $routes->post('expenses/(:num)', 'Expenses::update/$1', ['filter' => 'permission:expenses.manage']);

    $routes->get('borrowed-stock', 'BorrowedStock::index', ['filter' => 'permission:borrow.manage']);
    $routes->post('borrowed-stock/(:num)', 'BorrowedStock::update/$1', ['filter' => 'permission:borrow.manage']);
    $routes->get('reports', 'Reports::index', ['filter' => 'permission:reports.view']);

    $routes->get('users', 'Users::index', ['filter' => 'permission:users.manage']);
    $routes->post('users', 'Users::store', ['filter' => 'permission:users.manage']);
    $routes->post('users/(:num)', 'Users::update/$1', ['filter' => 'permission:users.manage']);
    $routes->post('users/(:num)/status', 'Users::status/$1', ['filter' => 'permission:users.manage']);

    $routes->get('settings', 'Settings::index', ['filter' => 'permission:settings.manage']);
    $routes->post('settings', 'Settings::update', ['filter' => 'permission:settings.manage']);

    $routes->get('whatsapp', 'Whatsapp::index', ['filter' => 'permission:whatsapp.manage']);
    $routes->get('whatsapp/status', 'Whatsapp::status', ['filter' => 'permission:whatsapp.manage']);
    $routes->get('whatsapp/qr', 'Whatsapp::qr', ['filter' => 'permission:whatsapp.manage']);
    $routes->post('whatsapp/send-test', 'Whatsapp::sendTest', ['filter' => 'permission:whatsapp.manage']);
});
