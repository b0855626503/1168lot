<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoResultSourceDataTable;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoResultSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class LottoResultSourceController extends AppBaseController
{
    protected array $_config;

    public function __construct()
    {
        $this->middleware('admin');
        $this->_config = (array) request('_config', []);
    }

    public function index(LottoResultSourceDataTable $dataTable)
    {
        $marketOptions = LotteryMarket::query()
            ->with('group:id,name')
            ->orderBy('group_id')
            ->orderBy('name')
            ->get(['id', 'group_id', 'name'])
            ->map(static function (LotteryMarket $market): array {
                return [
                    'value' => (int) $market->id,
                    'group_id' => (int) $market->group_id,
                    'text' => (string) $market->name,
                    'group' => (string) optional($market->group)->name,
                ];
            })
            ->values()
            ->toArray();

        $groupOptions = LotteryGroup::query()
            ->orderBy('sort')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static function (LotteryGroup $group): array {
                return [
                    'value' => (int) $group->id,
                    'text' => (string) $group->name,
                ];
            })
            ->values()
            ->toArray();

        return $dataTable->render($this->_config['view'], [
            'marketOptions' => $marketOptions,
            'groupOptions' => $groupOptions,
            'lookupDateModes' => [
                'ROUND_DATE',
                'ROUND_DATE_MINUS_DAYS',
                'ROUND_DATE_PLUS_DAYS',
                'RESULT_AT_DATE',
            ],
            'parserTypes' => [
                'JSON_PATH',
                'CSS_SELECTOR',
                'REGEX',
            ],
            'sourceTypes' => [
                'api',
                'html',
            ],
            'httpMethods' => [
                'GET',
                'POST',
                'PUT',
            ],
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $validated = validator($request->all(), [
            'group_id' => ['nullable', 'integer', 'exists:lotto_groups,id'],
            'market_id' => ['nullable', 'integer', 'exists:lotto_markets,id'],
        ])->validate();

        $query = LottoResultSource::query()->with('market:id,name')->orderBy('priority')->orderBy('id');
        if (! empty($validated['group_id'])) {
            $query->whereHas('market', function ($q) use ($validated): void {
                $q->where('group_id', (int) $validated['group_id']);
            });
        }
        if (! empty($validated['market_id'])) {
            $query->where('market_id', (int) $validated['market_id']);
        }

        $items = $query->get()->map(static function (LottoResultSource $source): array {
            return [
                'id' => (int) $source->id,
                'market_id' => (int) $source->market_id,
                'market_name' => (string) optional($source->market)->name,
                'is_active' => (bool) $source->is_active,
                'priority' => (int) $source->priority,
                'source_type' => (string) $source->source_type,
                'endpoint_url' => (string) $source->endpoint_url,
                'http_method' => (string) $source->http_method,
                'lookup_date_mode' => (string) $source->lookup_date_mode,
                'lookup_date_offset_days' => (int) $source->lookup_date_offset_days,
                'parser_type' => (string) $source->parser_type,
                'timeout_seconds' => (int) $source->timeout_seconds,
                'request_headers_json' => $source->request_headers_json,
                'request_query_template_json' => $source->request_query_template_json,
                'request_body_template_json' => $source->request_body_template_json,
                'parser_config_json' => $source->parser_config_json,
                'mapping_config_json' => $source->mapping_config_json,
                'validation_config_json' => $source->validation_config_json,
                'retry_policy_json' => $source->retry_policy_json,
                'effective_from' => optional($source->effective_from)->format('Y-m-d H:i:s'),
                'effective_to' => optional($source->effective_to)->format('Y-m-d H:i:s'),
                'updated_at' => optional($source->updated_at)->format('Y-m-d H:i:s'),
            ];
        })->values()->all();

        return $this->sendResponse(['items' => $items], 'ดึงรายการ source สำเร็จ');
    }

    public function loadData(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $source = LottoResultSource::query()->find($id);

        if (! $source instanceof LottoResultSource) {
            return $this->sendError('ไม่พบ source ดังกล่าว', 404);
        }

        return $this->sendResponse([
            'id' => (int) $source->id,
            'market_id' => (int) $source->market_id,
            'is_active' => (bool) $source->is_active,
            'priority' => (int) $source->priority,
            'source_type' => (string) $source->source_type,
            'endpoint_url' => (string) $source->endpoint_url,
            'http_method' => (string) $source->http_method,
            'request_headers_json' => $source->request_headers_json,
            'request_query_template_json' => $source->request_query_template_json,
            'request_body_template_json' => $source->request_body_template_json,
            'lookup_date_mode' => (string) $source->lookup_date_mode,
            'lookup_date_offset_days' => (int) $source->lookup_date_offset_days,
            'parser_type' => (string) $source->parser_type,
            'parser_config_json' => $source->parser_config_json,
            'mapping_config_json' => $source->mapping_config_json,
            'validation_config_json' => $source->validation_config_json,
            'retry_policy_json' => $source->retry_policy_json,
            'timeout_seconds' => (int) $source->timeout_seconds,
            'effective_from' => optional($source->effective_from)->format('Y-m-d H:i:s'),
            'effective_to' => optional($source->effective_to)->format('Y-m-d H:i:s'),
        ], 'ดึงข้อมูล source สำเร็จ');
    }

    public function save(Request $request): JsonResponse
    {
        $payload = (array) $request->input('data', []);

        $validated = validator($payload, [
            'id' => ['nullable', 'integer', 'exists:lotto_result_sources,id'],
            'market_id' => ['required', 'integer', 'exists:lotto_markets,id'],
            'is_active' => ['required', 'boolean'],
            'priority' => ['required', 'integer', 'min:1', 'max:9999'],
            'source_type' => ['required', Rule::in(['api', 'html'])],
            'endpoint_url' => ['required', 'string', 'max:2048'],
            'http_method' => ['required', Rule::in(['GET', 'POST', 'PUT'])],
            'lookup_date_mode' => ['required', Rule::in(['ROUND_DATE', 'ROUND_DATE_MINUS_DAYS', 'ROUND_DATE_PLUS_DAYS', 'RESULT_AT_DATE'])],
            'lookup_date_offset_days' => ['nullable', 'integer', 'min:-365', 'max:365'],
            'parser_type' => ['required', Rule::in(['JSON_PATH', 'CSS_SELECTOR', 'REGEX'])],
            'timeout_seconds' => ['nullable', 'integer', 'min:1', 'max:60'],
            'effective_from' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'effective_to' => ['nullable', 'date_format:Y-m-d H:i:s', 'after_or_equal:effective_from'],
        ])->validate();

        try {
            $this->assertNoActivePriorityWindowConflict($validated);

            $source = ! empty($validated['id'])
                ? LottoResultSource::query()->findOrFail((int) $validated['id'])
                : new LottoResultSource();

            $source->fill([
                'market_id' => (int) $validated['market_id'],
                'is_active' => (bool) $validated['is_active'],
                'priority' => (int) $validated['priority'],
                'source_type' => (string) $validated['source_type'],
                'endpoint_url' => (string) $validated['endpoint_url'],
                'http_method' => strtoupper((string) $validated['http_method']),
                'lookup_date_mode' => (string) $validated['lookup_date_mode'],
                'lookup_date_offset_days' => (int) ($validated['lookup_date_offset_days'] ?? 0),
                'parser_type' => strtoupper((string) $validated['parser_type']),
                'timeout_seconds' => (int) ($validated['timeout_seconds'] ?? 10),
                'effective_from' => $validated['effective_from'] ?? null,
                'effective_to' => $validated['effective_to'] ?? null,
                'request_headers_json' => $this->parseJsonInput($payload['request_headers_json'] ?? null, 'request_headers_json'),
                'request_query_template_json' => $this->parseJsonInput($payload['request_query_template_json'] ?? null, 'request_query_template_json'),
                'request_body_template_json' => $this->parseJsonInput($payload['request_body_template_json'] ?? null, 'request_body_template_json'),
                'parser_config_json' => $this->parseJsonInput($payload['parser_config_json'] ?? null, 'parser_config_json'),
                'mapping_config_json' => $this->parseJsonInput($payload['mapping_config_json'] ?? null, 'mapping_config_json'),
                'validation_config_json' => $this->parseJsonInput($payload['validation_config_json'] ?? null, 'validation_config_json'),
                'retry_policy_json' => $this->parseJsonInput($payload['retry_policy_json'] ?? null, 'retry_policy_json'),
            ]);

            $source->save();

            return $this->sendSuccess('บันทึก source สำเร็จ');
        } catch (InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->sendError('บันทึก source ไม่สำเร็จ: ' . $e->getMessage(), 422);
        }
    }

    public function create(Request $request): JsonResponse
    {
        return $this->save($request);
    }

    public function update(Request $request): JsonResponse
    {
        return $this->save($request);
    }

    public function toggleActive(Request $request): JsonResponse
    {
        $validated = validator($request->all(), [
            'id' => ['required', 'integer', 'exists:lotto_result_sources,id'],
            'is_active' => ['required', 'boolean'],
        ])->validate();

        $source = LottoResultSource::query()->find((int) $validated['id']);
        if (! $source instanceof LottoResultSource) {
            return $this->sendError('ไม่พบ source ดังกล่าว', 404);
        }

        $source->forceFill([
            'is_active' => (bool) $validated['is_active'],
        ])->save();

        return $this->sendSuccess('อัปเดตสถานะ source สำเร็จ');
    }

    public function edit(Request $request): JsonResponse
    {
        $request->merge([
            'id' => $request->input('id'),
            'is_active' => $request->input('status'),
        ]);

        return $this->toggleActive($request);
    }

    /**
     * @param mixed $value
     * @return array<string,mixed>|array<int,mixed>|null
     */
    private function parseJsonInput($value, string $field)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException($field . ' ต้องเป็น JSON object/array หรือค่าว่าง');
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException($field . ' JSON ไม่ถูกต้อง: ' . json_last_error_msg());
        }

        if (! is_array($decoded)) {
            throw new InvalidArgumentException($field . ' ต้องเป็น JSON object/array');
        }

        return $decoded;
    }

    /**
     * @param array<string,mixed> $validated
     */
    private function assertNoActivePriorityWindowConflict(array $validated): void
    {
        if (! (bool) ($validated['is_active'] ?? false)) {
            return;
        }

        $marketId = (int) $validated['market_id'];
        $priority = (int) $validated['priority'];
        $currentId = ! empty($validated['id']) ? (int) $validated['id'] : null;
        $newFrom = ! empty($validated['effective_from'])
            ? Carbon::parse((string) $validated['effective_from'])->toDateTimeString()
            : null;
        $newTo = ! empty($validated['effective_to'])
            ? Carbon::parse((string) $validated['effective_to'])->toDateTimeString()
            : null;

        $query = LottoResultSource::query()
            ->where('market_id', $marketId)
            ->where('is_active', true)
            ->where('priority', $priority);

        if ($currentId !== null) {
            $query->where('id', '!=', $currentId);
        }

        $query->where(function ($outer) use ($newFrom, $newTo): void {
            $outer->where(function ($q) use ($newFrom): void {
                if ($newFrom === null) {
                    $q->whereRaw('1 = 1');

                    return;
                }

                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $newFrom);
            })->where(function ($q) use ($newTo): void {
                if ($newTo === null) {
                    $q->whereRaw('1 = 1');

                    return;
                }

                $q->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $newTo);
            });
        });

        if ($query->exists()) {
            throw new InvalidArgumentException(
                'พบ source active ชนกัน (market/priority เดียวกัน และ effective window ทับกัน) กรุณาปรับช่วงเวลา/priority หรือปิด source เดิมก่อน'
            );
        }
    }
}
