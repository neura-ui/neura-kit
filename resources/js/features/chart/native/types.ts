/**
 * Native chart engine — zero-dependency canvas rendering.
 * Supports: line, bar, pie, doughnut.
 */

export type NativeChartType = 'line' | 'bar' | 'pie' | 'doughnut';

export interface NativeDataset {
  label?: string;
  data: number[];
  borderColor?: string | string[];
  backgroundColor?: string | string[];
  fill?: boolean;
  tension?: number;
}

export interface NativeChartData {
  labels?: string[];
  datasets: NativeDataset[];
}

export interface NativePadding {
  top: number;
  right: number;
  bottom: number;
  left: number;
}

export interface NativeThemeColors {
  text: string;
  muted: string;
  grid: string;
  tooltipBg: string;
  tooltipText: string;
  tooltipBorder: string;
  surface: string;
}

export interface NativeChartOptions {
  padding?: Partial<NativePadding>;
  showLegend?: boolean;
  showGrid?: boolean;
  showTooltip?: boolean;
  animation?: boolean;
  barGap?: number;
  lineWidth?: number;
  pointRadius?: number;
  fillOpacity?: number;
  cutout?: number;
  colors?: string[];
}

export interface NativeChartConfig {
  type: NativeChartType;
  data: NativeChartData;
  options?: NativeChartOptions;
  theme?: Partial<NativeThemeColors>;
}

export interface PlotRect {
  x: number;
  y: number;
  width: number;
  height: number;
}

export interface Scale {
  domain: [number, number];
  range: [number, number];
  map(value: number): number;
  ticks(count?: number): number[];
}

export interface LayoutResult {
  plot: PlotRect;
  xScale: Scale;
  yScale: Scale;
  labels: string[];
  series: Array<{
    label: string;
    values: number[];
    borderColor: string;
    backgroundColor: string;
    fill: boolean;
    colors?: string[];
  }>;
}

export const SUPPORTED_TYPES: NativeChartType[] = ['line', 'bar', 'pie', 'doughnut'];

export function isNativeChartType(type: string): type is NativeChartType {
  return (SUPPORTED_TYPES as string[]).includes(type);
}
