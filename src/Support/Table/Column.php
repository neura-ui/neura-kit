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

    public bool $resizable = true;

    public ?Closure $format = null;

    public ?string $html = null;

    public ?string $formatUsing = null;

    public array $extraAttributes = [];

    public function __construct($key, $label)
    {
        $this->key = $key;
        $this->label = $label;
    }

    public static function make($key, $label = null)
    {
        return new static($key, $label ?? $key);
    }

    public function component($component)
    {
        $this->component = $component;

        return $this;
    }

    public function sortable($sortable = true)
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function searchable($searchable = true)
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function filterable($filterable = true, $type = null, $options = null, $query = null)
    {
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

    public function width($width, $minWidth = null, $maxWidth = null)
    {
        $this->width = $width;
        $this->minWidth = $minWidth;
        $this->maxWidth = $maxWidth;

        return $this;
    }

    public function resizable($resizable = true)
    {
        $this->resizable = $resizable;

        return $this;
    }

    public function format(Closure $callback)
    {
        $this->format = $callback;

        return $this;
    }

    public function html($html)
    {
        $this->html = $html;
        $this->component = 'neura::table.columns.html';

        return $this;
    }

    public function formatUsing($format)
    {
        $this->formatUsing = $format;

        return $this;
    }

    public static function htmlContent($key, $label = null)
    {
        return static::make($key, $label)
            ->component('neura::table.columns.html');
    }

    public static function humanDiff($key, $label = null)
    {
        return static::make($key, $label)
            ->component('neura::table.columns.human-diff');
    }

    public static function userType($key, $label = null)
    {
        return static::make($key, $label)
            ->component('neura::table.columns.user-type');
    }

    public static function text($key, $label = null)
    {
        return static::make($key, $label)
            ->component('neura::table.columns.column');
    }

    public static function array($key, $label = null)
    {
        return static::make($key, $label)
            ->component('neura::table.columns.array');
    }

    public static function avg($key, $label = null)
    {
        return static::make($key, $label)
            ->component('neura::table.columns.avg');
    }

    public static function boolean($key, $label = null)
    {
        return static::make($key, $label)
            ->component('neura::table.columns.boolean');
    }

    public static function buttonGroup($key, $label = null)
    {
        return static::make($key, $label)
            ->component('neura::table.columns.button-group');
    }

    public static function color($key, $label = null)
    {
        return static::make($key, $label)
            ->component('neura::table.columns.color');
    }

    public static function componentColumn($key, $label = null, $component = null)
    {
        $column = static::make($key, $label);
        
        if ($component) {
            $column->component($component);
        } else {
            $column->component('neura::table.columns.component');
        }

        return $column;
    }

    public static function count($key, $label = null)
    {
        return static::make($key, $label)
            ->component('neura::table.columns.count');
    }

    public static function date($key, $label = null, $format = null)
    {
        $column = static::make($key, $label)
            ->component('neura::table.columns.date');

        if ($format) {
            $column->formatUsing($format);
        }

        return $column;
    }

    public static function icon($key, $label = null)
    {
        return static::make($key, $label)
            ->component('neura::table.columns.icon');
    }

    public static function image($key, $label = null)
    {
        return static::make($key, $label)
            ->component('neura::table.columns.image');
    }

    public static function increment($key, $label = null)
    {
        return static::make($key, $label)
            ->component('neura::table.columns.increment');
    }

    public static function link($key, $label = null, $url = null)
    {
        $column = static::make($key, $label)
            ->component('neura::table.columns.link');

        if ($url) {
            $column->extraAttributes['url'] = $url;
        }

        return $column;
    }

    public static function livewire($key, $label = null, $component = null)
    {
        $column = static::make($key, $label)
            ->component('neura::table.columns.livewire');

        if ($component) {
            $column->extraAttributes['component'] = $component;
        }

        return $column;
    }

    public static function status($key, $label = null, $enum = null, $colors = [])
    {
        $column = static::make($key, $label)
            ->component('neura::table.columns.status');

        if ($enum) {
            $column->extraAttributes['enum'] = $enum;
        }

        if ($colors) {
            $column->extraAttributes['colors'] = $colors;
        }

        return $column;
    }

    public static function relation($key, $label = null, $relation = null, $attribute = 'name')
    {
        $column = static::make($key, $label)
            ->component('neura::table.columns.relation');

        if ($relation) {
            $column->extraAttributes['relation'] = $relation;
        }

        $column->extraAttributes['attribute'] = $attribute;

        return $column;
    }

    public static function relationCount($key, $label = null, $relation = null, $showPopover = false, $popoverAttribute = 'name')
    {
        $column = static::make($key, $label)
            ->component('neura::table.columns.relation-count');

        if ($relation) {
            $column->extraAttributes['relation'] = $relation;
        }

        $column->extraAttributes['showPopover'] = $showPopover;
        $column->extraAttributes['popoverAttribute'] = $popoverAttribute;

        return $column;
    }

    public static function belongsTo($key, $label = null, $model = null, $attribute = 'name')
    {
        $column = static::make($key, $label)
            ->component('neura::table.columns.belongs-to');

        if ($model) {
            $column->extraAttributes['model'] = $model;
        }

        $column->extraAttributes['attribute'] = $attribute;

        return $column;
    }

    public static function actions($key, $label = null, array $actions = [])
    {
        $column = static::make($key, $label)
            ->component('neura::table.columns.actions');

        $column->extraAttributes['actions'] = $actions;

        return $column;
    }

    public function extraAttributes(array $attributes)
    {
        $this->extraAttributes = array_merge($this->extraAttributes, $attributes);

        return $this;
    }
}

