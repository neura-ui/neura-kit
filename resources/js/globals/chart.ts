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

      colors(): {
        text: string;
        muted: string;
        grid: string;
      } {
        const dark = document.documentElement.classList.contains('dark');
        return {
          text: dark ? 'rgba(255,255,255,0.9)' : 'rgba(0,0,0,0.9)',
          muted: dark ? 'rgba(255,255,255,0.6)' : 'rgba(0,0,0,0.6)',
          grid: dark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)',
        };
      },

      applyTheme(opts: ChartConfiguration): ChartConfiguration {
        const c = this.colors();
        opts.scales = opts.scales || {};
        opts.scales.x = {
          ...opts.scales.x,
          ticks: { color: c.muted },
          grid: { color: c.grid },
        } as never;
        opts.scales.y = {
          ...opts.scales.y,
          ticks: { color: c.muted },
          grid: { color: c.grid },
        } as never;
        opts.plugins = opts.plugins || {};
        opts.plugins.legend = {
          ...opts.plugins.legend,
          labels: { color: c.text },
        } as never;
        opts.plugins.tooltip = {
          ...opts.plugins.tooltip,
          backgroundColor: 'rgba(0,0,0,0.9)',
        } as never;
        return opts;
      },

      async init(): Promise<void> {
        const Chart = await loadChart();
        const canvas = (this as any).$refs.chartCanvas as HTMLCanvasElement | null;
        if (!canvas) return;

        chartInstance = new Chart(canvas.getContext('2d') as CanvasRenderingContext2D, {
          type,
          data: data as never,
          options: this.applyTheme(structuredClone(options) as ChartConfiguration),
        });

        new MutationObserver(() => {
          if (chartInstance) {
            Object.assign(
              chartInstance.options,
              this.applyTheme(structuredClone(options) as ChartConfiguration)
            );
            chartInstance.update('none');
          }
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
      },

      update(newData: unknown, newOptions?: ChartConfiguration): void {
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

