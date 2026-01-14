import type { ColProps, ColSpan, ColStart, ColEnd, RowSpan, RowStart, RowEnd } from './types';

/**
 * Modern Tailwind 4 approach: Hybrid system using static classes + CSS variables
 * This gives you true dynamic behavior without sacrificing performance
 */
export class ColHelper {
  /**
   * Generate inline grid styles for dynamic values
   * Uses CSS Grid properties directly for runtime flexibility
   */
  static generateInlineStyles(props: {
    rowSpan: any;
    xl: number | null;
    "2xl": number | null;
    md: number | null;
    responsive: boolean;
    start: any;
    sm: number | null;
    end: any;
    rowStart: any;
    rowEnd: any;
    lg: number | null;
    span: any
  }): Record<string, string> {
    const {
      span = null,
      sm = null,
      md = null,
      lg = null,
      xl = null,
      '2xl': xxl = null,
      start = null,
      end = null,
      rowSpan = null,
      rowStart = null,
      rowEnd = null,
    } = props;

    const styles: Record<string, string> = {};

    // Column Span - Mobile First
    if (span !== null && span !== 'auto') {
      if (span === 'full') {
        styles['--col-span'] = '1 / -1';
      } else if (typeof span === 'number') {
        styles['--col-span'] = `span ${span} / span ${span}`;
      }
    }

    // Responsive Column Spans via CSS Variables
    if (sm !== null) {
      styles['--col-span-sm'] = `span ${sm} / span ${sm}`;
    }
    if (md !== null) {
      styles['--col-span-md'] = `span ${md} / span ${md}`;
    }
    if (lg !== null) {
      styles['--col-span-lg'] = `span ${lg} / span ${lg}`;
    }
    if (xl !== null) {
      styles['--col-span-xl'] = `span ${xl} / span ${xl}`;
    }
    if (xxl !== null) {
      styles['--col-span-2xl'] = `span ${xxl} / span ${xxl}`;
    }

    // Column Start/End
    if (start !== null && start !== 'auto') {
      styles['--col-start'] = typeof start === 'number' ? `${start}` : start;
    }
    if (end !== null && end !== 'auto') {
      if (end === 'full' || end === 'rest') {
        styles['--col-end'] = '-1';
      } else {
        styles['--col-end'] = `${end}`;
      }
    }

    // Row Span
    if (rowSpan !== null && rowSpan !== 'auto') {
      if (rowSpan === 'full') {
        styles['--row-span'] = '1 / -1';
      } else if (typeof rowSpan === 'number') {
        styles['--row-span'] = `span ${rowSpan} / span ${rowSpan}`;
      }
    }

    // Row Start/End
    if (rowStart !== null && rowStart !== 'auto') {
      styles['--row-start'] = `${rowStart}`;
    }
    if (rowEnd !== null && rowEnd !== 'auto') {
      if (rowEnd === 'full' || rowEnd === 'rest') {
        styles['--row-end'] = '-1';
      } else {
        styles['--row-end'] = `${rowEnd}`;
      }
    }

    return styles;
  }

  /**
   * Generate static Tailwind classes (for common patterns)
   * Only use classes that are guaranteed to be in the build
   */
  static generateStaticClasses(props: {
    rowSpan: any;
    xl: number | null;
    "2xl": number | null;
    md: number | null;
    responsive: boolean;
    start: any;
    sm: number | null;
    end: any;
    rowStart: any;
    rowEnd: any;
    lg: number | null;
    span: any
  }): string[] {
    const classes: string[] = [];
    const { span, start, end, rowSpan } = props;

    // Only add static classes for auto values
    if (span === 'auto') classes.push('col-auto');
    if (start === 'auto') classes.push('col-start-auto');
    if (end === 'auto') classes.push('col-end-auto');
    if (rowSpan === 'auto') classes.push('row-auto');

    return classes;
  }

  /**
   * Convert style object to inline style string
   */
  static stylesToString(styles: Record<string, string>): string {
    return Object.entries(styles)
      .map(([key, value]) => `${key}: ${value}`)
      .join('; ');
  }

  /**
   * Main method: Apply dynamic grid behavior to element
   */
  static applyToElement(element: HTMLElement, props: {
    rowSpan: any;
    xl: number | null;
    "2xl": number | null;
    md: number | null;
    responsive: boolean;
    start: any;
    sm: number | null;
    end: any;
    rowStart: any;
    rowEnd: any;
    lg: number | null;
    span: any
  }): void {
    // Add static classes
    const staticClasses = this.generateStaticClasses(props);
    staticClasses.forEach(cls => element.classList.add(cls));

    // Add inline styles for dynamic values
    const styles = this.generateInlineStyles(props);
    if (Object.keys(styles).length > 0) {
      const styleString = this.stylesToString(styles);
      const existingStyle = element.getAttribute('style') || '';
      element.setAttribute('style', existingStyle ? `${existingStyle}; ${styleString}` : styleString);
    }

    // Add responsive class that consumes CSS variables
    element.classList.add('col-dynamic');
  }

  /**
   * Generate props object from data attributes (for server-rendered HTML)
   */
  static propsFromElement(element: HTMLElement): {
    rowSpan: any;
    xl: number | null;
    "2xl": number | null;
    md: number | null;
    responsive: boolean;
    start: any;
    sm: number | null;
    end: any;
    rowStart: any;
    rowEnd: any;
    lg: number | null;
    span: any
  } {
    const dataset = element.dataset;

    return {
      span: this.parseValue(dataset.colSpan),
      sm: this.parseNumeric(dataset.sm),
      md: this.parseNumeric(dataset.md),
      lg: this.parseNumeric(dataset.lg),
      xl: this.parseNumeric(dataset.xl),
      '2xl': this.parseNumeric(dataset['2xl']),
      start: this.parseValue(dataset.colStart),
      end: this.parseValue(dataset.colEnd),
      responsive: dataset.responsive !== 'false',
      rowSpan: this.parseValue(dataset.rowSpan),
      rowStart: this.parseValue(dataset.rowStart),
      rowEnd: this.parseValue(dataset.rowEnd),
    };
  }

  private static parseValue(value: string | undefined): any {
    if (!value) return null;
    if (value === 'null') return null;
    if (value === 'auto' || value === 'full' || value === 'rest') return value;
    const num = parseInt(value, 10);
    return isNaN(num) ? value : num;
  }

  private static parseNumeric(value: string | undefined): number | null {
    if (!value) return null;
    const num = parseInt(value, 10);
    return isNaN(num) ? null : num;
  }
}

// ============================================================================
// LIVEWIRE-COMPATIBLE INITIALIZATION WITH MUTATIONOBSERVER
// ============================================================================

let observer: MutationObserver | null = null;
let isInitialized = false;

/**
 * Initialize a single col element
 */
function initializeColElement(element: HTMLElement): void {
  // Skip if already initialized
  if (element.dataset.colInitialized === 'true') {
    return;
  }

  try {
    const props = ColHelper.propsFromElement(element);
    ColHelper.applyToElement(element, props);

    // Mark as initialized to avoid double-processing
    element.dataset.colInitialized = 'true';
  } catch (error) {
    console.error('Error initializing col element:', error, element);
  }
}

/**
 * Initialize all col components on the page
 */
export function initColComponents(): void {
  const colElements = document.querySelectorAll<HTMLElement>('[data-slot="col"]:not([data-col-initialized])');

  colElements.forEach(initializeColElement);
}

/**
 * Handle node additions from MutationObserver
 */
function handleAddedNode(node: Node): void {
  if (!(node instanceof HTMLElement)) {
    return;
  }

  // Check if the node itself is a col element
  if (node.dataset.slot === 'col' && !node.dataset.colInitialized) {
    initializeColElement(node);
  }

  // Check for col elements within the added node
  const colElements = node.querySelectorAll<HTMLElement>('[data-slot="col"]:not([data-col-initialized])');
  colElements.forEach(initializeColElement);
}

/**
 * Start the MutationObserver to watch for dynamic content
 */
export function startColObserver(): void {
  // Prevent multiple initializations
  if (isInitialized) {
    return;
  }

  // Clean up existing observer if any
  if (observer) {
    observer.disconnect();
    observer = null;
  }

  // Initialize existing elements first
  initColComponents();

  // Create new observer
  observer = new MutationObserver((mutations) => {
    // Process all mutations
    for (const mutation of mutations) {
      // Only process added nodes
      if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
        mutation.addedNodes.forEach(handleAddedNode);
      }
    }
  });

  // Start observing the document body for changes
  observer.observe(document.body, {
    childList: true,
    subtree: true
  });

  isInitialized = true;
}

/**
 * Stop the MutationObserver (useful for cleanup)
 */
export function stopColObserver(): void {
  if (observer) {
    observer.disconnect();
    observer = null;
  }
  isInitialized = false;
}

/**
 * Reset all col elements (removes initialization markers)
 * Useful for forcing re-initialization
 */
export function resetColComponents(): void {
  const colElements = document.querySelectorAll<HTMLElement>('[data-slot="col"][data-col-initialized]');
  colElements.forEach(element => {
    delete element.dataset.colInitialized;
  });
}
