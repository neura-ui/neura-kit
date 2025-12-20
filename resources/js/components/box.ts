import type { BoxProps, BoxPadding, BoxVariant, BoxWidth, BoxGap, BoxDisplay, BoxDirection, BoxPosition } from './types';

/**
 * Utilitaires pour générer les classes CSS du composant Box
 */
export class BoxHelper {
  /**
   * Génère la classe pour gap
   */
  static getGapClass(gap: BoxGap): string {
    const gapMap: Record<BoxGap, string> = {
      none: 'gap-0',
      xs: 'gap-1',
      sm: 'gap-2',
      md: 'gap-3',
      lg: 'gap-4',
      xl: 'gap-5',
      default: 'gap-3',
    };
    return gapMap[gap] || 'gap-3';
  }

  /**
   * Génère la classe pour padding
   */
  static getPaddingClass(padding: BoxPadding): string {
    const paddingMap: Record<BoxPadding, string> = {
      none: 'p-0',
      xs: 'p-2',
      sm: 'p-3',
      md: 'p-4',
      lg: 'p-6',
      xl: 'p-8',
      default: 'p-4',
    };
    return paddingMap[padding] || 'p-4';
  }

  /**
   * Génère la classe pour variant
   */
  static getVariantClass(variant: BoxVariant): string {
    const variantMap: Record<BoxVariant, string> = {
      default: '',
      bordered: 'border border-neutral-200 dark:border-neutral-800 rounded-lg',
      muted: 'bg-neutral-50 dark:bg-neutral-900/50 rounded-lg',
      card: 'bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-lg shadow-sm',
    };
    return variantMap[variant] || '';
  }

  /**
   * Génère la classe pour width
   */
  static getWidthClass(width: BoxWidth): string | null {
    const widthMap: Record<BoxWidth, string> = {
      auto: 'w-auto',
      full: 'w-full',
      fit: 'w-fit',
      sm: 'w-64',
      md: 'w-96',
      lg: 'w-[32rem]',
      xl: 'w-[40rem]',
    };
    return widthMap[width] || null;
  }

  /**
   * Génère la classe pour display
   */
  static getDisplayClass(display: BoxDisplay): string {
    const displayMap: Record<BoxDisplay, string> = {
      flex: 'flex',
      inline: 'inline-block',
      'inline-flex': 'inline-flex',
      grid: 'grid',
      block: 'block',
    };
    return displayMap[display] || 'block';
  }

  /**
   * Génère la classe pour direction
   */
  static getDirectionClass(direction: BoxDirection, display: BoxDisplay): string | null {
    if (display !== 'flex') {
      return null;
    }
    return direction === 'horizontal' ? 'flex-row' : 'flex-col';
  }

  /**
   * Génère la classe pour position
   */
  static getPositionClass(position: BoxPosition): string {
    if (position === null) {
      return 'static';
    }
    return position;
  }

  /**
   * Génère toutes les classes CSS pour le composant Box
   */
  static generateClasses(props: BoxProps): string[] {
    const {
      padding = 'default',
      variant = 'default',
      width = 'auto',
      gap = 'default',
      display = 'block',
      direction = 'vertical',
      position = null,
    } = props;

    const classes: (string | null)[] = [
      this.getDisplayClass(display),
      this.getDirectionClass(direction, display),
      this.getPositionClass(position),
      this.getWidthClass(width),
      this.getPaddingClass(padding),
      this.getVariantClass(variant),
      this.getGapClass(gap),
    ];

    return classes.filter((cls): cls is string => cls !== null && cls !== '');
  }

  /**
   * Applique les classes générées à un élément DOM
   * Note: Les props doivent être passées explicitement, car les data attributes ne sont plus utilisés
   */
  static applyToElement(element: HTMLElement, props: BoxProps): void {
    const classes = this.generateClasses(props);

    // Retire les anciennes classes
    const currentClasses = Array.from(element.classList);
    currentClasses.forEach(cls => {
      if (
        cls.startsWith('flex') ||
        cls.startsWith('inline-flex') ||
        cls.startsWith('inline-block') ||
        cls.startsWith('block') ||
        cls.startsWith('grid') ||
        cls.startsWith('gap-') ||
        cls.startsWith('p-') ||
        cls.startsWith('w-') ||
        cls.startsWith('border') ||
        cls.startsWith('bg-') ||
        cls.startsWith('rounded') ||
        cls.startsWith('shadow') ||
        cls === 'static' ||
        cls === 'relative' ||
        cls === 'absolute' ||
        cls === 'fixed' ||
        cls === 'sticky'
      ) {
        element.classList.remove(cls);
      }
    });

    // Ajoute les nouvelles classes
    classes.forEach(cls => {
      if (cls) {
        // Pour les classes multiples (comme variant), on les split
        cls.split(' ').forEach(c => {
          if (c) element.classList.add(c);
        });
      }
    });
  }

  /**
   * Valide les props du composant Box
   */
  static validateProps(props: BoxProps): { valid: boolean; errors: string[] } {
    const errors: string[] = [];

    if (props.padding !== undefined) {
      const validPaddings: BoxPadding[] = ['none', 'xs', 'sm', 'md', 'lg', 'xl', 'default'];
      if (!validPaddings.includes(props.padding)) {
        errors.push(`padding doit être l'un des suivants: ${validPaddings.join(', ')}`);
      }
    }

    if (props.variant !== undefined) {
      const validVariants: BoxVariant[] = ['default', 'bordered', 'muted', 'card'];
      if (!validVariants.includes(props.variant)) {
        errors.push(`variant doit être l'un des suivants: ${validVariants.join(', ')}`);
      }
    }

    if (props.width !== undefined) {
      const validWidths: BoxWidth[] = ['auto', 'full', 'fit', 'sm', 'md', 'lg', 'xl'];
      if (!validWidths.includes(props.width)) {
        errors.push(`width doit être l'un des suivants: ${validWidths.join(', ')}`);
      }
    }

    if (props.display !== undefined) {
      const validDisplays: BoxDisplay[] = ['block', 'flex', 'inline', 'inline-flex', 'grid'];
      if (!validDisplays.includes(props.display)) {
        errors.push(`display doit être l'un des suivants: ${validDisplays.join(', ')}`);
      }
    }

    return {
      valid: errors.length === 0,
      errors,
    };
  }
}

/**
 * Initialise les composants Box sur la page
 * Note: Les classes sont déjà générées par PHP, cette fonction est disponible
 * pour une utilisation programmatique si nécessaire
 */
export function initBoxComponents(): void {
  // Les composants Box sont gérés par PHP via les classes générées
  // Cette fonction est disponible pour une utilisation programmatique si nécessaire
  const boxElements = document.querySelectorAll<HTMLElement>('[data-slot="box"]');
  
  // Les éléments sont déjà stylés par PHP, pas besoin d'action supplémentaire
  // Sauf si vous voulez ajouter une logique JavaScript personnalisée
}



