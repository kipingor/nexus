<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add multi-tenancy and profile columns to the users table.
 *
 * We add tenant_id (string, FK to tenants.id) and phone.
 * We also add is_active to support suspending individual users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('tenant_id')->nullable()->after('id');
            $table->string('phone', 30)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('phone');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn(['tenant_id', 'phone', 'is_active']);
        });
    }
};
