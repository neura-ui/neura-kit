import type { StackProps, StackDirection, StackGap, StackAlign, StackJustify, StackPadding, StackMargin, StackRounded, StackDisplay, StackPosition } from './types';

/**
 * Utilitaires pour générer les classes CSS du composant Stack
 */
export class StackHelper {
  /**
   * Génère la classe pour display
   */
  static getDisplayClass(display: StackDisplay): string {
    const displayMap: Record<StackDisplay, string> = {
      'inline-flex': 'inline-flex',
      'block': 'block',
      'grid': 'grid',
      'flex': 'flex',
    };
    return displayMap[display] || 'flex';
  }

  /**
   * Génère la classe pour direction
   */
  static getDirectionClass(direction: StackDirection, display: StackDisplay): string | null {
    if (display !== 'flex' && display !== 'inline-flex') {
      return null;
    }
    return direction === 'horizontal' ? 'flex-row' : 'flex-col';
  }

  /**
   * Génère la classe pour gap
   */
  static getGapClass(gap: StackGap, display: StackDisplay): string | null {
    if (!['flex', 'inline-flex', 'grid'].includes(display)) {
      return null;
    }
    const gapMap: Record<StackGap, string> = {
      none: 'gap-0',
      xs: 'gap-1',
      sm: 'gap-2',
      md: 'gap-4',
      lg: 'gap-6',
      xl: 'gap-8',
    };
    return gapMap[gap] || 'gap-4';
  }

  /**
   * Génère la classe pour padding
   */
  static getPaddingClass(padding: StackPadding): string | null {
    if (padding === null) {
      return null;
    }
    const paddingMap: Record<Exclude<StackPadding, null>, string> = {
      none: 'p-0',
      xs: 'p-1',
      sm: 'p-2',
      md: 'p-4',
      lg: 'p-6',
      xl: 'p-8',
    };
    return paddingMap[padding] || null;
  }

  /**
   * Génère la classe pour margin
   */
  static getMarginClass(margin: StackMargin): string | null {
    if (margin === null) {
      return null;
    }
    const marginMap: Record<Exclude<StackMargin, null>, string> = {
      none: 'm-0',
      xs: 'm-1',
      sm: 'm-2',
      md: 'm-4',
      lg: 'm-6',
      xl: 'm-8',
    };
    return marginMap[margin] || null;
  }

  /**
   * Génère la classe pour rounded
   */
  static getRoundedClass(rounded: StackRounded): string | null {
    if (rounded === null) {
      return null;
    }
    const roundedMap: Record<Exclude<StackRounded, null>, string> = {
      none: 'rounded-none',
      sm: 'rounded-sm',
      md: 'rounded-md',
      lg: 'rounded-lg',
      xl: 'rounded-xl',
      '2xl': 'rounded-2xl',
      '3xl': 'rounded-3xl',
      '4xl': 'rounded-4xl',
      '5xl': 'rounded-5xl',
      '6xl': 'rounded-6xl',
      '7xl': 'rounded-7xl',
      '8xl': 'rounded-8xl',
      '9xl': 'rounded-9xl',
      full: 'rounded-full',
    };
    return roundedMap[rounded] || null;
  }

  /**
   * Génère la classe pour align
   */
  static getAlignClass(align: StackAlign, display: StackDisplay): string | null {
    if (display !== 'flex' && display !== 'inline-flex') {
      return null;
    }
    const alignMap: Record<StackAlign, string> = {
      start: 'items-start',
      center: 'items-center',
      end: 'items-end',
      stretch: 'items-stretch',
    };
    return alignMap[align] || null;
  }

  /**
   * Génère la classe pour justify
   */
  static getJustifyClass(justify: StackJustify, display: StackDisplay): string | null {
    if (display !== 'flex' && display !== 'inline-flex') {
      return null;
    }
    if (justify === null) {
      return null;
    }
    const justifyMap: Record<Exclude<StackJustify, null>, string> = {
      start: 'justify-start',
      center: 'justify-center',
      end: 'justify-end',
      between: 'justify-between',
      around: 'justify-around',
    };
    return justifyMap[justify] || null;
  }

  /**
   * Génère la classe pour position
   */
  static getPositionClass(position: StackPosition): string {
    if (position === null) {
      return 'static';
    }
    return position;
  }

  /**
   * Génère toutes les classes CSS pour le composant Stack
   */
  static generateClasses(props: StackProps): string[] {
    const {
      direction = 'vertical',
      gap = 'md',
      align = 'center',
      justify = null,
      padding = null,
      margin = null,
      rounded = null,
      display = 'flex',
      position = null,
    } = props;

    const childFullWidth = direction === 'vertical' ? '[&>*:not([class*="w-"])]:w-full' : null;

    const classes: (string | null)[] = [
      this.getDisplayClass(display),
      this.getPositionClass(position),
      'w-full',
      this.getDirectionClass(direction, display),
      this.getGapClass(gap, display),
      this.getPaddingClass(padding),
      this.getMarginClass(margin),
      this.getRoundedClass(rounded),
      this.getAlignClass(align, display),
      this.getJustifyClass(justify, display),
      childFullWidth,
    ];

    return classes.filter((cls): cls is string => cls !== null);
  }

  /**
   * Applique les classes générées à un élément DOM
   * Note: Les props doivent être passées explicitement, car les data attributes ne sont plus utilisés
   */
  static applyToElement(element: HTMLElement, props: StackProps): void {
    const classes = this.generateClasses(props);

    // Retire les anciennes classes
    const currentClasses = Array.from(element.classList);
    currentClasses.forEach(cls => {
      if (
        cls.startsWith('flex') ||
        cls.startsWith('inline-flex') ||
        cls.startsWith('block') ||
        cls.startsWith('grid') ||
        cls.startsWith('gap-') ||
        cls.startsWith('p-') ||
        cls.startsWith('m-') ||
        cls.startsWith('rounded') ||
        cls.startsWith('items-') ||
        cls.startsWith('justify-') ||
        cls === 'static' ||
        cls === 'relative' ||
        cls === 'absolute' ||
        cls === 'fixed' ||
        cls === 'sticky' ||
        cls === 'w-full'
      ) {
        element.classList.remove(cls);
      }
    });

    // Ajoute les nouvelles classes
    classes.forEach(cls => element.classList.add(cls));
  }

  /**
   * Valide les props du composant Stack
   */
  static validateProps(props: StackProps): { valid: boolean; errors: string[] } {
    const errors: string[] = [];

    if (props.direction !== undefined) {
      const validDirections: StackDirection[] = ['vertical', 'horizontal'];
      if (!validDirections.includes(props.direction)) {
        errors.push(`direction doit être l'un des suivants: ${validDirections.join(', ')}`);
      }
    }

    if (props.gap !== undefined) {
      const validGaps: StackGap[] = ['none', 'xs', 'sm', 'md', 'lg', 'xl'];
      if (!validGaps.includes(props.gap)) {
        errors.push(`gap doit être l'un des suivants: ${validGaps.join(', ')}`);
      }
    }

    if (props.align !== undefined) {
      const validAligns: StackAlign[] = ['start', 'center', 'end', 'stretch'];
      if (!validAligns.includes(props.align)) {
        errors.push(`align doit être l'un des suivants: ${validAligns.join(', ')}`);
      }
    }

    if (props.display !== undefined) {
      const validDisplays: StackDisplay[] = ['flex', 'inline-flex', 'block', 'grid'];
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
 * Initialise les composants Stack sur la page
 * Note: Les classes sont déjà générées par PHP, cette fonction est disponible
 * pour une utilisation programmatique si nécessaire
 */
export function initStackComponents(): void {
  // Les composants Stack sont gérés par PHP via les classes générées
  // Cette fonction est disponible pour une utilisation programmatique si nécessaire
  const stackElements = document.querySelectorAll<HTMLElement>('[data-slot="stack"]');
  
  // Les éléments sont déjà stylés par PHP, pas besoin d'action supplémentaire
  // Sauf si vous voulez ajouter une logique JavaScript personnalisée
}

