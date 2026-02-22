<?php

namespace Neura\Kit\Components\Atoms;

use Exception;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Reflector;
use Illuminate\View\View;
use Livewire\Component;
use Neura\Kit\Support\Sideover\SideoverComponent;
use Neura\Kit\Support\Sideover\Contracts\SideoverComponent as SideoverComponentContract;
use ReflectionClass;

class SideoverManager extends Component
{
    public ?string $activeComponent = null;

    public array $components = [];

    protected static array $classCache = [];

    protected static array $reflectionCache = [];

    protected static array $propertyTypesCache = [];

    public function resetState(): void
    {
        $this->components = [];
        $this->activeComponent = null;
    }

    /**
     * @throws Exception
     */
    public function openSideover($component, $arguments = [], $sideoverAttributes = []): void
    {
        $arguments = is_array($arguments) ? $arguments : [];
        $sideoverAttributes = is_array($sideoverAttributes) ? $sideoverAttributes : [];

        $componentClass = $this->resolveComponentClass($component);

        if (! is_subclass_of($componentClass, SideoverComponentContract::class)) {
            throw new Exception("[{$componentClass}] does not implement [".SideoverComponentContract::class.'] interface.');
        }

        $id = $this->generateComponentId($component, $arguments);

        if (isset($this->components[$id])) {
            $this->activeComponent = $id;
            $this->dispatch('activeSideoverComponentChanged', id: $id, sideoverAttributes: $this->components[$id]['sideoverAttributes']);
            $this->dispatch('openSideover');

            return;
        }

        $reflect = $this->getReflectionClass($componentClass);
        $resolvedArgs = $this->resolveComponentProps($arguments, $componentClass, $reflect);
        $mergedArgs = $arguments;
        foreach ($resolvedArgs as $key => $resolvedValue) {
            $mergedArgs[$key] = $resolvedValue;
        }

        $sideoverAttributes = array_merge([
            'closeOnClickAway' => $componentClass::closeSideoverOnClickAway(),
            'closeOnEscape' => $componentClass::closeSideoverOnEscape(),
            'closeOnEscapeIsForceful' => $componentClass::closeSideoverOnEscapeIsForceful(),
            'dispatchCloseEvent' => $componentClass::dispatchCloseEvent(),
            'destroyOnClose' => $componentClass::destroyOnClose(),
            'side' => $componentClass::sideoverSide(),
            'width' => $componentClass::sideoverWidth(),
            'widthClass' => $componentClass::sideoverWidthClass(),
        ], $sideoverAttributes);

        if (isset($sideoverAttributes['width'])) {
            $widthClass = $componentClass::getWidthClass($sideoverAttributes['width']);
            if ($widthClass !== null) {
                $sideoverAttributes['widthClass'] = $widthClass;
            }
        }

        $this->components[$id] = [
            'name' => $component,
            'arguments' => $mergedArgs,
            'sideoverAttributes' => $sideoverAttributes,
        ];

        $this->activeComponent = $id;
        $this->dispatch('activeSideoverComponentChanged', id: $id, sideoverAttributes: $this->components[$id]['sideoverAttributes']);
        $this->dispatch('openSideover');
    }

    protected function resolveComponentClass(string $component): string
    {
        if (isset(static::$classCache[$component])) {
            return static::$classCache[$component];
        }

        if (class_exists($component)) {
            static::$classCache[$component] = $component;

            return $component;
        }

        $namespace = config('livewire.class_namespace', 'App\\Livewire');
        $parts = explode('.', $component);
        $className = implode('\\', array_map(
            fn ($part) => str_replace(['-', '_'], '', ucwords($part, '-_')),
            $parts
        ));

        $fullClassName = $namespace.'\\'.$className;

        if (! class_exists($fullClassName)) {
            throw new Exception("Component class [{$fullClassName}] not found for component [{$component}].");
        }

        static::$classCache[$component] = $fullClassName;

        return $fullClassName;
    }

    public function resolveComponentProps(array $attributes, string $componentClass, ReflectionClass $reflect): Collection
    {
        return $this->getPublicPropertyTypes($componentClass, $reflect)
            ->intersectByKeys($attributes)
            ->map(fn ($className, $propName) => $this->resolveParameter($attributes, $propName, $className));
    }

    protected function resolveParameter($attributes, $parameterName, $parameterClassName)
    {
        $value = $attributes[$parameterName] ?? null;

        if ($value instanceof $parameterClassName) {
            return $value;
        }

        if ($value instanceof UrlRoutable && is_subclass_of($parameterClassName, UrlRoutable::class)) {
            return $value;
        }

        if (enum_exists($parameterClassName)) {
            if ($value === null) {
                return null;
            }
            $enum = $parameterClassName::tryFrom($value);

            return $enum ?? $value;
        }

        if (is_subclass_of($parameterClassName, UrlRoutable::class)) {
            if ($value === null) {
                return null;
            }

            $instance = app()->make($parameterClassName);
            $model = $instance->resolveRouteBinding($value);

            if (! $model) {
                throw (new ModelNotFoundException)->setModel(get_class($instance), [$value]);
            }

            return $model;
        }

        return $value;
    }

    protected function getReflectionClass(string $componentClass): ReflectionClass
    {
        return static::$reflectionCache[$componentClass]
            ??= new ReflectionClass($componentClass);
    }

    protected function generateComponentId(string $component, array $arguments): string
    {
        return md5($component.json_encode($this->normalizeArguments($arguments), JSON_UNESCAPED_SLASHES));
    }

    protected function normalizeArguments(array $arguments): array
    {
        return array_map(function ($value) {
            if ($value instanceof UrlRoutable) {
                return get_class($value).':'.$value->getRouteKey();
            }
            if (is_object($value)) {
                return get_class($value).':'.(method_exists($value, 'getRouteKey') ? $value->getRouteKey() : spl_object_hash($value));
            }

            return $value;
        }, $arguments);
    }

    public function getPublicPropertyTypes(string $componentClass, ReflectionClass $reflect): Collection
    {
        if (isset(static::$propertyTypesCache[$componentClass])) {
            return static::$propertyTypesCache[$componentClass];
        }

        $propertyTypes = collect($reflect->getProperties(\ReflectionProperty::IS_PUBLIC))
            ->mapWithKeys(fn ($prop) => ($type = Reflector::getParameterClassName($prop)) ? [$prop->getName() => $type] : [])
            ->filter();

        static::$propertyTypesCache[$componentClass] = $propertyTypes;

        return $propertyTypes;
    }

    public function destroyComponent($id): void
    {
        unset($this->components[$id]);
    }

    public function getListeners(): array
    {
        return [
            'closeSideover',
            'destroyComponent',
        ];
    }

    public function closeSideover($force = false, $skipPreviousSideovers = 0, $destroySkipped = false): void
    {
        if (empty($this->components) || $this->activeComponent === null) {
            $this->resetState();

            return;
        }

        $keys = array_keys($this->components);
        $currentId = $this->activeComponent;

        if ($skipPreviousSideovers > 0) {
            $currentIndex = array_search($currentId, $keys);

            if ($currentIndex !== false) {
                $startIndex = max(0, $currentIndex - $skipPreviousSideovers);
                $toRemove = array_slice($keys, $startIndex, $skipPreviousSideovers + 1);

                foreach ($toRemove as $id) {
                    $this->destroyComponent($id);
                }

                $remaining = array_keys($this->components);
                $this->activeComponent = $remaining ? end($remaining) : null;
            }
        } else {
            $attrs = $this->components[$currentId]['sideoverAttributes'] ?? [];
            $shouldDestroy = $force || ($attrs['destroyOnClose'] ?? true);

            if ($shouldDestroy) {
                $this->destroyComponent($currentId);
            }

            $remaining = array_keys($this->components);

            if (! $shouldDestroy) {
                $currentIndex = array_search($currentId, $remaining);
                if ($currentIndex !== false && $currentIndex > 0) {
                    $this->activeComponent = $remaining[$currentIndex - 1];
                } else {
                    $this->activeComponent = null;
                }
            } else {
                $this->activeComponent = $remaining ? end($remaining) : null;
            }
        }

        if (empty($this->components) || $this->activeComponent === null) {
            $this->resetState();
            $this->dispatch('closeSideover');
        } else {
            $this->dispatch('activeSideoverComponentChanged', id: $this->activeComponent, sideoverAttributes: $this->components[$this->activeComponent]['sideoverAttributes'] ?? []);
        }
    }

    public function render(): View
    {
        if (view()->exists('neura::sideover-manager.index')) {
            return view('neura::sideover-manager.index');
        }

        $viewPath = realpath(__DIR__.'/../../resources/views/neura/sideover-manager/index.blade.php');
        if ($viewPath && file_exists($viewPath)) {
            return view()->file($viewPath);
        }

        return view('neura-kit::neura.sideover-manager.index');
    }
}
