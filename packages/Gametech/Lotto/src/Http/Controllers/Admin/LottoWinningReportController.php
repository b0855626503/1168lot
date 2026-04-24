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
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Services\WinningReport\WinningReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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

        $initialDate = (string) ($request->query('date') ?: now()->toDateString());
        $initialLotteryType = (string) ($request->query('lottery_type') ?: '');
        $initialMarket = (string) ($request->query('market') ?: '');
        $filterPayload = $this->resolveFilterOptionsByDate($initialDate, $initialLotteryType);

        return view($this->_config['view'], [
            'lotteryTypeOptions' => $filterPayload['lottery_type_options'],
            'marketOptions' => $filterPayload['market_options'],
            'initialDate' => $initialDate,
            'initialLotteryType' => $initialLotteryType,
            'initialMarket' => $initialMarket,
            'hasMaterializedReportData' => DB::table('settlement_batches')->exists(),
        ]);
    }

    public function filterOptions(Request $request): JsonResponse
    {
        $this->assertCanView();

        $date = is_string($request->query('date')) && $request->query('date') !== ''
            ? (string) $request->query('date')
            : now()->toDateString();
        $lotteryType = is_string($request->query('lottery_type')) ? (string) $request->query('lottery_type') : '';
        $payload = $this->resolveFilterOptionsByDate($date, $lotteryType);

        return response()->json([
            'lottery_type_options' => $payload['lottery_type_options'],
            'market_options' => $payload['market_options'],
        ]);
    }

    /**
     * @return array{lottery_type_options: array<int, string>, market_options: array<int, array<string, mixed>>}
     */
    private function resolveFilterOptionsByDate(string $date, string $lotteryType = ''): array
    {
        $baseQuery = DB::table('lotto_winnings as w')
            ->join('settlement_batches as b', 'b.id', '=', 'w.settlement_batch_id');

        if (Schema::hasColumn('settlement_batches', 'draw_date')) {
            $baseQuery->whereDate('b.draw_date', $date);
        } else {
            $baseQuery->whereDate('b.started_at', $date);
        }

        $lotteryTypeOptions = (clone $baseQuery)
            ->select('w.lottery_type')
            ->whereNotNull('w.lottery_type')
            ->where('w.lottery_type', '!=', '')
            ->distinct()
            ->orderBy('w.lottery_type')
            ->pluck('w.lottery_type')
            ->filter(static fn ($value): bool => is_string($value) && $value !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($lotteryType !== '') {
            $baseQuery->where('w.lottery_type', $lotteryType);
        }

        $marketCodes = (clone $baseQuery)
            ->select('w.market')
            ->whereNotNull('w.market')
            ->where('w.market', '!=', '')
            ->distinct()
            ->pluck('w.market')
            ->filter(static fn ($value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();

        return [
            'lottery_type_options' => $lotteryTypeOptions,
            'market_options' => $this->resolveMarketOptions($marketCodes),
        ];
    }

    /**
     * @param  array<int, string>  $marketCodes
     * @return array<int, array<string, mixed>>
     */
    private function resolveMarketOptions(array $marketCodes): array
    {
        if ($marketCodes === []) {
            return [];
        }

        $markets = LotteryMarket::query()
            ->with('group:id,name')
            ->whereIn('code', $marketCodes)
            ->orderBy('group_id')
            ->orderBy('name')
            ->get(['id', 'group_id', 'name', 'logo', 'icon', 'code']);

        $mappedCodes = $markets->pluck('code')->filter()->values()->all();
        $missingCodes = array_values(array_diff($marketCodes, $mappedCodes));

        $grouped = $markets->groupBy(static function (LotteryMarket $market): string {
            return (string) (optional($market->group)->name ?: 'ไม่ระบุกลุ่ม');
        })->map(static function ($groupMarkets, $groupName): array {
            return [
                'label' => (string) $groupName,
                'options' => $groupMarkets->map(static fn (LotteryMarket $market): array => [
                    'value' => (string) ($market->code ?? ''),
                    'text' => (string) ($market->name ?: $market->code),
                    'logo' => (string) ($market->logo ?: $market->icon ?: ''),
                ])->values()->all(),
            ];
        })->values()->all();

        if ($missingCodes !== []) {
            $grouped[] = [
                'label' => 'อื่นๆ',
                'options' => collect($missingCodes)->map(static fn (string $code): array => [
                    'value' => $code,
                    'text' => $code,
                    'logo' => '',
                ])->values()->all(),
            ];
        }

        return $grouped;
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
