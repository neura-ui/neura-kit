import { onAlpineInit } from '../../runtime/alpine';
import {
  NativeChart,
  isNativeChartType,
  type NativeChartData,
  type NativeChartType,
} from './native';

function resolveNativeOptions(type: NativeChartType, options: Record<string, any> | null | undefined) {
  const opts = options ?? {};
  return {
    showLegend: opts.plugins?.legend?.display !== false && opts.showLegend !== false,
    showGrid: opts.scales?.y?.grid?.display !== false && opts.showGrid !== false,
    showTooltip: opts.plugins?.tooltip !== false && opts.showTooltip !== false,
    cutout: type === 'doughnut' || type === 'donut' ? (opts.cutout ?? 0.64) : 0,
    startLabel: String(opts.startLabel ?? 'Start'),
    endLabel: String(opts.endLabel ?? 'End'),
    unit: String(opts.unit ?? ''),
    rows: Number(opts.rows ?? 10),
    columns: Number(opts.columns ?? 10),
    lineWidth: opts.elements?.line?.borderWidth ?? opts.lineWidth ?? 2.25,
    pointRadius: opts.elements?.point?.radius ?? opts.pointRadius ?? 0,
    barGap: opts.barGap ?? 0.34,
    fillOpacity: opts.fillOpacity ?? 0.22,
    title: opts.plugins?.title?.display
      ? String(opts.plugins.title.text ?? '')
      : String(opts.title ?? ''),
  };
}

if (typeof document !== 'undefined') {
  onAlpineInit(() => {
    window.Alpine.data(
      'chartComponent',
      (_chartId: string, type: string, data: unknown, options: unknown) => {
        let chartInstance: NativeChart | null = null;

        return {
          chart(): NativeChart | null {
            return chartInstance;
          },

          isDark(): boolean {
            return document.documentElement.classList.contains('dark');
          },

          init(): void {
            const canvas = (this as any).$refs.chartCanvas as HTMLCanvasElement | null;
            if (!canvas) return;

            if (!isNativeChartType(type)) {
              console.warn(
                `[neura-chart] Unsupported type "${type}". Supported: line, bar, pie, doughnut, donut, waffle, slope.`
              );
              return;
            }

            chartInstance = new NativeChart(canvas, {
              type,
              data: data as NativeChartData,
              options: resolveNativeOptions(type, options as Record<string, any>),
            });

            new MutationObserver(() => {
              chartInstance?.setTheme(this.isDark());
            }).observe(document.documentElement, {
              attributes: true,
              attributeFilter: ['class'],
            });
          },

          update(newData: unknown, newOptions?: Record<string, any>): void {
            if (!chartInstance || !isNativeChartType(type)) return;
            chartInstance.update(
              newData as NativeChartData,
              newOptions ? resolveNativeOptions(type, newOptions) : undefined
            );
          },

          destroy(): void {
            chartInstance?.destroy();
            chartInstance = null;
          },
        };
      }
    );
  });
}
