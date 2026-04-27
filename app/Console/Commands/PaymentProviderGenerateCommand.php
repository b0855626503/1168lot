<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PaymentProviderGeneratorV3\ApiDocAnalyzer;
use App\Services\PaymentProviderGeneratorV3\CapabilityGate;
use App\Services\PaymentProviderGeneratorV3\DocLoader;
use App\Services\PaymentProviderGeneratorV3\PaymentProviderGenerator;
use App\Services\PaymentProviderGeneratorV3\PaymentProviderInspector;
use App\Services\PaymentProviderGeneratorV3\PaymentProviderName;
use App\Services\PaymentProviderGeneratorV3\PaymentProviderPackager;
use App\Services\PaymentProviderGeneratorV3\PaymentProviderPlanner;
use App\Services\PaymentProviderGeneratorV3\PaymentProviderValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class PaymentProviderGenerateCommand extends Command
{
    protected $signature = 'payment:provider-generate
        {--provider= : Provider key, e.g. boat_pay}
        {--doc-url= : API document URL}
        {--doc-file= : Local API document file}
        {--doc-text= : Raw API document text}
        {--reference=smkpay : Reference provider}
        {--mode=dry_run : dry_run or write_files}
        {--non-interactive : Do not ask questions; fail when confirmation is required}
        {--package : Create zip package after generation}';

    protected $description = 'Generate payment provider from API document with interactive capability gate.';

    public function handle(): int
    {
        $provider = (string) ($this->option('provider') ?: '');
        if ($provider === '') {
            $provider = (string) $this->ask('Provider name เช่น boat_pay');
        }

        $name = PaymentProviderName::from($provider);
        $reference = (string) $this->option('reference');
        $mode = (string) $this->option('mode');

        if (!in_array($mode, ['dry_run', 'write_files'], true)) {
            $this->error('Invalid mode. Use dry_run or write_files.');
            return self::FAILURE;
        }

        $loader = app(DocLoader::class);
        $doc = $loader->load(
            docUrl: $this->option('doc-url') ? (string) $this->option('doc-url') : null,
            docFile: $this->option('doc-file') ? (string) $this->option('doc-file') : null,
            docText: $this->option('doc-text') ? (string) $this->option('doc-text') : null,
        );

        if (trim($doc['content']) === '') {
            $this->error('API document content is empty. Provide --doc-url, --doc-file, or --doc-text.');
            return self::FAILURE;
        }

        $this->info('1) Inspect reference provider: ' . $reference);
        $inspect = app(PaymentProviderInspector::class)->inspect($reference);

        $this->info('2) Analyze API document');
        $analysis = app(ApiDocAnalyzer::class)->analyze($doc['content']);

        $this->line(json_encode([
            'source' => $doc['source'],
            'capabilities' => $analysis['capabilities'],
            'auth' => $analysis['auth'],
            'endpoints' => $analysis['endpoints'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info('3) Capability gate');
        $gate = app(CapabilityGate::class)->evaluate($analysis);

        $decisions = [];
        foreach ($gate['questions'] as $question) {
            if ($this->option('non-interactive')) {
                $this->error('Confirmation required: ' . $question['message']);
                return self::FAILURE;
            }

            $choice = $this->choice(
                $question['message'],
                $question['options'],
                $question['default']
            );

            if ($choice === 'abort') {
                $this->warn('Aborted by user.');
                return self::FAILURE;
            }

            $decisions[$question['key']] = $choice;
        }

        $this->info('4) Build plan');
        $plan = app(PaymentProviderPlanner::class)->plan($name, $reference, $inspect, $analysis, $decisions);

        $this->info('5) Generate: ' . $mode);
        $result = app(PaymentProviderGenerator::class)->generate($name, $analysis, $plan, $decisions, $mode);

        $this->info('6) Validate');
        $validation = app(PaymentProviderValidator::class)->validate($result['manifest']);

        $outputDir = storage_path('app/mcp/payment-providers/' . $name->key);
        File::ensureDirectoryExists($outputDir);
        File::put($outputDir . '/analysis.json', json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::put($outputDir . '/plan.json', json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::put($outputDir . '/manifest.json', json_encode($result['manifest'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::put($outputDir . '/validation.json', json_encode($validation, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if (!$validation['passed']) {
            $this->error('Validation failed. See: ' . $outputDir . '/validation.json');
            foreach ($validation['errors'] as $error) {
                $this->line('- ' . $error);
            }
            return self::FAILURE;
        }

        if ($this->option('package')) {
            $package = app(PaymentProviderPackager::class)->package($name, $result['manifest']);
            $this->info('Package created: ' . $package['zip_path']);
        }

        $this->info('Done.');
        $this->line('Output: ' . $outputDir);

        return self::SUCCESS;
    }
}
