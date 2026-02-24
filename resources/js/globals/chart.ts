import type {
  Chart,
  ChartConfiguration,
  ChartType,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
  LineController,
  BarController,
  PieController,
  DoughnutController,
  RadarController,
  PolarAreaController,
  BubbleController,
  ScatterController,
} from 'chart.js';

type ChartModule = {
  Chart: typeof Chart;
  CategoryScale: typeof CategoryScale;
  LinearScale: typeof LinearScale;
  PointElement: typeof PointElement;
  LineElement: typeof LineElement;
  BarElement: typeof BarElement;
  ArcElement: typeof ArcElement;
  Title: typeof Title;
  Tooltip: typeof Tooltip;
  Legend: typeof Legend;
  Filler: typeof Filler;
  LineController: typeof LineController;
  BarController: typeof BarController;
  PieController: typeof PieController;
  DoughnutController: typeof DoughnutController;
  RadarController: typeof RadarController;
  PolarAreaController: typeof PolarAreaController;
  BubbleController: typeof BubbleController;
  ScatterController: typeof ScatterController;
};

let ChartJS: typeof Chart | null = null;

async function loadChart(): Promise<typeof Chart> {
  if (ChartJS) return ChartJS;

  const module = (await import('chart.js')) as ChartModule;
  ChartJS = module.Chart;

  ChartJS.register(
    module.CategoryScale,
    module.LinearScale,
    module.PointElement,
    module.LineElement,
    module.BarElement,
    module.ArcElement,
    module.Title,
    module.Tooltip,
    module.Legend,
    module.Filler,
    module.LineController,
    module.BarController,
    module.PieController,
    module.DoughnutController,
    module.RadarController,
    module.PolarAreaController,
    module.BubbleController,
    module.ScatterController
  );

  return ChartJS;
}

declare global {
  interface Window {
    Alpine: {
      data(name: string, fn: (...args: unknown[]) => unknown): void;
      $watch: (property: string, callback: (value: unknown) => void) => void;
      $nextTick: (callback: () => void) => void;
    };
  }
}

if (typeof document !== 'undefined') {
  document.addEventListener('alpine:init', () => {
  window.Alpine.data('chartComponent', (chartId: string, type: ChartType, data: unknown, options: unknown) => {
    let chartInstance: Chart | null = null;

    return {
      chart(): Chart | null {
        return chartInstance;
      },

      isDark(): boolean {
        return document.documentElement.classList.contains('dark');
      },

      colors() {
        const dark = this.isDark();
        return {
          text:       dark ? 'rgba(255,255,255,0.85)' : 'rgba(0,0,0,0.8)',
          muted:      dark ? 'rgba(255,255,255,0.45)' : 'rgba(0,0,0,0.45)',
          grid:       dark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)',
          tooltipBg:  dark ? 'rgba(24,24,27,0.95)'    : 'rgba(255,255,255,0.97)',
          tooltipText:dark ? 'rgba(255,255,255,0.9)'   : 'rgba(0,0,0,0.85)',
          tooltipBorder: dark ? 'rgba(255,255,255,0.1)': 'rgba(0,0,0,0.08)',
          arcBorder:  dark ? 'rgba(24,24,27,1)'        : 'rgba(255,255,255,1)',
        };
      },

      applyTheme(opts: Record<string, any>): Record<string, any> {
        const c = this.colors();

        if (opts.scales) {
          if (opts.scales.x) {
            opts.scales.x.ticks = { ...(opts.scales.x.ticks || {}), color: c.muted };
            opts.scales.x.grid = { ...(opts.scales.x.grid || {}), color: c.grid };
          }
          if (opts.scales.y) {
            opts.scales.y.ticks = { ...(opts.scales.y.ticks || {}), color: c.muted };
            opts.scales.y.grid = { ...(opts.scales.y.grid || {}), color: c.grid };
          }
        }

        opts.plugins = opts.plugins || {};
        if (opts.plugins.legend) {
          opts.plugins.legend.labels = {
            ...(opts.plugins.legend.labels || {}),
            color: c.muted,
          };
        }
        if (opts.plugins.tooltip !== false) {
          opts.plugins.tooltip = {
            ...(opts.plugins.tooltip || {}),
            backgroundColor: c.tooltipBg,
            titleColor: c.tooltipText,
            bodyColor: c.tooltipText,
            borderColor: c.tooltipBorder,
          };
        }

        if (opts.elements?.arc) {
          opts.elements.arc.borderColor = c.arcBorder;
        }

        return opts;
      },

      async init(): Promise<void> {
        const Chart = await loadChart();
        const canvas = (this as any).$refs.chartCanvas as HTMLCanvasElement | null;
        if (!canvas) return;

        const themedOpts = this.applyTheme(structuredClone(options) as Record<string, any>);
        chartInstance = new Chart(canvas.getContext('2d') as CanvasRenderingContext2D, {
          type,
          data: data as never,
          options: themedOpts as never,
        });

        new MutationObserver(() => {
          if (chartInstance) {
            Object.assign(
              chartInstance.options,
              this.applyTheme(structuredClone(options) as Record<string, any>)
            );
            chartInstance.update('none');
          }
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
      },

      update(newData: unknown, newOptions?: Record<string, any>): void {
        if (!chartInstance) return;
        chartInstance.data = newData as never;
        if (newOptions) {
          Object.assign(chartInstance.options, this.applyTheme(newOptions));
        }
        chartInstance.update();
      },

      destroy(): void {
        chartInstance?.destroy();
        chartInstance = null;
      },
    };
  });
  });
}

