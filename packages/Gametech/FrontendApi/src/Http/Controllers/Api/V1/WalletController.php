<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\Core\Core;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WalletController extends BaseController
{
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT = 100;
    private const CLAIMABLE_TYPES = [
        'bonus' => 'BONUS',
        'faststart' => 'FASTSTART',
        'cashback' => 'CASHBACK',
        'ic' => 'IC',
    ];

    /**
     * @var string[]
     */
    private const SUPPORTED_TYPES = [
        'all',
        'deposit',
        'withdraw',
        'lotto_bet',
        'lotto_refund',
        'referral',
        'cashback',
        'ic',
        'bonus',
        'game',
        'admin_adjust',
        'rollback',
        'other',
    ];

    public function claim(Request $request): JsonResponse
    {
        try {
            $member = $request->user();
            if (! $member || ! isset($member->code)) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $type = strtolower(trim((string) $request->input('type')));
            if (! array_key_exists($type, self::CLAIMABLE_TYPES)) {
                return $this->sendError('ไม่รองรับประเภทโบนัสที่ร้องขอ', 422);
            }

            /** @var Core $core */
            $core = app(Core::class);
            $config = (object) $core->getConfigData();
            $memberCode = (int) $member->code;
            $targetWallet = (($config->freecredit_open ?? 'N') === 'Y') ? 'balance_free' : 'balance';
            $currentWalletBalance = (float) ($targetWallet === 'balance_free'
                ? ($member->balance_free ?? 0)
                : ($member->balance ?? 0));
            $proReset = (float) ($config->pro_reset ?? 0);

            if ($currentWalletBalance > $proReset) {
                return $this->sendError(
                    'ไม่สามารถทำรายการได้ โยกเข้าได้เมื่อยอดเครดิต น้อยกว่าหรือเท่ากับ ' . $proReset,
                    422
                );
            }

            $repositoryKey = $targetWallet === 'balance_free'
                ? 'Gametech\Member\Repositories\MemberCreditFreeLogRepository'
                : 'Gametech\Member\Repositories\MemberCreditLogRepository';

            $legacyType = self::CLAIMABLE_TYPES[$type];
            $claimSourceAmount = (float) ($member->{$type} ?? 0);
            $claimed = app($repositoryKey)->tranBonus([
                'member_code' => $memberCode,
            ], $legacyType);

            if (! $claimed) {
                return $this->sendError('ไม่สามารถทำรายการได้ โปรดลองใหม่ในภายหลัง', 422);
            }

            $freshMember = app('Gametech\Member\Repositories\MemberRepository')->findOrFail($memberCode);

            return $this->sendResponse([
                'type' => $type,
                'legacy_type' => $legacyType,
                'claimed_amount' => $claimSourceAmount,
                'target_wallet' => $targetWallet,
                'profile' => [
                    'balance' => (float) ($freshMember->balance ?? 0),
                    'balance_free' => (float) ($freshMember->balance_free ?? 0),
                    'bonus' => (float) ($freshMember->bonus ?? 0),
                    'cashback' => (float) ($freshMember->cashback ?? 0),
                    'ic' => (float) ($freshMember->ic ?? 0),
                    'faststart' => (float) ($freshMember->faststart ?? 0),
                ],
            ], 'ดำเนินการโยก เข้ากระเป๋าสำเร็จแล้ว');
        } catch (\Throwable $exception) {
            return $this->sendError('ไม่สามารถทำรายการได้ โปรดลองใหม่ในภายหลัง', 422);
        }
    }

    public function transactions(Request $request): JsonResponse
    {
        try {
            $member = $request->user();
            if (! $member || ! isset($member->code)) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $language = $this->requestLanguage($request);
            $memberId = (int) $member->code;
            $type = strtolower(trim((string) $request->query('type', 'all')));

            if (! in_array($type, self::SUPPORTED_TYPES, true)) {
                return $this->sendError('ไม่รองรับประเภทประวัติที่ร้องขอ', 422);
            }

            [$dateStart, $dateStop] = $this->resolveDateRange($request);
            $limit = $this->resolveLimit($request);
            $page = max(1, (int) $request->query('page', 1));

            if (! Schema::hasTable('wallet_transactions')) {
                return $this->sendResponse([
                    'filters' => [
                        'type' => $type,
                        'date_start' => $dateStart?->toDateString(),
                        'date_stop' => $dateStop?->toDateString(),
                    ],
                    'summary' => [
                        'count' => 0,
                        'total_credit_amount' => 0,
                        'total_debit_amount' => 0,
                        'net_amount' => 0,
                    ],
                    'items' => [],
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'count' => 0,
                        'total' => 0,
                        'has_more' => false,
                    ],
                    'language' => $language,
                ], 'ดึงประวัติการเงินสำเร็จ');
            }

            $query = $this->walletTransactionsQuery($memberId, $type, $dateStart, $dateStop);

            $summary = (clone $query)
                ->selectRaw('COUNT(*) as count')
                ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'CREDIT' THEN amount ELSE 0 END), 0) as total_credit_amount")
                ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'DEBIT' THEN amount ELSE 0 END), 0) as total_debit_amount")
                ->selectRaw("
                    COALESCE(SUM(CASE WHEN direction = 'CREDIT' THEN amount ELSE 0 END), 0)
                    - COALESCE(SUM(CASE WHEN direction = 'DEBIT' THEN amount ELSE 0 END), 0)
                    as net_amount
                ")
                ->first();

            $total = (int) ($summary->count ?? 0);

            $rows = $query
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->forPage($page, $limit)
                ->get([
                    'id',
                    'member_id',
                    'direction',
                    'amount',
                    'balance_before',
                    'balance_after',
                    'ref_type',
                    'ref_id',
                    'ref_code',
                    'group_code',
                    'status',
                    'description',
                    'meta',
                    'created_at',
                ]);

            $lottoContextByTicket = $this->lottoContextByTicketIds($rows);

            $items = $rows->map(function ($row) use ($language, $lottoContextByTicket): array {
                $refType = (string) ($row->ref_type ?? '');
                $transactionType = $this->transactionType($refType);
                $amount = (float) ($row->amount ?? 0);
                $direction = (string) ($row->direction ?? '');
                $lottoContext = in_array($transactionType, ['lotto_bet', 'lotto_refund'], true)
                    ? ($lottoContextByTicket[(int) ($row->ref_id ?? 0)] ?? null)
                    : null;

                return [
                    'id' => (int) $row->id,
                    'created_at' => $this->formatDateTimeValue($row->created_at),
                    'type' => $transactionType,
                    'type_label' => $this->transactionTypeLabel($transactionType, $language),
                    'ref_type' => $refType,
                    'direction' => $direction,
                    'direction_label' => $this->directionLabel($direction, $language),
                    'amount' => $amount,
                    'signed_amount' => $direction === 'DEBIT' ? ($amount * -1) : $amount,
                    'balance_before' => (float) ($row->balance_before ?? 0),
                    'balance_after' => (float) ($row->balance_after ?? 0),
                    'status' => (string) ($row->status ?? ''),
                    'title' => $this->transactionTitle($transactionType, $language),
                    'detail' => $this->transactionDetail($transactionType, $row, $lottoContext, $language),
                    'description' => (string) ($row->description ?? ''),
                    'ref_id' => isset($row->ref_id) ? (int) $row->ref_id : null,
                    'ref_code' => (string) ($row->ref_code ?? ''),
                    'group_code' => (string) ($row->group_code ?? ''),
                    'lotto' => $lottoContext,
                ];
            })->values()->all();

            return $this->sendResponse([
                'filters' => [
                    'type' => $type,
                    'date_start' => $dateStart?->toDateString(),
                    'date_stop' => $dateStop?->toDateString(),
                ],
                'summary' => [
                    'count' => $total,
                    'total_credit_amount' => (float) ($summary->total_credit_amount ?? 0),
                    'total_debit_amount' => (float) ($summary->total_debit_amount ?? 0),
                    'net_amount' => (float) ($summary->net_amount ?? 0),
                ],
                'items' => $items,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'count' => count($items),
                    'total' => $total,
                    'has_more' => ($page * $limit) < $total,
                ],
                'language' => $language,
            ], 'ดึงประวัติการเงินสำเร็จ');
        } catch (\RuntimeException $exception) {
            return $this->sendError($exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            return $this->sendError('ไม่สามารถดึงประวัติการเงินได้ในขณะนี้', 422);
        }
    }

    private function walletTransactionsQuery(int $memberId, string $type, ?Carbon $dateStart, ?Carbon $dateStop): Builder
    {
        $query = DB::table('wallet_transactions')
            ->where('member_id', $memberId)
            ->where('scope', 'MEMBER');

        if ($dateStart instanceof Carbon) {
            $query->where('created_at', '>=', $dateStart->copy()->startOfDay());
        }

        if ($dateStop instanceof Carbon) {
            $query->where('created_at', '<=', $dateStop->copy()->endOfDay());
        }

        $this->applyTypeFilter($query, $type);

        return $query;
    }

    private function resolveLimit(Request $request): int
    {
        $limit = (int) $request->query('limit', self::DEFAULT_LIMIT);

        return max(1, min($limit, self::MAX_LIMIT));
    }

    /**
     * @return array{0:?Carbon,1:?Carbon}
     */
    private function resolveDateRange(Request $request): array
    {
        $dateStart = $this->parseDateValue($request->query('date_start'));
        $dateStop = $this->parseDateValue($request->query('date_stop'));

        if ($dateStart && $dateStop && $dateStart->gt($dateStop)) {
            throw new \RuntimeException('ช่วงวันที่ไม่ถูกต้อง');
        }

        return [$dateStart, $dateStop];
    }

    private function parseDateValue(?string $value): ?Carbon
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('รูปแบบวันที่ไม่ถูกต้อง');
        }
    }

    private function applyTypeFilter(Builder $query, string $type): void
    {
        if ($type === 'all') {
            return;
        }

        if ($type === 'game') {
            $query->where('ref_type', 'like', 'GAME_%');

            return;
        }

        if ($type === 'other') {
            $knownRefTypes = [
                'DEPOSIT',
                'WITHDRAW',
                'LOTTO_BET',
                'LOTTO_CANCEL',
                'TRANFT',
                'TRANCB',
                'TRANIC',
                'TRANBONUS',
                'SETWALLET',
                'ADJUST',
                'ROLLBACK',
            ];

            $query->whereNotIn('ref_type', $knownRefTypes)
                ->where('ref_type', 'not like', 'GAME_%');

            return;
        }

        $map = [
            'deposit' => ['DEPOSIT'],
            'withdraw' => ['WITHDRAW'],
            'lotto_bet' => ['LOTTO_BET'],
            'lotto_refund' => ['LOTTO_CANCEL'],
            'referral' => ['TRANFT'],
            'cashback' => ['TRANCB'],
            'ic' => ['TRANIC'],
            'bonus' => ['TRANBONUS'],
            'admin_adjust' => ['SETWALLET', 'ADJUST'],
            'rollback' => ['ROLLBACK'],
        ];

        $query->whereIn('ref_type', $map[$type] ?? ['__NO_MATCH__']);
    }

    private function transactionType(string $refType): string
    {
        if (str_starts_with($refType, 'GAME_')) {
            return 'game';
        }

        return match ($refType) {
            'DEPOSIT' => 'deposit',
            'WITHDRAW' => 'withdraw',
            'LOTTO_BET' => 'lotto_bet',
            'LOTTO_CANCEL' => 'lotto_refund',
            'TRANFT' => 'referral',
            'TRANCB' => 'cashback',
            'TRANIC' => 'ic',
            'TRANBONUS' => 'bonus',
            'SETWALLET', 'ADJUST' => 'admin_adjust',
            'ROLLBACK' => 'rollback',
            default => 'other',
        };
    }

    /**
     * @param Collection<int, object> $rows
     * @return array<int, array{ticket_id:int,market_name:string,draw_date:?string}>
     */
    private function lottoContextByTicketIds(Collection $rows): array
    {
        $ticketIds = $rows
            ->filter(fn ($row) => in_array((string) ($row->ref_type ?? ''), ['LOTTO_BET', 'LOTTO_CANCEL'], true))
            ->pluck('ref_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($ticketIds)
            || ! Schema::hasTable('lotto_tickets')
            || ! Schema::hasTable('lotto_draws')
            || ! Schema::hasTable('lotto_markets')) {
            return [];
        }

        return DB::table('lotto_tickets as tickets')
            ->join('lotto_draws as draws', 'draws.id', '=', 'tickets.draw_id')
            ->leftJoin('lotto_markets as markets', 'markets.id', '=', 'draws.market_id')
            ->whereIn('tickets.id', $ticketIds)
            ->get([
                'tickets.id as ticket_id',
                'markets.name as market_name',
                'draws.draw_date as draw_date',
            ])
            ->mapWithKeys(function ($row): array {
                return [
                    (int) $row->ticket_id => [
                        'ticket_id' => (int) $row->ticket_id,
                        'market_name' => (string) ($row->market_name ?? ''),
                        'draw_date' => $this->formatDateValue($row->draw_date),
                    ],
                ];
            })
            ->all();
    }

    private function formatDateTimeValue($value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->toDateTimeString();
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->toDateTimeString();
        } catch (\Throwable $exception) {
            return $raw;
        }
    }

    private function formatDateValue($value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->toDateString();
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Throwable $exception) {
            return $raw;
        }
    }

    private function transactionTypeLabel(string $type, string $language): string
    {
        return match ($this->normalizeLanguage($language)) {
            'en' => match ($type) {
                'deposit' => 'Deposit',
                'withdraw' => 'Withdraw',
                'lotto_bet' => 'Lotto bet',
                'lotto_refund' => 'Lotto refund',
                'referral' => 'Referral',
                'cashback' => 'Cashback',
                'ic' => 'Downline loss',
                'bonus' => 'Bonus',
                'game' => 'Game',
                'admin_adjust' => 'Adjustment',
                'rollback' => 'Rollback',
                default => 'Other',
            },
            default => match ($type) {
                'deposit' => 'ฝากเงิน',
                'withdraw' => 'ถอนเงิน',
                'lotto_bet' => 'แทงหวย',
                'lotto_refund' => 'คืนเงินหวย',
                'referral' => 'ค่าแนะนำ',
                'cashback' => 'ยอดเสีย',
                'ic' => 'ยอดเสียเพื่อน',
                'bonus' => 'โบนัส',
                'game' => 'เกม',
                'admin_adjust' => 'ปรับยอด',
                'rollback' => 'คืนยอด',
                default => 'อื่นๆ',
            },
        };
    }

    private function directionLabel(string $direction, string $language): string
    {
        return match ($this->normalizeLanguage($language)) {
            'en' => $direction === 'DEBIT' ? 'Debit' : 'Credit',
            default => $direction === 'DEBIT' ? 'หักออก' : 'รับเข้า',
        };
    }

    private function transactionTitle(string $type, string $language): string
    {
        return $this->transactionTypeLabel($type, $language);
    }

    private function transactionDetail(string $type, object $row, ?array $lottoContext, string $language): string
    {
        $description = trim((string) ($row->description ?? ''));

        $base = match ($this->normalizeLanguage($language)) {
            'en' => match ($type) {
                'deposit' => 'Deposit to main wallet',
                'withdraw' => 'Withdraw from main wallet',
                'lotto_bet' => 'Lotto bet placed',
                'lotto_refund' => 'Lotto ticket refunded',
                'referral' => 'Referral income moved to main wallet',
                'cashback' => 'Cashback moved to main wallet',
                'ic' => 'Downline loss income moved to main wallet',
                'bonus' => 'Bonus moved to main wallet',
                'game' => $description !== '' ? $description : 'Game wallet transaction',
                'admin_adjust' => $description !== '' ? $description : 'Wallet adjusted',
                'rollback' => $description !== '' ? $description : 'Rollback to main wallet',
                default => $description !== '' ? $description : 'Wallet transaction',
            },
            default => match ($type) {
                'deposit' => 'ฝากเงินเข้ากระเป๋าหลัก',
                'withdraw' => 'ถอนเงินจากกระเป๋าหลัก',
                'lotto_bet' => 'แทงหวยจากกระเป๋าหลัก',
                'lotto_refund' => 'คืนเงินโพยหวยเข้ากระเป๋าหลัก',
                'referral' => 'รับค่าแนะนำเข้ากระเป๋าหลัก',
                'cashback' => 'รับยอดเสียเข้ากระเป๋าหลัก',
                'ic' => 'รับยอดเสียเพื่อนเข้ากระเป๋าหลัก',
                'bonus' => 'รับโบนัสเข้ากระเป๋าหลัก',
                'game' => $description !== '' ? $description : 'ธุรกรรมเกม',
                'admin_adjust' => $description !== '' ? $description : 'ปรับยอดกระเป๋าหลัก',
                'rollback' => $description !== '' ? $description : 'คืนยอดเข้ากระเป๋าหลัก',
                default => $description !== '' ? $description : 'ธุรกรรมกระเป๋าเงิน',
            },
        };

        if (! is_array($lottoContext) || $lottoContext === []) {
            return $base;
        }

        $marketName = trim((string) ($lottoContext['market_name'] ?? ''));
        $drawDate = trim((string) ($lottoContext['draw_date'] ?? ''));
        if ($marketName === '' && $drawDate === '') {
            return $base;
        }

        return match ($this->normalizeLanguage($language)) {
            'en' => $base
                . ($marketName !== '' ? ': ' . $marketName : '')
                . ($drawDate !== '' ? ' draw ' . $drawDate : ''),
            default => $base
                . ($marketName !== '' ? ': ' . $marketName : '')
                . ($drawDate !== '' ? ' งวดวันที่ ' . $drawDate : ''),
        };
    }

    private function normalizeLanguage(string $language): string
    {
        return strtolower(trim($language)) === 'en' ? 'en' : 'th';
    }
}
