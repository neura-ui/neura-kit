<?php

namespace Neura\Kit\Tests\Unit\Components;

use Illuminate\Database\Eloquent\Builder;
use Neura\Kit\Components\Atoms\Table;
use Neura\Kit\Enum\Table\Variant as VariantEnum;
use Neura\Kit\Support\Table\Column;
use Neura\Kit\Tests\TestCase;

class TableStyleConfigTest extends TestCase
{
    public function test_table_style_methods_read_defaults_from_config(): void
    {
        if (! class_exists(\Livewire\Component::class)) {
            $this->markTestSkipped('Livewire is not installed');

            return;
        }

        config()->set('neura-kit.table.default', [
            'variant' => 'minimal',
            'rounded' => 'md',
            'shadow' => 'none',
            'density' => 'compact',
        ]);

        $table = new class extends Table
        {
            public function query(): Builder
            {
                return \Illuminate\Database\Eloquent\Model::query()->whereRaw('0 = 1');
            }

            public function columns(): array
            {
                return [Column::text('id', 'ID')];
            }
        };

        $this->assertSame('minimal', $this->packValue($table->variant()));
        $this->assertSame('md', $this->packValue($table->rounded()));
        $this->assertSame('none', $this->packValue($table->shadow()));
        $this->assertSame('compact', $this->packValue($table->density()));

        $styles = $table->getTableStyles();
        $this->assertSame('bg-transparent', $styles['variant']['wrapper']);
    }

    public function test_subclass_can_still_override_table_variant(): void
    {
        if (! class_exists(\Livewire\Component::class)) {
            $this->markTestSkipped('Livewire is not installed');

            return;
        }

        config()->set('neura-kit.table.default.variant', 'minimal');

        $table = new class extends Table
        {
            public function query(): Builder
            {
                return \Illuminate\Database\Eloquent\Model::query()->whereRaw('0 = 1');
            }

            public function columns(): array
            {
                return [Column::text('id', 'ID')];
            }

            public function variant(): string|VariantEnum
            {
                return VariantEnum::STRIPED;
            }
        };

        $this->assertSame('striped', $this->packValue($table->variant()));
    }

    private function packValue(string|\BackedEnum $value): string
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
