import type { GridProps, GridCols, GridGap, GridAlign, GridJustify } from './types';

/**
 * Utilitaires pour générer les classes CSS du composant Grid
 */
export class GridHelper {
  /**
   * Génère dynamiquement les classes responsive pour grid-cols
   * Exemple: 6 colonnes -> grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6
   */
  static getColsClass(
    cols: GridCols,
    responsive: boolean,
    sm: number | null = null,
    md: number | null = null,
    lg: number | null = null
  ): string {
    // CSS Grid auto functions - no responsive needed
    if (cols === 'auto-fit') {
      return 'grid-cols-[repeat(auto-fit,minmax(0,1fr))]';
    }
    
    if (cols === 'auto-fill') {
      return 'grid-cols-[repeat(auto-fill,minmax(0,1fr))]';
    }
    
    if (cols === 'auto') {
      return 'grid-cols-[repeat(auto-fit,minmax(min-content,1fr))]';
    }

    // Custom CSS value - no responsive needed
    if (typeof cols === 'string') {
      const colsStr = cols.toString();
      if (
        colsStr.includes('[') ||
        colsStr.includes('repeat') ||
        colsStr.includes('minmax')
      ) {
        return `grid-cols-[${colsStr}]`;
      }
    }

    // If not responsive, return simple class
    if (!responsive) {
      const numCols = typeof cols === 'number' ? cols : parseInt(cols as string, 10);
      return isNaN(numCols) ? `grid-cols-${cols}` : `grid-cols-${numCols}`;
    }

    // Convert cols to number for calculations
    const numCols = typeof cols === 'number' ? cols : parseInt(cols as string, 10);
    
    if (isNaN(numCols) || numCols <= 0) {
      return 'grid-cols-1';
    }

    // Calculate default breakpoints if not provided
    // Smart defaults based on column count
    let smCols = sm;
    let mdCols = md;
    let lgCols = lg;

    if (smCols === null) {
      smCols = numCols >= 4 ? 2 : (numCols >= 2 ? 2 : null);
    }
    if (mdCols === null) {
      mdCols = numCols >= 6 ? 3 : (numCols >= 3 ? 2 : (numCols >= 2 ? 2 : null));
    }
    if (lgCols === null) {
      lgCols = numCols;
    }

    // Build responsive classes
    const classes: string[] = ['grid-cols-1']; // Always start with 1 column on mobile

    if (smCols !== null && smCols > 0 && smCols < numCols) {
      classes.push(`sm:grid-cols-${smCols}`);
    }
    if (mdCols !== null && mdCols > 0 && mdCols < numCols) {
      classes.push(`md:grid-cols-${mdCols}`);
    }
    if (lgCols !== null && lgCols > 0) {
      classes.push(`lg:grid-cols-${lgCols}`);
    } else {
      classes.push(`lg:grid-cols-${numCols}`);
    }

    return classes.join(' ');
  }

  /**
   * Génère la classe pour gap
   */
  static getGapClass(gap: GridGap): string {
    const gapMap: Record<GridGap, string> = {
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
   * Génère la classe pour align (items)
   */
  static getAlignClass(align: GridAlign): string {
    const alignMap: Record<GridAlign, string> = {
      start: 'items-start',
      center: 'items-center',
      end: 'items-end',
      stretch: 'items-stretch',
    };

    return alignMap[align] || 'items-stretch';
  }

  /**
   * Génère la classe pour justify (justify-items)
   */
  static getJustifyClass(justify: GridJustify): string {
    const justifyMap: Record<GridJustify, string> = {
      start: 'justify-items-start',
      center: 'justify-items-center',
      end: 'justify-items-end',
      stretch: 'justify-items-stretch',
    };

    return justifyMap[justify] || 'justify-items-stretch';
  }

  /**
   * Génère la classe pour col-start
   */
  static getColStartClass(colStart: number | null): string | null {
    return colStart ? `col-start-${colStart}` : null;
  }

  /**
   * Génère la classe pour col-end
   */
  static getColEndClass(colEnd: number | null): string | null {
    return colEnd ? `col-end-${colEnd}` : null;
  }

  /**
   * Génère toutes les classes CSS pour le composant Grid
   */
  static generateClasses(props: GridProps): string[] {
    const {
      cols = '1',
      gap = 'md',
      responsive = true,
      align = 'stretch',
      justify = 'stretch',
      colStart = null,
      colEnd = null,
      sm = null,
      md = null,
      lg = null,
    } = props;

    const classes: (string | null)[] = [
      'grid',
      this.getColsClass(cols, responsive, sm, md, lg),
      this.getGapClass(gap),
      this.getAlignClass(align),
      this.getJustifyClass(justify),
      this.getColStartClass(colStart),
      this.getColEndClass(colEnd),
    ];

    return classes.filter((cls): cls is string => cls !== null);
  }

  /**
   * Applique les classes générées à un élément DOM
   * Note: Les props doivent être passées explicitement, car les data attributes ne sont plus utilisés
   */
  static applyToElement(element: HTMLElement, props: GridProps): void {
    const classes = this.generateClasses(props);

    // Retire les anciennes classes grid-*
    const currentClasses = Array.from(element.classList);
    currentClasses.forEach(cls => {
      if (
        cls.startsWith('grid') ||
        cls.startsWith('gap-') ||
        cls.startsWith('items-') ||
        cls.startsWith('justify-items-') ||
        cls.startsWith('col-start-') ||
        cls.startsWith('col-end-')
      ) {
        element.classList.remove(cls);
      }
    });

    // Ajoute les nouvelles classes
    classes.forEach(cls => element.classList.add(cls));
  }

  /**
   * Valide les props du composant Grid
   */
  static validateProps(props: GridProps): { valid: boolean; errors: string[] } {
    const errors: string[] = [];

    if (props.cols !== undefined) {
      if (typeof props.cols === 'number' && props.cols < 1) {
        errors.push('cols doit être un nombre positif');
      }
    }

    if (props.gap !== undefined) {
      const validGaps: GridGap[] = ['none', 'xs', 'sm', 'md', 'lg', 'xl'];
      if (!validGaps.includes(props.gap)) {
        errors.push(`gap doit être l'un des suivants: ${validGaps.join(', ')}`);
      }
    }

    if (props.align !== undefined) {
      const validAligns: GridAlign[] = ['start', 'center', 'end', 'stretch'];
      if (!validAligns.includes(props.align)) {
        errors.push(`align doit être l'un des suivants: ${validAligns.join(', ')}`);
      }
    }

    if (props.justify !== undefined) {
      const validJustifies: GridJustify[] = ['start', 'center', 'end', 'stretch'];
      if (!validJustifies.includes(props.justify)) {
        errors.push(`justify doit être l'un des suivants: ${validJustifies.join(', ')}`);
      }
    }

    return {
      valid: errors.length === 0,
      errors,
    };
  }
}

/**
 * Initialise les composants Grid sur la page
 * Note: Les classes sont déjà générées par PHP, cette fonction est disponible
 * pour une utilisation programmatique si nécessaire
 */
export function initGridComponents(): void {
  // Les composants Grid sont gérés par PHP via les classes générées
  // Cette fonction est disponible pour une utilisation programmatique si nécessaire
  const gridElements = document.querySelectorAll<HTMLElement>('[data-slot="grid"]');
  
  // Les éléments sont déjà stylés par PHP, pas besoin d'action supplémentaire
  // Sauf si vous voulez ajouter une logique JavaScript personnalisée
}

// Auto-initialisation si le DOM est déjà chargé
if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initGridComponents);
  } else {
    initGridComponents();
  }
}

