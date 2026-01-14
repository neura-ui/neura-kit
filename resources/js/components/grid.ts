import type { GridProps, GridCols, GridGap, GridAlign, GridJustify } from './types';

/**
 * Utilities for generating CSS classes for the Grid component
 */
export class GridHelper {
  /**
   * Generate responsive grid-cols classes dynamically
   * Example: 6 columns -> grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6
   */
  static getColsClass(
    cols: GridCols,
    responsive: boolean,
    sm: number | null = null,
    md: number | null = null,
    lg: number | null = null,
    xl: number | null = null,
    xxl: number | null = null
  ): string {
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

    // Convert cols to number for calculations
    const numCols = typeof cols === 'number' ? cols : parseInt(cols as string, 10);

    if (isNaN(numCols) || numCols <= 0) {
      return 'grid-cols-1';
    }

    // If not responsive, return simple class
    if (!responsive) {
      return numCols <= 12 ? `grid-cols-${numCols}` : `grid-cols-[${numCols}]`;
    }

    // Calculate default breakpoints if not provided
    let smCols = sm;
    let mdCols = md;
    let lgCols = lg;
    let xlCols = xl;
    let xxlCols = xxl;

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

    if (smCols !== null && smCols > 0 && smCols !== 1) {
      const colClass = smCols <= 12 ? `sm:grid-cols-${smCols}` : `sm:grid-cols-[${smCols}]`;
      classes.push(colClass);
    }

    if (mdCols !== null && mdCols > 0 && mdCols !== smCols) {
      const colClass = mdCols <= 12 ? `md:grid-cols-${mdCols}` : `md:grid-cols-[${mdCols}]`;
      classes.push(colClass);
    }

    if (lgCols !== null && lgCols > 0) {
      const colClass = lgCols <= 12 ? `lg:grid-cols-${lgCols}` : `lg:grid-cols-[${lgCols}]`;
      classes.push(colClass);
    }

    if (xlCols !== null && xlCols > 0) {
      const colClass = xlCols <= 12 ? `xl:grid-cols-${xlCols}` : `xl:grid-cols-[${xlCols}]`;
      classes.push(colClass);
    }

    if (xxlCols !== null && xxlCols > 0) {
      const colClass = xxlCols <= 12 ? `2xl:grid-cols-${xxlCols}` : `2xl:grid-cols-[${xxlCols}]`;
      classes.push(colClass);
    }

    return classes.join(' ');
  }

  /**
   * Generate gap class
   */
  static getGapClass(gap: string): string {
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
   * Generate align class (items)
   */
  static getAlignClass(align: string): string {
    const alignMap: Record<GridAlign, string> = {
      start: 'items-start',
      center: 'items-center',
      end: 'items-end',
      stretch: 'items-stretch',
    };

    return alignMap[align] || 'items-stretch';
  }

  /**
   * Generate justify class (justify-items)
   */
  static getJustifyClass(justify: string): string {
    const justifyMap: Record<GridJustify, string> = {
      start: 'justify-items-start',
      center: 'justify-items-center',
      end: 'justify-items-end',
      stretch: 'justify-items-stretch',
    };

    return justifyMap[justify] || 'justify-items-stretch';
  }

  /**
   * Generate col-start class
   */
  static getColStartClass(colStart: number | null): string | null {
    if (colStart && colStart >= 1 && colStart <= 13) {
      return `col-start-${colStart}`;
    }
    return null;
  }

  /**
   * Generate col-end class
   */
  static getColEndClass(colEnd: number | null): string | null {
    if (colEnd && colEnd >= 1 && colEnd <= 13) {
      return `col-end-${colEnd}`;
    }
    return null;
  }

  /**
   * Generate all CSS classes for Grid component
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
      xl = null,
      '2xl': xxl = null,
    } = props;

    const classes: (string | null)[] = [
      'grid',
      this.getColsClass(cols, responsive, sm, md, lg, xl, xxl),
      this.getGapClass(gap),
      this.getAlignClass(align),
      this.getJustifyClass(justify),
      this.getColStartClass(colStart),
      this.getColEndClass(colEnd),
    ];

    return classes.filter((cls): cls is string => cls !== null);
  }

  /**
   * Apply generated classes to a DOM element
   */
  static applyToElement(element: HTMLElement, props: GridProps): void {
    const classes = this.generateClasses(props);

    // Remove old grid-related classes
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

    // Add new classes
    classes.forEach(cls => element.classList.add(cls));
  }

  /**
   * Parse props from data attributes
   */
  static propsFromElement(element: HTMLElement): GridProps {
    const dataset = element.dataset;

    return {
      cols: dataset.cols || '1',
      gap: (dataset.gap as GridGap) || 'md',
      responsive: dataset.responsive !== 'false',
      align: (dataset.align as GridAlign) || 'stretch',
      justify: (dataset.justify as GridJustify) || 'stretch',
      colStart: dataset.colStart ? parseInt(dataset.colStart, 10) : null,
      colEnd: dataset.colEnd ? parseInt(dataset.colEnd, 10) : null,
      sm: dataset.sm ? parseInt(dataset.sm, 10) : null,
      md: dataset.md ? parseInt(dataset.md, 10) : null,
      lg: dataset.lg ? parseInt(dataset.lg, 10) : null,
      xl: dataset.xl ? parseInt(dataset.xl, 10) : null,
      '2xl': dataset['2xl'] ? parseInt(dataset['2xl'], 10) : null,
    };
  }

  /**
   * Validate Grid component props
   */
  static validateProps(props: GridProps): { valid: boolean; errors: string[] } {
    const errors: string[] = [];

    if (props.cols !== undefined) {
      if (typeof props.cols === 'number' && props.cols < 1) {
        errors.push('cols must be a positive number');
      }
    }

    if (props.gap !== undefined) {
      const validGaps: GridGap[] = ['none', 'xs', 'sm', 'md', 'lg', 'xl'];
      if (!validGaps.includes(props.gap)) {
        errors.push(`gap must be one of: ${validGaps.join(', ')}`);
      }
    }

    if (props.align !== undefined) {
      const validAligns: GridAlign[] = ['start', 'center', 'end', 'stretch'];
      if (!validAligns.includes(props.align)) {
        errors.push(`align must be one of: ${validAligns.join(', ')}`);
      }
    }

    if (props.justify !== undefined) {
      const validJustifies: GridJustify[] = ['start', 'center', 'end', 'stretch'];
      if (!validJustifies.includes(props.justify)) {
        errors.push(`justify must be one of: ${validJustifies.join(', ')}`);
      }
    }

    return {
      valid: errors.length === 0,
      errors,
    };
  }
}

/**
 * Initialize Grid components on the page
 */
export function initGridComponents(): void {
  const gridElements = document.querySelectorAll<HTMLElement>('[data-slot="grid"]');

  gridElements.forEach(element => {
    const props = GridHelper.propsFromElement(element);

    // Validate if in development
    if (import.meta.env?.DEV) {
      const validation = GridHelper.validateProps(props);
      if (!validation.valid) {
        console.warn('Grid validation errors:', validation.errors, element);
      }
    }
  });
}
