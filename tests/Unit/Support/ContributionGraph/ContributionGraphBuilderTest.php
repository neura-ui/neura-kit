<?php

namespace Neura\Kit\Tests\Unit\Support\ContributionGraph;

use Neura\Kit\Support\ContributionGraph\ContributionGraphBuilder;
use Neura\Kit\Tests\TestCase;

class ContributionGraphBuilderTest extends TestCase
{
    public function test_normalize_accepts_map_and_list_shapes(): void
    {
        $counts = ContributionGraphBuilder::normalize([
            '2026-01-01' => 2,
            ['date' => '2026-01-02', 'count' => 5],
        ]);

        $this->assertSame(2, $counts['2026-01-01']);
        $this->assertSame(5, $counts['2026-01-02']);
    }

    public function test_level_uses_thresholds(): void
    {
        $thresholds = [1, 3, 6, 10];

        $this->assertSame(0, ContributionGraphBuilder::level(0, $thresholds));
        $this->assertSame(1, ContributionGraphBuilder::level(1, $thresholds));
        $this->assertSame(2, ContributionGraphBuilder::level(3, $thresholds));
        $this->assertSame(3, ContributionGraphBuilder::level(6, $thresholds));
        $this->assertSame(4, ContributionGraphBuilder::level(10, $thresholds));
        $this->assertSame(4, ContributionGraphBuilder::level(99, $thresholds));
    }

    public function test_build_pads_full_weeks_and_sums_in_range(): void
    {
        $graph = ContributionGraphBuilder::build(
            data: [
                '2026-01-01' => 2,
                '2026-01-02' => 3,
                '2025-12-31' => 50,
            ],
            from: '2026-01-01',
            to: '2026-01-07',
        );

        $this->assertSame(5, $graph['total']);
        $this->assertSame('2026-01-01', $graph['from']);
        $this->assertSame('2026-01-07', $graph['to']);
        $this->assertNotEmpty($graph['weeks']);
        $this->assertCount(count($graph['weeks']), $graph['monthLabels']);

        foreach ($graph['weeks'] as $week) {
            $this->assertCount(7, $week);
        }
    }
}
