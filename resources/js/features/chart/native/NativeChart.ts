import { createLinearScale, extent } from './scale';
import { areaPath, linePath, withAlpha } from './path';
import { DEFAULT_PALETTE, resolveTheme } from './theme';
import type {
  LayoutResult,
  NativeChartConfig,
  NativeChartData,
  NativeChartOptions,
  NativeChartType,
  NativePadding,
  NativeThemeColors,
  PlotRect,
} from './types';
import { isNativeChartType } from './types';

const DEFAULT_PADDING: NativePadding = { top: 16, right: 12, bottom: 28, left: 40 };

export class NativeChart {
  private canvas: HTMLCanvasElement;
  private ctx: CanvasRenderingContext2D;
  private type: NativeChartType;
  private data: NativeChartData;
  private options: Required<
    Pick<
      NativeChartOptions,
      | 'showLegend'
      | 'showGrid'
      | 'showTooltip'
      | 'barGap'
      | 'lineWidth'
      | 'pointRadius'
      | 'fillOpacity'
      | 'cutout'
      | 'colors'
    >
  > & { padding: NativePadding };
  private theme: NativeThemeColors;
  private layout: LayoutResult | null = null;
  private hoverIndex: number | null = null;
  private raf = 0;
  private ro: ResizeObserver | null = null;
  private onMove: ((e: MouseEvent) => void) | null = null;
  private onLeave: (() => void) | null = null;
  private sliceAngles: Array<{ start: number; end: number }> = [];

  constructor(canvas: HTMLCanvasElement, config: NativeChartConfig) {
    const ctx = canvas.getContext('2d');
    if (!ctx) throw new Error('Canvas 2D context unavailable');

    if (!isNativeChartType(config.type)) {
      throw new Error(`[neura-chart] Unsupported type "${config.type}". Use line, bar, pie, or doughnut.`);
    }

    this.canvas = canvas;
    this.ctx = ctx;
    this.type = config.type;
    this.data = config.data;
    this.options = {
      showLegend: config.options?.showLegend ?? true,
      showGrid: config.options?.showGrid ?? true,
      showTooltip: config.options?.showTooltip ?? true,
      barGap: config.options?.barGap ?? 0.28,
      lineWidth: config.options?.lineWidth ?? 2,
      pointRadius: config.options?.pointRadius ?? 0,
      fillOpacity: config.options?.fillOpacity ?? 0.12,
      cutout: config.options?.cutout ?? (config.type === 'doughnut' ? 0.55 : 0),
      colors: config.options?.colors ?? DEFAULT_PALETTE,
      padding: { ...DEFAULT_PADDING, ...(config.options?.padding ?? {}) },
    };
    this.theme = resolveTheme(
      document.documentElement.classList.contains('dark'),
      config.theme
    );

    this.bind();
    this.draw();
  }

  update(data: NativeChartData, options?: NativeChartOptions): void {
    this.data = data;
    if (options) {
      this.options = {
        ...this.options,
        ...options,
        padding: { ...this.options.padding, ...(options.padding ?? {}) },
        colors: options.colors ?? this.options.colors,
      };
    }
    this.draw();
  }

  setTheme(isDark: boolean, override: Partial<NativeThemeColors> = {}): void {
    this.theme = resolveTheme(isDark, override);
    this.draw();
  }

  destroy(): void {
    cancelAnimationFrame(this.raf);
    this.ro?.disconnect();
    if (this.onMove) this.canvas.removeEventListener('mousemove', this.onMove);
    if (this.onLeave) this.canvas.removeEventListener('mouseleave', this.onLeave);
  }

  private get isRadial(): boolean {
    return this.type === 'pie' || this.type === 'doughnut';
  }

  private bind(): void {
    this.ro = new ResizeObserver(() => {
      cancelAnimationFrame(this.raf);
      this.raf = requestAnimationFrame(() => this.draw());
    });
    this.ro.observe(this.canvas.parentElement ?? this.canvas);

    this.onMove = (e: MouseEvent) => {
      if (!this.layout) return;
      const rect = this.canvas.getBoundingClientRect();
      const x = ((e.clientX - rect.left) / rect.width) * this.canvas.width / (window.devicePixelRatio || 1);
      const y = ((e.clientY - rect.top) / rect.height) * this.canvas.height / (window.devicePixelRatio || 1);
      const idx = this.isRadial ? this.indexFromAngle(x, y) : this.indexFromX(x);
      if (idx !== this.hoverIndex) {
        this.hoverIndex = idx;
        this.draw();
      }
    };
    this.onLeave = () => {
      this.hoverIndex = null;
      this.draw();
    };
    this.canvas.addEventListener('mousemove', this.onMove);
    this.canvas.addEventListener('mouseleave', this.onLeave);
  }

  private resize(): void {
    const parent = this.canvas.parentElement ?? this.canvas;
    const dpr = window.devicePixelRatio || 1;
    const width = Math.max(1, parent.clientWidth);
    const height = Math.max(1, parent.clientHeight);
    this.canvas.width = Math.floor(width * dpr);
    this.canvas.height = Math.floor(height * dpr);
    this.canvas.style.width = `${width}px`;
    this.canvas.style.height = `${height}px`;
    this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }

  private computeLayout(width: number, height: number): LayoutResult {
    const pad = { ...this.options.padding };
    if (this.options.showLegend) pad.top += 20;
    if (this.isRadial) {
      pad.left = 12;
      pad.right = 12;
      pad.bottom = 12;
    }

    const plot: PlotRect = {
      x: pad.left,
      y: pad.top,
      width: Math.max(1, width - pad.left - pad.right),
      height: Math.max(1, height - pad.top - pad.bottom),
    };

    const labels = this.data.labels ?? this.data.datasets[0]?.data.map((_, i) => String(i + 1)) ?? [];
    const allValues = this.data.datasets.flatMap((d) => d.data);
    const yDomain = extent(allValues);
    const xScale = createLinearScale([0, Math.max(labels.length - 1, 1)], [plot.x, plot.x + plot.width]);
    const yScale = createLinearScale(yDomain, [plot.y + plot.height, plot.y]);

    const series = this.data.datasets.map((ds, i) => {
      const color = Array.isArray(ds.borderColor)
        ? ds.borderColor[0]
        : ds.borderColor ?? this.options.colors[i % this.options.colors.length];

      const bg = Array.isArray(ds.backgroundColor)
        ? ds.backgroundColor[0]
        : ds.backgroundColor ?? withAlpha(color, this.type === 'bar' ? 1 : this.options.fillOpacity);

      const colors = Array.isArray(ds.backgroundColor)
        ? ds.backgroundColor
        : Array.isArray(ds.borderColor)
          ? ds.borderColor
          : labels.map(() => bg);

      return {
        label: ds.label ?? `Series ${i + 1}`,
        values: ds.data,
        borderColor: color,
        backgroundColor: bg,
        fill: ds.fill ?? this.type === 'line',
        colors,
      };
    });

    return { plot, xScale, yScale, labels, series };
  }

  private draw(): void {
    this.resize();
    const width = this.canvas.clientWidth;
    const height = this.canvas.clientHeight;
    const ctx = this.ctx;

    ctx.clearRect(0, 0, width, height);
    this.layout = this.computeLayout(width, height);
    const { plot, xScale, yScale, labels, series } = this.layout;

    if (this.isRadial) {
      this.drawRadial(plot, labels, series);
      if (this.options.showLegend) this.drawRadialLegend(labels, series);
      if (this.options.showTooltip && this.hoverIndex !== null) {
        this.drawRadialTooltip(plot, labels, series, this.hoverIndex);
      }
      return;
    }

    if (this.options.showGrid) this.drawGrid(plot, yScale);
    this.drawAxes(plot, xScale, yScale, labels);

    if (this.type === 'bar') this.drawBars(plot, yScale, series, labels.length);
    else this.drawLines(yScale, series, labels.length);

    if (this.options.showLegend) this.drawLegend(series);
    if (this.options.showTooltip && this.hoverIndex !== null) {
      this.drawTooltip(plot, xScale, labels, series, this.hoverIndex);
    }
  }

  private drawRadial(
    plot: PlotRect,
    labels: string[],
    series: LayoutResult['series']
  ): void {
    const values = series[0]?.values ?? [];
    const colors = series[0]?.colors ?? this.options.colors;
    const total = values.reduce((a, b) => a + Math.max(0, b), 0) || 1;
    const cx = plot.x + plot.width / 2;
    const cy = plot.y + plot.height / 2;
    const radius = Math.max(8, Math.min(plot.width, plot.height) / 2 - 4);
    const inner = radius * this.options.cutout;
    const ctx = this.ctx;
    this.sliceAngles = [];

    let angle = -Math.PI / 2;
    values.forEach((value, i) => {
      const slice = (Math.max(0, value) / total) * Math.PI * 2;
      const start = angle;
      const end = angle + slice;
      this.sliceAngles.push({ start, end });

      const explode = this.hoverIndex === i ? 6 : 0;
      const mid = (start + end) / 2;
      const ox = Math.cos(mid) * explode;
      const oy = Math.sin(mid) * explode;

      ctx.beginPath();
      ctx.moveTo(cx + ox + Math.cos(start) * inner, cy + oy + Math.sin(start) * inner);
      ctx.arc(cx + ox, cy + oy, radius, start, end);
      ctx.arc(cx + ox, cy + oy, inner, end, start, true);
      ctx.closePath();
      ctx.fillStyle = colors[i % colors.length];
      ctx.fill();
      ctx.strokeStyle = this.theme.surface;
      ctx.lineWidth = 2;
      ctx.stroke();

      angle = end;
    });
  }

  private drawRadialLegend(labels: string[], series: LayoutResult['series']): void {
    const colors = series[0]?.colors ?? this.options.colors;
    const ctx = this.ctx;
    ctx.save();
    ctx.font = '500 12px Inter, system-ui, sans-serif';
    let x = 12;
    const y = 10;
    labels.forEach((label, i) => {
      ctx.fillStyle = colors[i % colors.length];
      ctx.beginPath();
      ctx.arc(x + 3, y + 4, 3, 0, Math.PI * 2);
      ctx.fill();
      ctx.fillStyle = this.theme.muted;
      ctx.textBaseline = 'top';
      ctx.fillText(label, x + 10, y);
      x += ctx.measureText(label).width + 28;
    });
    ctx.restore();
  }

  private drawRadialTooltip(
    plot: PlotRect,
    labels: string[],
    series: LayoutResult['series'],
    index: number
  ): void {
    const values = series[0]?.values ?? [];
    const colors = series[0]?.colors ?? this.options.colors;
    const total = values.reduce((a, b) => a + Math.max(0, b), 0) || 1;
    const value = values[index] ?? 0;
    const pct = ((value / total) * 100).toFixed(0);
    const title = labels[index] ?? String(index + 1);
    const line = `${formatTick(value)} (${pct}%)`;
    const ctx = this.ctx;

    ctx.save();
    ctx.font = '600 12px Inter, system-ui, sans-serif';
    const boxW = Math.max(ctx.measureText(title).width, ctx.measureText(line).width) + 36;
    const boxH = 48;
    const x = plot.x + plot.width / 2 - boxW / 2;
    const y = plot.y + 8;

    ctx.fillStyle = this.theme.tooltipBg;
    ctx.strokeStyle = this.theme.tooltipBorder;
    roundRect(ctx, x, y, boxW, boxH, 10);
    ctx.fill();
    ctx.stroke();

    ctx.fillStyle = colors[index % colors.length];
    ctx.beginPath();
    ctx.arc(x + 14, y + 16, 3, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = this.theme.tooltipText;
    ctx.fillText(title, x + 22, y + 12);
    ctx.font = '400 12px Inter, system-ui, sans-serif';
    ctx.fillText(line, x + 22, y + 30);
    ctx.restore();
  }

  private drawGrid(plot: PlotRect, yScale: ReturnType<typeof createLinearScale>): void {
    const ctx = this.ctx;
    ctx.save();
    ctx.strokeStyle = this.theme.grid;
    ctx.lineWidth = 1;
    for (const tick of yScale.ticks(4)) {
      const y = yScale.map(tick);
      ctx.beginPath();
      ctx.moveTo(plot.x, y);
      ctx.lineTo(plot.x + plot.width, y);
      ctx.stroke();
    }
    ctx.restore();
  }

  private drawAxes(
    plot: PlotRect,
    xScale: ReturnType<typeof createLinearScale>,
    yScale: ReturnType<typeof createLinearScale>,
    labels: string[]
  ): void {
    const ctx = this.ctx;
    ctx.save();
    ctx.fillStyle = this.theme.muted;
    ctx.font = '500 11px Inter, system-ui, sans-serif';
    ctx.textAlign = 'right';
    ctx.textBaseline = 'middle';

    for (const tick of yScale.ticks(4)) {
      ctx.fillText(formatTick(tick), plot.x - 8, yScale.map(tick));
    }

    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    const step = Math.max(1, Math.ceil(labels.length / Math.max(1, Math.floor(plot.width / 56))));
    for (let i = 0; i < labels.length; i += step) {
      ctx.fillText(labels[i], xScale.map(i), plot.y + plot.height + 8);
    }
    ctx.restore();
  }

  private drawLines(
    yScale: ReturnType<typeof createLinearScale>,
    series: LayoutResult['series'],
    count: number
  ): void {
    if (!this.layout) return;
    const { xScale, plot } = this.layout;
    const ctx = this.ctx;
    const baseline = yScale.map(yScale.domain[0]);

    for (const s of series) {
      const points: Array<[number, number]> = s.values.map((v, i) => [
        xScale.map(count === 1 ? 0 : i),
        yScale.map(v),
      ]);

      if (s.fill) {
        ctx.fillStyle = s.backgroundColor;
        ctx.fill(areaPath(points, baseline, 0.35));
      }

      ctx.strokeStyle = s.borderColor;
      ctx.lineWidth = this.options.lineWidth;
      ctx.lineJoin = 'round';
      ctx.lineCap = 'round';
      ctx.stroke(linePath(points, 0.35));

      if (this.options.pointRadius > 0 || this.hoverIndex !== null) {
        for (let i = 0; i < points.length; i++) {
          const r =
            this.hoverIndex === i
              ? Math.max(4, this.options.pointRadius + 2)
              : this.options.pointRadius;
          if (r <= 0) continue;
          ctx.beginPath();
          ctx.fillStyle = s.borderColor;
          ctx.arc(points[i][0], points[i][1], r, 0, Math.PI * 2);
          ctx.fill();
        }
      }
    }

    if (this.hoverIndex !== null && count > 0) {
      const x = xScale.map(this.hoverIndex);
      ctx.save();
      ctx.strokeStyle = this.theme.grid;
      ctx.setLineDash([4, 4]);
      ctx.beginPath();
      ctx.moveTo(x, plot.y);
      ctx.lineTo(x, plot.y + plot.height);
      ctx.stroke();
      ctx.restore();
    }
  }

  private drawBars(
    plot: PlotRect,
    yScale: ReturnType<typeof createLinearScale>,
    series: LayoutResult['series'],
    count: number
  ): void {
    if (!count) return;
    const ctx = this.ctx;
    const groupWidth = plot.width / count;
    const gap = groupWidth * this.options.barGap;
    const inner = groupWidth - gap;
    const barW = inner / Math.max(series.length, 1);
    const baseline = yScale.map(Math.max(0, yScale.domain[0]));

    series.forEach((s, si) => {
      s.values.forEach((v, i) => {
        ctx.fillStyle = s.colors?.[i] ?? s.backgroundColor ?? s.borderColor;
        const x = plot.x + i * groupWidth + gap / 2 + si * barW;
        const y = yScale.map(v);
        const h = baseline - y;
        const radius = Math.min(6, barW / 2);
        roundRect(ctx, x, Math.min(y, baseline), Math.max(barW - 1, 1), Math.abs(h) || 1, radius);
        ctx.fill();
      });
    });
  }

  private drawLegend(series: LayoutResult['series']): void {
    const ctx = this.ctx;
    ctx.save();
    ctx.font = '500 12px Inter, system-ui, sans-serif';
    let x = this.options.padding.left;
    const y = 10;
    for (const s of series) {
      ctx.fillStyle = s.borderColor;
      ctx.beginPath();
      ctx.arc(x + 3, y + 4, 3, 0, Math.PI * 2);
      ctx.fill();
      ctx.fillStyle = this.theme.muted;
      ctx.textBaseline = 'top';
      ctx.fillText(s.label, x + 10, y);
      x += ctx.measureText(s.label).width + 28;
    }
    ctx.restore();
  }

  private drawTooltip(
    plot: PlotRect,
    xScale: ReturnType<typeof createLinearScale>,
    labels: string[],
    series: LayoutResult['series'],
    index: number
  ): void {
    const ctx = this.ctx;
    const title = labels[index] ?? String(index + 1);
    const lines = series.map((s) => `${s.label}: ${formatTick(s.values[index] ?? 0)}`);
    ctx.save();
    ctx.font = '600 12px Inter, system-ui, sans-serif';
    const titleW = ctx.measureText(title).width;
    ctx.font = '400 12px Inter, system-ui, sans-serif';
    const bodyW = Math.max(...lines.map((l) => ctx.measureText(l).width), titleW);
    const boxW = bodyW + 24;
    const boxH = 18 + lines.length * 18 + 12;
    let x = xScale.map(index) + 12;
    let y = plot.y + 8;
    if (x + boxW > plot.x + plot.width) x = xScale.map(index) - boxW - 12;

    ctx.fillStyle = this.theme.tooltipBg;
    ctx.strokeStyle = this.theme.tooltipBorder;
    ctx.lineWidth = 1;
    roundRect(ctx, x, y, boxW, boxH, 10);
    ctx.fill();
    ctx.stroke();

    ctx.fillStyle = this.theme.tooltipText;
    ctx.font = '600 12px Inter, system-ui, sans-serif';
    ctx.fillText(title, x + 12, y + 14);
    ctx.font = '400 12px Inter, system-ui, sans-serif';
    lines.forEach((line, i) => {
      ctx.fillStyle = series[i].borderColor;
      ctx.beginPath();
      ctx.arc(x + 16, y + 36 + i * 18, 3, 0, Math.PI * 2);
      ctx.fill();
      ctx.fillStyle = this.theme.tooltipText;
      ctx.fillText(line, x + 24, y + 32 + i * 18);
    });
    ctx.restore();
  }

  private indexFromX(x: number): number | null {
    if (!this.layout) return null;
    const { plot, labels } = this.layout;
    if (x < plot.x || x > plot.x + plot.width || labels.length === 0) return null;
    if (this.type === 'bar') {
      const groupWidth = plot.width / labels.length;
      return Math.min(labels.length - 1, Math.max(0, Math.floor((x - plot.x) / groupWidth)));
    }
    const t = (x - plot.x) / plot.width;
    return Math.min(labels.length - 1, Math.max(0, Math.round(t * (labels.length - 1))));
  }

  private indexFromAngle(x: number, y: number): number | null {
    if (!this.layout || !this.sliceAngles.length) return null;
    const { plot } = this.layout;
    const cx = plot.x + plot.width / 2;
    const cy = plot.y + plot.height / 2;
    const dx = x - cx;
    const dy = y - cy;
    const dist = Math.sqrt(dx * dx + dy * dy);
    const radius = Math.max(8, Math.min(plot.width, plot.height) / 2 - 4);
    const inner = radius * this.options.cutout;
    if (dist > radius || dist < inner) return null;

    let angle = Math.atan2(dy, dx);
    if (angle < -Math.PI / 2) angle += Math.PI * 2;
    for (let i = 0; i < this.sliceAngles.length; i++) {
      const { start, end } = this.sliceAngles[i];
      if (angle >= start && angle <= end) return i;
    }
    return null;
  }
}

function formatTick(n: number): string {
  if (Math.abs(n) >= 1000) return `${(n / 1000).toFixed(n % 1000 === 0 ? 0 : 1)}k`;
  return Number.isInteger(n) ? String(n) : n.toFixed(1);
}

function roundRect(
  ctx: CanvasRenderingContext2D,
  x: number,
  y: number,
  w: number,
  h: number,
  r: number
): void {
  const radius = Math.min(r, w / 2, h / 2);
  ctx.beginPath();
  ctx.moveTo(x + radius, y);
  ctx.arcTo(x + w, y, x + w, y + h, radius);
  ctx.arcTo(x + w, y + h, x, y + h, radius);
  ctx.arcTo(x, y + h, x, y, radius);
  ctx.arcTo(x, y, x + w, y, radius);
  ctx.closePath();
}
