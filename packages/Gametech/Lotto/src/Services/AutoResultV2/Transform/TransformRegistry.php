<?php

namespace Gametech\Lotto\Services\AutoResultV2\Transform;

use Illuminate\Support\Carbon;

class TransformRegistry
{
    /** @var array<string, callable> */
    private array $transforms = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    public function register(string $name, callable $transform): self
    {
        $this->transforms[strtolower(trim($name))] = $transform;

        return $this;
    }

    public function has(string $name): bool
    {
        return array_key_exists(strtolower(trim($name)), $this->transforms);
    }

    /**
     * @param mixed $value
     * @param array<string,mixed> $context
     * @return mixed
     */
    public function apply(string $name, $value, array $context = [])
    {
        $key = strtolower(trim($name));
        if (! isset($this->transforms[$key])) {
            return $value;
        }

        return ($this->transforms[$key])($value, $context);
    }

    /** @return array<string, callable> */
    public function all(): array
    {
        return $this->transforms;
    }

    private function registerDefaults(): void
    {
        $this->register('trim', static fn ($value) => is_string($value) ? trim($value) : (is_scalar($value) ? trim((string) $value) : $value));
        $this->register('digits_only', static fn ($value) => $value === null ? null : preg_replace('/\D+/', '', (string) $value));
        $this->register('upper', static fn ($value) => $value === null ? null : mb_strtoupper(trim((string) $value)));
        $this->register('lower', static fn ($value) => $value === null ? null : mb_strtolower(trim((string) $value)));
        $this->register('left', static function ($value, array $context) {
            $len = max(0, (int) ($context['length'] ?? 0));
            $string = (string) $value;

            return $len > 0 ? mb_substr($string, 0, $len) : $string;
        });
        $this->register('right', static function ($value, array $context) {
            $len = max(0, (int) ($context['length'] ?? 0));
            $string = (string) $value;

            return $len > 0 ? mb_substr($string, -$len) : $string;
        });
        $this->register('replace', static function ($value, array $context) {
            $search = (string) ($context['search'] ?? '');
            $replace = (string) ($context['replace'] ?? '');

            return $search === '' ? $value : str_replace($search, $replace, (string) $value);
        });
        $this->register('regex_replace', static function ($value, array $context) {
            $pattern = (string) ($context['pattern'] ?? '');
            $replace = (string) ($context['replace'] ?? '');
            if ($pattern === '') {
                return $value;
            }

            return @preg_replace($pattern, $replace, (string) $value) ?? $value;
        });
        $this->register('remove_commas', static fn ($value) => str_replace(',', '', (string) $value));
        $this->register('decimal_part', static function ($value) {
            $parts = explode('.', str_replace(',', '', (string) $value));

            return $parts[1] ?? '';
        });
        $this->register('integer_part', static function ($value) {
            $parts = explode('.', str_replace(',', '', (string) $value));

            return $parts[0] ?? '';
        });
        $this->register('prefix', static function ($value, array $context) {
            $prefix = (string) ($context['value'] ?? $context['prefix'] ?? '');

            return $prefix . (string) $value;
        });
        $this->register('suffix', static function ($value, array $context) {
            $suffix = (string) ($context['value'] ?? $context['suffix'] ?? '');

            return (string) $value . $suffix;
        });
        $this->register('split', static function ($value, array $context) {
            $delimiter = (string) ($context['delimiter'] ?? ',');

            return explode($delimiter, (string) $value);
        });
        $this->register('pick_index', static function ($value, array $context) {
            if (! is_array($value)) {
                return null;
            }
            $index = (int) ($context['index'] ?? 0);

            return $value[$index] ?? null;
        });
        $this->register('concat', static function ($value, array $context) {
            if (is_array($value)) {
                $separator = (string) ($context['separator'] ?? '');

                return implode($separator, array_map(static fn ($item) => (string) $item, $value));
            }

            return (string) $value;
        });
        $this->register('date', static function ($value, array $context) {
            $raw = trim((string) $value);
            if ($raw === '') {
                return '';
            }

            $from = (string) ($context['from'] ?? $context['format'] ?? '');
            $to = (string) ($context['to'] ?? 'Y-m-d');

            try {
                $dt = $from !== '' ? Carbon::createFromFormat($from, $raw) : Carbon::parse($raw);

                return $dt->format($to);
            } catch (\Throwable $e) {
                return $value;
            }
        });
    }
}
