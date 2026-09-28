<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Throwable;

final class ApiStatusController extends Controller {
    public function __invoke() {
        $isDbConnected = true;

        try {
            DB::connection()->getPdo();
        } catch (Throwable) {
            $isDbConnected = false;
        }

        return response()->json([
            'message' => 'System status',
            'data' => [
                'php_version' => PHP_VERSION,
                'is_production' => app()->isProduction(),
                'is_db_connected' => $isDbConnected,
            ],
        ]);
    }
}
