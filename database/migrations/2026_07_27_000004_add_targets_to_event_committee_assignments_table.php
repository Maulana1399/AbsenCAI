<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_committee_assignments', function (Blueprint $table) {
            $table->foreignId('activity_group_id')->nullable()->after('event_role_id')->constrained('activity_groups')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->after('activity_group_id')->constrained('activities')->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->after('activity_id')->constrained('venues')->nullOnDelete();

            $table->index('activity_group_id');
            $table->index('activity_id');
            $table->index('venue_id');
        });
    }

    public function down(): void
    {
        Schema::table('event_committee_assignments', function (Blueprint $table) {
            $table->dropForeign(['activity_group_id']);
            $table->dropForeign(['activity_id']);
            $table->dropForeign(['venue_id']);
            $table->dropIndex(['activity_group_id']);
            $table->dropIndex(['activity_id']);
            $table->dropIndex(['venue_id']);
            $table->dropColumn(['activity_group_id', 'activity_id', 'venue_id']);
        });
    }
};
