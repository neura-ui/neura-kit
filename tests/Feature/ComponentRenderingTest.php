<?php

namespace Neura\Kit\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Neura\Kit\Tests\TestCase;

class ComponentRenderingTest extends TestCase
{
    public function test_button_component_renders_correctly()
    {
        $html = Blade::render('<x-neura::button>Click me</x-neura::button>');

        $this->assertStringContainsString('Click me', $html);
        $this->assertStringContainsString('button', $html);
    }

    public function test_button_component_with_attributes()
    {
        $html = Blade::render('<x-neura::button variant="outline" size="lg" class="custom-class">Button</x-neura::button>');

        $this->assertStringContainsString('Button', $html);
        $this->assertStringContainsString('custom-class', $html);
    }

    public function test_button_component_with_icon()
    {
        if (! class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $this->markTestSkipped('Heroicons package is not installed');

            return;
        }

        try {
            $html = Blade::render('<x-neura::button icon="check">Save</x-neura::button>');
            $this->assertStringContainsString('Save', $html);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'heroicons')) {
                $this->markTestSkipped('Heroicons components not properly registered: '.$e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function test_button_loading_orb_indicator_renders()
    {
        $html = Blade::render('<x-neura::button loading="true" loadingIndicator="orb" orbVariant="S3">Save</x-neura::button>');

        $this->assertStringContainsString('data-slot="loading-indicator"', $html);
        $this->assertStringContainsString('data-engine="css"', $html);
        $this->assertStringContainsString('data-variant="S3"', $html);
        $this->assertStringNotContainsString('animate-spin', $html);
        $this->assertStringNotContainsString('data-nk-orb', $html);
    }

    public function test_button_loading_spinner_indicator_renders_by_default()
    {
        $html = Blade::render('<x-neura::button loading="true">Save</x-neura::button>');

        $this->assertStringContainsString('data-slot="loading-indicator"', $html);
        $this->assertStringContainsString('animate-spin', $html);
        $this->assertStringNotContainsString('data-nk-orb', $html);
    }

    public function test_modal_manager_component_renders()
    {
        $html = Blade::render('<x-neura::modal-manager :components="[]" />');

        $this->assertStringContainsString('modalManager', $html);
        $this->assertStringContainsString('x-data="modalManager()"', $html);
    }

    public function test_table_livewire_column_component_renders()
    {
        $html = Blade::render('<x-neura::table.columns.livewire :value="\'test-value\'" :row="null" :column="null" :extraAttributes="[\'component\' => \'test-component\']" />');

        $this->assertNotEmpty($html);
    }

    public function test_select_component_renders()
    {
        if (! class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $this->markTestSkipped('Heroicons package is not installed');

            return;
        }

        try {
            $html = Blade::render('<x-neura::select name="test" />');
            $this->assertNotEmpty($html);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'heroicons')) {
                $this->markTestSkipped('Heroicons components not properly registered: '.$e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    /**
     * A switch bound with wire:model or x-model starts from the bound value: without
     * x-modelable it always started off, whatever the value.
     */
    public function test_switch_starts_from_its_bound_value()
    {
        $html = Blade::render('<x-neura::switch wire:model="form.active" label="Active" />');

        $this->assertMatchesRegularExpression('/wire:model="form\.active"[^>]*x-modelable="checked"|x-modelable="checked"[^>]*wire:model="form\.active"/', $html);
    }

    /**
     * The select writes the chosen label itself, so a Livewire morph must leave that part alone,
     * and the muted placeholder colour must give way once a value is chosen.
     */
    public function test_select_keeps_its_chosen_label_through_livewire_morphs()
    {
        if (! class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $this->markTestSkipped('Heroicons package is not installed');

            return;
        }

        $html = Blade::render('<x-neura::select name="status"><x-neura::select.option value="Active" label="Active" /></x-neura::select>');

        $this->assertMatchesRegularExpression('/x-ref="valueReel"[^>]*wire:ignore/', $html);
        $this->assertStringContainsString("{ 'text-fg': hasSelection, 'text-fg-muted': !hasSelection }", $html);
        $this->assertStringContainsString('destroy()', $html);
        $this->assertStringNotContainsString("Livewire.hook('commit'", $html);
    }

    public function test_input_component_renders()
    {
        $html = Blade::render('<x-neura::input name="email" type="email" />');

        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('type="email"', $html);
    }

    public function test_checkbox_component_renders()
    {
        if (! class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $this->markTestSkipped('Heroicons package is not installed');

            return;
        }

        try {
            $html = Blade::render('<x-neura::checkbox name="agree" />');
            $this->assertNotEmpty($html);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'heroicons')) {
                $this->markTestSkipped('Heroicons components not properly registered: '.$e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function test_textarea_component_renders()
    {
        $html = Blade::render('<x-neura::textarea name="message" />');

        $this->assertNotEmpty($html);
        $this->assertStringContainsString('textarea', $html);
    }

    public function test_rule_builder_component_renders()
    {
        if (! class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $this->markTestSkipped('Heroicons package is not installed');

            return;
        }

        try {
            $html = Blade::render(<<<'BLADE'
                <x-neura::rule-builder
                    :fields="[
                        ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [['value' => 'a', 'label' => 'A']]],
                    ]"
                    :actions="[
                        ['key' => 'notify', 'label' => 'Notify'],
                    ]"
                />
            BLADE);

            $this->assertStringContainsString('neuraRuleBuilder', $html);
            $this->assertStringContainsString('nk-rule-builder', $html);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'heroicons') || str_contains($e->getMessage(), 'Svg by Name')) {
                $this->markTestSkipped('Heroicons components not properly registered: '.$e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function test_rule_builder_can_hold_conditions_only_with_one_combinator()
    {
        if (! class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $this->markTestSkipped('Heroicons package is not installed');

            return;
        }

        try {
            $html = Blade::render(<<<'BLADE'
                <x-neura::rule-builder
                    :then="false"
                    :combinators="['and']"
                    :max-depth="1"
                    :fields="[
                        ['key' => 'age', 'label' => 'Age', 'type' => 'number', 'operators' => ['at_least', 'at_most']],
                        ['key' => 'zip', 'label' => 'Zip code', 'type' => 'text', 'operators' => ['is_any_of', 'is_not_any_of']],
                    ]"
                />
            BLADE);

            $this->assertStringNotContainsString('nk-rule-builder-then', $html);
            $this->assertStringNotContainsString('addGroup(value.when.id)', $html);
            $this->assertStringContainsString('combinators', $html);
            $this->assertStringContainsString('onListKeydown', $html);
            $this->assertStringContainsString(__('atLeast'), html_entity_decode($html));
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'heroicons') || str_contains($e->getMessage(), 'Svg by Name')) {
                $this->markTestSkipped('Heroicons components not properly registered: '.$e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function test_filters_offer_number_and_free_list_fields()
    {
        if (! class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $this->markTestSkipped('Heroicons package is not installed');

            return;
        }

        try {
            $html = Blade::render(<<<'BLADE'
                <x-neura::filters
                    :fields="[
                        ['key' => 'age', 'label' => 'Age', 'type' => 'number', 'operators' => ['at_least']],
                        ['key' => 'zip', 'label' => 'Zip code', 'type' => 'tags'],
                    ]"
                    :value="[['field' => 'zip', 'values' => ['75', '92']]]"
                />
            BLADE);

            $this->assertStringContainsString('data-filter-tags', $html);
            $this->assertStringContainsString('at_least', $html);
            $this->assertStringContainsString('is_any_of', $html);
            $this->assertStringContainsString(__('atLeast'), html_entity_decode($html));
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'heroicons') || str_contains($e->getMessage(), 'Svg by Name')) {
                $this->markTestSkipped('Heroicons components not properly registered: '.$e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function test_thinking_state_component_renders()
    {
        $html = Blade::render('<x-neura::thinking-state label="Thinking" />');

        $this->assertStringContainsString('Thinking', $html);
        $this->assertStringContainsString('data-slot="thinking-state"', $html);
        $this->assertStringContainsString('data-animate="true"', $html);
    }

    public function test_thinking_state_reasoning_mode_renders()
    {
        $html = Blade::render(<<<'BLADE'
            <x-neura::thinking-state
                :reasoning="['First step', 'Second step']"
                :done="true"
                :duration="3"
            />
        BLADE);

        $this->assertStringContainsString('neuraThinkingState', $html);
        $this->assertStringContainsString('data-variant="reasoning"', $html);
        $this->assertStringContainsString('First step', $html);
    }

    public function test_web_search_component_renders()
    {
        $html = Blade::render('<x-neura::web-search query="test query" :done="true" :loop="false" />');

        $this->assertStringContainsString('neuraWebSearch', $html);
        $this->assertStringContainsString('data-slot="web-search"', $html);
        $this->assertStringContainsString('test query', $html);
    }

    public function test_image_generation_component_renders()
    {
        $html = Blade::render('<x-neura::image-generation prompt="lake at dawn" aspect="landscape" />');

        $this->assertStringContainsString('data-slot="image-generation"', $html);
        $this->assertStringContainsString('nk-ig-canvas', $html);
        $this->assertStringContainsString('lake at dawn', $html);
    }

    public function test_file_diff_component_renders()
    {
        $html = Blade::render(<<<'BLADE'
            <x-neura::file-diff
                file="src/auth.ts"
                :rows="[
                    ['old' => 1, 'cur' => 1, 'type' => 'ctx', 'text' => 'const x = 1'],
                    ['old' => null, 'cur' => 2, 'type' => 'add', 'text' => 'const y = 2'],
                ]"
            />
        BLADE);

        $this->assertStringContainsString('data-slot="file-diff"', $html);
        $this->assertStringContainsString('src/auth.ts', $html);
        $this->assertStringContainsString('nk-diff-row', $html);
        $this->assertStringContainsString('const y = 2', $html);
    }

    public function test_todo_list_component_renders()
    {
        $html = Blade::render(<<<'BLADE'
            <x-neura::todo-list
                :items="[
                    ['label' => 'One', 'status' => 'done'],
                    ['label' => 'Two', 'status' => 'active'],
                ]"
            />
        BLADE);

        $this->assertStringContainsString('neuraTodoList', $html);
        $this->assertStringContainsString('data-slot="todo-list"', $html);
        $this->assertStringContainsString('One', $html);
    }

    public function test_error_component_renders_slot_content_with_error_styles()
    {
        if (! class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $this->markTestSkipped('Heroicons package is not installed');

            return;
        }

        $html = Blade::render('<x-neura::error>Please enter a valid email address</x-neura::error>');

        $this->assertStringContainsString('Please enter a valid email address', $html);
        $this->assertStringContainsString('data-slot="error"', $html);
        $this->assertStringContainsString('text-red-600', $html);
        $this->assertStringContainsString('role="alert"', $html);
    }

    public function test_error_component_renders_messages_prop()
    {
        if (! class_exists(\BladeUI\Heroicons\BladeHeroiconsServiceProvider::class)) {
            $this->markTestSkipped('Heroicons package is not installed');

            return;
        }

        $html = Blade::render('<x-neura::error :messages="[\'Invalid value\']" />');

        $this->assertStringContainsString('Invalid value', $html);
        $this->assertStringContainsString('text-red-600', $html);
    }
}
