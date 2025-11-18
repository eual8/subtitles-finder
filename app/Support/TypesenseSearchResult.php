<?php

namespace App\Support;

use App\Models\Fragment;
use Illuminate\Support\Collection;

class TypesenseSearchResult
{
    private Collection $hits;

    private int $total;

    private int $currentPage;

    private int $perPage;

    public function __construct(array $typesenseResponse, int $currentPage, int $perPage)
    {
        $this->total = $typesenseResponse['found'] ?? 0;
        $this->currentPage = $currentPage;
        $this->perPage = $perPage;

        $this->hits = collect($typesenseResponse['hits'] ?? [])->map(function ($hit) {
            return new TypesenseHit($hit);
        });
    }

    public function hits(): Collection
    {
        return $this->hits;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function lastPage(): int
    {
        return (int) ceil($this->total / $this->perPage);
    }

    public function hasPages(): bool
    {
        return $this->lastPage() > 1;
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage();
    }

    public function onFirstPage(): bool
    {
        return $this->currentPage <= 1;
    }

    public function isEmpty(): bool
    {
        return $this->hits->isEmpty();
    }

    public function isNotEmpty(): bool
    {
        return $this->hits->isNotEmpty();
    }
}

class TypesenseHit
{
    private array $hit;

    private ?Fragment $model = null;

    public function __construct(array $hit)
    {
        $this->hit = $hit;
    }

    public function model(): ?Fragment
    {
        if ($this->model === null) {
            $documentId = $this->hit['document']['id'] ?? null;

            if ($documentId !== null) {
                $this->model = Fragment::with('video')->find((int) $documentId);
            }
        }

        return $this->model;
    }

    public function distance(): float
    {
        return $this->hit['vector_distance'] ?? 0.0;
    }

    public function document(): array
    {
        return $this->hit['document'] ?? [];
    }
}
