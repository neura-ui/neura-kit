import './globals/types';

export function defineMagic<T extends Record<string, unknown>>(
  name: string,
  object: T & { init?: () => void }
): void {
  const instance = window.Alpine.reactive(object);
  if (instance.init) instance.init();
  window.Alpine.magic(name, () => instance);
  const capitalizedName = name[0].toUpperCase() + name.slice(1);
  (window as unknown as Record<string, unknown>)[capitalizedName] = instance;
}

export function debounce<T extends (...args: unknown[]) => unknown>(
  fn: T,
  ms = 300
): (...args: Parameters<T>) => void {
  let id: ReturnType<typeof setTimeout>;
  return (...args: Parameters<T>) => {
    clearTimeout(id);
    id = setTimeout(() => fn(...args), ms);
  };
}

export function throttle<T extends (...args: unknown[]) => unknown>(
  fn: T,
  ms = 300
): (...args: Parameters<T>) => void {
  let last = 0;
  return (...args: Parameters<T>) => {
    const now = Date.now();
    if (now - last >= ms) {
      last = now;
      fn(...args);
    }
  };
}

