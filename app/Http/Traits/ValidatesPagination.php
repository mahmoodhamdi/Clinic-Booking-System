<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;

trait ValidatesPagination
{
    /**
     * Get the validated per_page value from request.
     */
    protected function getPerPage(Request $request, int $default = 15, int $max = 100): int
    {
        $perPage = $request->integer('per_page', $default);
        return min(max($perPage, 1), $max);
    }

    /**
     * Get the validated limit value from request.
     */
    protected function getLimit(Request $request, int $default = 10, int $max = 100): int
    {
        $limit = $request->integer('limit', $default);
        return min(max($limit, 1), $max);
    }

    /**
     * Get the validated page value from request.
     */
    protected function getPage(Request $request, int $default = 1): int
    {
        $page = $request->integer('page', $default);
        return max($page, 1);
    }
}
