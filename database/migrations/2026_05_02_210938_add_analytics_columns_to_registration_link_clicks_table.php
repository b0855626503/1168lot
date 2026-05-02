<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_link_clicks', function (Blueprint $table) {
            $table->string('classification_type', 32)->default('unknown')->after('referrer');
            $table->string('classification_reason', 191)->nullable()->after('classification_type');
            $table->unsignedTinyInteger('risk_score')->default(0)->after('classification_reason');

            $table->string('ip_hash', 64)->nullable()->after('risk_score');
            $table->string('visitor_id', 64)->nullable()->after('ip_hash');
            $table->string('session_id', 128)->nullable()->after('visitor_id');

            $table->string('method', 16)->nullable()->after('session_id');
            $table->text('landing_url')->nullable()->after('method');
            $table->string('referrer_domain', 191)->nullable()->after('landing_url');

            $table->string('utm_source', 191)->nullable()->after('referrer_domain');
            $table->string('utm_medium', 191)->nullable()->after('utm_source');
            $table->string('utm_campaign', 191)->nullable()->after('utm_medium');
            $table->string('utm_content', 191)->nullable()->after('utm_campaign');
            $table->string('utm_term', 191)->nullable()->after('utm_content');

            $table->string('device_type', 64)->nullable()->after('utm_term');
            $table->string('browser_name', 64)->nullable()->after('device_type');
            $table->string('browser_version', 64)->nullable()->after('browser_name');
            $table->string('os_name', 64)->nullable()->after('browser_version');
            $table->string('os_version', 64)->nullable()->after('os_name');

            $table->boolean('is_bot')->default(false)->after('os_version');
            $table->boolean('is_preview_bot')->default(false)->after('is_bot');
            $table->boolean('is_suspicious')->default(false)->after('is_preview_bot');

            $table->timestamp('client_confirmed_at')->nullable()->after('is_suspicious');
            $table->timestamp('submitted_at')->nullable()->after('client_confirmed_at');
            $table->string('converted_member_id', 64)->nullable()->after('submitted_at');
            $table->timestamp('converted_at')->nullable()->after('converted_member_id');
            $table->string('register_type', 32)->nullable()->after('converted_at');

            $table->json('metadata_json')->nullable()->after('register_type');

            $table->index(['registration_link_id', 'created_at'], 'rlc_link_created_at');
            $table->index(['classification_type', 'created_at'], 'rlc_classification_created_at');
            $table->index(['ip_hash', 'created_at'], 'rlc_ip_hash_created_at');
            $table->index(['visitor_id', 'created_at'], 'rlc_visitor_created_at');
            $table->index('converted_member_id', 'rlc_converted_member');
            $table->index(['register_type', 'created_at'], 'rlc_register_type_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('registration_link_clicks', function (Blueprint $table) {
            $table->dropIndex('rlc_link_created_at');
            $table->dropIndex('rlc_classification_created_at');
            $table->dropIndex('rlc_ip_hash_created_at');
            $table->dropIndex('rlc_visitor_created_at');
            $table->dropIndex('rlc_converted_member');
            $table->dropIndex('rlc_register_type_created_at');

            $table->dropColumn([
                'classification_type',
                'classification_reason',
                'risk_score',
                'ip_hash',
                'visitor_id',
                'session_id',
                'method',
                'landing_url',
                'referrer_domain',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_content',
                'utm_term',
                'device_type',
                'browser_name',
                'browser_version',
                'os_name',
                'os_version',
                'is_bot',
                'is_preview_bot',
                'is_suspicious',
                'client_confirmed_at',
                'submitted_at',
                'converted_member_id',
                'converted_at',
                'register_type',
                'metadata_json',
            ]);
        });
    }
};
