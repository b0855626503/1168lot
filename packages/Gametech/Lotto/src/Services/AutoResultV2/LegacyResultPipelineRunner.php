<?php

namespace Gametech\Lotto\Services\AutoResultV2;

use Gametech\Lotto\Exceptions\ResultParseException;
use Gametech\Lotto\Exceptions\ResultValidationException;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoResultSource;
use Gametech\Lotto\Services\AutoResult\ResultCandidateSelector;
use Gametech\Lotto\Services\AutoResult\ResultFetcher;
use Gametech\Lotto\Services\AutoResult\ResultMapper;
use Gametech\Lotto\Services\AutoResult\ResultParseContext;
use Gametech\Lotto\Services\AutoResult\ResultParser;
use Gametech\Lotto\Services\AutoResult\ResultRequestBuilder;
use Gametech\Lotto\Services\AutoResult\ResultTransformChain;
use Gametech\Lotto\Services\AutoResult\ResultValidator;

class LegacyResultPipelineRunner
{
    public function __construct(
        private ?ResultRequestBuilder $requestBuilder = null,
        private ?ResultFetcher $fetcher = null,
        private ?ResultParser $parser = null,
        private ?ResultCandidateSelector $selector = null,
        private ?ResultMapper $mapper = null,
        private ?ResultValidator $validator = null
    ) {
        $this->requestBuilder = $this->requestBuilder ?: new ResultRequestBuilder();
        $this->fetcher = $this->fetcher ?: new ResultFetcher();
        $this->parser = $this->parser ?: new ResultParser();
        $this->selector = $this->selector ?: new ResultCandidateSelector();
        $this->mapper = $this->mapper ?: new ResultMapper(new ResultTransformChain());
        $this->validator = $this->validator ?: new ResultValidator();
    }

    /**
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function run(LottoDraw $draw, LottoResultSource $source, array $options = [], ?callable $legacyCallback = null): array
    {
        $expectedDrawDate = is_string($options['expected_draw_date'] ?? null)
            ? trim((string) $options['expected_draw_date'])
            : optional($draw->draw_date)->format('Y-m-d');

        try {
            $request = $this->requestBuilder->build($draw, $source);
            $fetched = $this->fetcher->fetch($source, $request);
            if ((string) ($fetched['status'] ?? '') === 'HTTP_ERROR') {
                return [
                    'status' => 'HTTP_ERROR',
                    'error_code' => 'FETCH_FAILED',
                    'fetch' => $fetched,
                ];
            }

            $parsed = $this->parser->parse((string) $source->parser_type, (array) ($source->parser_config_json ?? []), (string) ($fetched['response_body'] ?? ''));
            $selection = $this->selector->select(
                $parsed,
                (array) ($source->parser_config_json ?? []),
                (array) ($source->validation_config_json ?? []),
                new ResultParseContext($expectedDrawDate)
            );

            if ((string) ($selection['decision'] ?? '') !== 'selected') {
                return [
                    'status' => 'VALIDATION_ERROR',
                    'error_code' => 'NO_CANDIDATE_MATCHES_EXPECTED_DRAW_DATE',
                    'selection' => $selection,
                ];
            }

            $selectedFields = (array) ($selection['selected_candidate']['fields'] ?? []);
            $mapped = $this->mapper->map($selectedFields, (array) ($source->mapping_config_json ?? []));
            $validated = $this->validator->validate($mapped, (array) ($source->validation_config_json ?? []), $expectedDrawDate);

            return [
                'status' => 'VALID',
                'error_code' => null,
                'canonical_outcome' => $validated,
            ];
        } catch (ResultParseException $e) {
            return [
                'status' => 'PARSE_ERROR',
                'error_code' => 'FIELD_NOT_PARSED',
                'error_message' => $e->getMessage(),
            ];
        } catch (ResultValidationException $e) {
            return [
                'status' => 'VALIDATION_ERROR',
                'error_code' => str_contains($e->getMessage(), 'NOT_READY') ? 'NOT_READY_PARTIAL_RESULT' : 'REQUIRED_FIELD_MISSING',
                'error_message' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'LEGACY_ERROR',
                'error_code' => 'FETCH_FAILED',
                'error_message' => $e->getMessage(),
            ];
        }
    }
}
