/**
 * Geographic data as the map draws it: GeoJSON, or TopoJSON decoded natively, reduced to
 * features made of rings of [lng, lat] pairs. Datasets are fetched once per URL and shared by
 * every map on the page.
 */

export type Ring = Array<[number, number]>;

export interface GeoFeature {
  properties: Record<string, unknown>;
  /** Closed shapes: each polygon is an outer ring followed by its holes. */
  polygons: Ring[][];
  /** Open shapes. */
  lines: Ring[];
  /** Single positions. */
  points: Array<[number, number]>;
}

export interface Bounds {
  west: number;
  south: number;
  east: number;
  north: number;
}

const datasets = new Map<string, Promise<GeoFeature[]>>();

/** Load a dataset from a URL (once per page) or read it from inline data. */
export function loadGeo(source: string | Record<string, unknown> | null): Promise<GeoFeature[]> {
  if (!source) {
    return Promise.resolve([]);
  }

  if (typeof source !== 'string') {
    return Promise.resolve(readGeo(source));
  }

  let loading = datasets.get(source);

  if (!loading) {
    loading = fetch(source, { headers: { Accept: 'application/geo+json, application/json' } })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`[neura-map] ${source} answered ${response.status}`);
        }

        return response.json();
      })
      .then((data) => readGeo(data));

    loading.catch(() => datasets.delete(source));
    datasets.set(source, loading);
  }

  return loading;
}

/** GeoJSON (FeatureCollection, Feature or bare geometry) or TopoJSON, as features. */
export function readGeo(data: any): GeoFeature[] {
  if (!data || typeof data !== 'object') {
    return [];
  }

  if (data.type === 'Topology') {
    return readTopology(data);
  }

  if (data.type === 'FeatureCollection') {
    return (data.features ?? []).flatMap((feature: any) => readGeo(feature));
  }

  if (data.type === 'Feature') {
    const feature = emptyFeature(data.properties ?? {});
    addGeometry(feature, data.geometry);

    return [feature];
  }

  const feature = emptyFeature({});
  addGeometry(feature, data);

  return [feature];
}

/** The box that holds every shape of the features. */
export function featureBounds(features: GeoFeature[]): Bounds | null {
  let bounds: Bounds | null = null;

  const add = ([lng, lat]: [number, number]) => {
    bounds = bounds
      ? {
          west: Math.min(bounds.west, lng),
          south: Math.min(bounds.south, lat),
          east: Math.max(bounds.east, lng),
          north: Math.max(bounds.north, lat),
        }
      : { west: lng, south: lat, east: lng, north: lat };
  };

  for (const feature of features) {
    feature.polygons.forEach((polygon) => polygon.forEach((ring) => ring.forEach(add)));
    feature.lines.forEach((line) => line.forEach(add));
    feature.points.forEach(add);
  }

  return bounds;
}

function emptyFeature(properties: Record<string, unknown>): GeoFeature {
  return { properties, polygons: [], lines: [], points: [] };
}

function addGeometry(feature: GeoFeature, geometry: any): void {
  if (!geometry) {
    return;
  }

  switch (geometry.type) {
    case 'Polygon':
      feature.polygons.push(geometry.coordinates);
      break;
    case 'MultiPolygon':
      feature.polygons.push(...geometry.coordinates);
      break;
    case 'LineString':
      feature.lines.push(geometry.coordinates);
      break;
    case 'MultiLineString':
      feature.lines.push(...geometry.coordinates);
      break;
    case 'Point':
      feature.points.push(geometry.coordinates);
      break;
    case 'MultiPoint':
      feature.points.push(...geometry.coordinates);
      break;
    case 'GeometryCollection':
      (geometry.geometries ?? []).forEach((child: any) => addGeometry(feature, child));
      break;
  }
}

/**
 * TopoJSON: shapes share arcs, stored once and delta-encoded on a quantized grid. Every object
 * of the topology becomes features, each arc index pointing into the shared arcs (a negative
 * index `~i` walks arc `i` backwards).
 */
function readTopology(topology: any): GeoFeature[] {
  const transform = topology.transform;
  const arcs: Ring[] = (topology.arcs ?? []).map((arc: number[][]) => {
    let x = 0;
    let y = 0;

    return arc.map((position) => {
      if (!transform) {
        return [position[0], position[1]] as [number, number];
      }

      x += position[0];
      y += position[1];

      return [
        x * transform.scale[0] + transform.translate[0],
        y * transform.scale[1] + transform.translate[1],
      ] as [number, number];
    });
  });

  const point = (position: number[]): [number, number] =>
    transform
      ? [
          position[0] * transform.scale[0] + transform.translate[0],
          position[1] * transform.scale[1] + transform.translate[1],
        ]
      : [position[0], position[1]];

  const line = (indexes: number[]): Ring => {
    const coordinates: Ring = [];

    indexes.forEach((index, position) => {
      const arc = index < 0 ? arcs[~index].slice().reverse() : arcs[index];
      coordinates.push(...(position === 0 ? arc : arc.slice(1)));
    });

    return coordinates;
  };

  const geometry = (object: any): any => {
    switch (object.type) {
      case 'Polygon':
        return { type: 'Polygon', coordinates: object.arcs.map(line) };
      case 'MultiPolygon':
        return { type: 'MultiPolygon', coordinates: object.arcs.map((polygon: number[][]) => polygon.map(line)) };
      case 'LineString':
        return { type: 'LineString', coordinates: line(object.arcs) };
      case 'MultiLineString':
        return { type: 'MultiLineString', coordinates: object.arcs.map(line) };
      case 'Point':
        return { type: 'Point', coordinates: point(object.coordinates) };
      case 'MultiPoint':
        return { type: 'MultiPoint', coordinates: object.coordinates.map(point) };
      default:
        return null;
    }
  };

  return Object.values(topology.objects ?? {}).flatMap((object: any) => {
    const members = object.type === 'GeometryCollection' ? object.geometries ?? [] : [object];

    return members.map((member: any) => {
      const feature = emptyFeature({ ...(member.properties ?? {}), ...(member.id !== undefined ? { id: member.id } : {}) });
      addGeometry(feature, geometry(member));

      return feature;
    });
  });
}
