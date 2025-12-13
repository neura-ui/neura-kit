<?php

namespace Neura\Kit\Components\Atoms;

use Exception;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Reflector;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Attributes\On;
use ReflectionClass;
use Neura\Kit\Support\Modal\Contracts\ModalComponent as ModalComponentContract;

class ModalManager extends Component
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

    public function openModal($component, $arguments = [], $modalAttributes = []): void
    {
        $componentClass = $this->resolveComponentClass($component);

        if (!is_subclass_of($componentClass, ModalComponentContract::class)) {
            throw new Exception("[{$componentClass}] does not implement [" . ModalComponentContract::class . "] interface.");
        }

        $id = $this->generateComponentId($component, $arguments);
        
        if (isset($this->components[$id])) {
            $this->activeComponent = $id;
            $this->dispatch('activeModalComponentChanged', id: $id, modalAttributes: $this->components[$id]['modalAttributes']);
            $this->dispatch('openModal');
            return;
        }

        $reflect = $this->getReflectionClass($componentClass);
        $resolvedArgs = $this->resolveComponentProps($arguments, $componentClass, $reflect);
        $mergedArgs = array_merge($arguments, $resolvedArgs->all());

        $this->components[$id] = [
            'name' => $component,
            'arguments' => $mergedArgs,
            'modalAttributes' => array_merge([
                'closeOnClickAway' => $componentClass::closeModalOnClickAway(),
                'closeOnEscape' => $componentClass::closeModalOnEscape(),
                'closeOnEscapeIsForceful' => $componentClass::closeModalOnEscapeIsForceful(),
                'dispatchCloseEvent' => $componentClass::dispatchCloseEvent(),
                'destroyOnClose' => $componentClass::destroyOnClose(),
                'maxWidth' => $componentClass::modalMaxWidth(),
                'maxWidthClass' => $componentClass::modalMaxWidthClass(),
            ], $modalAttributes),
        ];

        $this->activeComponent = $id;
        $this->dispatch('activeModalComponentChanged', id: $id, modalAttributes: $this->components[$id]['modalAttributes']);
        $this->dispatch('openModal');
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
            fn($part) => str_replace(['-', '_'], '', ucwords($part, '-_')),
            $parts
        ));
        
        $fullClassName = $namespace . '\\' . $className;
        
        if (!class_exists($fullClassName)) {
            throw new Exception("Component class [{$fullClassName}] not found for component [{$component}].");
        }
        
        static::$classCache[$component] = $fullClassName;
        return $fullClassName;
    }

    public function resolveComponentProps(array $attributes, string $componentClass, ReflectionClass $reflect): Collection
    {
        return $this->getPublicPropertyTypes($componentClass, $reflect)
            ->intersectByKeys($attributes)
            ->map(fn($className, $propName) => $this->resolveParameter($attributes, $propName, $className));
    }

    protected function resolveParameter($attributes, $parameterName, $parameterClassName)
    {
        $value = $attributes[$parameterName];

        if ($value instanceof $parameterClassName || $value instanceof UrlRoutable) {
            return $value;
        }

        if (enum_exists($parameterClassName)) {
            $enum = $parameterClassName::tryFrom($value);
            if ($enum !== null) return $enum;
        }

        if (is_subclass_of($parameterClassName, UrlRoutable::class)) {
        $instance = app()->make($parameterClassName);
            $model = $instance->resolveRouteBinding($value);

            if (!$model) {
                throw (new ModelNotFoundException())->setModel(get_class($instance), [$value]);
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
        return md5($component . json_encode($this->normalizeArguments($arguments), JSON_UNESCAPED_SLASHES));
    }
    
    protected function normalizeArguments(array $arguments): array
    {
        return array_map(function ($value) {
            if ($value instanceof UrlRoutable) {
                return get_class($value) . ':' . $value->getRouteKey();
            }
            if (is_object($value)) {
                return get_class($value) . ':' . (method_exists($value, 'getRouteKey') ? $value->getRouteKey() : spl_object_hash($value));
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
            ->mapWithKeys(fn($prop) => ($type = Reflector::getParameterClassName($prop)) ? [$prop->getName() => $type] : [])
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
        return ['destroyComponent'];
    }

    #[On('openModal')]
    public function handleOpenModal(array $data = []): void
    {
        if ($component = $data['component'] ?? null) {
            $this->openModal($component, $data['arguments'] ?? [], $data['modalAttributes'] ?? []);
        }
    }

    public function closeModal($force = false, $skipPreviousModals = 0, $destroySkipped = false): void
    {
        if (empty($this->components) || $this->activeComponent === null) {
            $this->resetState();
            return;
        }

        $keys = array_keys($this->components);

        if ($skipPreviousModals > 0) {
            $currentIndex = array_search($this->activeComponent, $keys);

            if ($currentIndex !== false) {
                $startIndex = max(0, $currentIndex - $skipPreviousModals);
                $toRemove = array_slice($keys, $startIndex, $skipPreviousModals + 1);

                    if ($destroySkipped || $force) {
                    foreach ($toRemove as $id) {
                        $this->destroyComponent($id);
                    }
                }

                $remaining = array_diff($keys, $toRemove);
                $this->activeComponent = $remaining ? end($remaining) : null;
            }
        } else {
            $attrs = $this->components[$this->activeComponent]['modalAttributes'] ?? [];
            
            if ($force || ($attrs['destroyOnClose'] ?? false)) {
                $this->destroyComponent($this->activeComponent);
            }

            $remaining = array_keys($this->components);
            $this->activeComponent = $remaining ? end($remaining) : null;
        }

        if (empty($this->components)) {
            $this->resetState();
        } else {
            $this->dispatch('activeModalComponentChanged', id: $this->activeComponent);
        }
        
        $this->dispatch('closeModal');
    }

    public function render(): View
    {
        if (view()->exists('neura::modal-manager.index')) {
            return view('neura::modal-manager.index');
        }
        
        $viewPath = realpath(__DIR__.'/../../resources/views/neura/modal-manager/index.blade.php');
        if ($viewPath && file_exists($viewPath)) {
            return view()->file($viewPath);
        }

        return view('neura-kit::neura.modal-manager.index');
    }
}
