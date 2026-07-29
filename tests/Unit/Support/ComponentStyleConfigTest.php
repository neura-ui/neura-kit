<?php

namespace Neura\Kit\Tests\Unit\Support;

use Neura\Kit\Enum\Packs\Rounded;
use Neura\Kit\Enum\Packs\Shadow;
use Neura\Kit\Support\PackResolver;
use Neura\Kit\Tests\TestCase;

class ComponentStyleConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('neura-kit.style', [
            'rounded' => Rounded::XL,
            'shadow' => Shadow::MD,
            'color' => 'primary',
        ]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function globalStyleComponentsProvider(): array
    {
        return [
            'select' => ['select'],
            'input' => ['input'],
            'textarea' => ['textarea'],
            'otp' => ['otp'],
            'card' => ['card'],
            'dropdown' => ['dropdown'],
            'modal' => ['modal'],
            'popup' => ['popup'],
            'avatar' => ['avatar'],
            'button' => ['button'],
            'dropzone' => ['dropzone'],
            'rich-editor' => ['rich-editor'],
            'dialog' => ['dialog'],
            'toast' => ['toast'],
            'empty-state' => ['empty-state'],
            'callout' => ['callout'],
            'chart' => ['chart'],
            'flow' => ['flow'],
            'kanban' => ['kanban'],
            'command' => ['command'],
            'spotlight' => ['spotlight'],
            'composer' => ['composer'],
        ];
    }

    public function test_context_menu_aliases_dropdown_config(): void
    {
        $this->assertSame(neura_config('dropdown', 'rounded'), neura_config('context-menu', 'rounded'));
        $this->assertSame(neura_config('dropdown', 'shadow'), neura_config('context-menu', 'shadow'));
    }

    public function test_table_inherits_global_style(): void
    {
        $this->assertSame('xl', neura_config('table', 'rounded'));
        $this->assertSame('md', neura_config('table', 'shadow'));
    }

    public function test_tree_and_image_gallery_inherit_global_rounded(): void
    {
        $this->assertSame('xl', neura_config('tree', 'rounded'));
        $this->assertSame('rounded-xl', PackResolver::rounded(neura_config('tree', 'rounded')));
        $this->assertSame('xl', neura_config('image-gallery', 'rounded'));
        $this->assertSame('rounded-xl', PackResolver::rounded(neura_config('image-gallery', 'rounded')));
    }

    public function test_tabs_inherits_global_rounded(): void
    {
        $this->assertSame('xl', neura_config('tabs', 'rounded'));
        $this->assertSame('rounded-xl', PackResolver::rounded(neura_config('tabs', 'rounded')));
    }

    public function test_alert_inherits_global_shadow(): void
    {
        $this->assertSame('md', neura_config('alert', 'shadow'));
        $this->assertSame('shadow-md', PackResolver::shadow(neura_config('alert', 'shadow')));
    }

    public function test_progress_defaults_to_full_rounded(): void
    {
        $this->assertSame('full', neura_config('progress', 'rounded'));
        $this->assertSame('rounded-full', PackResolver::rounded(neura_config('progress', 'rounded')));
    }

    public function test_skeleton_inherits_global_rounded(): void
    {
        $this->assertSame('xl', neura_config('skeleton', 'rounded'));
        $this->assertSame('rounded-xl', PackResolver::rounded(neura_config('skeleton', 'rounded')));
    }

    public function test_sideover_inherits_global_shadow(): void
    {
        $this->assertSame('md', neura_config('sideover', 'shadow'));
        $this->assertSame('shadow-md', PackResolver::shadow(neura_config('sideover', 'shadow')));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function popupStyleComponentsProvider(): array
    {
        return [
            'popup' => ['popup'],
            'calendar via popup' => ['popup'],
        ];
    }

    public function test_dropzone_and_rich_editor_inherit_global_style(): void
    {
        $this->assertSame('xl', neura_config('dropzone', 'rounded'));
        $this->assertSame('md', neura_config('dropzone', 'shadow'));
        $this->assertSame('xl', neura_config('rich-editor', 'rounded'));
        $this->assertSame('md', neura_config('rich-editor', 'shadow'));
    }

    public function test_fieldset_inherits_global_rounded(): void
    {
        $this->assertSame('xl', neura_config('fieldset', 'rounded'));
        $this->assertSame('rounded-xl', PackResolver::rounded(neura_config('fieldset', 'rounded')));
    }

    public function test_toggle_inherits_global_shadow(): void
    {
        $this->assertSame('md', neura_config('toggle', 'shadow'));
        $this->assertSame('shadow-md', PackResolver::shadow(neura_config('toggle', 'shadow')));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function globalRoundedComponentsProvider(): array
    {
        return [
            'badge' => ['badge'],
            'alert' => ['alert'],
            'navlist' => ['navlist'],
            'fieldset' => ['fieldset'],
        ];
    }

    /**
     * @dataProvider globalStyleComponentsProvider
     */
    public function test_components_with_global_defaults_inherit_style_config(string $component): void
    {
        $this->assertSame('xl', neura_config($component, 'rounded'));
        $this->assertSame('md', neura_config($component, 'shadow'));
        $this->assertSame('rounded-xl', PackResolver::rounded(neura_config($component, 'rounded')));
        $this->assertSame('shadow-md', PackResolver::shadow(neura_config($component, 'shadow')));
    }

    /**
     * @dataProvider globalRoundedComponentsProvider
     */
    public function test_components_with_global_rounded_default_inherit_style_config(string $component): void
    {
        $this->assertSame('xl', neura_config($component, 'rounded'));
        $this->assertSame('rounded-xl', PackResolver::rounded(neura_config($component, 'rounded')));
    }

    public function test_checkbox_reads_configured_rounded_default(): void
    {
        config()->set('neura-kit.checkbox.default.rounded', Rounded::SM);

        $this->assertSame('sm', neura_config('checkbox', 'rounded'));
        $this->assertSame('rounded-sm', PackResolver::rounded(neura_config('checkbox', 'rounded')));
    }

    public function test_card_component_defaults_can_override_global_style(): void
    {
        config()->set('neura-kit.card.default.rounded', 'none');
        config()->set('neura-kit.card.default.shadow', 'lg');

        $this->assertSame('none', neura_config('card', 'rounded'));
        $this->assertSame('lg', neura_config('card', 'shadow'));
    }

    public function test_dropdown_component_defaults_can_override_global_style(): void
    {
        config()->set('neura-kit.dropdown.default.rounded', '2xl');
        config()->set('neura-kit.dropdown.default.shadow', 'none');

        $this->assertSame('2xl', neura_config('dropdown', 'rounded'));
        $this->assertSame('none', neura_config('dropdown', 'shadow'));
        $this->assertSame('rounded-2xl', PackResolver::rounded(neura_config('dropdown', 'rounded')));
        $this->assertSame('shadow-none', PackResolver::shadow(neura_config('dropdown', 'shadow')));
    }
}
