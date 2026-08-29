<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Compatibility migration retained because early builds already recorded this
 * migration version. The corrected single-shop schema is now defined in
 * CreateShopCore and legacy installations are converted by migration 000003.
 */
class FixShopSchema extends Migration
{
    public function up() {}
    public function down() {}
}
