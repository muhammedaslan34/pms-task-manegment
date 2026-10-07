<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track when an access token last authenticated a request to the HTTP MCP endpoint
     * (set by App\Http\Middleware\TouchMcpAccessToken, read by App\Services\McpTokenService).
     */
    public function up(): void
    {
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->dateTime('last_used_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->dropColumn('last_used_at');
        });
    }
};
