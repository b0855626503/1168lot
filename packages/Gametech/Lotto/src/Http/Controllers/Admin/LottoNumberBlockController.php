<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoNumberBlockDataTable;
use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoNumberBlock;
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
            ->with('market')
            ->orderByDesc('draw_date')
            ->get(['id', 'market_id', 'draw_date'])
            ->map(function (LottoDraw $draw) {
                $date = $draw->draw_date ? $draw->draw_date->format('d/m/Y') : '-';
                $market = $draw->market->name ?? '-';

                return [
                    'value' => (int) $draw->id,
                    'text' => $date . ' (' . $market . ')',
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
                Rule::unique('lotto_number_blocks', 'bet_type')
                    ->where(fn ($query) => $query
                        ->where('draw_id', (int) ($data['draw_id'] ?? 0))
                        ->where('number', (string) ($data['number'] ?? ''))),
            ],
            'number'    => ['required', 'string', 'max:20'],
            'mode'      => ['required', Rule::in(['block', 'limit_future'])],
            'reason'    => ['nullable', 'string', 'max:65535'],
            'blocked_at' => ['nullable', 'date_format:Y-m-d H:i'],
        ], [
            'bet_type.unique' => 'เลขนี้ในประเภทเดิมพันเดียวกันของงวดนี้ถูกอั้นไว้แล้ว',
            'bet_type.in' => 'ประเภทเดิมพันไม่ถูกต้อง',
        ])->validate();

        LottoNumberBlock::query()->create([
            'draw_id' => $validated['draw_id'],
            'bet_type' => $validated['bet_type'],
            'number' => trim((string) $validated['number']),
            'mode' => $validated['mode'],
            'reason' => $validated['reason'] ?? null,
            'blocked_by' => auth()->id(),
            'blocked_at' => $validated['blocked_at'] ?? now()->format('Y-m-d H:i:s'),
        ]);

        return $this->sendSuccess('เพิ่มเลขอั้นเรียบร้อยแล้ว');
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
                        ->where('number', (string) ($data['number'] ?? ''))),
            ],
            'number'    => ['required', 'string', 'max:20'],
            'mode'      => ['required', Rule::in(['block', 'limit_future'])],
            'reason'    => ['nullable', 'string', 'max:65535'],
            'blocked_at' => ['nullable', 'date_format:Y-m-d H:i'],
        ], [
            'bet_type.unique' => 'เลขนี้ในประเภทเดิมพันเดียวกันของงวดนี้ถูกอั้นไว้แล้ว',
            'bet_type.in' => 'ประเภทเดิมพันไม่ถูกต้อง',
        ])->validate();

        $block->update([
            'draw_id' => $validated['draw_id'],
            'bet_type' => $validated['bet_type'],
            'number' => trim((string) $validated['number']),
            'mode' => $validated['mode'],
            'reason' => $validated['reason'] ?? null,
            'blocked_at' => $validated['blocked_at'] ?? now()->format('Y-m-d H:i:s'),
        ]);

        return $this->sendSuccess('อัปเดตเลขอั้นเรียบร้อยแล้ว');
    }
}

