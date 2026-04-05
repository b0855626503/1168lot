<?php

namespace App\Support\DataTables;

use Illuminate\Support\Collection;
use League\Fractal\TransformerAbstract;
use Traversable;

class FractalTransformerCompat
{
    /**
     * @param  iterable<mixed>  $results
     * @return array<int, array<string, mixed>>
     */
    public function transform(iterable $results, mixed $transformer, mixed $serializer = null): array
    {
        if (! $transformer instanceof TransformerAbstract && ! is_callable($transformer)) {
            return $this->normalizeRows($results);
        }

        $rows = [];

        foreach ($results as $row) {
            $rows[] = $transformer instanceof TransformerAbstract
                ? (array) $transformer->transform($row)
                : (array) $transformer($row);
        }

        return $rows;
    }

    /**
     * @param  iterable<mixed>  $results
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRows(iterable $results): array
    {
        if ($results instanceof Collection) {
            return $results->map(fn ($row) => (array) $row)->all();
        }

        if ($results instanceof Traversable) {
            return collect(iterator_to_array($results))->map(fn ($row) => (array) $row)->all();
        }

        return collect($results)->map(fn ($row) => (array) $row)->all();
    }
}
