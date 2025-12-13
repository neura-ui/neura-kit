import './types';

if (typeof document !== 'undefined') {
  document.addEventListener('alpine:init', () => {
    window.Alpine.data('contextMenu', contextMenu);
  });
}

/* -------------------------------------------------------------------------- */
/* Alpine factory                                                             */
/* -------------------------------------------------------------------------- */

function contextMenu() {
  return {
    isOpen: false,
    x: 0,
    y: 0,

    init() {
      const closeIfOpen = () => this.isOpen && this.close();

      window.addEventListener('scroll', closeIfOpen, true);
      window.addEventListener('resize', closeIfOpen);

      document.addEventListener('livewire:navigated', closeIfOpen);
    },

    open(event: MouseEvent) {
      event.preventDefault();
      event.stopPropagation();

      this.isOpen = true;
      this.x = event.clientX;
      this.y = event.clientY;

      (this as unknown as AlpineThis).$nextTick(() => this.adjustPosition());
    },

    close() {
      this.isOpen = false;
    },

    adjustPosition() {
      const menu = (this as unknown as AlpineThis).$refs.menu;
      if (!menu) return;

      const { width, height } = menu.getBoundingClientRect();

      this.x = clamp(this.x, 0, window.innerWidth - width);
      this.y = clamp(this.y, 0, window.innerHeight - height);
    },
  };
}

/* -------------------------------------------------------------------------- */
/* Types                                                                      */
/* -------------------------------------------------------------------------- */

/**
 * Alpine runtime augmentation
 * (never returned by the factory)
 */
interface AlpineMagic {
  $nextTick(cb: () => void): void;
}

/**
 * Component "this" context
 */
type ContextMenu = ReturnType<typeof contextMenu> & AlpineMagic;

/* -------------------------------------------------------------------------- */
/* Utilities                                                                  */
/* -------------------------------------------------------------------------- */

function clamp(value: number, min: number, max: number): number {
  return Math.min(Math.max(value, min), max);
}
