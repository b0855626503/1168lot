<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoDrawDataTable;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Services\DrawService;
use Gametech\Lotto\Services\SettlementService;
use Gametech\Lotto\Support\DrawStatusFlow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class LottoDrawController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LottoDrawDataTable $dataTable)
    {
        $marketOptions = LotteryMarket::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($market) => ['value' => (int) $market->id, 'text' => $market->name])
            ->values()
            ->toArray();

        return $dataTable->render($this->_config['view'], [
            'marketOptions' => $marketOptions,
        ]);
    }

    public function loadData(Request $request): JsonResponse
    {
        $id   = $request->input('id');
        $data = LottoDraw::query()->with('market')->find((int) $id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
    }

    public function create(Request $request, DrawService $drawService): JsonResponse
    {
        $data = (array) $request->input('data', []);

        $validated = validator($data, [
            'market_id'   => ['required', 'integer', 'exists:lotto_markets,id'],
            'draw_date'   => ['required', 'date_format:Y-m-d'],
            'open_at'     => ['required', 'date_format:Y-m-d H:i'],
            'close_at'    => ['required', 'date_format:Y-m-d H:i', 'after:open_at'],
            'status'      => ['nullable', Rule::in(DrawStatusFlow::allowedStatuses())],
            'result_at'   => ['nullable', 'date_format:Y-m-d H:i'],
        ])->validate();

        try {
            $targetStatus = (string) ($validated['status'] ?? 'draft');

            $draw = $drawService->createDraft([
                'market_id'   => $validated['market_id'],
                'draw_date'   => $validated['draw_date'],
                'open_at'     => $validated['open_at'],
                'close_at'    => $validated['close_at'],
                'result_at'   => $validated['result_at'] ?? null,
                'created_by'  => auth()->id(),
            ]);

            $this->applyStatusTransition($drawService, $draw, 'draft', $targetStatus);

            return $this->sendSuccess('เพิ่มงวดหวยสำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->sendError('เพิ่มงวดหวยไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    public function edit(Request $request): JsonResponse
    {
        $id   = $request->input('id');
        $data = LottoDraw::query()->find((int) $id);

        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');
    }

    public function update(Request $request, DrawService $drawService): JsonResponse
    {
        $id   = $request->input('id');
        $data = (array) $request->input('data', []);

        $draw = LottoDraw::query()->find((int) $id);
        if (! $draw instanceof LottoDraw) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $validated = validator($data, [
            'market_id'   => ['required', 'integer', 'exists:lotto_markets,id'],
            'draw_date'   => ['required', 'date_format:Y-m-d'],
            'open_at'     => ['required', 'date_format:Y-m-d H:i'],
            'close_at'    => ['required', 'date_format:Y-m-d H:i', 'after:open_at'],
            'status'      => ['nullable', Rule::in(DrawStatusFlow::allowedStatuses())],
            'result_at'   => ['nullable', 'date_format:Y-m-d H:i'],
        ])->validate();

        try {
            $currentStatus = (string) $draw->status;
            $targetStatus = (string) ($validated['status'] ?? $draw->status);

            $draw->update([
                'market_id'   => $validated['market_id'],
                'draw_date'   => $validated['draw_date'],
                'open_at'     => $validated['open_at'],
                'close_at'    => $validated['close_at'],
                'result_at'   => $validated['result_at'] ?? null,
            ]);

            $this->applyStatusTransition($drawService, $draw, $currentStatus, $targetStatus);

            return $this->sendSuccess('อัปเดตงวดหวยสำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->sendError('อัปเดตงวดหวยไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    public function open(Request $request, DrawService $drawService): JsonResponse
    {
        $id = (int) $request->input('id');
        $draw = LottoDraw::query()->find($id);

        if (! $draw instanceof LottoDraw) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        try {
            $drawService->openDraw($draw);

            return $this->sendSuccess('เปิดรับงวดหวยสำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->sendError('เปิดรับงวดไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    public function close(Request $request, DrawService $drawService): JsonResponse
    {
        $id = (int) $request->input('id');
        $draw = LottoDraw::query()->find($id);

        if (! $draw instanceof LottoDraw) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        try {
            $drawService->closeDraw($draw);

            return $this->sendSuccess('ปิดรับงวดหวยสำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->sendError('ปิดรับงวดไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    public function settle(Request $request, SettlementService $settlementService): JsonResponse
    {
        $id = (int) $request->input('id');
        $data = (array) $request->input('data', []);

        $draw = LottoDraw::query()->find($id);

        if (! $draw instanceof LottoDraw) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        if ((string) $draw->status !== 'closed') {
            return $this->sendError('ประกาศผลได้เฉพาะงวดที่ปิดรับแล้ว', 422);
        }

        $validated = validator($data, [
            'result_number' => ['required', 'array'],
            'result_number.top_3' => ['required', 'digits:3'],
            'result_number.bottom_2' => ['required', 'digits:2'],
            'result_at' => ['nullable', 'date_format:Y-m-d H:i'],
        ])->validate();

        try {
            $summary = $settlementService->settleDraw(
                $draw,
                (array) $validated['result_number'],
                $validated['result_at'] ?? null
            );

            return $this->sendResponse($summary, 'ประกาศผลและประมวลผลโพยสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ประกาศผลไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    private function applyStatusTransition(
        DrawService $drawService,
        LottoDraw $draw,
        string $currentStatus,
        string $targetStatus
    ): void {
        $steps = DrawStatusFlow::transitionSteps($currentStatus, $targetStatus);

        foreach ($steps as $step) {
            if ($step === 'open') {
                $draw = $drawService->openDraw($draw);
            }

            if ($step === 'close') {
                $draw = $drawService->closeDraw($draw);
            }
        }
    }

}

