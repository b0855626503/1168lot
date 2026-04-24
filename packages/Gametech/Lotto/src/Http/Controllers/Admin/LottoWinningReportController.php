<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Core\Models\Log as CoreLog;
use Gametech\Lotto\Exports\LottoWinningReportExport;
use Gametech\Lotto\Http\Requests\Admin\WinningReportBetsRequest;
use Gametech\Lotto\Http\Requests\Admin\WinningReportExportRequest;
use Gametech\Lotto\Http\Requests\Admin\WinningReportSummaryRequest;
use Gametech\Lotto\Http\Requests\Admin\WinningReportUsersRequest;
use Gametech\Lotto\Jobs\LottoWinningReportExportJob;
use Gametech\Lotto\Services\WinningReport\WinningReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

class LottoWinningReportController extends AppBaseController
{
    private const EXPORT_SYNC_THRESHOLD = 1000;

    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(Request $request)
    {
        $this->assertCanView();

        $lotteryTypeOptions = $this->resolveFilterOptions('lottery_type');
        $marketOptions = $this->resolveFilterOptions('market');

        return view($this->_config['view'], [
            'lotteryTypeOptions' => $lotteryTypeOptions,
            'marketOptions' => $marketOptions,
            'initialRoundId' => $this->normalizePositiveInt($request->query('round_id')),
            'initialDate' => (string) ($request->query('date') ?: $this->latestReportDate() ?: now()->toDateString()),
            'hasMaterializedReportData' => DB::table('settlement_batches')->exists(),
        ]);
    }

    private function latestReportDate(): ?string
    {
        if (! DB::getSchemaBuilder()->hasColumn('settlement_batches', 'draw_date')) {
            return null;
        }

        $date = DB::table('settlement_batches')
            ->whereNotNull('draw_date')
            ->orderByDesc('draw_date')
            ->value('draw_date');

        return is_string($date) && $date !== '' ? $date : null;
    }

    /**
     * @return array<int, string>
     */
    private function resolveFilterOptions(string $column): array
    {
        $fromBatches = DB::table('settlement_batches')
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->pluck($column)
            ->filter(static fn ($value): bool => is_string($value) && $value !== '');

        $fromWinnings = DB::table('lotto_winnings')
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->pluck($column)
            ->filter(static fn ($value): bool => is_string($value) && $value !== '');

        return $fromBatches
            ->merge($fromWinnings)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function summary(WinningReportSummaryRequest $request, WinningReportService $service): JsonResponse
    {
        $this->assertCanView();

        try {
            $filters = [
                'draw_id' => $this->normalizePositiveInt($request->query('round_id')),
                'date' => $request->query('date'),
                'lottery_type' => $request->query('lottery_type'),
                'market' => $request->query('market'),
            ];

            return response()->json($service->summary($filters));
        } catch (RuntimeException $exception) {
            return $this->mapRuntimeException($exception);
        } catch (Throwable $exception) {
            return response()->json(['message' => 'REPORT_FAILED'], 500);
        }
    }

    public function users(WinningReportUsersRequest $request, WinningReportService $service): JsonResponse
    {
        $this->assertCanView();

        $drawId = (int) $request->validated('round_id');

        try {
            $filters = [
                'user_id' => $this->normalizePositiveInt($request->query('user_id')),
            ];
            $perPage = (int) ($request->query('per_page') ?? 20);

            $paginator = $service->users($drawId, $filters, $perPage);

            return response()->json([
                'round_id' => $drawId,
                'data' => collect($paginator->items())->map(static function ($row): array {
                    return [
                        'user_id' => (int) $row->user_id,
                        'username' => (string) ($row->username ?? ''),
                        'total_stake' => round((float) $row->total_stake, 2),
                        'total_payout' => $row->total_payout === null ? null : round((float) $row->total_payout, 2),
                        'net_by_user' => $row->net_by_user === null ? null : round((float) $row->net_by_user, 2),
                        'winning_bet_count' => (int) $row->winning_bet_count,
                        'winning_numbers' => (string) ($row->winning_numbers ?? ''),
                        'credited_status' => (string) $row->credited_status,
                    ];
                })->values()->all(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        } catch (RuntimeException $exception) {
            return $this->mapRuntimeException($exception);
        } catch (Throwable $exception) {
            return response()->json(['message' => 'REPORT_FAILED'], 500);
        }
    }

    public function bets(WinningReportBetsRequest $request, WinningReportService $service): JsonResponse
    {
        $this->assertCanView();

        $drawId = (int) $request->validated('round_id');

        try {
            $filters = [
                'user_id' => $this->normalizePositiveInt($request->query('user_id')),
                'bet_type' => $request->query('bet_type'),
                'number' => $request->query('number'),
                'status' => $request->query('status'),
            ];
            $perPage = (int) ($request->query('per_page') ?? 20);

            $paginator = $service->bets($drawId, $filters, $perPage);

            return response()->json([
                'round_id' => $drawId,
                'data' => collect($paginator->items())->map(static function ($row): array {
                    $isPending = (string) $row->status === 'pending';

                    return [
                        'ticket_no' => (string) ($row->ticket_no ?? ''),
                        'bet_type' => (string) $row->bet_type,
                        'number' => (string) $row->number,
                        'stake' => round((float) $row->stake, 2),
                        'odds' => round((float) $row->odds, 4),
                        'payout' => $isPending ? null : round((float) ($row->payout ?? 0), 2),
                        'net_profit' => $isPending ? null : round((float) ($row->net_profit ?? 0), 2),
                        'result_number' => (string) ($row->result_number ?? ''),
                        'matched_rule' => (string) ($row->matched_rule ?? ''),
                        'status' => (string) $row->status,
                        'settlement_batch_id' => (int) $row->settlement_batch_id,
                        'settled_at' => $row->settled_at,
                        'credited_at' => $row->credited_at,
                    ];
                })->values()->all(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        } catch (RuntimeException $exception) {
            return $this->mapRuntimeException($exception);
        } catch (Throwable $exception) {
            return response()->json(['message' => 'REPORT_FAILED'], 500);
        }
    }

    public function export(WinningReportExportRequest $request, WinningReportService $service)
    {
        $this->assertCanExport();

        $drawId = (int) $request->validated('round_id');
        $level = (string) $request->validated('level');
        $format = (string) $request->validated('format');

        try {
            $exportData = $service->exportRows($drawId, [
                'user_id' => $this->normalizePositiveInt($request->query('user_id')),
                'bet_type' => $request->query('bet_type'),
                'number' => $request->query('number'),
                'status' => $request->query('status'),
                'level' => $level,
            ]);
        } catch (RuntimeException $exception) {
            return $this->mapRuntimeException($exception);
        } catch (Throwable $exception) {
            return response()->json(['message' => 'EXPORT_FAILED'], 500);
        }

        $this->writeAuditLog('EXPORT', [
            'round_id' => $drawId,
            'level' => $level,
            'format' => $format,
            'count' => (int) $exportData['count'],
            'exported_at' => now()->toDateTimeString(),
        ]);

        $isLargeDataset = (int) $exportData['count'] > self::EXPORT_SYNC_THRESHOLD;

        if ($format === 'xlsx' || $isLargeDataset) {
            if ($this->isQueueUsable()) {
                LottoWinningReportExportJob::dispatch(
                    $exportData['rows'],
                    $drawId,
                    $level,
                    $format,
                    (int) (auth()->id() ?? 0),
                    (string) (auth()->user()->user_name ?? 'SYSTEM')
                );

                return response()->json([
                    'queued' => true,
                    'message' => 'EXPORT_QUEUED',
                    'round_id' => $drawId,
                    'level' => $level,
                    'format' => $format,
                ]);
            }

            // TODO: Queue/storage readiness should be validated by ops; fallback keeps export available.
            Log::warning('lotto.winning_report.export.queue_not_ready_fallback_csv', [
                'round_id' => $drawId,
                'level' => $level,
                'format' => $format,
                'count' => (int) $exportData['count'],
            ]);

            return $this->downloadCsv($drawId, $level, $exportData['rows']);
        }

        return $this->downloadCsv($drawId, $level, $exportData['rows']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function downloadCsv(int $drawId, string $level, array $rows)
    {
        $filename = sprintf('winning_report_round_%d_%s_%s.csv', $drawId, $level, now()->format('Ymd_His'));

        return Excel::download(new LottoWinningReportExport($rows), $filename, ExcelFormat::CSV);
    }

    private function assertCanView(): void
    {
        if (! bouncer()->hasPermission('lotto_reports.winning_report')) {
            abort(403, 'Forbidden');
        }
    }

    private function assertCanExport(): void
    {
        if (! bouncer()->hasPermission('lotto_reports.winning_report.export')) {
            abort(403, 'Forbidden');
        }
    }

    private function normalizePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $normalized === false ? null : (int) $normalized;
    }

    private function isQueueUsable(): bool
    {
        $defaultConnection = (string) config('queue.default', 'sync');

        return $defaultConnection !== '' && $defaultConnection !== 'sync';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeAuditLog(string $mode, array $payload): void
    {
        $log = new CoreLog;
        $log->emp_code = (int) (auth()->id() ?? 0);
        $log->mode = $mode;
        $log->menu = 'lotto_winning_report_export';
        $log->record = (string) ($payload['round_id'] ?? '');
        $log->item_before = json_encode([], JSON_UNESCAPED_UNICODE);
        $log->item = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $log->ip = request()->ip();
        $log->user_create = (string) (auth()->user()->user_name ?? 'SYSTEM');
        $log->save();
    }

    private function mapRuntimeException(RuntimeException $exception): JsonResponse
    {
        return match ($exception->getMessage()) {
            'ROUND_NOT_FOUND' => response()->json(['message' => 'ROUND_NOT_FOUND'], 404),
            'SETTLEMENT_PENDING', 'SETTLEMENT_NOT_FINALIZED' => response()->json(['message' => 'SETTLEMENT_PENDING'], 409),
            default => response()->json(['message' => 'REPORT_FAILED'], 500),
        };
    }
}
