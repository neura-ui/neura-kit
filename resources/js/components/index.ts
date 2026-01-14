/**
 * Composants Col et Grid pour Neura Kit
 *
 * Ces composants TypeScript permettent une gestion dynamique et typée
 * des props depuis PHP via les data attributes.
 */

// Types
export type {
  ColSpan,
  ColStart,
  ColEnd,
  RowSpan,
  RowStart,
  RowEnd,
  ColProps,
  GridCols,
  GridGap,
  GridAlign,
  GridJustify,
  GridProps,
  DynamicColProps,
  DynamicGridProps,
  StackDirection,
  StackGap,
  StackAlign,
  StackJustify,
  StackPadding,
  StackMargin,
  StackRounded,
  StackDisplay,
  StackPosition,
  StackProps,
  BoxPadding,
  BoxVariant,
  BoxWidth,
  BoxGap,
  BoxDisplay,
  BoxDirection,
  BoxPosition,
  BoxProps,
} from './types';

// Composant Col
export {
  ColHelper,
  initColComponents,
  startColObserver,
  stopColObserver,
  resetColComponents
} from './col';

// Composant Grid
export { GridHelper, initGridComponents } from './grid';

// Composant Stack
export { StackHelper, initStackComponents } from './stack';

// Composant Box
export { BoxHelper, initBoxComponents } from './box';

// Import pour utilisation locale
import { initColComponents, startColObserver } from './col';
import { initGridComponents } from './grid';
import { initStackComponents } from './stack';
import { initBoxComponents } from './box';

export function initComponents(): void {
  initColComponents();
  initGridComponents();
  initStackComponents();
  initBoxComponents();
}

function init(): void {
  initComponents();

  startColObserver();
}

if (typeof window !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  document.addEventListener('livewire:navigated', () => {
    initComponents();
  });

  document.addEventListener('livewire:load', () => {
    initComponents();
  });

  document.addEventListener('livewire:update', () => {
    initComponents();
  });

  document.addEventListener('livewire:morph', () => {
    initComponents();
  });
}
