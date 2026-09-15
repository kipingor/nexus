<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add business-domain columns to the tenants table created by stancl/tenancy.
 *
 * The base migration creates: id, data (jsonb), created_at, updated_at.
 * We store frequently-queried fields as proper columns instead of in the jsonb blob.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('name')->after('id');
            $table->string('slug')->unique()->after('name');
            $table->string('status', 20)->default('trial')->after('slug'); // trial|active|suspended
            $table->string('plan', 50)->nullable()->after('status');
            $table->timestamp('trial_ends_at')->nullable()->after('plan');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['name', 'slug', 'status', 'plan', 'trial_ends_at']);
        });
    }
};
