/**
 * Types pour les composants Col et Grid
 */
export type ColSpan = number | 'full' | 'auto' | null;
export type ColStart = number | 'auto' | null;
export type ColEnd = number | 'full' | 'rest' | 'auto' | null;
export type RowSpan = number | 'full' | 'auto' | null;
export type RowStart = number | 'auto' | null;
export type RowEnd = number | 'full' | 'rest' | 'auto' | null;

export interface ColProps {
  span?: ColSpan;
  sm?: number | null;
  md?: number | null;
  lg?: number | null;
  xl?: number | null;
  '2xl'?: number | null;
  start?: ColStart;
  end?: ColEnd;
  responsive?: boolean;
  rowSpan?: RowSpan;
  rowStart?: RowStart;
  rowEnd?: RowEnd;
}

export type GridCols =
  | '1'
  | '2'
  | '3'
  | '4'
  | '5'
  | '6'
  | 'auto'
  | 'auto-fit'
  | 'auto-fill'
  | number
  | string; // Pour les valeurs CSS personnalisées

export type GridGap = 'none' | 'xs' | 'sm' | 'md' | 'lg' | 'xl';
export type GridAlign = 'start' | 'center' | 'end' | 'stretch';
export type GridJustify = 'start' | 'center' | 'end' | 'stretch';

export interface GridProps {
  cols?: GridCols;
  gap?: GridGap;
  responsive?: boolean;
  align?: GridAlign;
  justify?: GridJustify;
  colStart?: number | null;
  colEnd?: number | null;
  sm?: number | null; // Number of columns at sm breakpoint
  md?: number | null; // Number of columns at md breakpoint
  lg?: number | null; // Number of columns at lg breakpoint
  xl?: number | null; // Number of columns at xl breakpoint
  '2xl'?: number | null; // Number of columns at 2xl breakpoint
}

/**
 * Props dynamiques depuis PHP (via data attributes)
 */
export interface DynamicColProps {
  'data-col-span'?: string;
  'data-col-start'?: string;
  'data-col-end'?: string;
  'data-responsive'?: string;
  'data-sm'?: string;
  'data-md'?: string;
  'data-lg'?: string;
  'data-xl'?: string;
  'data-2xl'?: string;
  'data-row-span'?: string;
  'data-row-start'?: string;
  'data-row-end'?: string;
}

export interface DynamicGridProps {
  'data-cols'?: string;
  'data-gap'?: string;
  'data-responsive'?: string;
  'data-align'?: string;
  'data-justify'?: string;
  'data-col-start'?: string;
  'data-col-end'?: string;
  'data-sm'?: string;
  'data-md'?: string;
  'data-lg'?: string;
  'data-xl'?: string;
  'data-2xl'?: string;
}

/**
 * Types pour les composants Stack et Box
 */
export type StackDirection = 'vertical' | 'horizontal';
export type StackGap = 'none' | 'xs' | 'sm' | 'md' | 'lg' | 'xl';
export type StackAlign = 'start' | 'center' | 'end' | 'stretch';
export type StackJustify = 'start' | 'center' | 'end' | 'between' | 'around' | null;
export type StackPadding = 'none' | 'xs' | 'sm' | 'md' | 'lg' | 'xl' | null;
export type StackMargin = 'none' | 'xs' | 'sm' | 'md' | 'lg' | 'xl' | null;
export type StackRounded =
  | 'none'
  | 'sm'
  | 'md'
  | 'lg'
  | 'xl'
  | '2xl'
  | '3xl'
  | '4xl'
  | '5xl'
  | '6xl'
  | '7xl'
  | '8xl'
  | '9xl'
  | 'full'
  | null;
export type StackDisplay = 'flex' | 'inline-flex' | 'block' | 'grid' | 'contents';
export type StackPosition = 'relative' | 'absolute' | 'fixed' | 'sticky' | 'static' | null;

export interface StackProps {
  direction?: StackDirection;
  gap?: StackGap;
  align?: StackAlign;
  justify?: StackJustify;
  padding?: StackPadding;
  margin?: StackMargin;
  rounded?: StackRounded;
  display?: StackDisplay;
  position?: StackPosition;
}

export type BoxPadding = 'none' | 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'default';
export type BoxVariant = 'default' | 'bordered' | 'muted' | 'card';
export type BoxWidth = 'auto' | 'full' | 'fit' | 'sm' | 'md' | 'lg' | 'xl';
export type BoxGap = 'none' | 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'default';
export type BoxDisplay = 'block' | 'flex' | 'inline' | 'inline-flex' | 'grid';
export type BoxDirection = 'vertical' | 'horizontal';
export type BoxPosition = 'relative' | 'absolute' | 'fixed' | 'sticky' | 'static' | null;

export interface BoxProps {
  padding?: BoxPadding;
  variant?: BoxVariant;
  width?: BoxWidth;
  gap?: BoxGap;
  display?: BoxDisplay;
  direction?: BoxDirection;
  position?: BoxPosition;
}
