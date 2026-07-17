<?php

declare(strict_types=1);

namespace SerpApiOrg\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SerpApiOrg\SerpApiResponse;

#[CoversClass(SerpApiResponse::class)]
class SerpApiResponseTest extends TestCase
{
    private function makeResponse(array $raw = []): SerpApiResponse
    {
        return new SerpApiResponse($raw);
    }

    private function makeWebResponse(int $count = 3): SerpApiResponse
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $items[] = [
                'position'    => $i,
                'title'       => "Result {$i}",
                'link'        => "https://example.com/{$i}",
                'description' => "Snippet for result {$i}",
            ];
        }

        return $this->makeResponse([
            'request'    => ['keyword' => 'test', 'gl' => 'US'],
            'data'       => $items,
            'in_seconds' => 0.42,
        ]);
    }

    // ── data() ────────────────────────────────────────────────────────────────

    public function test_data_returns_list(): void
    {
        $res = $this->makeWebResponse(3);
        $this->assertCount(3, $res->data());
    }

    public function test_data_returns_empty_array_when_missing(): void
    {
        $res = $this->makeResponse([]);
        $this->assertSame([], $res->data());
    }

    public function test_data_returns_empty_array_when_data_is_not_array(): void
    {
        $res = $this->makeResponse(['data' => 'not-an-array']);
        $this->assertSame([], $res->data());
    }

    // ── first() ───────────────────────────────────────────────────────────────

    public function test_first_returns_first_item(): void
    {
        $res   = $this->makeWebResponse(3);
        $first = $res->first();

        $this->assertIsArray($first);
        $this->assertSame('Result 1', $first['title']);
    }

    public function test_first_returns_null_when_empty(): void
    {
        $res = $this->makeResponse(['data' => []]);
        $this->assertNull($res->first());
    }

    // ── pluck() ───────────────────────────────────────────────────────────────

    public function test_pluck_extracts_field(): void
    {
        $res    = $this->makeWebResponse(3);
        $titles = $res->pluck('title');

        $this->assertSame(['Result 1', 'Result 2', 'Result 3'], $titles);
    }

    public function test_pluck_skips_missing_fields(): void
    {
        $res = $this->makeResponse([
            'data' => [
                ['title' => 'Has title'],
                ['description' => 'No title here'],
                ['title' => 'Has title too'],
            ],
        ]);

        $this->assertSame(['Has title', 'Has title too'], $res->pluck('title'));
    }

    // ── count() / Countable ───────────────────────────────────────────────────

    public function test_count_matches_data_length(): void
    {
        $res = $this->makeWebResponse(5);
        $this->assertSame(5, count($res));
        $this->assertSame(5, $res->count());
    }

    public function test_count_zero_when_empty(): void
    {
        $res = $this->makeResponse(['data' => []]);
        $this->assertSame(0, count($res));
    }

    // ── isEmpty() ─────────────────────────────────────────────────────────────

    public function test_is_empty_true_when_no_data(): void
    {
        $this->assertTrue($this->makeResponse(['data' => []])->isEmpty());
        $this->assertTrue($this->makeResponse([])->isEmpty());
    }

    public function test_is_empty_false_when_has_data(): void
    {
        $this->assertFalse($this->makeWebResponse(1)->isEmpty());
    }

    // ── IteratorAggregate / foreach ───────────────────────────────────────────

    public function test_foreach_iterates_over_data(): void
    {
        $res    = $this->makeWebResponse(3);
        $titles = [];

        foreach ($res as $item) {
            $titles[] = $item['title'];
        }

        $this->assertSame(['Result 1', 'Result 2', 'Result 3'], $titles);
    }

    // ── request() ─────────────────────────────────────────────────────────────

    public function test_request_returns_request_params(): void
    {
        $res = $this->makeWebResponse();
        $this->assertSame(['keyword' => 'test', 'gl' => 'US'], $res->request());
    }

    public function test_request_returns_empty_array_when_missing(): void
    {
        $this->assertSame([], $this->makeResponse([])->request());
    }

    // ── inSeconds() ───────────────────────────────────────────────────────────

    public function test_in_seconds_returns_float(): void
    {
        $res = $this->makeWebResponse();
        $this->assertSame(0.42, $res->inSeconds());
    }

    public function test_in_seconds_defaults_to_zero(): void
    {
        $this->assertSame(0.0, $this->makeResponse([])->inSeconds());
    }

    // ── isStaticSample() ──────────────────────────────────────────────────────

    public function test_is_static_sample_false_by_default(): void
    {
        $this->assertFalse($this->makeResponse([])->isStaticSample());
    }

    public function test_is_static_sample_true_when_set(): void
    {
        $res = $this->makeResponse(['static_sample' => true]);
        $this->assertTrue($res->isStaticSample());
    }

    // ── toArray() / toJson() ──────────────────────────────────────────────────

    public function test_to_array_returns_full_raw(): void
    {
        $raw = ['data' => [['title' => 'T1']], 'in_seconds' => 0.1];
        $res = $this->makeResponse($raw);
        $this->assertSame($raw, $res->toArray());
    }

    public function test_to_json_is_valid_json(): void
    {
        $res  = $this->makeWebResponse(2);
        $json = $res->toJson();
        $this->assertJson($json);
        $this->assertArrayHasKey('data', json_decode($json, true));
    }

    // ── Nested data format (e.g. unified endpoint) ────────────────────────────

    public function test_data_extracts_nested_items_key(): void
    {
        $res = $this->makeResponse([
            'data' => ['items' => [['id' => 1], ['id' => 2]]],
        ]);

        $this->assertCount(2, $res->data());
        $this->assertSame(1, $res->data()[0]['id']);
    }

    public function test_data_extracts_nested_news_key(): void
    {
        $res = $this->makeResponse([
            'data' => ['news' => [['title' => 'N1'], ['title' => 'N2']]],
        ]);

        $this->assertSame('N1', $res->data()[0]['title']);
    }
}
