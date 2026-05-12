<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 120)->nullable()->after('name');
            $table->string('last_name', 120)->nullable()->after('first_name');
        });

        foreach (DB::table('users')->select('id', 'name')->cursor() as $row) {
            $name = trim((string) $row->name);
            if ($name === '') {
                $first = 'User';
                $last = '';
            } else {
                $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $first = (string) ($parts[0] ?? 'User');
                $last = trim(implode(' ', array_slice($parts, 1)));
            }

            DB::table('users')->where('id', $row->id)->update([
                'first_name' => $first,
                'last_name' => $last,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
