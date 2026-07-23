<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NO-OP: Sprint 3 migration 2026_07_23_000001 already dropped
        // participation_id, event_id, and backfill_batch_id from
        // legacy_peserta_mappings entirely. This migration's purpose
        // (making those columns nullable) is obsolete.
    }

    public function down(): void
    {
        // NO-OP: matched to up(). Columns no longer exist.
    }
};
