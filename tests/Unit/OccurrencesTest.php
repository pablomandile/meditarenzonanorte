<?php

namespace Tests\Unit;

use App\Support\Occurrences;
use PHPUnit\Framework\TestCase;

class OccurrencesTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function weekly(int $weekday, array $overrides = []): array
    {
        return [
            'type' => 'weekly',
            'weekday' => $weekday,
            'date' => null,
            'from' => null,
            'until' => null,
            'start' => '19:00',
            'end' => '20:15',
            'label' => null,
            ...$overrides,
        ];
    }

    /**
     * @param  array<int, array{date: string, start: ?string, end: ?string, label: ?string}>  $dates
     * @return array<int, string>
     */
    private function dates(array $dates): array
    {
        return array_column($dates, 'date');
    }

    public function test_weekly_rule_lands_on_every_matching_day_of_the_window(): void
    {
        // Agosto 2026: la grilla va del lunes 27/7 al domingo 6/9.
        $dates = Occurrences::expand([$this->weekly(3)], '2026-07-27', '2026-09-06');

        $this->assertSame(
            ['2026-07-29', '2026-08-05', '2026-08-12', '2026-08-19', '2026-08-26', '2026-09-02'],
            $this->dates($dates),
        );
        $this->assertSame('19:00', $dates[0]['start']);
        $this->assertSame('20:15', $dates[0]['end']);
    }

    public function test_two_rules_of_the_same_card_are_both_expanded(): void
    {
        $dates = Occurrences::expand(
            [$this->weekly(2, ['start' => '18:00', 'end' => '18:30']), $this->weekly(4, ['start' => '18:00', 'end' => '18:30'])],
            '2026-08-03',
            '2026-08-09',
        );

        $this->assertSame(['2026-08-04', '2026-08-06'], $this->dates($dates));
    }

    public function test_from_and_until_clip_the_weekly_rule_at_both_ends(): void
    {
        $rows = [$this->weekly(3, ['from' => '2026-08-10', 'until' => '2026-08-20'])];

        $this->assertSame(['2026-08-12', '2026-08-19'], $this->dates(Occurrences::expand($rows, '2026-07-27', '2026-09-06')));
    }

    public function test_inverted_validity_range_yields_nothing(): void
    {
        $rows = [$this->weekly(3, ['from' => '2026-08-20', 'until' => '2026-08-10'])];

        $this->assertSame([], Occurrences::expand($rows, '2026-07-27', '2026-09-06'));
    }

    public function test_one_off_date_appears_only_inside_the_window(): void
    {
        $row = ['type' => 'date', 'date' => '2026-08-15', 'start' => '10:00', 'end' => '17:00'];

        $this->assertSame(['2026-08-15'], $this->dates(Occurrences::expand([$row], '2026-08-01', '2026-08-31')));
        $this->assertSame([], Occurrences::expand([$row], '2026-09-01', '2026-09-30'));
    }

    public function test_multi_day_date_spans_its_range_and_is_clipped_by_the_window(): void
    {
        $row = ['type' => 'date', 'date' => '2026-08-28', 'until' => '2026-09-02'];

        $this->assertSame(
            ['2026-08-28', '2026-08-29', '2026-08-30', '2026-08-31'],
            $this->dates(Occurrences::expand([$row], '2026-08-01', '2026-08-31')),
        );
    }

    public function test_garbage_rows_are_ignored(): void
    {
        $rows = [
            $this->weekly(0),                                    // fuera de 1..7
            $this->weekly(8),
            ['type' => 'weekly', 'weekday' => null],             // sin día
            ['type' => 'date', 'date' => '2026-02-31'],          // no existe
            ['type' => 'date', 'date' => 'el jueves'],
            ['type' => 'date', 'date' => null],
        ];

        $this->assertSame([], Occurrences::expand($rows, '2026-01-01', '2026-12-31'));
    }

    public function test_impossible_times_become_null_but_keep_the_date(): void
    {
        $dates = Occurrences::expand([$this->weekly(3, ['start' => '99:99', 'end' => ''])], '2026-08-03', '2026-08-09');

        $this->assertSame(['2026-08-05'], $this->dates($dates));
        $this->assertNull($dates[0]['start']);
        $this->assertNull($dates[0]['end']);
    }

    public function test_times_coming_from_mysql_with_seconds_are_normalised(): void
    {
        $dates = Occurrences::expand([$this->weekly(3, ['start' => '19:00:00', 'end' => '20:15:00'])], '2026-08-03', '2026-08-09');

        $this->assertSame('19:00', $dates[0]['start']);
        $this->assertSame('20:15', $dates[0]['end']);
    }

    public function test_label_overrides_are_trimmed_and_blanks_become_null(): void
    {
        $withLabel = Occurrences::expand([$this->weekly(3, ['label' => '  Clase de los miércoles  '])], '2026-08-03', '2026-08-09');
        $withBlank = Occurrences::expand([$this->weekly(3, ['label' => '   '])], '2026-08-03', '2026-08-09');

        $this->assertSame('Clase de los miércoles', $withLabel[0]['label']);
        $this->assertNull($withBlank[0]['label']);
    }

    public function test_empty_rows_and_a_broken_window_yield_nothing(): void
    {
        $this->assertSame([], Occurrences::expand([], '2026-08-01', '2026-08-31'));
        $this->assertSame([], Occurrences::expand([$this->weekly(3)], '2026-08-31', '2026-08-01'));
        $this->assertSame([], Occurrences::expand([$this->weekly(3)], 'basura', '2026-08-31'));
    }
}
