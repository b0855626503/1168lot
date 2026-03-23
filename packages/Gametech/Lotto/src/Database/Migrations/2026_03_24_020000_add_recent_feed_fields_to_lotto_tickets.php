<?php

use Gametech\Lotto\Enums\BetType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lotto_tickets')) {
            return;
        }

        Schema::table('lotto_tickets', function (Blueprint $table): void {
            if (!Schema::hasColumn('lotto_tickets', 'bet_type_summary')) {
                $table->string('bet_type_summary', 255)
                    ->nullable()
                    ->after('status');
            }
        });

        if (!$this->hasIndex('lotto_tickets', 'idx_lotto_tickets_recent_feed')) {
            Schema::table('lotto_tickets', function (Blueprint $table): void {
                $table->index(['created_at', 'id'], 'idx_lotto_tickets_recent_feed');
            });
        }

        $labelMap = [];
        foreach (BetType::all() as $type) {
            $labelMap[$type] = BetType::label($type);
        }

        DB::table('lotto_tickets')
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(500, function ($tickets) use ($labelMap): void {
                $ticketIds = collect($tickets)->pluck('id')->map(static fn ($id) => (int) $id)->all();
                if (empty($ticketIds)) {
                    return;
                }

                $rows = DB::table('lotto_ticket_items')
                    ->select(['ticket_id', 'bet_type'])
                    ->whereIn('ticket_id', $ticketIds)
                    ->get()
                    ->groupBy('ticket_id');

                foreach ($ticketIds as $ticketId) {
                    $types = collect($rows->get($ticketId, []))
                        ->pluck('bet_type')
                        ->filter()
                        ->map(static fn ($type) => (string) $type)
                        ->unique()
                        ->values();

                    if ($types->isEmpty()) {
                        continue;
                    }

                    $summary = $types
                        ->map(static fn ($type) => $labelMap[$type] ?? $type)
                        ->implode(', ');

                    DB::table('lotto_tickets')
                        ->where('id', $ticketId)
                        ->update([
                            'bet_type_summary' => mb_substr($summary, 0, 255),
                        ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        if (!Schema::hasTable('lotto_tickets')) {
            return;
        }

        Schema::table('lotto_tickets', function (Blueprint $table): void {
            if (Schema::hasColumn('lotto_tickets', 'bet_type_summary')) {
                $table->dropColumn('bet_type_summary');
            }
        });

        if ($this->hasIndex('lotto_tickets', 'idx_lotto_tickets_recent_feed')) {
            Schema::table('lotto_tickets', function (Blueprint $table): void {
                $table->dropIndex('idx_lotto_tickets_recent_feed');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();
        if (!$database) {
            return false;
        }

        $row = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->first();

        return $row !== null;
    }
};
