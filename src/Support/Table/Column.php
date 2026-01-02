<?php

namespace Neura\Kit\Support\Table;

use Closure;

class Column
{
    public string $component = 'neura::table.columns.column';
    public string $key;
    public string $label;

    public bool $sortable = false;
    public bool $searchable = false;
    public bool $filterable = false;
    public ?string $filterType = null;
    public ?array $filterOptions = null;
    public ?Closure $filterQuery = null;

    public ?int $width = null;
    public ?int $minWidth = null;
    public ?int $maxWidth = null;
    public bool $resizable = false;

    public ?Closure $format = null;
    public ?string $html = null;
    public ?string $formatUsing = null;

    public ?string $placeholder = null;
    public ?string $align = null;
    public ?Closure $tooltip = null;
    public ?Closure $labelCallback = null;
    public bool $badge = false;
    public bool $visible = true;
    public ?Closure $visibleCallback = null;
    public ?string $helpText = null;
    public bool $copyable = false;
    public bool $truncate = false;
    public ?int $truncateLength = null;

    public array $extraAttributes = [];

    public function __construct($key, $label) {
        $this->key = $key;
        $this->label = $label;
    }

    public static function status($key, $label = null, $enum = null, $colors = []): static {
        $column = static::make($key, $label)->component('neura::table.columns.status')->badge();
        if ($enum) {
            $column->extraAttributes['enum'] = $enum;
        }
        if (!empty($colors)) {
            $column->badgeColors($colors);
        }
        return $column;
    }

    public function badge(bool $badge = true): static {
        $this->badge = $badge;
        $this->extraAttributes['badge'] = $badge;
        return $this;
    }

    public function component(string $component): static {
        $this->component = $component;
        return $this;
    }

    public static function make($key, $label = null): static {
        return new static($key, $label ?? $key);
    }

    public function badgeColors(array $colors): static {
        $this->extraAttributes['colors'] = $colors;
        return $this;
    }

    public static function badgeColumn($key, $label = null, array $options = []): static {
        $column = static::make($key, $label)->component('neura::table.columns.badge')->badge();
        if (isset($options['colors'])) {
            $column->badgeColors($options['colors']);
        }
        if (isset($options['icons'])) {
            $column->badgeIcons($options['icons']);
        }
        if (isset($options['variants'])) {
            $column->badgeVariants($options['variants']);
        }
        if (isset($options['sizes'])) {
            $column->badgeSizes($options['sizes']);
        }
        if (isset($options['pill'])) {
            $column->badgePill($options['pill']);
        }
        if (isset($options['uppercase'])) {
            $column->extraAttributes['uppercase'] = $options['uppercase'];
        }
        return $column;
    }

    public function badgeIcons(array $icons): static {
        $this->extraAttributes['icons'] = $icons;
        return $this;
    }

    public function badgeVariants(array $variants): static {
        $this->extraAttributes['variants'] = $variants;
        return $this;
    }

    public function badgeSizes(array $sizes): static {
        $this->extraAttributes['sizes'] = $sizes;
        return $this;
    }

    public function badgePill(bool $pill = true): static {
        $this->extraAttributes['badgePill'] = $pill;
        return $this;
    }

    public static function boolean($key, $label = null): static {
        return static::make($key, $label)->component('neura::table.columns.boolean');
    }

    public static function date($key, $label = null, $format = null): static {
        $column = static::make($key, $label)->component('neura::table.columns.date');
        if ($format) {
            $column->formatUsing($format);
        }
        return $column;
    }

    public function formatUsing(string $format): static {
        $this->formatUsing = $format;
        return $this;
    }

    public static function humanDiff($key, $label = null): static {
        return static::make($key, $label)->component('neura::table.columns.human-diff')->sortable();
    }

    public function sortable(bool $sortable = true): static {
        $this->sortable = $sortable;
        return $this;
    }

    public static function icon($key, $label = null): static {
        return static::make($key, $label)->component('neura::table.columns.icon');
    }

    public static function color($key, $label = null): static {
        return static::make($key, $label)->component('neura::table.columns.color');
    }

    public static function count($key, $label = null): static {
        return static::make($key, $label)->component('neura::table.columns.count');
    }

    public static function avg($key, $label = null): static {
        return static::make($key, $label)->component('neura::table.columns.avg');
    }

    public static function increment($key, $label = null): static {
        return static::make($key, $label)->component('neura::table.columns.increment');
    }

    public static function htmlContent($key, $label = null): static {
        return static::make($key, $label)->component('neura::table.columns.html');
    }

    public static function userType($key, $label = null): static {
        return static::make($key, $label)->component('neura::table.columns.user-type');
    }

    public static function buttonGroup($key, $label = null): static {
        return static::make($key, $label)->component('neura::table.columns.button-group');
    }

    public static function componentColumn($key, $label = null, $component = null): static {
        $column = static::make($key, $label);
        if ($component) {
            $column->component($component);
        } else {
            $column->component('neura::table.columns.component');
        }
        return $column;
    }

    public static function livewire($key, $label = null, $component = null): static {
        $column = static::make($key, $label)->component('neura::table.columns.livewire');
        if ($component) {
            $column->extraAttributes['component'] = $component;
        }
        return $column;
    }

    public static function belongsTo($key, $label = null, $model = null, $attribute = 'name'): static {
        $column = static::make($key, $label)->component('neura::table.columns.belongs-to');
        if ($model) {
            $column->extraAttributes['model'] = $model;
        }
        $column->extraAttributes['attribute'] = $attribute;
        return $column;
    }

    public static function relation($key, $label = null, $relation = null, $attribute = 'name'): static {
        $column = static::make($key, $label)->component('neura::table.columns.relation');
        if ($relation) {
            $column->extraAttributes['relation'] = $relation;
        }
        $column->extraAttributes['attribute'] = $attribute;
        return $column;
    }

    public static function relationCount($key, $label = null, $relation = null, $showPopover = false, $popoverAttribute = 'name'): static {
        $column = static::make($key, $label)->component('neura::table.columns.relation-count');
        if ($relation) {
            $column->extraAttributes['relation'] = $relation;
        }
        $column->extraAttributes['showPopover'] = $showPopover;
        $column->extraAttributes['popoverAttribute'] = $popoverAttribute;
        return $column;
    }

    public static function actions($key, $label = null, array $actions = []): static {
        $column = static::make($key, $label)->component('neura::table.columns.actions');
        $column->extraAttributes['actions'] = $actions;
        return $column;
    }

    public static function email($key, $label = null): static {
        return static::link($key, $label)->copyable()->extraAttributes(['linkType' => 'email']);
    }

    public function extraAttributes(array $attributes): static {
        $this->extraAttributes = array_merge($this->extraAttributes, $attributes);
        return $this;
    }

    public function copyable(bool $copyable = true): static {
        $this->copyable = $copyable;
        $this->extraAttributes['copyable'] = $copyable;
        return $this;
    }

    public static function link($key, $label = null, $url = null): static {
        $column = static::make($key, $label)->component('neura::table.columns.link');
        if ($url) {
            $column->extraAttributes['url'] = $url;
        }
        return $column;
    }

    public static function phone($key, $label = null): static {
        return static::link($key, $label)->copyable()->extraAttributes(['linkType' => 'phone']);
    }

    public static function money($key, $label = null, string $currency = 'USD'): static {
        return static::text($key, $label)->alignEnd()->extraAttributes(['type' => 'money', 'currency' => $currency]);
    }

    public function alignEnd(): static {
        return $this->align('end');
    }

    public static function text($key, $label = null): static {
        return static::make($key, $label)->component('neura::table.columns.column');
    }

    public static function percentage($key, $label = null, int $decimals = 2): static {
        return static::text($key, $label)
            ->alignEnd()
            ->extraAttributes(['type' => 'percentage', 'decimals' => $decimals]);
    }

    public static function tags($key, $label = null, array $colors = []): static {
        return static::array($key, $label)->extraAttributes(['type' => 'tags', 'colors' => $colors]);
    }

    public static function array($key, $label = null): static {
        return static::make($key, $label)->component('neura::table.columns.array');
    }

    public static function avatar($key, $label = null, ?string $nameKey = null): static {
        $column = static::image($key, $label)->extraAttributes(['type' => 'avatar', 'rounded' => 'full']);
        if ($nameKey) {
            $column->extraAttributes['nameKey'] = $nameKey;
        }
        return $column;
    }

    public static function image($key, $label = null): static {
        return static::make($key, $label)->component('neura::table.columns.image');
    }

    public function searchable(bool $searchable = true): static {
        $this->searchable = $searchable;
        return $this;
    }

    public function filterable($filterable = true, $type = null, $options = null, $query = null): static {
        if (is_string($filterable)) {
            $this->filterable = true;
            $this->filterType = $filterable;
            if (is_array($type)) {
                $this->filterOptions = $type;
                $this->filterQuery = $options instanceof Closure ? $options : $query;
            } else {
                $this->filterOptions = is_array($options) ? $options : null;
                $this->filterQuery = $options instanceof Closure ? $options : ($query instanceof Closure ? $query : null);
            }
        } else {
            $this->filterable = $filterable;
            $this->filterType = is_string($type) ? $type : 'text';
            $this->filterOptions = is_array($options) ? $options : null;
            $this->filterQuery = $query instanceof Closure ? $query : null;
        }
        return $this;
    }

    public function width($width, $minWidth = null, $maxWidth = null): static {
        $this->width = $width;
        $this->minWidth = $minWidth;
        $this->maxWidth = $maxWidth;
        return $this;
    }

    public function resizable(bool $resizable = true): static {
        $this->resizable = $resizable;
        return $this;
    }

    public function format(Closure $callback): static {
        $this->format = $callback;
        return $this;
    }

    public function html(string $html): static {
        $this->html = $html;
        $this->component = 'neura::table.columns.html';
        return $this;
    }

    public function placeholder(string $placeholder): static {
        $this->placeholder = $placeholder;
        $this->extraAttributes['placeholder'] = $placeholder;
        return $this;
    }

    public function label(Closure $callback): static {
        $this->labelCallback = $callback;
        $this->extraAttributes['labelCallback'] = $callback;
        return $this;
    }

    public function alignStart(): static {
        return $this->align('start');
    }

    public function align(string $align): static {
        $this->align = $align;
        $this->extraAttributes['align'] = $align;
        return $this;
    }

    public function alignCenter(): static {
        return $this->align('center');
    }

    public function tooltip(Closure|string $tooltip): static {
        $this->tooltip = is_string($tooltip) ? fn() => $tooltip : $tooltip;
        $this->extraAttributes['tooltip'] = $this->tooltip;
        return $this;
    }

    public function badgeColor(Closure|string $color): static {
        $this->extraAttributes['badgeColor'] = $color;
        return $this;
    }

    public function badgeIcon(Closure|string $icon): static {
        $this->extraAttributes['badgeIcon'] = $icon;
        return $this;
    }

    public function badgeVariant(string $variant): static {
        $this->extraAttributes['badgeVariant'] = $variant;
        return $this;
    }

    public function badgeSize(string $size): static {
        $this->extraAttributes['badgeSize'] = $size;
        return $this;
    }

    public function helpText(string $text): static {
        $this->helpText = $text;
        $this->extraAttributes['helpText'] = $text;
        return $this;
    }

    public function truncate(bool $truncate = true, ?int $length = 50): static {
        $this->truncate = $truncate;
        $this->truncateLength = $length;
        $this->extraAttributes['truncate'] = $truncate;
        $this->extraAttributes['truncateLength'] = $length;
        return $this;
    }

    public function hidden(): static {
        return $this->visible(false);
    }

    public function visible(bool|Closure $visible): static {
        if ($visible instanceof Closure) {
            $this->visibleCallback = $visible;
        } else {
            $this->visible = $visible;
        }
        return $this;
    }

    public function class(string $class): static {
        $this->extraAttributes['class'] = $class;
        return $this;
    }
}
