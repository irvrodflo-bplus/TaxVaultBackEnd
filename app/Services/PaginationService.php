<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PaginationService {    
    public function paginateData(
        array | Collection $data, 
        int $perPage = 15, 
        int $page = 1, 
    ): LengthAwarePaginator {
    $collection = collect($data);

    return new LengthAwarePaginator(
        $collection->forPage($page, $perPage)->values(),
        $collection->count(),
        $perPage,
        $page,
    );
}
}
