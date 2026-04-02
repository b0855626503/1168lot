<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoNumberBlockDataTable;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoNumberBlock;
use Gametech\Lotto\Models\LotteryMarket;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LottoNumberBlockController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LottoNumberBlockDataTable $dataTable)
    {
        $drawOptions = LottoDraw::query()
            ->with('market:id,name')
            ->whereIn('status', ['draft', 'open'])
            ->orderByDesc('draw_date')
            ->orderByDesc('id')
            ->get(['id', 'market_id', 'draw_date', 'status'])
            ->groupBy(static fn (LottoDraw $draw): int => (int) $draw->market_id)
            ->map(static fn ($draws) => $draws->first())
            ->filter()
            ->map(static function (LottoDraw $draw): array {
                return [
                    'value' => (int) $draw->id,
                    'text' => ($draw->market->name ?? '-') . ' | '
                        . ($draw->draw_date ? $draw->draw_date->format('d/m/Y') : '-')
                        . ' (' . strtoupper((string) $draw->status) . ')',
                ];
            })
            ->sortBy('text')
            ->values()
            ->toArray();

        $marketOptionsGrouped = LotteryMarket::query()
            ->with('group:id,name')
            ->orderBy('group_id')
            ->orderBy('name')
            ->get(['id', 'group_id', 'name'])
            ->groupBy(static function (LotteryMarket $market): int {
                return (int) ($market->group_id ?? 0);
            })
            ->map(static function ($markets, $groupId): array {
                $first = $markets->first();
                $label = (string) optional(optional($first)->group)->name ?: 'ไม่ระบุกลุ่ม';

                return [
                    'group_id' => (int) $groupId,
                    'label' => $label,
                    'options' => $markets->map(static function (LotteryMarket $market): array {
                        return [
                            'value' => (int) $market->id,
                            'text' => (string) $market->name,
                        ];
                    })->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();

        $betTypeOptions = collect(BetType::all())
            ->map(fn (string $type) => [
                'value' => $type,
                'text' => $type . ' = ' . BetType::label($type),
            ])
            ->values()
            ->toArray();

        return $dataTable->render($this->_config['view'], [
            'drawOptions' => $drawOptions,
            'marketOptionsGrouped' => $marketOptionsGrouped,
            'betTypeOptions' => $betTypeOptions,
        ]);
    }

    public function loadData(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $data = LottoNumberBlock::query()->find($id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
    }

    public function create(Request $request): JsonResponse
    {
        $data = (array) $request->input('data', []);

        $validated = validator($data, [
            'draw_id'   => ['required', 'integer', 'exists:lotto_draws,id'],
            'bet_type'  => [
                'required',
                Rule::in(BetType::all()),
            ],
            'number'    => ['required', 'string', 'max:2000'],
            'mode'      => ['required', Rule::in(['block', 'limit_future'])],
            'reason'    => ['nullable', 'string', 'max:65535'],
            'blocked_at' => ['nullable', 'date_format:Y-m-d H:i'],
        ], [
            'bet_type.in' => 'ประเภทเดิมพันไม่ถูกต้อง',
        ])->validate();

        $numbers = $this->parseNumbers((string) $validated['number']);
        if (empty($numbers)) {
            return $this->sendError('กรุณาระบุเลขอย่างน้อย 1 รายการ', 422);
        }
        if ($this->hasNumberTooLong($numbers)) {
            return $this->sendError('เลขแต่ละรายการต้องไม่เกิน 20 ตัวอักษร', 422);
        }

        $duplicateNumbers = LottoNumberBlock::query()
            ->where('draw_id', (int) $validated['draw_id'])
            ->where('bet_type', (string) $validated['bet_type'])
            ->whereIn('number', $numbers)
            ->pluck('number')
            ->map(fn ($number) => (string) $number)
            ->unique()
            ->values()
            ->all();

        if (! empty($duplicateNumbers)) {
            return $this->sendError('มีเลขอั้นซ้ำอยู่แล้ว: ' . implode(', ', $duplicateNumbers), 422);
        }

        DB::transaction(function () use ($validated, $numbers): void {
            foreach ($numbers as $number) {
                LottoNumberBlock::query()->create([
                    'draw_id' => $validated['draw_id'],
                    'bet_type' => $validated['bet_type'],
                    'number' => $number,
                    'mode' => $validated['mode'],
                    'reason' => $validated['reason'] ?? null,
                    'blocked_by' => auth()->id(),
                    'blocked_at' => $validated['blocked_at'] ?? now()->format('Y-m-d H:i:s'),
                ]);
            }
        });

        return $this->sendSuccess('เพิ่มเลขอั้นเรียบร้อยแล้ว (' . count($numbers) . ' รายการ)');
    }

    public function edit(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $data = LottoNumberBlock::query()->find($id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $data = (array) $request->input('data', []);

        $block = LottoNumberBlock::query()->find($id);

        if (! $block) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $validated = validator($data, [
            'draw_id'   => ['required', 'integer', 'exists:lotto_draws,id'],
            'bet_type'  => [
                'required',
                Rule::in(BetType::all()),
                Rule::unique('lotto_number_blocks', 'bet_type')
                    ->ignore($block->id)
                    ->where(fn ($query) => $query
                        ->where('draw_id', (int) ($data['draw_id'] ?? 0))
                        ->where('number', (string) ($this->parseNumbers((string) ($data['number'] ?? ''))[0] ?? ''))),
            ],
            'number'    => ['required', 'string', 'max:2000'],
            'mode'      => ['required', Rule::in(['block', 'limit_future'])],
            'reason'    => ['nullable', 'string', 'max:65535'],
            'blocked_at' => ['nullable', 'date_format:Y-m-d H:i'],
        ], [
            'bet_type.unique' => 'เลขนี้ในประเภทเดิมพันเดียวกันของงวดนี้ถูกอั้นไว้แล้ว',
            'bet_type.in' => 'ประเภทเดิมพันไม่ถูกต้อง',
        ])->validate();

        $numbers = $this->parseNumbers((string) $validated['number']);
        if (empty($numbers)) {
            return $this->sendError('กรุณาระบุเลขอย่างน้อย 1 รายการ', 422);
        }
        if ($this->hasNumberTooLong($numbers)) {
            return $this->sendError('เลขแต่ละรายการต้องไม่เกิน 20 ตัวอักษร', 422);
        }

        if (count($numbers) > 1) {
            return $this->sendError('การแก้ไขรองรับเลขได้ 1 รายการต่อครั้ง กรุณาเพิ่มหลายเลขผ่านปุ่มเพิ่ม', 422);
        }

        $block->update([
            'draw_id' => $validated['draw_id'],
            'bet_type' => $validated['bet_type'],
            'number' => $numbers[0],
            'mode' => $validated['mode'],
            'reason' => $validated['reason'] ?? null,
            'blocked_at' => $validated['blocked_at'] ?? now()->format('Y-m-d H:i:s'),
        ]);

        return $this->sendSuccess('อัปเดตเลขอั้นเรียบร้อยแล้ว');
    }

    public function delete(Request $request): JsonResponse
    {
        $validated = validator($request->all(), [
            'id' => ['required', 'integer', 'exists:lotto_number_blocks,id'],
        ])->validate();

        $deleted = LottoNumberBlock::query()
            ->where('id', (int) $validated['id'])
            ->delete();

        if ($deleted < 1) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 404);
        }

        return $this->sendSuccess('ลบเลขอั้นเรียบร้อยแล้ว');
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = validator($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:lotto_number_blocks,id'],
        ])->validate();

        $ids = collect($validated['ids'])
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return $this->sendError('กรุณาเลือกรายการที่ต้องการลบ', 422);
        }

        $deleted = LottoNumberBlock::query()
            ->whereIn('id', $ids->all())
            ->delete();

        return $this->sendSuccess('ลบเลขอั้นเรียบร้อยแล้ว ' . (int) $deleted . ' รายการ');
    }

    /**
     * @return string[]
     */
    private function parseNumbers(string $raw): array
    {
        $parts = preg_split('/[\s,，]+/u', trim($raw)) ?: [];
        $normalized = collect($parts)
            ->map(fn ($part) => trim((string) $part))
            ->filter(fn ($part) => $part !== '')
            ->unique()
            ->values()
            ->all();

        return $normalized;
    }

    /**
     * @param string[] $numbers
     */
    private function hasNumberTooLong(array $numbers): bool
    {
        foreach ($numbers as $number) {
            if (mb_strlen((string) $number) > 20) {
                return true;
            }
        }

        return false;
    }
}
