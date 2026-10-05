import { prefersReducedMotion } from '../../../shared/motion-tokens';
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
      | 'title'
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
  private legendTop = 8;
  private startLabel = 'Start';
  private endLabel = 'End';
  private unit = '';
  private gridRows = 10;
  private gridCols = 10;
  private reveal = 1;
  private revealRaf = 0;
  private destroyed = false;
  private waffleHits: Array<{ x: number; y: number; size: number; index: number }> = [];
  private slopeHits: Array<{ x0: number; y0: number; x1: number; y1: number; index: number }> = [];

  constructor(canvas: HTMLCanvasElement, config: NativeChartConfig) {
    const ctx = canvas.getContext('2d');
    if (!ctx) throw new Error('Canvas 2D context unavailable');

    if (!isNativeChartType(config.type)) {
      throw new Error(`[neura-chart] Unsupported type "${config.type}". Use line, bar, pie, doughnut, donut, waffle, or slope.`);
    }

    this.canvas = canvas;
    this.ctx = ctx;
    this.type = config.type;
    this.data = config.data;
    this.options = {
      showLegend: config.options?.showLegend ?? true,
      showGrid: config.options?.showGrid ?? true,
      showTooltip: config.options?.showTooltip ?? true,
      barGap: config.options?.barGap ?? 0.34,
      lineWidth: config.options?.lineWidth ?? 2.25,
      pointRadius: config.options?.pointRadius ?? 0,
      fillOpacity: config.options?.fillOpacity ?? 0.22,
      cutout: config.options?.cutout ?? (config.type === 'doughnut' || config.type === 'donut' ? 0.64 : 0),
      colors: config.options?.colors ?? DEFAULT_PALETTE,
      title: config.options?.title ?? '',
      padding: { ...DEFAULT_PADDING, ...(config.options?.padding ?? {}) },
    };
    this.startLabel = config.options?.startLabel || 'Start';
    this.endLabel = config.options?.endLabel || 'End';
    this.unit = config.options?.unit ?? '';
    this.gridRows = Math.max(1, Math.round(config.options?.rows ?? 10));
    this.gridCols = Math.max(1, Math.round(config.options?.columns ?? 10));
    this.reveal = prefersReducedMotion() ? 1 : 0;
    this.theme = this.readTheme(config.theme);

    this.bind();
    this.draw();
    this.kickReveal();
  }

  update(data: NativeChartData, options?: NativeChartOptions): void {
    this.data = data;
    if (options) {
      this.options = {
        ...this.options,
        ...options,
        padding: { ...this.options.padding, ...(options.padding ?? {}) },
        colors: options.colors ?? this.options.colors,
        title: options.title ?? this.options.title,
      };
      if (options.startLabel) this.startLabel = options.startLabel;
      if (options.endLabel) this.endLabel = options.endLabel;
      if (options.unit !== undefined) this.unit = options.unit;
      if (options.rows) this.gridRows = Math.max(1, Math.round(options.rows));
      if (options.columns) this.gridCols = Math.max(1, Math.round(options.columns));
    }
    this.draw();
  }

  setTheme(isDark: boolean, override: Partial<NativeThemeColors> = {}): void {
    this.theme = this.readTheme(override, isDark);
    this.draw();
  }

  private readTheme(override: Partial<NativeThemeColors> = {}, isDark?: boolean): NativeThemeColors {
    const host = this.canvas.parentElement ?? document.documentElement;
    const style = getComputedStyle(host);
    const fg = style.getPropertyValue('--nk-fg').trim();
    const edge = style.getPropertyValue('--nk-edge').trim();
    const surface =
      style.getPropertyValue('--nk-surface-raised').trim() ||
      style.getPropertyValue('--nk-surface').trim();
    const base =
      fg && edge && surface
        ? {
            text: fg,
            muted: `color-mix(in oklab, ${fg} 46%, transparent)`,
            grid: `color-mix(in oklab, ${fg} 9%, transparent)`,
            tooltipBg: surface,
            tooltipText: fg,
            tooltipBorder: edge,
            surface,
          }
        : resolveTheme(isDark ?? document.documentElement.classList.contains('dark'));
    return { ...base, ...override };
  }

  private font(weight: number, size: number): string {
    const family =
      getComputedStyle(document.body).fontFamily || 'ui-sans-serif, system-ui, sans-serif';
    return `${weight} ${size}px ${family}`;
  }

  destroy(): void {
    this.destroyed = true;
    cancelAnimationFrame(this.raf);
    cancelAnimationFrame(this.revealRaf);
    this.ro?.disconnect();
    if (this.onMove) this.canvas.removeEventListener('mousemove', this.onMove);
    if (this.onLeave) this.canvas.removeEventListener('mouseleave', this.onLeave);
  }

  private get isRadial(): boolean {
    return this.type === 'pie' || this.type === 'doughnut' || this.type === 'donut';
  }

  private kickReveal(): void {
    if (this.reveal >= 1 || this.destroyed) return;
    const step = () => {
      if (this.destroyed) return;
      this.reveal = Math.min(1, this.reveal + 0.028);
      this.draw();
      if (this.reveal < 1) this.revealRaf = requestAnimationFrame(step);
    };
    this.revealRaf = requestAnimationFrame(step);
  }

  private easedReveal(): number {
    const t = this.reveal;
    return 1 - (1 - t) ** 3;
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
      const idx = this.type === 'waffle'
        ? this.indexFromWaffle(x, y)
        : this.type === 'slope'
          ? this.indexFromSlope(x, y)
          : this.isRadial
            ? this.indexFromAngle(x, y)
            : this.indexFromX(x);
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
    const style = getComputedStyle(parent);
    const padX = (parseFloat(style.paddingLeft) || 0) + (parseFloat(style.paddingRight) || 0);
    const padY = (parseFloat(style.paddingTop) || 0) + (parseFloat(style.paddingBottom) || 0);
    const dpr = window.devicePixelRatio || 1;
    const width = Math.max(1, parent.clientWidth - padX);
    const height = Math.max(1, parent.clientHeight - padY);
    this.canvas.width = Math.floor(width * dpr);
    this.canvas.height = Math.floor(height * dpr);
    this.canvas.style.width = `${width}px`;
    this.canvas.style.height = `${height}px`;
    this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }

  private computeLayout(width: number, height: number): LayoutResult {
    const pad = { ...this.options.padding };
    const labels = this.data.labels ?? this.data.datasets[0]?.data.map((_, i) => String(i + 1)) ?? [];
    const ctx = this.ctx;
    ctx.font = this.font(500, 11);

    if (this.isRadial || this.type === 'waffle' || this.type === 'slope') {
      pad.left = 16;
      pad.right = 16;
      pad.bottom = 12;
    } else {
      const values = this.data.datasets.flatMap((dataset) => dataset.data);
      const ticks = createLinearScale(extent(values), [0, 1]).ticks(4);
      const yWidth = ticks.reduce((max, tick) => Math.max(max, ctx.measureText(formatTick(tick)).width), 0);
      pad.left = Math.max(pad.left, Math.ceil(yWidth) + 14);
      if (labels.length) {
        const first = ctx.measureText(String(labels[0])).width / 2 + 8;
        const last = ctx.measureText(String(labels[labels.length - 1])).width / 2 + 8;
        pad.left = Math.max(pad.left, first);
        pad.right = Math.max(pad.right, last);
      }
      pad.bottom = Math.max(pad.bottom, 28);
    }

    const legendLabels = this.isRadial || this.type === 'waffle'
      ? labels.map((label) => String(label))
      : this.data.datasets.map((dataset, index) => dataset.label ?? `Series ${index + 1}`);
    const rows = this.options.showLegend ? this.legendRows(width, legendLabels) : 0;
    const titleHeight = this.options.title ? 22 : 0;
    this.legendTop = 8 + titleHeight;
    pad.top = Math.max(pad.top, this.legendTop + rows * 18 + 6);

    const plot: PlotRect = {
      x: pad.left,
      y: pad.top,
      width: Math.max(1, width - pad.left - pad.right),
      height: Math.max(1, height - pad.top - pad.bottom),
    };

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
      if (this.options.title) this.drawTitle(width);
      this.drawRadial(plot, labels, series);
      if (this.options.showLegend) this.drawRadialLegend(labels, series, width);
      if (this.options.showTooltip && this.hoverIndex !== null) {
        this.drawRadialTooltip(plot, labels, series, this.hoverIndex);
      }
      return;
    }

    if (this.type === 'waffle') {
      if (this.options.title) this.drawTitle(width);
      this.drawWaffle(plot, labels, series);
      if (this.options.showLegend) {
        const colors = this.categoryColors(labels, series);
        this.drawLegendItems(
          labels.map((label, index) => ({ label, color: colors[index % colors.length] })),
          width
        );
      }
      if (this.options.showTooltip && this.hoverIndex !== null) {
        this.drawWaffleTooltip(labels, series, this.hoverIndex);
      }
      return;
    }

    if (this.type === 'slope') {
      if (this.options.title) this.drawTitle(width);
      this.drawSlope(plot, series);
      if (this.options.showLegend) {
        this.drawLegendItems(
          series.map((item, index) => ({ label: item.label, color: this.slopeColor(index) })),
          width
        );
      }
      if (this.options.showTooltip && this.hoverIndex !== null) {
        this.drawSlopeTooltip(series, this.hoverIndex);
      }
      return;
    }

    if (this.options.title) this.drawTitle(width);
    if (this.options.showGrid) this.drawGrid(plot, yScale);
    this.drawAxes(plot, xScale, yScale, labels);

    if (this.type === 'bar') this.drawBars(plot, yScale, series, labels.length);
    else this.drawLines(yScale, series, labels.length);

    if (this.options.showLegend) this.drawLegend(series, width);
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
      const gap = slice > 0.12 ? 0.03 : 0;
      const active = this.hoverIndex === null || this.hoverIndex === i;
      const lift = this.hoverIndex === i && this.type !== 'pie' ? 6 : 0;
      const mid = start + slice / 2;
      ctx.save();
      ctx.globalAlpha = active ? 1 : 0.28;
      if (lift) ctx.translate(Math.cos(mid) * lift, Math.sin(mid) * lift);
      ctx.beginPath();
      ctx.moveTo(cx + Math.cos(start + gap) * inner, cy + Math.sin(start + gap) * inner);
      ctx.arc(cx, cy, radius, start + gap, end - gap);
      ctx.arc(cx, cy, inner, end - gap, start + gap, true);
      ctx.closePath();
      ctx.fillStyle = colors[i % colors.length];
      ctx.fill();
      ctx.restore();
      angle = end;
    });

    if (this.options.cutout > 0.2) {
      const index = this.hoverIndex;
      const caption = index === null ? 'Total' : labels[index] ?? '';
      const shown = index === null ? values.reduce((sum, value) => sum + Math.max(0, value), 0) : values[index] ?? 0;
      const hole = Math.max(24, inner * 1.5);
      ctx.save();
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillStyle = this.theme.text;
      let valueSize = 18;
      ctx.font = this.font(600, valueSize);
      while (valueSize > 12 && ctx.measureText(formatTick(shown)).width > hole) {
        valueSize -= 1;
        ctx.font = this.font(600, valueSize);
      }
      ctx.fillText(formatTick(shown), cx, cy - 8);
      ctx.fillStyle = this.theme.muted;
      ctx.font = this.font(500, 11);
      ctx.fillText(this.fitLabel(caption, hole), cx, cy + 12);
      ctx.restore();
    }
  }

  private drawRadialLegend(labels: string[], series: LayoutResult['series'], width: number): void {
    this.drawLegendItems(
      labels.map((label, index) => ({
        label,
        color: (series[0]?.colors ?? this.options.colors)[index % this.options.colors.length],
      })),
      width
    );
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
    ctx.font = this.font(600, 12);
    const boxW = Math.max(ctx.measureText(title).width, ctx.measureText(line).width) + 36;
    const boxH = 48;
    const canvasW = this.canvas.clientWidth;
    const x = Math.max(8, Math.min(plot.x + plot.width / 2 - boxW / 2, canvasW - boxW - 8));
    const y = Math.max(8, plot.y + 8);

    ctx.fillStyle = this.theme.tooltipBg;
    ctx.strokeStyle = this.theme.tooltipBorder;
    roundRect(ctx, x, y, boxW, boxH, 10);
    ctx.fill();
    ctx.stroke();

    ctx.textAlign = 'left';
    ctx.textBaseline = 'top';
    ctx.fillStyle = colors[index % colors.length];
    ctx.beginPath();
    ctx.arc(x + 14, y + 16, 3, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = this.theme.tooltipText;
    ctx.font = this.font(600, 12);
    ctx.fillText(title, x + 24, y + 8);
    ctx.font = this.font(400, 12);
    ctx.fillText(line, x + 24, y + 26);
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
    ctx.font = this.font(500, 11);
    ctx.textAlign = 'right';
    ctx.textBaseline = 'middle';

    for (const tick of yScale.ticks(4)) {
      ctx.fillText(formatTick(tick), plot.x - 8, yScale.map(tick));
    }

    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    const labelY = plot.y + plot.height + 10;
    let lastRight = -Infinity;
    labels.forEach((label, i) => {
      const text = String(label);
      const textWidth = ctx.measureText(text).width;
      const x = xScale.map(i);
      const isLast = i === labels.length - 1;
      if (!isLast && x - textWidth / 2 < lastRight + 10) return;
      if (isLast && x - textWidth / 2 < lastRight + 10 && i !== 0) return;
      ctx.fillText(text, x, labelY);
      lastRight = x + textWidth / 2;
    });
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

      if (s.fill && points.length) {
        const top = Math.min(...points.map((point) => point[1]));
        const fade = ctx.createLinearGradient(0, top, 0, baseline);
        fade.addColorStop(0, withAlpha(s.borderColor, this.options.fillOpacity));
        fade.addColorStop(1, withAlpha(s.borderColor, 0));
        ctx.fillStyle = fade;
        ctx.fill(areaPath(points, baseline, 0.35));
      }

      ctx.strokeStyle = s.borderColor;
      ctx.lineWidth = this.options.lineWidth;
      ctx.lineJoin = 'round';
      ctx.lineCap = 'round';
      ctx.stroke(linePath(points, 0.35));

      if (this.hoverIndex !== null && points[this.hoverIndex]) {
        const [px, py] = points[this.hoverIndex];
        ctx.beginPath();
        ctx.fillStyle = this.theme.surface;
        ctx.arc(px, py, 6, 0, Math.PI * 2);
        ctx.fill();
        ctx.beginPath();
        ctx.fillStyle = s.borderColor;
        ctx.arc(px, py, 3.25, 0, Math.PI * 2);
        ctx.fill();
      } else if (this.options.pointRadius > 0) {
        for (const [px, py] of points) {
          ctx.beginPath();
          ctx.fillStyle = s.borderColor;
          ctx.arc(px, py, this.options.pointRadius, 0, Math.PI * 2);
          ctx.fill();
        }
      }
    }

    if (this.hoverIndex !== null && count > 0) {
      const x = xScale.map(this.hoverIndex);
      ctx.save();
      ctx.strokeStyle = this.theme.grid;
      ctx.lineWidth = 1;
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
        const active = this.hoverIndex === null || this.hoverIndex === i;
        ctx.save();
        ctx.globalAlpha = active ? 1 : 0.32;
        ctx.fillStyle = s.colors?.[i] ?? s.backgroundColor ?? s.borderColor;
        const x = plot.x + i * groupWidth + gap / 2 + si * barW;
        const y = yScale.map(v);
        const h = baseline - y;
        const width = Math.max(barW - 2, 1);
        const height = Math.abs(h) || 1;
        const top = Math.min(y, baseline);
        roundTopRect(ctx, x, top, width, height, Math.min(8, width / 2));
        ctx.fill();
        ctx.restore();
      });
    });
  }

  private drawLegend(series: LayoutResult['series'], width: number): void {
    this.drawLegendItems(
      series.map((item) => ({ label: item.label, color: item.borderColor })),
      width
    );
  }

  private legendRows(width: number, labels: string[]): number {
    const ctx = this.ctx;
    ctx.save();
    ctx.font = this.font(500, 12);
    let rows = 1;
    let x = 12;
    labels.forEach((label) => {
      const itemWidth = ctx.measureText(label).width + 28;
      if (x > 12 && x + itemWidth > width - 8) {
        rows += 1;
        x = 12;
      }
      x += itemWidth;
    });
    ctx.restore();
    return rows;
  }

  private drawLegendItems(items: Array<{ label: string; color: string }>, width: number): void {
    const ctx = this.ctx;
    ctx.save();
    ctx.font = this.font(500, 12);
    ctx.textAlign = 'left';
    ctx.textBaseline = 'middle';
    let x = 12;
    let y = this.legendTop + 7;
    items.forEach((item) => {
      const itemWidth = ctx.measureText(item.label).width + 28;
      if (x > 12 && x + itemWidth > width - 8) {
        x = 12;
        y += 18;
      }
      ctx.fillStyle = item.color;
      ctx.beginPath();
      ctx.arc(x + 3, y, 3, 0, Math.PI * 2);
      ctx.fill();
      ctx.fillStyle = this.theme.muted;
      ctx.fillText(item.label, x + 12, y);
      x += itemWidth;
    });
    ctx.restore();
  }

  private drawTitle(width: number): void {
    const ctx = this.ctx;
    ctx.save();
    ctx.font = this.font(600, 13);
    ctx.fillStyle = this.theme.text;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    ctx.fillText(this.fitLabel(this.options.title, width - 24), width / 2, 8);
    ctx.restore();
  }

  private fitLabel(text: string, maxWidth: number): string {
    if (this.ctx.measureText(text).width <= maxWidth) return text;
    let value = text;
    while (value.length > 1 && this.ctx.measureText(`${value}…`).width > maxWidth) {
      value = value.slice(0, -1);
    }
    return `${value}…`;
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
    const lines = series.map((s) => `${s.label}  ${formatTick(s.values[index] ?? 0)}`);
    ctx.save();
    ctx.font = this.font(600, 12);
    const titleW = ctx.measureText(title).width;
    ctx.font = this.font(500, 12);
    const bodyW = Math.max(...lines.map((line) => ctx.measureText(line).width), titleW);
    const boxW = bodyW + 28;
    const boxH = 16 + lines.length * 18 + 14;
    let x = xScale.map(index) + 14;
    const anchor = this.layout ? this.layout.yScale.map(series[0]?.values[index] ?? 0) : plot.y;
    let y = anchor - boxH - 12;
    const canvasW = this.canvas.clientWidth;
    const canvasH = this.canvas.clientHeight;
    if (x + boxW > canvasW - 8) x = xScale.map(index) - boxW - 14;
    x = Math.max(8, Math.min(x, canvasW - boxW - 8));
    if (y < 8) y = Math.min(anchor + 12, canvasH - boxH - 8);
    y = Math.max(8, Math.min(y, canvasH - boxH - 8));

    ctx.shadowColor = 'rgba(0, 0, 0, 0.12)';
    ctx.shadowBlur = 18;
    ctx.shadowOffsetY = 8;
    ctx.fillStyle = this.theme.tooltipBg;
    ctx.strokeStyle = this.theme.tooltipBorder;
    ctx.lineWidth = 1;
    roundRect(ctx, x, y, boxW, boxH, 10);
    ctx.fill();
    ctx.shadowColor = 'transparent';
    ctx.stroke();

    ctx.textBaseline = 'top';
    ctx.fillStyle = this.theme.muted;
    ctx.font = this.font(500, 11);
    ctx.fillText(title, x + 12, y + 8);
    lines.forEach((line, i) => {
      ctx.fillStyle = series[i].borderColor;
      ctx.beginPath();
      ctx.arc(x + 16, y + 32 + i * 18, 3, 0, Math.PI * 2);
      ctx.fill();
      ctx.fillStyle = this.theme.tooltipText;
      ctx.font = this.font(560, 12);
      ctx.fillText(line, x + 24, y + 24 + i * 18);
    });
    ctx.restore();
  }

  private drawWaffle(
    plot: PlotRect,
    labels: string[],
    series: LayoutResult['series']
  ): void {
    const values = series[0]?.values ?? [];
    const colors = this.categoryColors(labels, series);
    const rows = this.gridRows;
    const cols = this.gridCols;
    const totalCells = rows * cols;
    const counts = cellCounts(values, totalCells);
    const gap = 3;
    const size = Math.min(
      (plot.width - gap * (cols - 1)) / cols,
      (plot.height - gap * (rows - 1)) / rows
    );
    const gridW = cols * size + (cols - 1) * gap;
    const gridH = rows * size + (rows - 1) * gap;
    const ox = plot.x + (plot.width - gridW) / 2;
    const oy = plot.y + (plot.height - gridH) / 2;
    const ctx = this.ctx;
    const reveal = this.easedReveal();
    this.waffleHits = [];

    let category = 0;
    let remaining = counts[0] ?? 0;
    let drawn = 0;

    for (let col = 0; col < cols; col++) {
      for (let row = rows - 1; row >= 0; row--) {
        while (category < counts.length && remaining === 0) {
          category += 1;
          remaining = counts[category] ?? 0;
        }
        if (category >= counts.length || remaining <= 0) return;
        const order = drawn / totalCells;
        const local = reveal >= 1 ? 1 : Math.max(0, Math.min(1, (reveal - order * 0.4) / 0.6));
        const x = ox + col * (size + gap);
        const y = oy + row * (size + gap);
        this.waffleHits.push({ x, y, size, index: category });
        if (local > 0) {
          const dim = this.hoverIndex !== null && this.hoverIndex !== category;
          const scale = 0.55 + 0.45 * local;
          const cx = x + size / 2;
          const cy = y + size / 2;
          ctx.save();
          ctx.globalAlpha = (dim ? 0.28 : 1) * local;
          ctx.translate(cx, cy);
          ctx.scale(scale, scale);
          ctx.translate(-cx, -cy);
          roundRect(ctx, x, y, size, size, Math.min(3, size / 2));
          ctx.fillStyle = colors[category % colors.length];
          ctx.fill();
          ctx.restore();
        }
        remaining -= 1;
        drawn += 1;
      }
    }
  }

  private drawWaffleTooltip(labels: string[], series: LayoutResult['series'], index: number): void {
    const values = series[0]?.values ?? [];
    const total = values.reduce((sum, value) => sum + Math.max(0, value), 0) || 1;
    const value = Math.max(0, values[index] ?? 0);
    const share = Math.round((value / total) * 100);
    const cells = this.waffleHits.filter((hit) => hit.index === index).length;
    const hit = this.waffleHits.find((item) => item.index === index);
    const unit = this.unit ? ` ${this.unit}` : '';
    this.drawFloatingTip(
      (hit?.x ?? 0) + (hit?.size ?? 0),
      (hit?.y ?? 0),
      labels[index] ?? '',
      [`${formatTick(value)}${unit}`, `${share}% · ${cells} cells`]
    );
  }

  private drawSlope(plot: PlotRect, series: LayoutResult['series']): void {
    if (!series.length) return;
    const ctx = this.ctx;
    ctx.save();
    ctx.font = this.font(500, 12);
    const nameW = Math.min(
      176,
      Math.max(72, ...series.map((item) => ctx.measureText(`${item.label}  ${formatTick(item.values[0] ?? 0)}`).width))
    );
    const valueW = 96;
    const x0 = plot.x + nameW + 18;
    const x1 = plot.x + plot.width - valueW;
    const values = series.flatMap((item) => [item.values[0] ?? 0, item.values[1] ?? 0]);
    const low = Math.min(...values);
    const high = Math.max(...values);
    const span = high - low || 1;
    const yScale = createLinearScale(
      [low - span * 0.12, high + span * 0.18],
      [plot.y + plot.height - 10, plot.y + 22]
    );
    const reveal = this.easedReveal();
    const startYs = series.map((item) => yScale.map(item.values[0] ?? 0));
    const endYs = series.map((item) => yScale.map(item.values[1] ?? 0));
    const startLabels = this.spreadYs(startYs, plot.y + 18, plot.y + plot.height - 8, 20);
    const endLabels = this.spreadYs(endYs, plot.y + 18, plot.y + plot.height - 8, 20);
    const startRank = rankOf(series.map((item) => item.values[0] ?? 0));
    const endRank = rankOf(series.map((item) => item.values[1] ?? 0));

    ctx.fillStyle = this.theme.muted;
    ctx.font = this.font(560, 11);
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(this.startLabel, x0, plot.y + 4);
    ctx.fillText(this.endLabel, x1, plot.y + 4);

    this.slopeHits = [];
    series.forEach((item, index) => {
      const color = this.slopeColor(index);
      const yStart = startYs[index];
      const yEnd = endYs[index];
      const labelStart = startLabels[index];
      const labelEnd = endLabels[index];
      const active = this.hoverIndex === null || this.hoverIndex === index;
      const tipX = x0 + (x1 - x0) * reveal;
      const tipY = yStart + (yEnd - yStart) * reveal;
      ctx.save();
      ctx.globalAlpha = active ? 1 : 0.22;
      ctx.strokeStyle = color;
      ctx.lineWidth = this.hoverIndex === index ? 2.5 : 1.75;
      ctx.lineCap = 'round';
      ctx.beginPath();
      ctx.moveTo(x0, yStart);
      ctx.lineTo(tipX, tipY);
      ctx.stroke();
      ctx.fillStyle = color;
      ctx.beginPath();
      ctx.arc(x0, yStart, 3.5, 0, Math.PI * 2);
      ctx.fill();
      if (reveal > 0.9) {
        ctx.beginPath();
        ctx.arc(x1, yEnd, 3.5, 0, Math.PI * 2);
        ctx.fill();
      }
      ctx.strokeStyle = this.theme.grid;
      ctx.lineWidth = 1;
      if (Math.abs(labelStart - yStart) > 3) {
        ctx.beginPath();
        ctx.moveTo(x0 - 8, labelStart);
        ctx.lineTo(x0, yStart);
        ctx.stroke();
      }
      if (reveal > 0.9 && Math.abs(labelEnd - yEnd) > 3) {
        ctx.beginPath();
        ctx.moveTo(x1, yEnd);
        ctx.lineTo(x1 + 8, labelEnd);
        ctx.stroke();
      }
      ctx.fillStyle = this.theme.text;
      ctx.font = this.font(500, 12);
      ctx.textAlign = 'right';
      ctx.textBaseline = 'middle';
      ctx.fillText(this.fitLabel(`${item.label}  ${formatTick(item.values[0] ?? 0)}`, nameW), x0 - 12, labelStart);
      if (reveal > 0.9) {
        const moved = endRank[index] - startRank[index];
        const arrow = moved < 0 ? '↑' : moved > 0 ? '↓' : '·';
        ctx.fillStyle = this.theme.text;
        ctx.fillText(
          `${formatTick(item.values[1] ?? 0)}  ${arrow} ${startRank[index]}→${endRank[index]}`,
          x1 + 10,
          labelEnd
        );
      }
      ctx.restore();
      this.slopeHits.push({ x0, y0: yStart, x1, y1: yEnd, index });
    });
    ctx.restore();
  }

  private categoryColors(labels: string[], series: LayoutResult['series']): string[] {
    const provided = series[0]?.colors ?? [];
    if (new Set(provided).size > 1) return provided;
    return labels.map((_, index) => this.options.colors[index % this.options.colors.length]);
  }

  private slopeColor(index: number): string {
    const explicit = this.data.datasets[index]?.borderColor;
    if (explicit) {
      return Array.isArray(explicit) ? explicit[0] : explicit;
    }
    if (index === 0) return this.options.colors[0];
    const mix = 28 + (index % 4) * 10;
    return `color-mix(in oklab, ${this.theme.text} ${mix}%, ${this.theme.surface})`;
  }

  private drawSlopeTooltip(series: LayoutResult['series'], index: number): void {
    const item = series[index];
    const hit = this.slopeHits.find((entry) => entry.index === index);
    if (!item || !hit) return;
    const start = item.values[0] ?? 0;
    const end = item.values[1] ?? 0;
    const change = end - start;
    const signed = `${change > 0 ? '+' : ''}${formatTick(change)}`;
    this.drawFloatingTip(
      (hit.x0 + hit.x1) / 2,
      (hit.y0 + hit.y1) / 2,
      item.label,
      [`${this.startLabel} ${this.withUnit(start)}`, `${this.endLabel} ${this.withUnit(end)}`, signed]
    );
  }

  private withUnit(value: number): string {
    return this.unit ? `${formatTick(value)} ${this.unit}` : formatTick(value);
  }

  private spreadYs(ys: number[], min: number, max: number, gap: number): number[] {
    const items = ys.map((y, index) => ({ y, index })).sort((a, b) => a.y - b.y);
    for (let n = 1; n < items.length; n++) {
      if (items[n].y < items[n - 1].y + gap) items[n].y = items[n - 1].y + gap;
    }
    if (items.length) {
      const over = items[items.length - 1].y - max;
      if (over > 0) items.forEach((item) => { item.y -= over; });
      const under = min - items[0].y;
      if (under > 0) items.forEach((item) => { item.y += under; });
    }
    const out = ys.slice();
    items.forEach((item) => { out[item.index] = item.y; });
    return out;
  }

  private drawFloatingTip(anchorX: number, anchorY: number, title: string, lines: string[]): void {
    const ctx = this.ctx;
    ctx.save();
    ctx.font = this.font(600, 12);
    const titleW = ctx.measureText(title).width;
    ctx.font = this.font(500, 12);
    const bodyW = Math.max(titleW, ...lines.map((line) => ctx.measureText(line).width), 0);
    const boxW = bodyW + 24;
    const boxH = 16 + lines.length * 18 + 10;
    const canvasW = this.canvas.clientWidth;
    const canvasH = this.canvas.clientHeight;
    let x = anchorX + 12;
    let y = anchorY - boxH - 10;
    if (x + boxW > canvasW - 8) x = anchorX - boxW - 12;
    x = Math.max(8, Math.min(x, canvasW - boxW - 8));
    if (y < 8) y = Math.min(anchorY + 12, canvasH - boxH - 8);
    y = Math.max(8, Math.min(y, canvasH - boxH - 8));

    ctx.shadowColor = 'rgba(0, 0, 0, 0.12)';
    ctx.shadowBlur = 18;
    ctx.shadowOffsetY = 8;
    ctx.fillStyle = this.theme.tooltipBg;
    ctx.strokeStyle = this.theme.tooltipBorder;
    ctx.lineWidth = 1;
    roundRect(ctx, x, y, boxW, boxH, 10);
    ctx.fill();
    ctx.shadowColor = 'transparent';
    ctx.stroke();
    ctx.textBaseline = 'top';
    ctx.fillStyle = this.theme.muted;
    ctx.font = this.font(500, 11);
    ctx.fillText(title, x + 12, y + 8);
    ctx.fillStyle = this.theme.tooltipText;
    ctx.font = this.font(560, 12);
    lines.forEach((line, index) => {
      ctx.fillText(line, x + 12, y + 26 + index * 18);
    });
    ctx.restore();
  }

  private indexFromWaffle(x: number, y: number): number | null {
    const hit = this.waffleHits.find((cell) => (
      x >= cell.x && x <= cell.x + cell.size && y >= cell.y && y <= cell.y + cell.size
    ));
    return hit ? hit.index : null;
  }

  private indexFromSlope(x: number, y: number): number | null {
    let best: number | null = null;
    let bestDist = 16;
    this.slopeHits.forEach((hit) => {
      const dist = distanceToSegment(x, y, hit.x0, hit.y0, hit.x1, hit.y1);
      if (dist < bestDist) {
        bestDist = dist;
        best = hit.index;
      }
    });
    return best;
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

function cellCounts(values: number[], cells: number): number[] {
  const total = values.reduce((sum, value) => sum + Math.max(0, value), 0);
  if (total <= 0) return values.map(() => 0);
  const exact = values.map((value) => (Math.max(0, value) / total) * cells);
  const counts = exact.map((value) => Math.floor(value));
  let leftover = cells - counts.reduce((sum, value) => sum + value, 0);
  const order = exact
    .map((value, index) => ({ index, frac: value - Math.floor(value) }))
    .sort((a, b) => b.frac - a.frac);
  let cursor = 0;
  while (leftover > 0 && order.length) {
    counts[order[cursor % order.length].index] += 1;
    leftover -= 1;
    cursor += 1;
  }
  return counts;
}

function rankOf(values: number[]): number[] {
  const order = values
    .map((value, index) => ({ value, index }))
    .sort((a, b) => b.value - a.value);
  const ranks = values.map(() => 0);
  order.forEach((item, rank) => {
    ranks[item.index] = rank + 1;
  });
  return ranks;
}

function distanceToSegment(
  px: number,
  py: number,
  x0: number,
  y0: number,
  x1: number,
  y1: number
): number {
  const dx = x1 - x0;
  const dy = y1 - y0;
  const len2 = dx * dx + dy * dy || 1;
  const t = Math.max(0, Math.min(1, ((px - x0) * dx + (py - y0) * dy) / len2));
  return Math.hypot(px - (x0 + t * dx), py - (y0 + t * dy));
}

function formatTick(n: number): string {
  if (Math.abs(n) >= 1000) return `${(n / 1000).toFixed(n % 1000 === 0 ? 0 : 1)}k`;
  return Number.isInteger(n) ? String(n) : n.toFixed(1);
}

function roundTopRect(
  ctx: CanvasRenderingContext2D,
  x: number,
  y: number,
  w: number,
  h: number,
  r: number
): void {
  const radius = Math.min(r, w / 2, Math.max(0, h / 2));
  ctx.beginPath();
  ctx.moveTo(x, y + h);
  ctx.lineTo(x, y + radius);
  ctx.arcTo(x, y, x + radius, y, radius);
  ctx.lineTo(x + w - radius, y);
  ctx.arcTo(x + w, y, x + w, y + radius, radius);
  ctx.lineTo(x + w, y + h);
  ctx.closePath();
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
