import './types';

/* -------------------------------------------------------------------------- */
/* Context Menu Manager (Singleton Pattern)                                  */
/* -------------------------------------------------------------------------- */

class ContextMenuManager {
  private instances = new Set<any>();
  private currentOpen: any = null;

  register(instance: any): void {
    this.instances.add(instance);
  }

  unregister(instance: any): void {
    this.instances.delete(instance);
    if (this.currentOpen === instance) {
      this.currentOpen = null;
    }
  }

  closeAll(exceptInstance?: any): void {
    // Force close all menus except the one specified
    this.instances.forEach((instance: any) => {
      if (instance !== exceptInstance) {
        // Force set isOpen to false
        instance.isOpen = false;
      }
    });
    
    // Update current open reference
    this.currentOpen = exceptInstance || null;
  }

  requestOpen(instance: any): void {
    // Close all other menus first
    this.closeAll(instance);
    this.currentOpen = instance;
  }

  // Public method to check if click is inside any open menu
  isClickInsideAnyMenu(target: HTMLElement): boolean {
    for (const instance of this.instances) {
      if (!instance.isOpen) continue;
      
      const menu = instance.$refs?.menu as HTMLElement | undefined;
      const trigger = instance.$refs?.trigger as HTMLElement | undefined;
      
      if (menu && menu.contains(target)) {
        return true;
      }
      if (trigger && trigger.contains(target)) {
        return true;
      }
    }
    return false;
  }
}

// Singleton instance
const contextMenuManager = new ContextMenuManager();

/* -------------------------------------------------------------------------- */
/* Alpine initialization                                                      */
/* -------------------------------------------------------------------------- */

if (typeof document !== 'undefined') {
  document.addEventListener('alpine:init', () => {
    window.Alpine.data('contextMenu', contextMenu);
  });
  
  // Global click handler to close all menus when clicking outside
  document.addEventListener('mousedown', (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (!target) return;
    
    // Check if click is inside any open menu using the manager method
    const clickedInsideAnyMenu = contextMenuManager.isClickInsideAnyMenu(target);
    
    // If clicked outside all menus, close them all
    if (!clickedInsideAnyMenu) {
      contextMenuManager.closeAll();
    }
  }, true);
  
  // Global contextmenu handler to close all menus before opening a new one
  document.addEventListener('contextmenu', () => {
    // Close all menus - the new one will open via its own handler
    contextMenuManager.closeAll();
  }, true);
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
      // Register this instance with the manager
      contextMenuManager.register(this);
      
      const closeIfOpen = () => {
        if (this.isOpen) {
          this.isOpen = false;
        }
      };

      // Store handlers for cleanup
      const scrollHandler = closeIfOpen;
      const resizeHandler = closeIfOpen;
      const navigateHandler = closeIfOpen;

      window.addEventListener('scroll', scrollHandler, true);
      window.addEventListener('resize', resizeHandler);
      document.addEventListener('livewire:navigated', navigateHandler);
      
      // Cleanup on destroy
      (this as any).__cleanup = () => {
        window.removeEventListener('scroll', scrollHandler, true);
        window.removeEventListener('resize', resizeHandler);
        document.removeEventListener('livewire:navigated', navigateHandler);
        contextMenuManager.unregister(this);
      };
    },

    open(event: MouseEvent) {
      event.preventDefault();
      event.stopPropagation();

      // Request to open - this will close all other menus first
      contextMenuManager.requestOpen(this);

      // Open this menu
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
 */
interface AlpineMagic {
  $nextTick(cb: () => void): void;
  $refs?: any;
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