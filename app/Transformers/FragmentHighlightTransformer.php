<?php

namespace App\Transformers;

use Elastic\ScoutDriverPlus\Decorators\Hit;
use Elastic\ScoutDriverPlus\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Prepares search hit snippets for rendering (adds highlighting for ngram matches, builds excerpts).
 */
class FragmentHighlightTransformer
{
    /**
     * @return Collection<int, array{model: mixed, snippets: Collection}>
     */
    public function transform(Paginator $paginator, string $searchQuery): Collection
    {
        $tokens = $this->buildTokens($searchQuery);

        return $paginator->hits()->map(function (Hit $hit) use ($tokens) {
            $snippets = $hit->highlight()?->snippets('text') ?? collect();

            if ($this->isEmpty($snippets)) {
                $snippets = $this->buildExcerpt($hit, $tokens);
            }

            $preparedSnippets = collect($snippets)->map(
                fn ($snippet) => $this->highlightSnippet($snippet, $tokens)
            );

            return [
                'model' => $hit->model(),
                'snippets' => $preparedSnippets,
            ];
        });
    }

    private function buildTokens(string $searchQuery): Collection
    {
        return collect(preg_split('/\s+/u', trim($searchQuery)))
            ->filter()
            ->flatMap(function (string $term) {
                $term = trim($term);
                $len = mb_strlen($term);

                if ($len < 3) {
                    return [$term];
                }

                $max = min(10, $len);
                $parts = [];
                for ($i = 3; $i <= $max; $i++) {
                    $parts[] = mb_substr($term, 0, $i); // edge ngrams to mimic analyzer
                }

                $parts[] = $term; // full token

                return $parts;
            })
            ->unique()
            ->sortByDesc(fn ($token) => mb_strlen($token))
            ->values();
    }

    private function highlightSnippet(string $snippet, Collection $tokens): string
    {
        if ($tokens->isEmpty()) {
            return e($snippet);
        }

        if (Str::contains($snippet, '<mark')) {
            return $snippet;
        }

        $safeSnippet = e($snippet);
        $escaped = $tokens->map(fn ($t) => preg_quote($t, '/'))->implode('|');

        return preg_replace('/('.$escaped.')/iu', '<mark><b>$1</b></mark>', $safeSnippet);
    }

    private function buildExcerpt(Hit $hit, Collection $tokens): array
    {
        $rawText = $hit->model()?->text ?? '';
        $plainText = strip_tags($rawText);

        $startPosition = 0;
        foreach ($tokens as $token) {
            $pos = mb_stripos($plainText, $token);
            if ($pos !== false) {
                $startPosition = $startPosition === 0 ? $pos : min($startPosition, $pos);
            }
        }

        return [mb_substr($plainText, max($startPosition - 60, 0), 220)];
    }

    private function isEmpty(Collection|array $snippets): bool
    {
        if (is_array($snippets)) {
            return count($snippets) === 0;
        }

        return $snippets->isEmpty();
    }
}
