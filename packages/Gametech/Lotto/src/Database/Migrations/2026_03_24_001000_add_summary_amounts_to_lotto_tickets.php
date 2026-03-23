<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotto_tickets', function (Blueprint $table): void {
            if (! Schema::hasColumn('lotto_tickets', 'total_bet_amount')) {
                $table->decimal('total_bet_amount', 12, 2)
                    ->default(0)
                    ->after('total_amount');
            }

            if (! Schema::hasColumn('lotto_tickets', 'total_discount_amount')) {
                $table->decimal('total_discount_amount', 12, 2)
                    ->default(0)
                    ->after('total_bet_amount');
            }

            if (! Schema::hasColumn('lotto_tickets', 'total_net_amount')) {
                $table->decimal('total_net_amount', 12, 2)
                    ->default(0)
                    ->after('total_discount_amount');
            }

            if (! Schema::hasColumn('lotto_tickets', 'total_win_amount')) {
                $table->decimal('total_win_amount', 12, 2)
                    ->default(0)
                    ->after('total_net_amount');
            }
        });

        DB::table('lotto_tickets')
            ->select(['id', 'total_amount'])
            ->orderBy('id')
            ->chunkById(500, function ($tickets): void {
                $ticketIds = collect($tickets)->pluck('id')->map(static fn ($id) => (int) $id)->all();

                $aggregates = DB::table('lotto_ticket_items')
                    ->select([
                        'ticket_id',
                        DB::raw('COALESCE(SUM(amount), 0) as total_bet_amount'),
                        DB::raw('COALESCE(SUM(discount_amount_at_time), 0) as total_discount_amount'),
                        DB::raw('COALESCE(SUM(payable_amount_at_time), 0) as total_net_amount'),
                        DB::raw('COALESCE(SUM(win_amount), 0) as total_win_amount'),
                    ])
                    ->whereIn('ticket_id', $ticketIds)
                    ->groupBy('ticket_id')
                    ->get()
                    ->keyBy('ticket_id');

                foreach ($tickets as $ticket) {
                    $summary = $aggregates->get($ticket->id);
                    $totalNetAmount = $summary ? (float) $summary->total_net_amount : (float) $ticket->total_amount;

                    DB::table('lotto_tickets')
                        ->where('id', (int) $ticket->id)
                        ->update([
                            'total_bet_amount' => $summary ? (float) $summary->total_bet_amount : (float) $ticket->total_amount,
                            'total_discount_amount' => $summary ? (float) $summary->total_discount_amount : 0.0,
                            'total_net_amount' => $totalNetAmount,
                            'total_win_amount' => $summary ? (float) $summary->total_win_amount : 0.0,
                            'total_amount' => $totalNetAmount,
                        ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::table('lotto_tickets', function (Blueprint $table): void {
            if (Schema::hasColumn('lotto_tickets', 'total_win_amount')) {
                $table->dropColumn('total_win_amount');
            }

            if (Schema::hasColumn('lotto_tickets', 'total_net_amount')) {
                $table->dropColumn('total_net_amount');
            }

            if (Schema::hasColumn('lotto_tickets', 'total_discount_amount')) {
                $table->dropColumn('total_discount_amount');
            }

            if (Schema::hasColumn('lotto_tickets', 'total_bet_amount')) {
                $table->dropColumn('total_bet_amount');
            }
        });
    }
};

