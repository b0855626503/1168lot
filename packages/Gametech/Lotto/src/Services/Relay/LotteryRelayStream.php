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
        return (string) Redis::connection($connection)->xadd($stream, '*', $payload, $maxLen, true);
    }

    public function ensureConsumerGroup(string $connection, string $stream, string $group): void
    {
        try {
            Redis::connection($connection)->command('XGROUP', ['CREATE', $stream, $group, '$', 'MKSTREAM']);
        } catch (\Throwable $exception) {
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
        $response = Redis::connection($connection)->command('XREADGROUP', [
            'GROUP', $group, $consumer,
            'COUNT', (string) $count,
            'BLOCK', (string) $blockMs,
            'STREAMS', $stream, '>',
        ]);

        return $this->normalizeReadGroupResponse($response);
    }

    public function ack(string $connection, string $stream, string $group, string $id): void
    {
        Redis::connection($connection)->command('XACK', [$stream, $group, $id]);
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

    /**
     * @return array<int,array{id:string,fields:array<string,string>}>
     */
    private function normalizeReadGroupResponse(mixed $response): array
    {
        if (! is_array($response)) {
            return [];
        }

        $messages = [];
        foreach ($response as $streamEntry) {
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
