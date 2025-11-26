<?php

namespace App\Data;

use Elastic\ScoutDriverPlus\Paginator;
use Illuminate\Support\Collection;

final class FragmentSearchResult
{
    public function __construct(
        public readonly Paginator $paginator,
        public readonly Collection $preparedHits,
    ) {}
}
