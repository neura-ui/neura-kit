@props([
    'label' => null,
    'placeholder' => 'Write a comment, @ to mention',
    'people' => [],
    'channels' => [],
    'value' => '',
    'mentions' => [],
    'name' => null,
    'id' => null,
    'minRows' => 1,
    'maxRows' => 8,
    'maxSuggestions' => 6,
    'placement' => 'auto',
    'submitOnEnter' => false,
    'method' => null,
    'size' => null,
    'variant' => null,
    'color' => null,
    'rounded' => null,
    'shadow' => null,
    'disabled' => false,
    'ariaLabel' => null,
])

@php
    use Neura\Kit\Support\PackResolver;

    $sizes = ['xs', 'sm', 'md', 'lg', 'xl'];
    $variantStyles = ['solid', 'dark', 'outline', 'soft', 'ghost'];
    $size = $size ?? neura_config('mention-input', 'size') ?? 'md';
    $variant = $variant ?? neura_config('mention-input', 'variant') ?? 'outline';
    $color = $color ?? neura_config('mention-input', 'color') ?? 'primary';
    $rounded = $rounded ?? neura_config('mention-input', 'rounded');
    $shadow = $shadow ?? neura_config('mention-input', 'shadow');

    if (str_contains((string) $variant, '-')) {
        [$maybeColor, $maybeVariant] = explode('-', (string) $variant, 2);
        if (in_array($maybeVariant, $variantStyles, true)) {
            $color = $color ?: $maybeColor;
            $variant = $maybeVariant;
        }
    }

    $size = in_array($size, $sizes, true) ? $size : 'md';
    $variant = in_array($variant, $variantStyles, true) ? $variant : 'outline';
    $color = strtolower((string) $color);
    $color = preg_match('/^[a-z0-9-]+$/', $color) ? $color : 'primary';
    $roundedToken = PackResolver::roundedToken($rounded);
    $roundedClass = PackResolver::rounded($rounded);
    $shadowClass = in_array($variant, ['ghost', 'soft'], true)
        ? 'shadow-none'
        : PackResolver::shadow($shadow);
    $toneStyle = $color !== '' && $color !== 'neutral'
        ? "--nk-tone: var(--color-{$color}-600); --nk-tone-strong: var(--color-{$color}-900); --nk-tone-soft: var(--color-{$color}-100);"
        : '';

    $peopleList = is_string($people) ? (json_decode($people, true) ?: []) : ($people ?: []);
    $channelList = is_string($channels) ? (json_decode($channels, true) ?: []) : ($channels ?: []);
    $mentionList = is_string($mentions) ? (json_decode($mentions, true) ?: []) : ($mentions ?: []);

    $wireModel = null;
    foreach ($attributes->getAttributes() as $key => $attrValue) {
        if (str_starts_with($key, 'wire:model')) {
            $wireModel = $attrValue;
            break;
        }
    }

    $ariaLabel = $ariaLabel ?: $attributes->get('aria-label');
    $describedBy = $attributes->get('aria-describedby');
    $fieldId = $id ?: 'nk-mention-'.substr(md5(uniqid((string) $label, true)), 0, 8);
    $fieldName = $name ?: $wireModel;
    $minRows = max(1, (int) $minRows);
    $maxRows = max($minRows, (int) $maxRows);

    $config = [
        'text' => (string) $value,
        'mentions' => array_values($mentionList),
        'people' => array_values($peopleList),
        'channels' => array_values($channelList),
        'minRows' => $minRows,
        'maxRows' => $maxRows,
        'maxSuggestions' => max(1, (int) $maxSuggestions),
        'placement' => in_array($placement, ['auto', 'top', 'bottom'], true) ? $placement : 'auto',
        'submitOnEnter' => (bool) $submitOnEnter,
        'disabled' => (bool) $disabled,
        'method' => $method,
        'wireProperty' => $wireModel,
        'listId' => $fieldId.'-list',
    ];

    $root = [
        'data-size' => $size,
        'data-variant' => $variant,
        'data-color' => $color !== '' ? $color : 'neutral',
        'data-rounded' => $roundedToken,
        'x-data' => 'neuraMentionInput('.json_encode($config).')',
        'x-modelable' => 'text',
        'x-bind:data-disabled' => "disabled ? '' : null",
    ];

    $userStyle = trim((string) $attributes->get('style'));
    $style = trim($toneStyle.' '.$userStyle);
    if ($style !== '') {
        $root['style'] = $style;
    }

    $rootAttributes = $attributes
        ->whereDoesntStartWith('wire:model')
        ->except(['style', 'aria-label', 'aria-describedby'])
        ->merge($root)
        ->class(['nk-mention']);
@endphp

<div {{ $rootAttributes }}>
    @if (filled($label))
        <label class="nk-mention-label" for="{{ $fieldId }}">{{ $label }}</label>
    @endif

    <div @class(['nk-mention-shell', $roundedClass, $shadowClass]) x-ref="shell">
        <div class="nk-mention-field">
            <div class="nk-mention-mirror" x-ref="mirror" aria-hidden="true"></div>
            <textarea
                x-ref="input"
                id="{{ $fieldId }}"
                class="nk-mention-input"
                rows="{{ $minRows }}"
                placeholder="{{ $placeholder }}"
                role="combobox"
                aria-autocomplete="list"
                aria-haspopup="listbox"
                @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                x-bind:aria-expanded="open ? 'true' : 'false'"
                x-bind:aria-controls="open ? listId : null"
                x-bind:aria-activedescendant="activeDescendant()"
                @disabled($disabled)
                @if ($fieldName) name="{{ $fieldName }}" @endif
                x-on:input="onInput()"
                x-on:keydown="onKey($event)"
                x-on:scroll="syncScroll()"
                x-on:click="refreshTrigger()"
            >{{ $value }}</textarea>
            <div class="nk-mention-probe" x-ref="probe"></div>
        </div>

        <div
            class="nk-mention-panel nk-menu-motion {{ $roundedClass }} {{ $shadowClass }}"
            data-side="bottom"
            data-align="start"
            x-ref="panel"
            x-show="open"
            x-cloak
            x-transition:enter="nk-menu-motion"
            x-transition:enter-start="nk-menu-from"
            x-transition:enter-end="nk-menu-to"
            x-transition:leave="nk-menu-leaving"
            x-transition:leave-start="nk-menu-to"
            x-transition:leave-end="nk-menu-out"
        >
            <div class="nk-mention-panel-label" x-text="trigger && trigger.kind === 'channel' ? 'Channels' : 'People'"></div>
            <div
                class="nk-mention-list"
                role="listbox"
                x-ref="list"
                x-bind:id="listId"
                x-bind:aria-label="trigger && trigger.kind === 'channel' ? 'Channels' : 'People'"
            >
                <div class="nk-mention-glide" x-ref="glide" x-show="suggestions.length"></div>
                <template x-for="(item, index) in suggestions" :key="item.id">
                    <button
                        type="button"
                        class="nk-mention-option"
                        role="option"
                        x-bind:id="optionId(index)"
                        x-bind:data-index="index"
                        x-bind:data-kind="trigger ? trigger.kind : 'person'"
                        x-bind:aria-selected="index === active ? 'true' : 'false'"
                        x-on:pointerdown.prevent="choose(item)"
                        x-on:pointerenter="active = index; moveGlide(true)"
                    >
                        <span class="nk-mention-avatar" aria-hidden="true">
                            <template x-if="item.avatar">
                                <img x-bind:src="item.avatar" alt="">
                            </template>
                            <template x-if="!item.avatar && trigger && trigger.kind === 'channel'">
                                <span>#</span>
                            </template>
                            <template x-if="!item.avatar && (!trigger || trigger.kind !== 'channel')">
                                <span x-text="initials(item.name)"></span>
                            </template>
                        </span>
                        <span class="nk-mention-copy">
                            <span class="nk-mention-name" x-text="item.name"></span>
                            <span class="nk-mention-meta" x-show="meta(item)" x-text="meta(item)"></span>
                        </span>
                    </button>
                </template>
            </div>
            <p class="nk-mention-empty" x-show="suggestions.length === 0">No matches</p>
        </div>
    </div>

    @if ($fieldName)
        <input type="hidden" name="{{ $fieldName }}_mentions" x-bind:value="serialized()" />
    @endif

    <span class="nk-mention-live" aria-live="polite" x-text="live"></span>
</div>
