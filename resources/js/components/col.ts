import type { ColProps, ColSpan, ColStart, ColEnd, RowSpan, RowStart, RowEnd } from './types';

/**
 * Utilitaires pour générer les classes CSS du composant Col
 */
export class ColHelper {
  /**
   * Génère la classe pour col-span
   */
  static getColSpanClass(span: ColSpan, hasStartOrEnd: boolean, responsive: boolean): string | null {
    if (hasStartOrEnd && responsive) {
      return 'col-span-1';
    }
    
    if (span === 'full') {
      return 'col-span-full';
    }
    
    if (span === 'auto') {
      return 'col-auto';
    }
    
    if (typeof span === 'number' && span > 0) {
      if (span <= 12) {
        return `col-span-${span}`;
      }
      return `col-span-[${span}]`;
    }
    
    return null;
  }

  /**
   * Génère la classe pour col-start
   */
  static getColStartClass(
    start: ColStart,
    hasStartOrEnd: boolean,
    responsive: boolean
  ): string | null {
    if (start === 'auto') {
      return 'col-start-auto';
    }
    
    if (start === null) {
      return null;
    }
    
    if (typeof start === 'number' && start > 0) {
      if (responsive && hasStartOrEnd) {
        if (start <= 13) {
          return `lg:col-start-${start}`;
        }
        return `lg:col-start-[${start}]`;
      }
      
      if (start <= 13) {
        return `col-start-${start}`;
      }
      return `col-start-[${start}]`;
    }
    
    if (responsive && hasStartOrEnd) {
      return `lg:col-start-[${start}]`;
    }
    
    return `col-start-[${start}]`;
  }

  /**
   * Génère la classe pour col-end
   */
  static getColEndClass(
    end: ColEnd,
    hasStartOrEnd: boolean,
    responsive: boolean
  ): string | null {
    if (end === 'auto') {
      return 'col-end-auto';
    }
    
    if (end === null) {
      return null;
    }
    
    if (end === 'full' || end === 'rest') {
      if (responsive && hasStartOrEnd) {
        return 'lg:col-end-[-1]';
      }
      return 'col-end-[-1]';
    }
    
    if (typeof end === 'number' && end > 0) {
      if (responsive && hasStartOrEnd) {
        if (end <= 13) {
          return `lg:col-end-${end}`;
        }
        return `lg:col-end-[${end}]`;
      }
      
      if (end <= 13) {
        return `col-end-${end}`;
      }
      return `col-end-[${end}]`;
    }
    
    if (responsive && hasStartOrEnd) {
      return `lg:col-end-[${end}]`;
    }
    
    return `col-end-[${end}]`;
  }

  /**
   * Génère la classe pour row-span
   */
  static getRowSpanClass(rowSpan: RowSpan): string | null {
    if (rowSpan === 'full') {
      return 'row-span-full';
    }
    
    if (rowSpan === 'auto') {
      return 'row-auto';
    }
    
    if (typeof rowSpan === 'number' && rowSpan > 0) {
      if (rowSpan <= 6) {
        return `row-span-${rowSpan}`;
      }
      return `row-span-[${rowSpan}]`;
    }
    
    return null;
  }

  /**
   * Génère la classe pour row-start
   */
  static getRowStartClass(rowStart: RowStart): string | null {
    if (rowStart === 'auto') {
      return 'row-start-auto';
    }
    
    if (rowStart === null) {
      return null;
    }
    
    if (typeof rowStart === 'number' && rowStart > 0) {
      if (rowStart <= 7) {
        return `row-start-${rowStart}`;
      }
      return `row-start-[${rowStart}]`;
    }
    
    return `row-start-[${rowStart}]`;
  }

  /**
   * Génère la classe pour row-end
   */
  static getRowEndClass(rowEnd: RowEnd): string | null {
    if (rowEnd === 'auto') {
      return 'row-end-auto';
    }
    
    if (rowEnd === null) {
      return null;
    }
    
    if (rowEnd === 'full' || rowEnd === 'rest') {
      return 'row-end-[-1]';
    }
    
    if (typeof rowEnd === 'number' && rowEnd > 0) {
      if (rowEnd <= 7) {
        return `row-end-${rowEnd}`;
      }
      return `row-end-[${rowEnd}]`;
    }
    
    return `row-end-[${rowEnd}]`;
  }

  /**
   * Génère toutes les classes CSS pour le composant Col
   */
  static generateClasses(props: ColProps): string[] {
    const {
      span = null,
      start = null,
      end = null,
      responsive = true,
      rowSpan = null,
      rowStart = null,
      rowEnd = null,
    } = props;

    const hasStartOrEnd = (start !== null || end !== null) && span === null;

    const classes: (string | null)[] = [
      this.getColSpanClass(span, hasStartOrEnd, responsive),
      this.getColStartClass(start, hasStartOrEnd, responsive),
      this.getColEndClass(end, hasStartOrEnd, responsive),
      this.getRowSpanClass(rowSpan),
      this.getRowStartClass(rowStart),
      this.getRowEndClass(rowEnd),
    ];

    return classes.filter((cls): cls is string => cls !== null);
  }

  /**
   * Applique les classes générées à un élément DOM
   * Note: Les props doivent être passées explicitement, car les data attributes ne sont plus utilisés
   */
  static applyToElement(element: HTMLElement, props: ColProps): void {
    const classes = this.generateClasses(props);
    
    // Retire les anciennes classes col-*
    const currentClasses = Array.from(element.classList);
    currentClasses.forEach(cls => {
      if (cls.startsWith('col-') || cls.startsWith('row-') || cls.startsWith('lg:col-')) {
        element.classList.remove(cls);
      }
    });

    // Ajoute les nouvelles classes
    classes.forEach(cls => element.classList.add(cls));
  }
}

/**
 * Initialise les composants Col sur la page
 * Note: Les classes sont déjà générées par PHP, cette fonction est disponible
 * pour une utilisation programmatique si nécessaire
 */
export function initColComponents(): void {
  // Les composants Col sont gérés par PHP via les classes générées
  // Cette fonction est disponible pour une utilisation programmatique si nécessaire
  const colElements = document.querySelectorAll<HTMLElement>('[data-slot="col"]');
  
  // Les éléments sont déjà stylés par PHP, pas besoin d'action supplémentaire
  // Sauf si vous voulez ajouter une logique JavaScript personnalisée
}

