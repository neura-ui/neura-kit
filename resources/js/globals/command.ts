import './types';

interface Command {
  id: string;
  name: string;
  description: string;
  synonyms?: string[];
  shortcut?: string;
  action?: (() => void) | string;
  href?: string;
  navigate?: boolean;
  wireClick?: string;
  onClick?: () => void;
}

interface CommandSpotlightInstance {
  inputPlaceholder?: string;
  commands: Command[];
  showResultsWithoutInput?: boolean;
  isOpen: boolean;
  activeIndex: number | null;
  input: string;
  init(): void;
  handleKeydown(e: KeyboardEvent): void;
  close(): void;
  toggleOpen(): void;
  open(): void;
  filteredItems(): Array<[{ item: Command }, number]>;
  selectUp(): void;
  selectDown(): void;
  scrollToActive(): void;
  go(id?: string): void;
  $watch: (property: string, callback: () => void) => void;
  $nextTick: (callback: () => void) => void;
  $refs: Record<string, HTMLElement | null>;
  $el: HTMLElement;
  $wire?: {
    call(method: string): void;
  };
}

interface CommandSpotlightManagerLocal {
  instances: CommandSpotlightInstance[];
  currentOpen: CommandSpotlightInstance | null;
  isOpening: boolean;
  register(instance: CommandSpotlightInstance): void;
  open(instance: CommandSpotlightInstance): boolean;
  close(instance: CommandSpotlightInstance): void;
}

if (typeof window !== 'undefined') {
  if (!window.CommandSpotlightManager) {
    (window as any).CommandSpotlightManager = {
      instances: [] as CommandSpotlightInstance[],
      currentOpen: null as CommandSpotlightInstance | null,
      isOpening: false,

      register(instance: CommandSpotlightInstance): void {
        this.instances.push(instance);
      },

      open(instance: CommandSpotlightInstance): boolean {
        if (this.isOpening) return false;

        if (this.currentOpen && this.currentOpen !== instance) {
          this.currentOpen.isOpen = false;
          this.currentOpen = null;
        }

        this.isOpening = true;
        this.currentOpen = instance;
        instance.isOpen = true;

        setTimeout(() => {
          this.isOpening = false;
        }, 50);

        return true;
      },

      close(instance: CommandSpotlightInstance): void {
        if (this.currentOpen === instance) {
          this.currentOpen = null;
        }
        instance.isOpen = false;
        instance.input = '';
        instance.activeIndex = null;
        this.isOpening = false;
      },
    } as CommandSpotlightManagerLocal;
  }

  (window as any).CommandSpotlight = (config: {
    placeholder?: string;
    commands?: Command[];
    showResultsWithoutInput?: boolean;
  }): CommandSpotlightInstance => {
    return {
      inputPlaceholder: config.placeholder,
      commands: config.commands || [],
      showResultsWithoutInput: config.showResultsWithoutInput,
      isOpen: false,
      activeIndex: null,
      input: '',

      init(): void {
        window.CommandSpotlightManager?.register(this);

        this.$watch('input', () => {
          this.activeIndex = 0;
        });

        this.commands = this.commands.map((cmd) => ({
          ...cmd,
          name: cmd.name || '',
          description: cmd.description || '',
          synonyms: cmd.synonyms || [],
        }));

        window.addEventListener('keydown', (e) => this.handleKeydown(e));
      },

      handleKeydown(e: KeyboardEvent): void {
        if (!this.isOpen) return;

        if (['Control', 'Shift', 'Alt', 'Meta'].includes(e.key)) return;

        const parts: string[] = [];
        if (e.ctrlKey) parts.push('Ctrl');
        if (e.metaKey) parts.push('Cmd');
        if (e.altKey) parts.push('Alt');
        if (e.shiftKey) parts.push('Shift');

        let key = e.key.toUpperCase();
        if (key === ' ') key = 'SPACE';
        if (key === 'ESCAPE') key = 'ESC';

        if (key.length === 1) {
          parts.push(key);
        } else {
          parts.push(key);
        }

        const matchedCommand = this.commands.find((cmd) => {
          if (!cmd.shortcut) return false;

          const defParts = cmd.shortcut.toUpperCase().split('+').map((p) => p.trim());

          const hasCtrl = defParts.includes('CTRL');
          const hasCmd = defParts.includes('CMD') || defParts.includes('META');
          const hasAlt = defParts.includes('ALT');
          const hasShift = defParts.includes('SHIFT');
          const defKey = defParts.find(
            (p) => !['CTRL', 'CMD', 'META', 'ALT', 'SHIFT'].includes(p)
          );

          if (!defKey) return false;

          if (hasCtrl !== e.ctrlKey) return false;
          if (hasAlt !== e.altKey) return false;
          if (hasShift !== e.shiftKey) return false;

          if (hasCmd) {
            if (!e.metaKey) return false;
          } else {
            if (e.metaKey && !hasCtrl) return false;
          }

          if (defKey === 'SPACE' && e.code === 'Space') return true;
          if (defKey === 'ESC' && e.code === 'Escape') return true;

          return e.key.toUpperCase() === defKey;
        });

        if (matchedCommand) {
          e.preventDefault();
          e.stopPropagation();
          this.go(matchedCommand.id);
        }
      },

      close(): void {
        window.CommandSpotlightManager?.close(this);
      },

      toggleOpen(): void {
        if (this.isOpen) {
          this.close();
        } else {
          this.open();
        }
      },

      open(): void {
        if (!this.commands || this.commands.length === 0) return;

        const opened = window.CommandSpotlightManager?.open(this);
        if (opened) {
          this.input = '';
          this.activeIndex = 0;
          this.$nextTick(() => {
            (this.$refs.input as HTMLInputElement | null)?.focus();
          });
        }
      },

      filteredItems(): Array<[{ item: Command }, number]> {
        if (!this.commands || this.commands.length === 0) return [];

        if (!this.input) {
          return this.showResultsWithoutInput
            ? this.commands.map((item, i) => [{ item }, i])
            : [];
        }

        const searchTerm = this.input.toLowerCase().trim();
        if (!searchTerm) return [];

        return this.commands
          .map((item, index) => {
            const name = item.name.toLowerCase();
            const description = item.description.toLowerCase();
            const synonyms = Array.isArray(item.synonyms)
              ? item.synonyms.map((s) => s.toLowerCase()).join(' ')
              : '';

            const searchableText = `${name} ${description} ${synonyms}`;

            if (searchableText.includes(searchTerm)) {
              return [{ item }, index];
            }
            return null;
          })
          .filter((item): item is [{ item: Command }, number] => item !== null);
      },

      selectUp(): void {
        if (!this.isOpen) return;
        const items = this.filteredItems();
        if (items.length === 0) return;

        if (this.activeIndex === null || this.activeIndex <= 0) {
          this.activeIndex = items.length - 1;
        } else {
          this.activeIndex--;
        }
        this.scrollToActive();
      },

      selectDown(): void {
        if (!this.isOpen) return;
        const items = this.filteredItems();
        if (items.length === 0) return;

        if (this.activeIndex === null || this.activeIndex >= items.length - 1) {
          this.activeIndex = 0;
        } else {
          this.activeIndex++;
        }
        this.scrollToActive();
      },

      scrollToActive(): void {
        this.$nextTick(() => {
          const activeElement = this.$el.querySelector(
            `[data-visible-index='${this.activeIndex}']`
          ) as HTMLElement;
          if (activeElement) {
            activeElement.scrollIntoView({
              block: 'nearest',
              behavior: 'smooth',
            });
          }
        });
      },

      go(id?: string): void {
        const items = this.filteredItems();
        if (items.length === 0) return;

        const selectedItem = id
          ? this.commands.find((cmd) => cmd.id === id)
          : this.activeIndex !== null
            ? items[this.activeIndex]?.[0]?.item
            : null;

        if (!selectedItem) return;

        this.close();

        this.$nextTick(() => {
          if (selectedItem.action) {
            if (typeof selectedItem.action === 'function') {
              selectedItem.action();
            } else if (typeof selectedItem.action === 'string') {
              try {
                const actionFunction = new Function(selectedItem.action);
                actionFunction();
              } catch (e) {
                console.error('Error executing action:', e);
              }
            }
          }

          if (selectedItem.href) {
            if (selectedItem.navigate && window.Livewire?.navigate) {
              window.Livewire.navigate(selectedItem.href);
            } else {
              window.location.href = selectedItem.href;
            }
          }

          if (selectedItem.wireClick) {
            if (this.$wire) {
              this.$wire.call(selectedItem.wireClick);
            } else {
              window.dispatchEvent(
                new CustomEvent<{ id: string; wireClick: string }>('command-selected', {
                  detail: {
                    id: selectedItem.id,
                    wireClick: selectedItem.wireClick,
                  },
                })
              );
              console.warn(
                'Command: $wire not available in this context. Dispatched "command-selected" event.'
              );
            }
          }

          if (selectedItem.onClick && typeof selectedItem.onClick === 'function') {
            selectedItem.onClick();
          }
        });
      },
    } as CommandSpotlightInstance;
  };
}

