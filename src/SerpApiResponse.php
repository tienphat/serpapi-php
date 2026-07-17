<?php

declare(strict_types=1);

namespace SerpApiOrg;

/**
 * Wraps the raw API response with convenient accessors.
 *
 *   $res->data()       → list of results
 *   $res->first()      → first result or null
 *   $res->pluck('url') → array of a single field
 *   $res->count()      → number of results
 *   $res->inSeconds()  → API latency
 *   $res->toArray()    → full raw response
 *   foreach ($res …)   → iterate results directly
 *
 * @implements \Countable
 * @implements \IteratorAggregate<int, array>
 */
final class SerpApiResponse implements \Countable, \IteratorAggregate
{
    /** @param array<string, mixed> $raw */
    public function __construct(private readonly array $raw) {}

    // ── Data ─────────────────────────────────────────────────────────────────

    /** @return list<array<string, mixed>> */
    public function data(): array
    {
        $data = $this->raw['data'] ?? [];

        // Some endpoints wrap results under a typed key
        if (is_array($data) && !array_is_list($data)) {
            foreach (['items', 'products', 'images', 'videos', 'news', 'scholar', 'places', 'reviews', 'suggestions'] as $key) {
                if (isset($data[$key]) && is_array($data[$key])) {
                    return $data[$key];
                }
            }
        }

        return is_array($data) ? $data : [];
    }

    /** First result, or null when empty. */
    public function first(): ?array
    {
        return $this->data()[0] ?? null;
    }

    /**
     * Pluck a single field from every result item.
     *
     * @return list<mixed>
     */
    public function pluck(string $field): array
    {
        return array_values(
            array_filter(array_column($this->data(), $field), static fn ($v) => $v !== null)
        );
    }

    // ── Meta ─────────────────────────────────────────────────────────────────

    /** Echo of the parameters sent to the API. */
    public function request(): array
    {
        return (array) ($this->raw['request'] ?? []);
    }

    /** Pagination info (total_results, page) when present. */
    public function meta(): array
    {
        return array_filter([
            'total_results' => $this->raw['data']['total_results'] ?? null,
            'page'          => $this->raw['data']['page'] ?? null,
        ], static fn ($v) => $v !== null);
    }

    /** API processing time in seconds. */
    public function inSeconds(): float
    {
        return (float) ($this->raw['in_seconds'] ?? 0.0);
    }

    /** True when the response is a static sample rather than a live call. */
    public function isStaticSample(): bool
    {
        return (bool) ($this->raw['static_sample'] ?? false);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    // ── Serialise ────────────────────────────────────────────────────────────

    /** Full raw response. */
    public function toArray(): array
    {
        return $this->raw;
    }

    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string
    {
        return (string) json_encode($this->raw, $flags);
    }

    // ── Interfaces ───────────────────────────────────────────────────────────

    public function count(): int
    {
        return count($this->data());
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->data());
    }
}
