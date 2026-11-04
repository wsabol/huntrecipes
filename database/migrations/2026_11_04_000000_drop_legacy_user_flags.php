<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    // Legacy flags: chef_app_pending is superseded by ChefApplication status, and
    // email verification is enforced from the Firebase token at sign-in.
    private const COLUMNS = ['chef_app_pending', 'is_email_verified'];

    public function up(): void {
        foreach (self::COLUMNS as $column) {
            if (Schema::hasColumn('User', $column)) {
                Schema::table('User', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }

    public function down(): void {
        Schema::table('User', function (Blueprint $table): void {
            if (!Schema::hasColumn('User', 'chef_app_pending')) {
                $table->boolean('chef_app_pending')->default(false);
            }
            if (!Schema::hasColumn('User', 'is_email_verified')) {
                $table->boolean('is_email_verified')->default(false);
            }
        });
    }
};
