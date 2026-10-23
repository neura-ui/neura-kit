/**
 * Web Mercator, as tile servers cut the world: at zoom z the world is a square of
 * 256 × 2^z pixels, x growing eastward from the antimeridian, y southward from 85.05°N.
 */

export const TILE_SIZE = 256;

/** The latitude where Web Mercator stops: beyond it the square would be infinite. */
export const MAX_LATITUDE = 85.0511287798;

export interface LatLng {
  lat: number;
  lng: number;
}

export interface Point {
  x: number;
  y: number;
}

export function worldSize(zoom: number): number {
  return TILE_SIZE * Math.pow(2, zoom);
}

export function clampLatitude(lat: number): number {
  return Math.max(-MAX_LATITUDE, Math.min(MAX_LATITUDE, lat));
}

/** A longitude brought back between -180 and 180. */
export function wrapLongitude(lng: number): number {
  return ((((lng + 180) % 360) + 360) % 360) - 180;
}

/** The world pixel of a coordinate at a zoom (fractional zooms included). */
export function project(lat: number, lng: number, zoom: number): Point {
  const size = worldSize(zoom);
  const sin = Math.sin((clampLatitude(lat) * Math.PI) / 180);

  return {
    x: ((lng + 180) / 360) * size,
    y: (0.5 - Math.log((1 + sin) / (1 - sin)) / (4 * Math.PI)) * size,
  };
}

/** The coordinate of a world pixel at a zoom. */
export function unproject(x: number, y: number, zoom: number): LatLng {
  const size = worldSize(zoom);
  const n = Math.PI - (2 * Math.PI * y) / size;

  return {
    lat: (180 / Math.PI) * Math.atan(0.5 * (Math.exp(n) - Math.exp(-n))),
    lng: (x / size) * 360 - 180,
  };
}
