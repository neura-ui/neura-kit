import type { Scale } from './types';

export function createLinearScale(domain: [number, number], range: [number, number]): Scale {
  const [d0, d1] = domain;
  const [r0, r1] = range;
  const dSpan = d1 - d0 || 1;
  const rSpan = r1 - r0;

  return {
    domain,
    range,
    map(value: number): number {
      return r0 + ((value - d0) / dSpan) * rSpan;
    },
    ticks(count = 4): number[] {
      if (count <= 0) return [];
      const step = niceStep(dSpan / count);
      const start = Math.ceil(d0 / step) * step;
      const ticks: number[] = [];
      for (let v = start; v <= d1 + step * 0.001; v += step) {
        ticks.push(Number(v.toPrecision(12)));
      }
      return ticks;
    },
  };
}

function niceStep(rough: number): number {
  if (!Number.isFinite(rough) || rough <= 0) return 1;
  const exp = Math.floor(Math.log10(rough));
  const base = rough / 10 ** exp;
  const nice = base <= 1 ? 1 : base <= 2 ? 2 : base <= 5 ? 5 : 10;
  return nice * 10 ** exp;
}

export function extent(values: number[], pad = 0.05): [number, number] {
  if (!values.length) return [0, 1];
  let min = Math.min(...values);
  let max = Math.max(...values);
  if (min === max) {
    min = min > 0 ? 0 : min - 1;
    max = max + 1;
  }
  if (min > 0) min = 0;
  const span = max - min;
  return [min, max + span * pad];
}
