<?php

namespace Neura\Kit\Tests\Unit\Support\TimeGrid;

use Neura\Kit\Support\TimeGrid\Layout;
use Neura\Kit\Tests\TestCase;

/**
 * The time grid's layout is worked out once on the server: which columns, in which order,
 * which cells, which way round. The script only reads it.
 */
class LayoutTest extends TestCase
{
    public function test_weekdays_start_on_the_locale_first_day()
    {
        $this->assertSame('mon', Layout::make(null, null, null, null, 60, 'auto', 'fr')['columns'][0]['key']);
        $this->assertSame('sun', Layout::make(null, null, null, null, 60, 'auto', 'en')['columns'][0]['key']);
        $this->assertSame('mon', Layout::make(null, 'mon', null, null, 60, 'auto', 'en')['columns'][0]['key']);
    }

    public function test_a_weekday_subset_keeps_the_week_order()
    {
        $layout = Layout::make(['fri', 'mon', 'wed'], 'mon', null, null, 60, 'auto', 'en');

        $this->assertSame(['mon', 'wed', 'fri'], array_column($layout['columns'], 'key'));
        $this->assertTrue($layout['weekdays']);
    }

    public function test_weekday_labels_come_from_carbon()
    {
        $monday = Layout::make(null, 'mon', null, null, 60, 'auto', 'fr')['columns'][0];

        $this->assertSame('lun.', $monday['label']);
        $this->assertSame('lundi', $monday['name']);
    }

    public function test_a_free_list_keeps_its_order_and_labels()
    {
        $layout = Layout::make(['a' => 'Salle A', 'studio' => 'Studio'], null, null, null, 60, 'auto', 'fr');

        $this->assertSame(['a', 'studio'], array_column($layout['columns'], 'key'));
        $this->assertSame(['Salle A', 'Studio'], array_column($layout['columns'], 'label'));
        $this->assertFalse($layout['weekdays']);
    }

    public function test_bounds_snap_to_the_step()
    {
        $layout = Layout::make(null, null, '08:20', '17:10', 30, 'auto', 'en');
        $this->assertSame(480, $layout['start']);
        $this->assertSame(1050, $layout['end']);
        $this->assertCount(19, $layout['slots']);

        $layout = Layout::make(null, null, 8, '24:00', 60, 'auto', 'en');
        $this->assertSame(480, $layout['start']);
        $this->assertSame(1440, $layout['end']);

        $layout = Layout::make(null, null, '10:00', '09:00', 60, 'auto', 'en');
        $this->assertSame(600, $layout['start']);
        $this->assertSame(660, $layout['end']);
    }

    public function test_an_unknown_step_falls_back_to_an_hour()
    {
        $this->assertSame(60, Layout::make(null, null, null, null, 20, 'auto', 'en')['step']);
    }

    public function test_auto_orientation()
    {
        $this->assertSame('rows', Layout::make(null, null, null, null, 60, 'auto', 'en')['orientation']);
        $this->assertSame('columns', Layout::make(null, null, null, null, 30, 'auto', 'en')['orientation']);
        $this->assertSame('rows', Layout::make(null, null, '08:00', '20:00', 30, 'auto', 'en')['orientation']);
        $this->assertSame('columns', Layout::make(null, null, null, null, 60, 'columns', 'en')['orientation']);
    }

    public function test_the_signature_follows_the_layout()
    {
        $a = Layout::make(null, null, null, null, 60, 'auto', 'en')['signature'];

        $this->assertSame($a, Layout::make(null, null, null, null, 60, 'auto', 'en')['signature']);
        $this->assertNotSame($a, Layout::make(null, null, null, null, 30, 'auto', 'en')['signature']);
    }

    public function test_hour_cycle_and_labels()
    {
        $this->assertSame(24, Layout::hourCycle(null, 'fr'));
        $this->assertSame(12, Layout::hourCycle(null, 'en'));
        $this->assertSame(24, Layout::hourCycle(24, 'en'));

        $this->assertSame('09', Layout::hourLabel(540, 24, true));
        $this->assertSame('09:00', Layout::hourLabel(540, 24, false));
        $this->assertSame('9a', Layout::hourLabel(540, 12, true));
        $this->assertSame('12 PM', Layout::hourLabel(720, 12, false));
        $this->assertSame('12a', Layout::hourLabel(0, 12, true));
    }

    public function test_css_color()
    {
        $this->assertSame('var(--color-primary-500, var(--color-primary))', Layout::cssColor('primary'));
        $this->assertSame('var(--color-warning-600)', Layout::cssColor('warning-600'));
        $this->assertSame('#ff0000', Layout::cssColor('#ff0000'));
    }
}
