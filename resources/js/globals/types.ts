export {};

/* -------------------------------------------------------------------------- */
/*  Shared primitives                                                          */
/* -------------------------------------------------------------------------- */

type ToastType = 'success' | 'error' | 'warning' | 'info';
type KeyValue = Record<string, unknown>;
type StringMap = Record<string, string>;

/* -------------------------------------------------------------------------- */
/*  Translations                                                               */
/* -------------------------------------------------------------------------- */

interface TranslationsAPI {
  translations: StringMap;
  currentLocale: string;
  fallbackLocale: string;

  load(locale?: string | null): Promise<StringMap>;
  setLocale(locale: string): Promise<StringMap>;

  getLocale(): string;
  getFallbackLocale(): string;

  t(key: string, params?: StringMap): string;
}

/* -------------------------------------------------------------------------- */
/*  Toast                                                                      */
/* -------------------------------------------------------------------------- */

interface ToastBuilder {
  duration(ms: number): ToastBuilder;
  success(content?: string): void;
  error(content?: string): void;
  warning(content?: string): void;
  info(content?: string): void;
}

interface ToastAPI {
  show(content: string, type?: ToastType | string, duration?: number): void;
  success(content: string, duration?: number): void;
  error(content: string, duration?: number): void;
  warning(content: string, duration?: number): void;
  info(content: string, duration?: number): void;
}

/* -------------------------------------------------------------------------- */
/*  Modal                                                                      */
/* -------------------------------------------------------------------------- */

interface ModalBuilder {
  with(args: KeyValue): ModalBuilder;
  attrs(attrs: KeyValue): ModalBuilder;
  maxWidth(width: string): ModalBuilder;
  open(args?: KeyValue): void;
  close(force?: boolean): void;
}

interface ModalAPI {
  open(
    component: string,
    args?: KeyValue,
    modalAttributes?: KeyValue
  ): void;
  close(force?: boolean, skipPreviousModals?: number, destroySkipped?: boolean): void;
}

/* -------------------------------------------------------------------------- */
/*  Dialog                                                                     */
/* -------------------------------------------------------------------------- */

interface DialogBuilder {
  title(t: string): DialogBuilder;
  message(m: string): DialogBuilder;

  info(): DialogBuilder;
  success(): DialogBuilder;
  warning(): DialogBuilder;
  danger(): DialogBuilder;

  confirmText(t: string): DialogBuilder;
  cancelText(t: string): DialogBuilder;
  hideCancel(): DialogBuilder;

  size(s: string): DialogBuilder;

  onConfirm(fn: () => void): DialogBuilder;
  onCancel(fn: () => void): DialogBuilder;

  show(): void;
}

/* -------------------------------------------------------------------------- */
/*  NeuraKit (public API only)                                                 */
/* -------------------------------------------------------------------------- */

interface NeuraKitAPI {
  toast(content?: string): ToastBuilder;
  modal(name?: string): ModalBuilder;
  dialog(title?: string): DialogBuilder;
}

/* -------------------------------------------------------------------------- */
/*  Command Spotlight                                                          */
/* -------------------------------------------------------------------------- */

interface CommandItem {
  id: string;
  name: string;
  description?: string;
  icon?: string;
  shortcut?: string;
  action?: () => void;
}

interface CommandSpotlightConfig {
  placeholder?: string;
  commands?: CommandItem[];
  showResultsWithoutInput?: boolean;
}

interface CommandSpotlightInstance {
  open(): void;
  close(): void;
  toggle(): void;
}

interface CommandSpotlightManager {
  register(instance: CommandSpotlightInstance): void;
  open(instance: CommandSpotlightInstance): boolean;
  close(instance: CommandSpotlightInstance): void;
}

/* -------------------------------------------------------------------------- */
/*  Alpine                                                                     */
/* -------------------------------------------------------------------------- */

interface AlpineComponent {
  $el: HTMLElement;
  $refs: Record<string, HTMLElement | null>;
  $watch<T = unknown>(prop: string, callback: (value: T) => void): void;
  $dispatch(event: string, detail?: unknown): void;
  $nextTick(callback?: () => void): Promise<void>;
}

type AlpineComponentFactory<T = Record<string, unknown>> = (
  ...args: any[]
) => T & Partial<AlpineComponent>;

interface AlpineGlobal {
  data<T = Record<string, unknown>>(
    name: string,
    factory: AlpineComponentFactory<T>
  ): void;

  reactive<T>(obj: T): T;

  magic(name: string, factory: () => unknown): void;
}

declare global {
  type AlpineThis<T = Record<string, unknown>> = T & AlpineComponent;
}

/* -------------------------------------------------------------------------- */
/*  Livewire                                                                  */
/* -------------------------------------------------------------------------- */

interface LivewireComponent {
  call(method: string, ...args: unknown[]): void;
}

interface LivewireAPI {
  find(id: string): LivewireComponent | null;
  navigate?(url: string): void;
}

/* -------------------------------------------------------------------------- */
/*  Global Window Augmentation                                                 */
/* -------------------------------------------------------------------------- */

declare global {
  interface Window {
    Alpine: AlpineGlobal;

    Livewire?: LivewireAPI;

    NeuraKit?: NeuraKitAPI;
    NeuraKitToast?: ToastAPI;
    NeuraKitModal?: ModalAPI;
    NeuraKitTranslations?: TranslationsAPI;

    t?: (key: string, params?: StringMap) => string;

    CommandSpotlightManager?: CommandSpotlightManager;
    CommandSpotlight?: (config: CommandSpotlightConfig) => CommandSpotlightInstance;
  }
}