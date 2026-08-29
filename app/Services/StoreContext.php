<?php

namespace App\Services;

use RuntimeException;

class StoreContext
{
    public static function id(): int
    {
        $id = (int) (session('store_id') ?: env('shop.defaultStoreId', 1));
        if ($id < 1) {
            throw new RuntimeException('No active store selected.');
        }
        return $id;
    }
}
