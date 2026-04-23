<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

trait TracksQueryCount
{
    protected function countSelectQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $callback();
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        return collect($queries)
            ->pluck('query')
            ->filter(fn ($sql): bool => is_string($sql) && str_starts_with(strtolower(ltrim($sql)), 'select'))
            ->count();
    }
}
