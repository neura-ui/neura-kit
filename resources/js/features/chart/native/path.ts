/**
 * Build SVG/canvas path commands for polylines.
 * `tension` 0 = straight segments, ~0.35 = smooth cubic bezier.
 */

export function linePath(
  points: Array<[number, number]>,
  tension = 0
): Path2D {
  const path = new Path2D();
  if (!points.length) return path;

  const [x0, y0] = points[0];
  path.moveTo(x0, y0);

  if (points.length === 1) return path;

  if (tension <= 0) {
    for (let i = 1; i < points.length; i++) {
      path.lineTo(points[i][0], points[i][1]);
    }
    return path;
  }

  for (let i = 0; i < points.length - 1; i++) {
    const [xA, yA] = points[i];
    const [xB, yB] = points[i + 1];
    const cx = (xA + xB) / 2;
    path.bezierCurveTo(cx, yA, cx, yB, xB, yB);
  }

  return path;
}

export function areaPath(
  points: Array<[number, number]>,
  baselineY: number,
  tension = 0
): Path2D {
  const path = linePath(points, tension);
  if (!points.length) return path;

  const last = points[points.length - 1];
  const first = points[0];
  path.lineTo(last[0], baselineY);
  path.lineTo(first[0], baselineY);
  path.closePath();
  return path;
}

export function withAlpha(color: string, alpha: number): string {
  if (color.startsWith('rgba(')) {
    return color.replace(/[\d.]+\)$/, `${alpha})`);
  }
  if (color.startsWith('rgb(')) {
    return color.replace('rgb(', 'rgba(').replace(')', `, ${alpha})`);
  }
  if (color.startsWith('#')) {
    const hex = color.slice(1);
    const full =
      hex.length === 3
        ? hex
            .split('')
            .map((c) => c + c)
            .join('')
        : hex;
    const r = parseInt(full.slice(0, 2), 16);
    const g = parseInt(full.slice(2, 4), 16);
    const b = parseInt(full.slice(4, 6), 16);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
  }
  return color;
}
