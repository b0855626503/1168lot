<?php

namespace Gametech\Lotto\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Lotto\DataTables\LottoResultSourceDataTable;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultSource;
use Gametech\Lotto\Models\LottoResultSourceRevision;
use Gametech\Lotto\Services\AutoResultV2\Config\SourcePipelineConfigCompiler;
use Gametech\Lotto\Services\AutoResultV2\ConfigData\CompiledSourcePipelineData;
use Gametech\Lotto\Services\AutoResultV2\LottoResultPipelineRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
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
            ->get(['id', 'group_id', 'name', 'logo', 'icon'])
            ->map(static function (LotteryMarket $market): array {
                return [
                    'value' => (int) $market->id,
                    'group_id' => (int) $market->group_id,
                    'text' => (string) $market->name,
                    'group' => (string) optional($market->group)->name,
                    'logo' => (string) ($market->logo ?: $market->icon ?: ''),
                ];
            })
            ->values()
            ->toArray();

        $marketOptionsGrouped = LotteryGroup::query()
            ->orderBy('sort')
            ->orderBy('name')
            ->with(['markets' => function ($query): void {
                $query->orderBy('name');
            }])
            ->get(['id', 'name'])
            ->map(static function (LotteryGroup $group): array {
                return [
                    'label' => (string) $group->name,
                    'options' => collect($group->markets ?? [])
                        ->map(static function (LotteryMarket $market): array {
                            return [
                                'value' => (int) $market->id,
                                'group_id' => (int) $market->group_id,
                                'text' => (string) $market->name,
                                'logo' => (string) ($market->logo ?: $market->icon ?: ''),
                            ];
                        })
                        ->values()
                        ->toArray(),
                ];
            })
            ->filter(static function (array $group): bool {
                return ! empty($group['options']);
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
            'marketOptionsGrouped' => $marketOptionsGrouped,
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
                'SCRIPT_JSON_PATH',
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
            'pipelineVersions' => [
                'LEGACY',
                'V2_SHADOW',
                'V2_CUTOVER',
            ],
            'fetchStrategies' => [
                'JSON_HTTP',
                'HTML_HTTP',
                'RENDERED_BROWSER',
                'EMBEDDED_JSON',
                'MANUAL_INPUT',
            ],
            'selectionStages' => [
                'PRE_MAPPING',
                'POST_MAPPING',
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
                'fetch_config_json' => $source->fetch_config_json,
                'selection_config_json' => $source->selection_config_json,
                'readiness_config_json' => $source->readiness_config_json,
                'pipeline_version' => (string) $source->pipeline_version,
                'fetch_strategy' => (string) $source->fetch_strategy,
                'selection_stage' => (string) $source->selection_stage,
                'supports_partial' => (bool) $source->supports_partial,
                'requires_browser' => (bool) $source->requires_browser,
                'shadow_enabled' => (bool) $source->shadow_enabled,
                'cutover_enabled' => (bool) $source->cutover_enabled,
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
            'fetch_config_json' => $source->fetch_config_json,
            'selection_config_json' => $source->selection_config_json,
            'readiness_config_json' => $source->readiness_config_json,
            'pipeline_version' => (string) $source->pipeline_version,
            'fetch_strategy' => (string) $source->fetch_strategy,
            'selection_stage' => (string) $source->selection_stage,
            'supports_partial' => (bool) $source->supports_partial,
            'requires_browser' => (bool) $source->requires_browser,
            'shadow_enabled' => (bool) $source->shadow_enabled,
            'cutover_enabled' => (bool) $source->cutover_enabled,
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
            'parser_type' => ['required', Rule::in(['JSON_PATH', 'CSS_SELECTOR', 'REGEX', 'SCRIPT_JSON_PATH'])],
            'timeout_seconds' => ['nullable', 'integer', 'min:1', 'max:60'],
            'effective_from' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'effective_to' => ['nullable', 'date_format:Y-m-d H:i:s', 'after_or_equal:effective_from'],
            'pipeline_version' => ['nullable', Rule::in(['LEGACY', 'V2_SHADOW', 'V2_CUTOVER'])],
            'fetch_strategy' => ['nullable', Rule::in(['JSON_HTTP', 'HTML_HTTP', 'RENDERED_BROWSER', 'EMBEDDED_JSON', 'MANUAL_INPUT'])],
            'selection_stage' => ['nullable', Rule::in(['PRE_MAPPING', 'POST_MAPPING'])],
            'supports_partial' => ['nullable', 'boolean'],
            'requires_browser' => ['nullable', 'boolean'],
            'shadow_enabled' => ['nullable', 'boolean'],
            'cutover_enabled' => ['nullable', 'boolean'],
            'revision_reason' => ['nullable', 'string', 'max:255'],
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
                'fetch_config_json' => $this->parseJsonInput($payload['fetch_config_json'] ?? null, 'fetch_config_json'),
                'selection_config_json' => $this->parseJsonInput($payload['selection_config_json'] ?? null, 'selection_config_json'),
                'readiness_config_json' => $this->parseJsonInput($payload['readiness_config_json'] ?? null, 'readiness_config_json'),
                'retry_policy_json' => $this->parseJsonInput($payload['retry_policy_json'] ?? null, 'retry_policy_json'),
                'pipeline_version' => strtoupper((string) ($validated['pipeline_version'] ?? 'LEGACY')),
                'fetch_strategy' => strtoupper((string) ($validated['fetch_strategy'] ?? 'JSON_HTTP')),
                'selection_stage' => strtoupper((string) ($validated['selection_stage'] ?? 'POST_MAPPING')),
                'supports_partial' => (bool) ($validated['supports_partial'] ?? false),
                'requires_browser' => (bool) ($validated['requires_browser'] ?? false),
                'shadow_enabled' => (bool) ($validated['shadow_enabled'] ?? false),
                'cutover_enabled' => (bool) ($validated['cutover_enabled'] ?? false),
            ]);

            $compiled = null;
            if (strtoupper((string) $source->pipeline_version) !== CompiledSourcePipelineData::VERSION_LEGACY
                || (bool) $source->shadow_enabled
                || (bool) $source->cutover_enabled) {
                $compiled = (new SourcePipelineConfigCompiler())->compile($this->buildPipelinePayload($source));
            }

            if ($source->cutover_enabled && $this->shouldEnforceFixtureGate() && ! $this->hasFixtureSet($source)) {
                throw new InvalidArgumentException('เปิด cutover ไม่ได้: ยังไม่พบ fixture test สำหรับ source นี้ (required only in local/testing)');
            }

            $source->save();
            $this->saveRevision($source, (string) ($validated['revision_reason'] ?? ''), $compiled);

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

    public function previewConfig(Request $request): JsonResponse
    {
        try {
            $payload = (array) $request->input('data', []);
            $compiled = (new SourcePipelineConfigCompiler())->compile($this->buildPreviewPayload($payload));

            return $this->sendResponse([
                'compiled' => $compiled->toArray(),
            ], 'Preview config สำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('Preview config ไม่สำเร็จ: ' . $e->getMessage(), 422);
        }
    }

    public function validateConfig(Request $request): JsonResponse
    {
        try {
            $payload = (array) $request->input('data', []);
            (new SourcePipelineConfigCompiler())->compile($this->buildPreviewPayload($payload));

            return $this->sendSuccess('Validate config สำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('Validate config ไม่สำเร็จ: ' . $e->getMessage(), 422);
        }
    }

    public function validateCutover(Request $request): JsonResponse
    {
        try {
            $payload = (array) $request->input('data', []);
            $compiled = (new SourcePipelineConfigCompiler())->compile($this->buildPreviewPayload($payload));
            $sourceId = (int) ($request->input('id') ?: 0);

            if ($compiled->pipelineVersion() !== CompiledSourcePipelineData::VERSION_V2_CUTOVER && ! (bool) ($payload['cutover_enabled'] ?? false)) {
                throw new InvalidArgumentException('source ยังไม่ถูกตั้งเป็น V2_CUTOVER/cutover_enabled');
            }

            if ($sourceId > 0) {
                $source = LottoResultSource::query()->find($sourceId);
                if (! $source) {
                    throw new InvalidArgumentException('ไม่พบ source สำหรับ validate cutover');
                }
                if ($this->shouldEnforceFixtureGate() && ! $this->hasFixtureSet($source)) {
                    throw new InvalidArgumentException('ยังไม่พบ fixture test สำหรับ source นี้ (required only in local/testing)');
                }
            }

            if (! $this->shouldEnforceFixtureGate()) {
                $source = $this->buildSourceForLiveValidation($payload, $sourceId);
                $draw = $this->resolveValidationDraw((int) ($payload['market_id'] ?? 0), $source);
                $expectedDrawDate = trim((string) ($payload['expected_draw_date'] ?? optional($draw->draw_date)->format('Y-m-d')));

                $runResult = (new LottoResultPipelineRunner())->run($draw, $source, [
                    'run_id' => 'cutover_validate_' . now()->format('YmdHisv'),
                    'expected_draw_date' => $expectedDrawDate !== '' ? $expectedDrawDate : null,
                ]);

                // Production live check should not fail only because current source payload date
                // is temporarily out of sync with latest draw date in the backoffice.
                if ((string) ($runResult['status'] ?? '') !== 'VALID'
                    && (string) ($runResult['error_code'] ?? '') === 'NO_CANDIDATE_MATCHES_EXPECTED_DRAW_DATE'
                ) {
                    $runResult = (new LottoResultPipelineRunner())->run($draw, $source, [
                        'run_id' => 'cutover_validate_retry_' . now()->format('YmdHisv'),
                        'expected_draw_date' => '',
                    ]);
                }

                if ((string) ($runResult['status'] ?? '') !== 'VALID') {
                    $errorCode = (string) ($runResult['error_code'] ?? 'VALIDATION_ERROR');
                    $errorStage = (string) ($runResult['error_stage'] ?? 'READINESS');
                    throw new InvalidArgumentException('live validate ไม่ผ่าน: ' . $errorCode . ' @ ' . $errorStage);
                }
            }

            return $this->sendSuccess('Validate cutover สำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('Validate cutover ไม่สำเร็จ: ' . $e->getMessage(), 422);
        }
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
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function buildPreviewPayload(array $payload): array
    {
        return [
            'pipeline_version' => strtoupper($this->stringValue($payload['pipeline_version'] ?? 'LEGACY')),
            'fetch_strategy' => strtoupper($this->stringValue($payload['fetch_strategy'] ?? 'JSON_HTTP')),
            'fetch_config_json' => (array) ($this->parseJsonInput($payload['fetch_config_json'] ?? null, 'fetch_config_json') ?? []),
            'endpoint_url' => $this->stringValue($payload['endpoint_url'] ?? ''),
            'http_method' => strtoupper($this->stringValue($payload['http_method'] ?? 'GET')),
            'request_headers_json' => (array) ($this->parseJsonInput($payload['request_headers_json'] ?? null, 'request_headers_json') ?? []),
            'request_query_template_json' => (array) ($this->parseJsonInput($payload['request_query_template_json'] ?? null, 'request_query_template_json') ?? []),
            'request_body_template_json' => (array) ($this->parseJsonInput($payload['request_body_template_json'] ?? null, 'request_body_template_json') ?? []),
            'timeout_seconds' => (int) ($payload['timeout_seconds'] ?? 10),
            'parser_type' => strtoupper($this->stringValue($payload['parser_type'] ?? 'JSON_PATH')),
            'parser_config_json' => (array) ($this->parseJsonInput($payload['parser_config_json'] ?? null, 'parser_config_json') ?? []),
            'mapping_config_json' => (array) ($this->parseJsonInput($payload['mapping_config_json'] ?? null, 'mapping_config_json') ?? []),
            'selection_config_json' => (array) ($this->parseJsonInput($payload['selection_config_json'] ?? null, 'selection_config_json') ?? []),
            'validation_config_json' => (array) ($this->parseJsonInput($payload['validation_config_json'] ?? null, 'validation_config_json') ?? []),
            'readiness_config_json' => (array) ($this->parseJsonInput($payload['readiness_config_json'] ?? null, 'readiness_config_json') ?? []),
            'selection_stage' => strtoupper($this->stringValue($payload['selection_stage'] ?? 'POST_MAPPING')),
            'supports_partial' => (bool) ($payload['supports_partial'] ?? false),
            'shadow_enabled' => (bool) ($payload['shadow_enabled'] ?? false),
            'cutover_enabled' => (bool) ($payload['cutover_enabled'] ?? false),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function buildPipelinePayload(LottoResultSource $source): array
    {
        return [
            'pipeline_version' => (string) ($source->pipeline_version ?: 'LEGACY'),
            'fetch_strategy' => (string) ($source->fetch_strategy ?: 'JSON_HTTP'),
            'fetch_config_json' => (array) ($source->fetch_config_json ?? []),
            'endpoint_url' => (string) $source->endpoint_url,
            'http_method' => (string) $source->http_method,
            'request_headers_json' => (array) ($source->request_headers_json ?? []),
            'request_query_template_json' => (array) ($source->request_query_template_json ?? []),
            'request_body_template_json' => (array) ($source->request_body_template_json ?? []),
            'timeout_seconds' => (int) $source->timeout_seconds,
            'parser_type' => (string) ($source->parser_type ?: 'JSON_PATH'),
            'parser_config_json' => (array) ($source->parser_config_json ?? []),
            'mapping_config_json' => (array) ($source->mapping_config_json ?? []),
            'selection_config_json' => (array) ($source->selection_config_json ?? []),
            'validation_config_json' => (array) ($source->validation_config_json ?? []),
            'readiness_config_json' => (array) ($source->readiness_config_json ?? []),
            'selection_stage' => (string) ($source->selection_stage ?: 'POST_MAPPING'),
            'supports_partial' => (bool) $source->supports_partial,
            'shadow_enabled' => (bool) $source->shadow_enabled,
            'cutover_enabled' => (bool) $source->cutover_enabled,
        ];
    }

    private function hasFixtureSet(LottoResultSource $source): bool
    {
        $fixtureKey = 'source_' . (int) $source->id;
        $fixturePath = base_path('tests/Fixtures/Lotto/V2/' . $fixtureKey);

        return is_dir($fixturePath) && count((array) glob($fixturePath . '/*')) > 0;
    }

    private function shouldEnforceFixtureGate(): bool
    {
        return app()->environment(['local', 'testing']);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function buildSourceForLiveValidation(array $payload, int $sourceId): LottoResultSource
    {
        $source = $sourceId > 0
            ? LottoResultSource::query()->find($sourceId)
            : null;

        if (! $source instanceof LottoResultSource) {
            $source = new LottoResultSource();
        }

        $source->forceFill([
            'id' => $source->id ?: $sourceId,
            'market_id' => (int) ($payload['market_id'] ?? $source->market_id),
            'source_type' => $this->stringValue($payload['source_type'] ?? $source->source_type ?? 'api'),
            'endpoint_url' => $this->stringValue($payload['endpoint_url'] ?? $source->endpoint_url ?? ''),
            'http_method' => strtoupper($this->stringValue($payload['http_method'] ?? $source->http_method ?? 'GET')),
            'timeout_seconds' => (int) ($payload['timeout_seconds'] ?? $source->timeout_seconds ?? 10),
            'parser_type' => strtoupper($this->stringValue($payload['parser_type'] ?? $source->parser_type ?? 'JSON_PATH')),
            'pipeline_version' => strtoupper($this->stringValue($payload['pipeline_version'] ?? $source->pipeline_version ?? CompiledSourcePipelineData::VERSION_V2_CUTOVER)),
            'fetch_strategy' => strtoupper($this->stringValue($payload['fetch_strategy'] ?? $source->fetch_strategy ?? 'JSON_HTTP')),
            'selection_stage' => strtoupper($this->stringValue($payload['selection_stage'] ?? $source->selection_stage ?? 'POST_MAPPING')),
            'supports_partial' => (bool) ($payload['supports_partial'] ?? $source->supports_partial ?? false),
            'requires_browser' => (bool) ($payload['requires_browser'] ?? $source->requires_browser ?? false),
            'shadow_enabled' => (bool) ($payload['shadow_enabled'] ?? $source->shadow_enabled ?? false),
            'cutover_enabled' => (bool) ($payload['cutover_enabled'] ?? $source->cutover_enabled ?? true),
            'request_headers_json' => $this->parseJsonInput($payload['request_headers_json'] ?? $source->request_headers_json ?? null, 'request_headers_json') ?? [],
            'request_query_template_json' => $this->parseJsonInput($payload['request_query_template_json'] ?? $source->request_query_template_json ?? null, 'request_query_template_json') ?? [],
            'request_body_template_json' => $this->parseJsonInput($payload['request_body_template_json'] ?? $source->request_body_template_json ?? null, 'request_body_template_json') ?? [],
            'parser_config_json' => $this->parseJsonInput($payload['parser_config_json'] ?? $source->parser_config_json ?? null, 'parser_config_json') ?? [],
            'mapping_config_json' => $this->parseJsonInput($payload['mapping_config_json'] ?? $source->mapping_config_json ?? null, 'mapping_config_json') ?? [],
            'validation_config_json' => $this->parseJsonInput($payload['validation_config_json'] ?? $source->validation_config_json ?? null, 'validation_config_json') ?? [],
            'fetch_config_json' => $this->parseJsonInput($payload['fetch_config_json'] ?? $source->fetch_config_json ?? null, 'fetch_config_json') ?? [],
            'selection_config_json' => $this->parseJsonInput($payload['selection_config_json'] ?? $source->selection_config_json ?? null, 'selection_config_json') ?? [],
            'readiness_config_json' => $this->parseJsonInput($payload['readiness_config_json'] ?? $source->readiness_config_json ?? null, 'readiness_config_json') ?? [],
            'retry_policy_json' => $this->parseJsonInput($payload['retry_policy_json'] ?? $source->retry_policy_json ?? null, 'retry_policy_json') ?? [],
        ]);

        return $source;
    }

    private function resolveValidationDraw(int $marketId, LottoResultSource $source): LottoDraw
    {
        $resolvedMarketId = $marketId > 0 ? $marketId : (int) ($source->market_id ?? 0);
        if ($resolvedMarketId <= 0) {
            throw new InvalidArgumentException('market_id จำเป็นสำหรับ live validate cutover');
        }

        $draw = LottoDraw::query()
            ->where('market_id', $resolvedMarketId)
            ->whereIn('status', ['open', 'closed', 'resulted'])
            ->orderByDesc('draw_date')
            ->orderByDesc('id')
            ->first();

        if (! $draw instanceof LottoDraw) {
            throw new InvalidArgumentException('ยังไม่พบ draw ของ market นี้สำหรับ live validate cutover');
        }

        return $draw;
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '' : $encoded;
    }

    private function saveRevision(LottoResultSource $source, string $reason, ?CompiledSourcePipelineData $compiled = null): void
    {
        if (! Schema::hasTable('lotto_result_source_revisions')) {
            return;
        }

        $snapshot = $compiled
            ? $compiled->toArray()
            : $this->buildPipelinePayload($source);
        $revisionNo = (int) LottoResultSourceRevision::query()
            ->where('source_id', (int) $source->id)
            ->max('revision_no') + 1;

        LottoResultSourceRevision::query()->create([
            'source_id' => (int) $source->id,
            'revision_no' => $revisionNo,
            'snapshot_json' => $snapshot,
            'config_hash' => hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'changed_by' => auth()->check() ? (int) auth()->id() : null,
            'reason' => trim($reason) !== '' ? trim($reason) : 'update source config',
            'created_at' => now(),
        ]);
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
