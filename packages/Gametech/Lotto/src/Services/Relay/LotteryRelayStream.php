<?php

namespace Gametech\Lotto\Services\Relay;

use Illuminate\Support\Facades\Redis;

class LotteryRelayStream
{
    /**
     * @param  array<string,string>  $payload
     */
    public function publish(string $connection, string $stream, array $payload, int $maxLen): string
    {
        $arguments = array_merge(
            [$stream, 'MAXLEN', '~', (string) $maxLen, '*'],
            $this->flattenFields($payload)
        );

        return (string) Redis::connection($connection)->command('XADD', $arguments);
    }

    public function ensureConsumerGroup(string $connection, string $stream, string $group): void
    {
        $redis = Redis::connection($connection);

        try {
            $redis->xgroup('CREATE', $stream, $group, '$', true);
        } catch (\Throwable $exception) {
            if ($this->shouldFallbackToRawCommand($exception)) {
                $redis->command('XGROUP', ['CREATE', $stream, $group, '$', 'MKSTREAM']);

                return;
            }

            if (! str_contains(strtoupper($exception->getMessage()), 'BUSYGROUP')) {
                throw $exception;
            }
        }
    }

    /**
     * @return array<int,array{id:string,fields:array<string,string>}>
     */
    public function readGroup(
        string $connection,
        string $stream,
        string $group,
        string $consumer,
        int $count,
        int $blockMs
    ): array {
        $redis = Redis::connection($connection);

        try {
            $response = $redis->xreadgroup(
                $group,
                $consumer,
                [$stream => '>'],
                max(1, $count),
                max(0, $blockMs)
            );

            return $this->normalizeReadGroupResponse($response);
        } catch (\Throwable $exception) {
            if (! $this->shouldFallbackToRawCommand($exception)) {
                throw $exception;
            }
        }

        $response = $redis->command('XREADGROUP', [
            'GROUP', $group, $consumer,
            'COUNT', (string) max(1, $count),
            'BLOCK', (string) max(0, $blockMs),
            'STREAMS', $stream, '>',
        ]);

        return $this->normalizeReadGroupResponse($response);
    }

    public function ack(string $connection, string $stream, string $group, string $id): void
    {
        $redis = Redis::connection($connection);

        try {
            $redis->xack($stream, $group, [$id]);

            return;
        } catch (\Throwable $exception) {
            if (! $this->shouldFallbackToRawCommand($exception)) {
                throw $exception;
            }
        }

        $redis->command('XACK', [$stream, $group, $id]);
    }

    public function get(string $connection, string $key): ?string
    {
        $value = Redis::connection($connection)->get($key);

        return is_string($value) ? $value : null;
    }

    public function set(string $connection, string $key, string $value): void
    {
        Redis::connection($connection)->set($key, $value);
    }

    /**
     * @param  array<string,string>  $payload
     * @return string[]
     */
    private function flattenFields(array $payload): array
    {
        $flattened = [];

        foreach ($payload as $field => $value) {
            $flattened[] = (string) $field;
            $flattened[] = (string) $value;
        }

        return $flattened;
    }

    private function shouldFallbackToRawCommand(\Throwable $exception): bool
    {
        $message = strtoupper($exception->getMessage());

        return str_contains($message, 'XREADGROUP')
            || str_contains($message, 'ARGUMENT')
            || str_contains($message, 'EXPECTS AT MOST')
            || str_contains($message, 'WRONG NUMBER OF ARGUMENTS');
    }

    /**
     * @return array<int,array{id:string,fields:array<string,string>}>
     */
    private function normalizeReadGroupResponse(mixed $response): array
    {
        if (! is_array($response)) {
            return [];
        }

        $messages = [];

        foreach ($response as $streamKey => $streamEntry) {
            if (is_string($streamKey) && is_array($streamEntry)) {
                foreach ($streamEntry as $messageId => $fields) {
                    if (! is_array($fields)) {
                        continue;
                    }

                    $messages[] = [
                        'id' => (string) $messageId,
                        'fields' => $this->normalizeFields($fields),
                    ];
                }

                continue;
            }

            if (! is_array($streamEntry)) {
                continue;
            }

            if ($this->looksLikePredisStreamEntry($streamEntry)) {
                foreach (($streamEntry[1] ?? []) as $message) {
                    if (! is_array($message) || count($message) < 2) {
                        continue;
                    }

                    $messages[] = [
                        'id' => (string) $message[0],
                        'fields' => $this->normalizeFields($message[1] ?? []),
                    ];
                }

                continue;
            }

            foreach ($streamEntry as $messageId => $fields) {
                if (! is_array($fields)) {
                    continue;
                }

                $messages[] = [
                    'id' => (string) $messageId,
                    'fields' => $this->normalizeFields($fields),
                ];
            }
        }

        return $messages;
    }

    /**
     * @param  array<int|string,mixed>  $entry
     */
    private function looksLikePredisStreamEntry(array $entry): bool
    {
        return count($entry) === 2
            && isset($entry[0])
            && isset($entry[1])
            && is_string($entry[0])
            && is_array($entry[1]);
    }

    /**
     * @param  array<int|string,mixed>  $fields
     * @return array<string,string>
     */
    private function normalizeFields(array $fields): array
    {
        $normalized = [];

        if (array_is_list($fields)) {
            for ($index = 0; $index < count($fields); $index += 2) {
                $field = $fields[$index] ?? null;
                $value = $fields[$index + 1] ?? null;

                if ($field === null) {
                    continue;
                }

                $normalized[(string) $field] = (string) $value;
            }

            return $normalized;
        }

        foreach ($fields as $field => $value) {
            $normalized[(string) $field] = (string) $value;
        }

        return $normalized;
    }
}
