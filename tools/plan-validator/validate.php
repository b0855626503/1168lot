#!/usr/bin/env php
<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

final class PlanComplianceValidator
{
    /** @var array<string, mixed> */
    private array $options;

    /** @var array<string, mixed> */
    private array $contract;

    /** @var array<int, string> */
    private array $changedFiles = [];

    /** @var array<int, array<string, mixed>> */
    private array $checks = [];

    /** @var array<int, string> */
    private array $runtimeChangedFiles = [];

    public function __construct(array $options)
    {
        $this->options = $options;
        $this->contract = [];
    }

    public function run(): int
    {
        $this->contract = $this->loadContract((string) $this->options['contract']);
        $issue = (string) ($this->options['issue'] ?? $this->contract['issue'] ?? 'UNKNOWN');

        $this->changedFiles = $this->readChangedFiles((string) $this->options['base'], (string) $this->options['head']);
        sort($this->changedFiles);

        $this->runtimeChangedFiles = array_values(array_filter(
            $this->changedFiles,
            static fn (string $file): bool => ! self::isDocsOrTestsFile($file)
        ));

        $this->checks[] = $this->checkChangedFileScope();
        $this->checks[] = $this->checkRequiredTerms();
        $this->checks[] = $this->checkForbiddenTerms();
        $this->checks[] = $this->checkRequiredTests();
        $this->checks[] = $this->checkRequiredTestCommands();
        $this->checks[] = $this->checkTestOutput();
        $this->checks[] = $this->checkHandoff();

        $report = $this->buildReport($issue);
        $this->writeReport($issue, $report);

        if (($this->options['json'] ?? false) === true) {
            echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
        }

        $this->printHumanSummary($issue, $report);

        return $this->exitCodeFromStatus((string) $report['status'], (bool) ($this->options['strict'] ?? false));
    }

    /** @return array<string, mixed> */
    private function loadContract(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('Contract file not found: '.$path);
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $raw = (string) file_get_contents($path);

        if ($extension === 'json') {
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                throw new RuntimeException('Invalid JSON contract: '.$path);
            }

            return $this->normalizeContract($decoded);
        }

        if (class_exists('Symfony\\Component\\Yaml\\Yaml')) {
            /** @var array<string, mixed> $parsed */
            $parsed = Yaml::parse($raw);

            return $this->normalizeContract($parsed);
        }

        return $this->normalizeContract($this->parseSimpleYaml($raw));
    }

    /** @param array<string, mixed> $contract */
    private function normalizeContract(array $contract): array
    {
        $defaults = [
            'issue' => null,
            'summary' => null,
            'allowed_files' => [],
            'forbidden_files' => [],
            'required_terms' => [],
            'forbidden_terms' => [],
            'required_tests' => [],
            'required_test_commands' => [],
            'handoff_required_sections' => [],
            'outside_allowed_runtime_status' => 'fail',
            'outside_allowed_docs_status' => 'warn',
            'forbidden_terms_docs_tests_status' => 'warn',
            'require_test_changes' => false,
            'require_test_output' => false,
            'require_handoff' => false,
            'strict_forbidden_terms' => false,
        ];

        $normalized = array_merge($defaults, $contract);

        foreach (['allowed_files', 'forbidden_files', 'required_terms', 'forbidden_terms', 'required_tests', 'required_test_commands', 'handoff_required_sections'] as $listKey) {
            if (! is_array($normalized[$listKey])) {
                $normalized[$listKey] = [];
            }
            $normalized[$listKey] = array_values(array_map('strval', $normalized[$listKey]));
        }

        return $normalized;
    }

    /** @return array<string, mixed> */
    private function parseSimpleYaml(string $raw): array
    {
        $result = [];
        $currentKey = null;

        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $trimmed = trim((string) $line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (preg_match('/^([A-Za-z0-9_]+):\s*(.*)$/', $trimmed, $matches) === 1) {
                $key = $matches[1];
                $value = trim($matches[2]);
                if ($value === '') {
                    $result[$key] = [];
                    $currentKey = $key;
                } else {
                    $result[$key] = $this->parseYamlScalar($value);
                    $currentKey = null;
                }

                continue;
            }

            if ($currentKey !== null && preg_match('/^-\s*(.*)$/', $trimmed, $matches) === 1) {
                $result[$currentKey][] = $this->parseYamlScalar(trim($matches[1]));
            }
        }

        return $result;
    }

    /** @return bool|int|float|string */
    private function parseYamlScalar(string $value)
    {
        $value = trim($value);
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }

        if ($value === 'true') {
            return true;
        }

        if ($value === 'false') {
            return false;
        }

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }

    /** @return array<int, string> */
    private function readChangedFiles(string $base, string $head): array
    {
        $command = sprintf('git diff --name-only %s...%s', escapeshellarg($base), escapeshellarg($head));
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException('Cannot read git diff for base/head.');
        }

        return array_values(array_filter(array_map('trim', $output), static fn (string $line): bool => $line !== ''));
    }

    /** @return array<string, mixed> */
    private function checkChangedFileScope(): array
    {
        $allowedPatterns = $this->contract['allowed_files'];
        $forbiddenPatterns = $this->contract['forbidden_files'];
        $docsStatus = (string) $this->contract['outside_allowed_docs_status'];
        $runtimeStatus = (string) $this->contract['outside_allowed_runtime_status'];

        $messages = [];
        $status = 'pass';

        foreach ($this->changedFiles as $file) {
            if ($this->matchesAny($file, $forbiddenPatterns)) {
                $status = $this->mergeStatus($status, 'fail');
                $messages[] = 'Forbidden file changed: '.$file;

                continue;
            }

            if (! $this->matchesAny($file, $allowedPatterns)) {
                $isDocsOrTests = self::isDocsOrTestsFile($file);
                $outsideStatus = $isDocsOrTests ? $docsStatus : $runtimeStatus;
                $status = $this->mergeStatus($status, $outsideStatus);
                $messages[] = sprintf('File outside allowed scope (%s): %s', $outsideStatus, $file);
            }
        }

        return [
            'name' => 'changed_file_scope',
            'status' => $status,
            'messages' => $messages,
        ];
    }

    /** @return array<string, mixed> */
    private function checkRequiredTerms(): array
    {
        $requiredTerms = $this->contract['required_terms'];
        $messages = [];
        $status = 'pass';

        $contents = $this->readChangedFilesContents();

        foreach ($requiredTerms as $term) {
            $found = false;
            foreach ($contents as $content) {
                if (str_contains($content, $term)) {
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                $status = 'fail';
                $messages[] = 'Required term missing: '.$term;
            }
        }

        return [
            'name' => 'required_terms',
            'status' => $status,
            'messages' => $messages,
        ];
    }

    /** @return array<string, mixed> */
    private function checkForbiddenTerms(): array
    {
        $forbiddenTerms = $this->contract['forbidden_terms'];
        $strictForDocs = (bool) $this->contract['strict_forbidden_terms'];
        $docsStatus = $strictForDocs ? 'fail' : (string) $this->contract['forbidden_terms_docs_tests_status'];

        $messages = [];
        $status = 'pass';

        foreach ($this->changedFiles as $file) {
            if (! is_file($file)) {
                continue;
            }

            $content = (string) file_get_contents($file);
            foreach ($forbiddenTerms as $term) {
                if (! str_contains($content, $term)) {
                    continue;
                }

                if (self::isDocsOrTestsFile($file)) {
                    $status = $this->mergeStatus($status, $docsStatus);
                    $messages[] = sprintf('Forbidden term found in docs/tests (%s): %s in %s', $docsStatus, $term, $file);
                } else {
                    $status = 'fail';
                    $messages[] = sprintf('Forbidden term found: %s in %s', $term, $file);
                }
            }
        }

        return [
            'name' => 'forbidden_terms',
            'status' => $status,
            'messages' => $messages,
        ];
    }

    /** @return array<string, mixed> */
    private function checkRequiredTests(): array
    {
        $requiredTests = $this->contract['required_tests'];
        $requireChanges = (bool) $this->contract['require_test_changes'];

        $messages = [];
        $status = 'pass';

        foreach ($requiredTests as $testFile) {
            if (! is_file($testFile)) {
                $status = 'fail';
                $messages[] = 'Required test file missing: '.$testFile;
            }
        }

        if ($requireChanges === true && $requiredTests !== []) {
            $changedTest = false;
            foreach ($requiredTests as $testFile) {
                if (in_array($testFile, $this->changedFiles, true)) {
                    $changedTest = true;
                    break;
                }
            }

            if (! $changedTest) {
                $status = $this->mergeStatus($status, 'warn');
                $messages[] = 'No required test file changed.';
            }
        }

        return [
            'name' => 'required_tests',
            'status' => $status,
            'messages' => $messages,
        ];
    }

    /** @return array<string, mixed> */
    private function checkRequiredTestCommands(): array
    {
        $messages = [];
        $status = 'pass';

        foreach ($this->contract['required_test_commands'] as $command) {
            if (! is_string($command) || trim($command) === '') {
                $status = 'fail';
                $messages[] = 'Invalid required test command value in contract.';
            }
        }

        return [
            'name' => 'required_test_commands',
            'status' => $status,
            'messages' => $messages,
        ];
    }

    /** @return array<string, mixed> */
    private function checkTestOutput(): array
    {
        $path = $this->options['test_output'] ?? null;
        $require = (bool) $this->contract['require_test_output'];
        $messages = [];
        $status = 'pass';

        if ($path === null || $path === '') {
            if ($require) {
                $status = 'fail';
                $messages[] = 'Test output path is required by contract but not provided.';
            } else {
                $status = 'warn';
                $messages[] = 'Test output path not provided.';
            }

            return [
                'name' => 'test_output',
                'status' => $status,
                'messages' => $messages,
            ];
        }

        if (! is_file((string) $path)) {
            $status = $require ? 'fail' : 'warn';
            $messages[] = 'Test output file not found: '.$path;

            return [
                'name' => 'test_output',
                'status' => $status,
                'messages' => $messages,
            ];
        }

        $content = (string) file_get_contents((string) $path);
        $failureMarkers = ['FAILURES!', 'FAILED', 'Error:', 'Exception', 'Segmentation fault'];

        foreach ($failureMarkers as $marker) {
            if (str_contains($content, $marker)) {
                $status = 'fail';
                $messages[] = 'Failure marker detected in test output: '.$marker;
                break;
            }
        }

        return [
            'name' => 'test_output',
            'status' => $status,
            'messages' => $messages,
        ];
    }

    /** @return array<string, mixed> */
    private function checkHandoff(): array
    {
        $path = $this->options['handoff'] ?? null;
        $requiredSections = $this->contract['handoff_required_sections'];
        $requireHandoff = (bool) $this->contract['require_handoff'];

        $messages = [];
        $status = 'pass';

        if ($path === null || $path === '') {
            $status = $requireHandoff ? 'fail' : 'warn';
            $messages[] = 'Handoff file path not provided.';

            return [
                'name' => 'handoff_sections',
                'status' => $status,
                'messages' => $messages,
            ];
        }

        if (! is_file((string) $path)) {
            $status = $requireHandoff ? 'fail' : 'warn';
            $messages[] = 'Handoff file not found: '.$path;

            return [
                'name' => 'handoff_sections',
                'status' => $status,
                'messages' => $messages,
            ];
        }

        $content = strtolower((string) file_get_contents((string) $path));

        foreach ($requiredSections as $section) {
            $needle = strtolower((string) $section);
            if ($needle === '') {
                continue;
            }

            if (! str_contains($content, $needle)) {
                $status = $this->mergeStatus($status, $requireHandoff ? 'fail' : 'warn');
                $messages[] = 'Missing handoff section: '.$section;
            }
        }

        return [
            'name' => 'handoff_sections',
            'status' => $status,
            'messages' => $messages,
        ];
    }

    /** @return array<int, string> */
    private function readChangedFilesContents(): array
    {
        $contents = [];

        foreach ($this->changedFiles as $file) {
            if (! is_file($file)) {
                continue;
            }

            $contents[] = (string) file_get_contents($file);
        }

        return $contents;
    }

    /** @param array<int, string> $patterns */
    private function matchesAny(string $file, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (self::globMatch($file, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private static function globMatch(string $path, string $pattern): bool
    {
        $escaped = preg_quote($pattern, '/');
        $escaped = str_replace('\\*\\*', '.*', $escaped);
        $escaped = str_replace('\\*', '[^/]*', $escaped);

        return preg_match('/^'.$escaped.'$/', $path) === 1;
    }

    private static function isDocsOrTestsFile(string $path): bool
    {
        if (str_starts_with($path, 'docs/') || str_starts_with($path, 'tests/')) {
            return true;
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['md', 'txt'], true);
    }

    private function mergeStatus(string $current, string $incoming): string
    {
        $rank = ['pass' => 0, 'warn' => 1, 'fail' => 2];

        return ($rank[$incoming] ?? 0) > ($rank[$current] ?? 0) ? $incoming : $current;
    }

    /** @return array<string, mixed> */
    private function buildReport(string $issue): array
    {
        $failureCount = 0;
        $warningCount = 0;
        $status = 'pass';

        foreach ($this->checks as $check) {
            if ($check['status'] === 'fail') {
                $failureCount++;
            }
            if ($check['status'] === 'warn') {
                $warningCount++;
            }
            $status = $this->mergeStatus($status, (string) $check['status']);
        }

        return [
            'issue' => $issue,
            'status' => $status,
            'summary' => [
                'changed_files' => count($this->changedFiles),
                'failures' => $failureCount,
                'warnings' => $warningCount,
            ],
            'checks' => $this->checks,
        ];
    }

    /** @param array<string, mixed> $report */
    private function writeReport(string $issue, array $report): void
    {
        $outputDir = 'storage/plan-validator';
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $path = sprintf('%s/%s-report.json', $outputDir, $issue);
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    /** @param array<string, mixed> $report */
    private function printHumanSummary(string $issue, array $report): void
    {
        echo 'Plan Compliance Report: '.$issue.PHP_EOL;
        echo 'Status: '.$report['status'].PHP_EOL;

        $failures = [];
        $warnings = [];

        foreach ($this->checks as $check) {
            foreach ($check['messages'] as $message) {
                if ($check['status'] === 'fail') {
                    $failures[] = sprintf('%s: %s', $check['name'], $message);
                } elseif ($check['status'] === 'warn') {
                    $warnings[] = sprintf('%s: %s', $check['name'], $message);
                }
            }
        }

        echo 'Failures:'.PHP_EOL;
        if ($failures === []) {
            echo '- none'.PHP_EOL;
        } else {
            foreach ($failures as $line) {
                echo '- '.$line.PHP_EOL;
            }
        }

        echo 'Warnings:'.PHP_EOL;
        if ($warnings === []) {
            echo '- none'.PHP_EOL;
        } else {
            foreach ($warnings as $line) {
                echo '- '.$line.PHP_EOL;
            }
        }
    }

    private function exitCodeFromStatus(string $status, bool $strict): int
    {
        if ($status === 'fail') {
            return 1;
        }

        if ($status === 'warn' && $strict) {
            return 1;
        }

        return 0;
    }
}

/** @return array<string, mixed> */
function parseOptions(array $argv): array
{
    $longOpts = [
        'issue:',
        'base:',
        'head:',
        'contract:',
        'test-output::',
        'handoff::',
        'json',
        'strict',
    ];

    $options = getopt('', $longOpts);

    $required = ['issue', 'base', 'head', 'contract'];
    foreach ($required as $key) {
        if (! isset($options[$key]) || trim((string) $options[$key]) === '') {
            fwrite(STDERR, 'Missing required option --'.$key.PHP_EOL);
            exit(2);
        }
    }

    return [
        'issue' => (string) $options['issue'],
        'base' => (string) $options['base'],
        'head' => (string) $options['head'],
        'contract' => (string) $options['contract'],
        'test_output' => isset($options['test-output']) ? (string) $options['test-output'] : null,
        'handoff' => isset($options['handoff']) ? (string) $options['handoff'] : null,
        'json' => isset($options['json']),
        'strict' => isset($options['strict']),
    ];
}

try {
    $options = parseOptions($argv);
    $validator = new PlanComplianceValidator($options);
    exit($validator->run());
} catch (Throwable $exception) {
    fwrite(STDERR, 'Validator error: '.$exception->getMessage().PHP_EOL);
    exit(1);
}
