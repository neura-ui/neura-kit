import { featureBounds, loadGeo, type Bounds, type GeoFeature } from './geo';
import {
  MAX_LATITUDE,
  clampLatitude,
  project,
  unproject,
  worldSize,
  wrapLongitude,
  type LatLng,
  type Point,
} from './projection';

/**
 * A map drawn natively from open data: the land, the lakes, the graticule and the labels of a
 * dataset such as Natural Earth, in a look the map paints itself, with the shapes coloured by
 * their values and markers on top. Focused on one country, it draws that country alone with
 * its cities, each in its own colour or coloured by its value. No tile, no provider: pan by
 * dragging, zoom with the wheel, a double click, a pinch or the keyboard, and the finer
 * resolution of a dataset is fetched once the zoom asks for it.
 */

export interface MapStyle {
  water: string;
  /** A colour, or `dots` for a dot pattern. */
  land: string;
  dot?: string | null;
  border: string;
  graticule: string | null;
  label: string;
  /** The strong text of the look, for the names that matter most (cities). */
  ink?: string | null;
  halo: string;
  theme: 'light' | 'dark' | 'auto';
}

export interface MapMarker {
  lat: number;
  lng: number;
  id?: string | number;
  label?: string;
  popup?: string;
  /** A palette name (`primary`, `success`, …) or any CSS color. */
  color?: string;
}

/** A dataset in its resolutions, coarsest first, each drawn from its zoom. */
export type Levels = Array<{ source: string | Record<string, unknown>; from: number }>;

/** The subdivisions of the focused country, and the finer ones a click opens. */
export interface MapDivisions {
  url: string;
  level: string;
  /** Where a division's own divisions come from, `__KEY__` standing for its key. */
  drill: string | null;
  /** What a division's label shows: its code (`18`), its name, or nothing. */
  labels: 'code' | 'name' | false;
}

/** The tooltip of a division: a line, lines, or a title over lines. */
export type MapDetail = string | string[] | { title?: string; lines?: string[] };

export interface MapOptions {
  center: LatLng;
  zoom: number;
  minZoom: number;
  maxZoom: number;
  style: MapStyle;
  land: Levels | null;
  lakes: Levels | null;
  overlay: Levels | null;
  divisions: MapDivisions | null;
  /** The ISO code of the one country to draw, the others left out. */
  country: string | null;
  /** The populated places to draw, those of the country when one is focused. */
  cities: Levels | null;
  /** Split the focused country into the zones of its cities. */
  zones: boolean;
  graticule: boolean;
  labels: boolean;
  scrollZoom: boolean;
  /** Feature properties that join a shape to its value, in order of preference. */
  valueKeys: string[];
  /** Feature properties that name a shape, in order of preference. */
  nameKeys: string[];
  locale: string;
  noData: string;
  /** The credit the open data asks for, drawn in the map's own colours. */
  credit: { text: string; url: string | null } | null;
}

export type MapEmit = (event: string, detail: Record<string, unknown>) => void;

type LayerName = 'graticule' | 'land' | 'lakes' | 'overlay' | 'divisions' | 'children';

interface Shape {
  el: SVGPathElement;
  feature: GeoFeature;
  layer: Layer;
  key: string | null;
  name: string | null;
  /** Every handle the data may use for the shape, upper-cased: key, code, name. */
  aliases: string[];
}

interface Layer {
  name: LayerName;
  group: SVGGElement;
  shapeClass: string;
  interactive: boolean;
  levels: Levels;
  /** The resolution drawn, -1 before any has loaded. */
  level: number;
  loading: Set<number>;
  shapes: Shape[];
}

interface Label {
  el: HTMLDivElement;
  at: LatLng;
  from: number;
  rank: number;
  width: number;
}

interface Box {
  left: number;
  top: number;
  right: number;
  bottom: number;
}

interface City {
  el: HTMLButtonElement;
  label: HTMLDivElement;
  name: string;
  key: string;
  at: LatLng;
  population: number;
  region: string | null;
  properties: Record<string, unknown>;
  size: number;
  labelWidth: number;
}

interface Gesture {
  target: EventTarget | null;
  start: Point;
  startCenter: Point;
  startZoom: number;
  startDistance: number;
  anchor: LatLng | null;
  moved: boolean;
  last: Point;
  lastTime: number;
  velocity: Point;
}

const SVG = 'http://www.w3.org/2000/svg';
const PALETTE = ['primary', 'secondary', 'success', 'danger', 'warning', 'info'];
const ZOOM_DURATION = 260;
const LABEL_HEIGHT = 16;
let patterns = 0;

export class NativeMap {
  private readonly viewport: HTMLElement;
  private readonly options: MapOptions;
  private readonly emit: MapEmit;
  private readonly svg: SVGSVGElement;
  private readonly world: SVGGElement;
  private readonly pattern: SVGPatternElement;
  private readonly patternId: string;
  private readonly layers: Record<LayerName, Layer>;
  private readonly zoneGroup: SVGGElement;
  private readonly zoneClip: SVGPathElement;
  private readonly zoneOutline: SVGPathElement;
  private readonly labelPane: HTMLDivElement;
  private readonly cityPane: HTMLDivElement;
  private readonly markerPane: HTMLDivElement;
  private readonly tooltip: HTMLDivElement;
  private readonly popup: HTMLDivElement;
  private readonly listeners = new AbortController();
  private readonly resizeObserver: ResizeObserver;
  private readonly numbers: Intl.NumberFormat;

  private style: MapStyle;
  private center: LatLng;
  private zoom: number;
  private width = 0;
  private height = 0;
  private projectedZoom: number | null = null;
  private projectedShifts = '';
  private shapeByElement = new WeakMap<Element, Shape>();
  private values: Record<string, number> = {};
  private colors: Record<string, string> = {};
  private details: Record<string, MapDetail> = {};
  private selected = new Set<string>();
  private drilled: Shape | null = null;
  private labels: Label[] = [];
  private landLabels: Label[] = [];
  private divisionLabels: Label[] = [];
  private cities: City[] = [];
  private cityByElement = new WeakMap<Element, City>();
  private zones: Array<{ el: SVGPathElement; cell: Point[] }> = [];
  private cityValues: Record<string, number> = {};
  private cityLevel = -1;
  private cityLoading = new Set<number>();
  private measureContext: CanvasRenderingContext2D | null = null;
  private markers: Array<{ data: MapMarker; el: HTMLButtonElement }> = [];
  private popupAt: LatLng | null = null;
  private frame = 0;
  private motion = 0;
  private busy = 0;
  private pointers = new Map<number, Point>();
  private gesture: Gesture | null = null;
  private hovered: Shape | null = null;
  private moveTimer = 0;
  private fitWhenReady = false;

  constructor(viewport: HTMLElement, options: MapOptions, emit: MapEmit) {
    this.viewport = viewport;
    this.options = options;
    this.emit = emit;
    this.style = options.style;
    this.center = { lat: clampLatitude(options.center.lat), lng: wrapLongitude(options.center.lng) };
    this.zoom = options.zoom;
    this.numbers = new Intl.NumberFormat(options.locale || undefined);

    this.svg = document.createElementNS(SVG, 'svg');
    this.svg.setAttribute('class', 'nk-map-world');
    this.svg.setAttribute('aria-hidden', 'true');

    // The dot pattern of the `dots` look, kept the same size on screen at every zoom.
    this.patternId = `nk-map-dots-${++patterns}`;
    const defs = document.createElementNS(SVG, 'defs');
    this.pattern = document.createElementNS(SVG, 'pattern');
    this.pattern.id = this.patternId;
    this.pattern.setAttribute('patternUnits', 'userSpaceOnUse');
    this.pattern.setAttribute('width', '6');
    this.pattern.setAttribute('height', '6');
    const dot = document.createElementNS(SVG, 'circle');
    dot.setAttribute('cx', '3');
    dot.setAttribute('cy', '3');
    dot.setAttribute('r', '1.25');
    dot.setAttribute('class', 'nk-map-dot');
    this.pattern.appendChild(dot);
    defs.appendChild(this.pattern);

    // The focused country's shape, which cuts its cities' zones to its borders.
    const clip = document.createElementNS(SVG, 'clipPath');
    clip.id = `nk-map-country-${patterns}`;
    this.zoneClip = document.createElementNS(SVG, 'path');
    clip.appendChild(this.zoneClip);
    defs.appendChild(clip);
    this.svg.appendChild(defs);

    this.world = document.createElementNS(SVG, 'g');
    this.svg.appendChild(this.world);

    this.layers = {
      graticule: this.layer('graticule', 'nk-map-graticule', false, options.graticule ? [{ source: 'graticule', from: 0 }] : []),
      land: this.layer('land', 'nk-map-land', true, options.land ?? []),
      lakes: this.layer('lakes', 'nk-map-lake', false, options.lakes ?? []),
      overlay: this.layer('overlay', 'nk-map-shape', true, options.overlay ?? []),
      divisions: this.layer('divisions', 'nk-map-division', true, options.divisions ? [{ source: options.divisions.url, from: 0 }] : []),
      children: this.layer('children', 'nk-map-division is-child', true, []),
    };

    this.zoneGroup = document.createElementNS(SVG, 'g');
    this.zoneGroup.setAttribute('class', 'nk-map-layer nk-map-layer-zones');
    this.zoneGroup.setAttribute('clip-path', `url(#${clip.id})`);
    this.zoneOutline = document.createElementNS(SVG, 'path');
    this.zoneOutline.setAttribute('class', 'nk-map-outline');
    this.world.insertBefore(this.zoneGroup, this.layers.lakes.group);
    this.world.insertBefore(this.zoneOutline, this.layers.lakes.group);

    this.viewport.appendChild(this.svg);
    this.cityPane = this.pane('nk-map-cities');
    this.labelPane = this.pane('nk-map-labels');
    this.markerPane = this.pane('nk-map-markers');
    this.popup = this.pane('nk-map-popup');
    this.popup.hidden = true;
    this.tooltip = this.pane('nk-map-tooltip');
    this.tooltip.hidden = true;
    this.credit(options.credit);

    this.setStyle(options.style);
    this.listen();
    this.resizeObserver = new ResizeObserver(() => this.measure());
    this.resizeObserver.observe(this.viewport);
    this.measure();
  }

  /* ------------------------------------------------------------------ */
  /* Public API                                                         */
  /* ------------------------------------------------------------------ */

  destroy(): void {
    this.listeners.abort();
    this.resizeObserver.disconnect();
    cancelAnimationFrame(this.frame);
    cancelAnimationFrame(this.motion);
    window.clearTimeout(this.moveTimer);
    this.viewport.replaceChildren();
  }

  view(): { lat: number; lng: number; zoom: number } {
    return { lat: this.center.lat, lng: this.center.lng, zoom: this.zoom };
  }

  setStyle(style: MapStyle): void {
    this.style = style;

    const properties: Record<string, string | null | undefined> = {
      '--nk-map-water': style.water,
      '--nk-map-land': style.land === 'dots' ? style.water : style.land,
      '--nk-map-dot': style.dot ?? 'currentColor',
      '--nk-map-border': style.border,
      '--nk-map-graticule': style.graticule ?? 'transparent',
      '--nk-map-label': style.label,
      '--nk-map-ink': style.ink ?? style.label,
      '--nk-map-halo': style.halo,
    };

    for (const [property, value] of Object.entries(properties)) {
      this.viewport.style.setProperty(property, value ?? '');
    }

    this.viewport.dataset.theme = style.theme;
    this.viewport.dataset.land = style.land === 'dots' ? 'dots' : 'fill';
    this.paint();
  }

  setValues(values: Record<string, number>): void {
    this.values = Object.fromEntries(
      Object.entries(values ?? {}).map(([key, value]) => [String(key).toUpperCase(), Number(value)])
    );
    this.paint();
  }

  /** Colours by division (key, code or name): a palette name (`success`, `red-500`) or any CSS colour. */
  setColors(colors: Record<string, string>): void {
    this.colors = this.byHandle(colors);
    this.paint();
  }

  /** Tooltip lines by division (key, code or name). */
  setDetails(details: Record<string, MapDetail>): void {
    this.details = this.byHandle(details);
  }

  /** The divisions drawn as selected. */
  setSelected(keys: string[]): void {
    this.selected = new Set((keys ?? []).map((key) => String(key).toUpperCase()));
    this.paint();
  }

  /** Leave the division the map has opened, back to the whole country. */
  back(): void {
    this.undrill();
  }

  /** Values by city name, accents and case aside. */
  setCityValues(values: Record<string, number>): void {
    this.cityValues = Object.fromEntries(
      Object.entries(values ?? {}).map(([name, value]) => [this.cityKey(name), Number(value)])
    );
    this.paintCities();
  }

  setMarkers(markers: MapMarker[]): void {
    this.markers.forEach((marker) => marker.el.remove());
    this.markers = (markers ?? [])
      .filter((marker) => Number.isFinite(Number(marker.lat)) && Number.isFinite(Number(marker.lng)))
      .map((data) => ({ data, el: this.markerElement(data) }));
    this.closePopup();
    this.render();
  }

  /**
   * Frame the data: the overlay, the shapes that carry a value and the markers, or the given
   * box. Before the data has loaded, the map frames it as soon as it arrives.
   */
  fit(bounds: Bounds | null = null, padding = 32, maxZoom = 6): void {
    const box = bounds ?? this.dataBounds();

    if (!box || this.width === 0 || this.height === 0) {
      this.fitWhenReady = bounds === null;

      return;
    }

    this.fitWhenReady = false;

    const east = box.east < box.west ? box.east + 360 : box.east;
    const northWest = project(box.north, box.west, 0);
    const southEast = project(box.south, east, 0);
    const spanX = Math.max(southEast.x - northWest.x, 1e-9);
    const spanY = Math.max(southEast.y - northWest.y, 1e-9);
    const zoom = Math.log2(Math.min((this.width - padding * 2) / spanX, (this.height - padding * 2) / spanY));

    this.zoom = this.clampZoom(Math.min(zoom, maxZoom));
    this.center = unproject((northWest.x + southEast.x) / 2, (northWest.y + southEast.y) / 2, 0);
    this.normalizeCenter();
    this.render();
  }

  zoomIn(): void {
    this.animateZoom(this.zoom + 1, { x: this.width / 2, y: this.height / 2 });
  }

  zoomOut(): void {
    this.animateZoom(this.zoom - 1, { x: this.width / 2, y: this.height / 2 });
  }

  /* ------------------------------------------------------------------ */
  /* Data                                                               */
  /* ------------------------------------------------------------------ */

  private layer(name: LayerName, shapeClass: string, interactive: boolean, levels: Levels): Layer {
    const group = document.createElementNS(SVG, 'g');

    group.setAttribute('class', `nk-map-layer nk-map-layer-${name}`);
    this.world.appendChild(group);

    return { name, group, shapeClass, interactive, levels, level: -1, loading: new Set(), shapes: [] };
  }

  /** The resolution a layer should draw at the current zoom. */
  private wantedLevel(layer: Layer): number {
    let wanted = -1;

    layer.levels.forEach((level, index) => {
      if (this.zoom >= level.from || wanted === -1) {
        wanted = index;
      }
    });

    return wanted;
  }

  /** Fetch the resolution the zoom asks for; the coarser one stays drawn until it arrives. */
  private ensureLevels(): void {
    for (const layer of Object.values(this.layers)) {
      const wanted = this.wantedLevel(layer);

      if (layer.name === 'graticule' || wanted === -1 || wanted === layer.level || layer.loading.has(wanted)) {
        continue;
      }

      layer.loading.add(wanted);
      this.setBusy(1);

      const source = layer.levels[wanted].source;

      loadGeo(source)
        .then((features) => {
          // The division opened may have changed, or closed, while this was on its way.
          if (layer.levels[wanted]?.source !== source) {
            return;
          }

          if (this.wantedLevel(layer) === wanted || layer.level === -1) {
            this.setShapes(layer, features, wanted);
          }
        })
        .catch((error) => {
          console.warn(error);
          this.emit('error', { layer: layer.name, message: String(error?.message ?? error) });
        })
        .finally(() => {
          layer.loading.delete(wanted);
          this.setBusy(-1);
        });
    }
  }

  /** Fetch the cities the zoom asks for, as the layers do. */
  private ensureCities(): void {
    const levels = this.options.cities ?? [];
    let wanted = -1;

    levels.forEach((level, index) => {
      if (this.zoom >= level.from || wanted === -1) {
        wanted = index;
      }
    });

    if (wanted === -1 || wanted === this.cityLevel || this.cityLoading.has(wanted)) {
      return;
    }

    this.cityLoading.add(wanted);
    this.setBusy(1);

    loadGeo(levels[wanted].source)
      .then((features) => {
        if (this.cityLevel < wanted || this.zoom >= levels[wanted].from) {
          this.setCities(features, wanted);
        }
      })
      .catch((error) => {
        console.warn(error);
        this.emit('error', { layer: 'cities', message: String(error?.message ?? error) });
      })
      .finally(() => {
        this.cityLoading.delete(wanted);
        this.setBusy(-1);
      });
  }

  private setCities(features: GeoFeature[], level: number): void {
    this.cities.forEach((city) => {
      city.el.remove();
      city.label.remove();
    });

    const country = this.options.country;
    const places = features
      .filter((feature) => feature.points.length > 0)
      .filter((feature) => !country || String(feature.properties.iso_a2 ?? feature.properties.ISO_A2 ?? '').toUpperCase() === country)
      .map((feature) => {
        const properties = feature.properties;
        const [lng, lat] = feature.points[0];
        const name = String(properties.name ?? properties.NAME ?? properties.nameascii ?? '');

        return {
          name,
          properties,
          at: { lat: Number(properties.latitude ?? lat), lng: Number(properties.longitude ?? lng) },
          population: Number(properties.pop_max ?? properties.POP_MAX ?? 0),
          region: typeof properties.adm1name === 'string' ? properties.adm1name : null,
        };
      })
      .filter((place) => place.name !== '' && Number.isFinite(place.at.lat) && Number.isFinite(place.at.lng))
      .sort((a, b) => b.population - a.population);

    const largest = Math.max(1, ...places.map((place) => place.population));

    this.cityLevel = level;
    this.cities = places.map((place) => {
      const el = document.createElement('button');
      const label = document.createElement('div');
      const size = Math.round(7 + 7 * Math.sqrt(place.population / largest));

      el.type = 'button';
      el.className = 'nk-map-city';
      el.style.setProperty('--nk-map-city-size', `${size}px`);
      el.setAttribute('aria-label', place.name);
      this.cityPane.appendChild(el);

      label.className = 'nk-map-label is-city';
      label.textContent = place.name;
      label.hidden = true;
      this.labelPane.appendChild(label);

      const city: City = { ...place, el, label, key: this.cityKey(place.name), size, labelWidth: this.textWidth(place.name) };
      this.cityByElement.set(el, city);

      return city;
    });

    this.computeZones();
    this.zoneOutline.setAttribute('d', this.zones.length > 0 ? this.zoneClip.getAttribute('d') ?? '' : '');
    this.paintCities();
    this.render();
  }

  /**
   * Each city's zone: the part of the country closer to it than to any other city (a Voronoi
   * cell), cut from the world by the bisector with every other city, then clipped to the
   * country's own shape by the SVG clip path.
   */
  private computeZones(): void {
    this.zoneGroup.replaceChildren();
    this.zones = [];

    if (!this.options.zones || this.options.country === null || this.cities.length === 0) {
      return;
    }

    const world = worldSize(0);
    const sites = this.cities.map((city) => project(city.at.lat, city.at.lng, 0));

    this.zones = this.cities.map((city, index) => {
      let cell: Point[] = [{ x: 0, y: 0 }, { x: world, y: 0 }, { x: world, y: world }, { x: 0, y: world }];

      for (let other = 0; other < sites.length && cell.length > 0; other++) {
        if (other !== index && Math.hypot(sites[other].x - sites[index].x, sites[other].y - sites[index].y) > 1e-9) {
          cell = this.closerHalf(cell, sites[index], sites[other]);
        }
      }

      const el = document.createElementNS(SVG, 'path');
      el.setAttribute('class', 'nk-map-zone');
      this.zoneGroup.appendChild(el);
      this.cityByElement.set(el, city);

      return { el, cell };
    });

    if (this.projectedZoom !== null) {
      this.projectZones(this.projectedZoom, this.projectedShifts.split(',').map(Number));
    }
  }

  /** The part of a convex polygon closer to `site` than to `other` (Sutherland–Hodgman). */
  private closerHalf(polygon: Point[], site: Point, other: Point): Point[] {
    const normal = { x: other.x - site.x, y: other.y - site.y };
    const middle = { x: (site.x + other.x) / 2, y: (site.y + other.y) / 2 };
    const side = (point: Point): number => (point.x - middle.x) * normal.x + (point.y - middle.y) * normal.y;
    const kept: Point[] = [];

    polygon.forEach((point, index) => {
      const next = polygon[(index + 1) % polygon.length];
      const here = side(point);
      const there = side(next);

      if (here <= 0) {
        kept.push(point);
      }

      if (here <= 0 !== there <= 0) {
        const t = here / (here - there);
        kept.push({ x: point.x + (next.x - point.x) * t, y: point.y + (next.y - point.y) * t });
      }
    });

    return kept;
  }

  private projectZones(z: number, shifts: number[]): void {
    const scale = worldSize(z) / worldSize(0);

    for (const zone of this.zones) {
      const d = shifts
        .map((shift) => `M${zone.cell.map((point) => `${(point.x * scale + shift).toFixed(1)} ${(point.y * scale).toFixed(1)}`).join('L')}Z`)
        .join('');

      zone.el.setAttribute('d', zone.cell.length > 2 ? d : '');
    }
  }

  /**
   * Each city in its own colour, spread around the hue circle by the golden angle; or, when
   * cities carry values, the higher the denser.
   */
  private paintCities(): void {
    const values = this.cities
      .map((city) => this.cityValues[city.key])
      .filter((value): value is number => Number.isFinite(value));
    const min = Math.min(...values);
    const max = Math.max(...values);

    this.cities.forEach((city, index) => {
      const value = this.cityValues[city.key];
      let colour = `oklch(0.66 0.16 ${Math.round((index * 137.508) % 360)})`;

      if (values.length > 0) {
        colour = Number.isFinite(value)
          ? `color-mix(in oklab, var(--nk-map-fill) ${Math.round(35 + (max > min ? (value - min) / (max - min) : 1) * 65)}%, var(--nk-map-land))`
          : 'color-mix(in oklab, var(--nk-map-label) 45%, transparent)';
      }

      city.el.style.setProperty('--nk-map-city', colour);

      // The zone takes a lighter shade of its city, or the city's own when it carries a value.
      this.zones[index]?.el.style.setProperty(
        'fill',
        `color-mix(in oklab, ${colour} ${values.length > 0 && Number.isFinite(value) ? 72 : 30}%, var(--nk-map-land))`
      );
    });
  }

  private cityKey(name: string): string {
    return name.normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim().toLowerCase();
  }

  private textWidth(text: string): number {
    if (!this.measureContext) {
      this.measureContext = document.createElement('canvas').getContext('2d');

      if (this.measureContext) {
        this.measureContext.font = `500 11px ${getComputedStyle(this.viewport).fontFamily || 'sans-serif'}`;
      }
    }

    return (this.measureContext?.measureText(text).width ?? text.length * 6) + 8;
  }

  private setBusy(change: number): void {
    const was = this.busy > 0;
    this.busy += change;

    if (was !== this.busy > 0) {
      this.emit('busy', { busy: this.busy > 0 });
    }
  }

  private setShapes(layer: Layer, features: GeoFeature[], level: number): void {
    layer.group.replaceChildren();
    layer.level = level;
    layer.shapes = features
      .filter((feature) => feature.polygons.length > 0 || feature.lines.length > 0)
      .map((feature) => {
        const el = document.createElementNS(SVG, 'path');
        const division = layer.name === 'divisions' || layer.name === 'children';
        const key = division && feature.properties.key ? String(feature.properties.key).toUpperCase() : this.featureKey(feature);
        const name = division && typeof feature.properties.name === 'string' ? feature.properties.name : this.featureName(feature);
        const aliases = division
          ? [...new Set([key, feature.properties.code, name].filter((handle) => handle !== null && handle !== undefined && handle !== '').map((handle) => String(handle).toUpperCase()))]
          : key !== null ? [key] : [];
        const shape: Shape = { el, feature, layer, key, name, aliases };
        const outOfFocus = layer.name === 'land' && this.options.country !== null && shape.key !== this.options.country;

        el.setAttribute('class', feature.polygons.length > 0 ? layer.shapeClass : `${layer.shapeClass} is-line`);
        el.classList.toggle('is-hidden', outOfFocus);

        if (layer.interactive && !outOfFocus) {
          this.shapeByElement.set(el, shape);
        }

        layer.group.appendChild(el);

        return shape;
      });

    if (layer.name === 'land') {
      this.buildLabels(layer.shapes);
    }

    if (layer.name === 'divisions' || layer.name === 'children') {
      this.buildDivisionLabels();
    }

    if (this.projectedZoom !== null) {
      this.projectLayer(layer, this.projectedZoom, this.projectedShifts.split(',').map(Number));
    }

    this.paint();

    if (this.fitWhenReady) {
      this.fit();
    }

    this.render();
  }

  /** The lines of latitude and longitude, denser as the zoom grows. */
  private graticuleFeatures(zoom: number): GeoFeature[] {
    const step = zoom < 3 ? 30 : zoom < 5 ? 10 : 5;
    const lines: Array<Array<[number, number]>> = [];

    for (let lat = -90 + step; lat < 90; lat += step) {
      if (Math.abs(lat) <= 80) {
        lines.push([[-180, lat], [180, lat]]);
      }
    }

    for (let lng = -180; lng < 180; lng += step) {
      lines.push([[lng, -80], [lng, 80]]);
    }

    return [{ properties: {}, polygons: [], lines, points: [] }];
  }

  /* ------------------------------------------------------------------ */
  /* Geometry                                                           */
  /* ------------------------------------------------------------------ */

  private minZoom(): number {
    // Never show empty bands above the north or below the south edge of the world.
    const fill = this.height > 0 ? Math.log2(this.height / 256) : 0;

    return Math.max(this.options.minZoom, fill, 0);
  }

  private clampZoom(zoom: number): number {
    return Math.max(this.minZoom(), Math.min(this.options.maxZoom, zoom));
  }

  /** The world pixel at the viewport's top-left corner, at a zoom. */
  private origin(zoom = this.zoom): Point {
    const center = project(this.center.lat, this.center.lng, zoom);

    return { x: center.x - this.width / 2, y: center.y - this.height / 2 };
  }

  /** Where a coordinate falls in the viewport, on the copy of the world nearest the centre. */
  private toViewport(lat: number, lng: number): Point {
    const world = worldSize(this.zoom);
    const origin = this.origin();
    const point = project(lat, lng, this.zoom);
    let x = point.x - origin.x;

    x -= Math.round((x - this.width / 2) / world) * world;

    return { x, y: point.y - origin.y };
  }

  private toLatLng(point: Point): LatLng {
    const origin = this.origin();

    return unproject(origin.x + point.x, origin.y + point.y, this.zoom);
  }

  /** Keep the centre on the world: longitudes wrap, the poles stay out of view. */
  private normalizeCenter(): void {
    const world = worldSize(this.zoom);
    const point = project(this.center.lat, wrapLongitude(this.center.lng), this.zoom);
    const half = this.height / 2;
    const y = world > this.height ? Math.max(half, Math.min(world - half, point.y)) : world / 2;
    const center = unproject(point.x, y, this.zoom);

    this.center = { lat: Math.max(-MAX_LATITUDE, Math.min(MAX_LATITUDE, center.lat)), lng: wrapLongitude(center.lng) };
  }

  /** Zoom while the coordinate under `anchor` stays under it. */
  private zoomAround(zoom: number, anchor: Point, at: LatLng): void {
    this.zoom = this.clampZoom(zoom);

    const point = project(at.lat, at.lng, this.zoom);
    this.center = unproject(point.x - anchor.x + this.width / 2, point.y - anchor.y + this.height / 2, this.zoom);
    this.normalizeCenter();
  }

  /** The largest polygon of the focused country: its mainland, without far territories. */
  private countryBounds(): Bounds | null {
    const shape = this.layers.land.shapes.find((candidate) => candidate.key === this.options.country);

    if (!shape) {
      return null;
    }

    let best: Bounds | null = null;
    let bestArea = -1;

    for (const polygon of shape.feature.polygons) {
      const bounds = featureBounds([{ properties: {}, polygons: [polygon], lines: [], points: [] }]);

      if (!bounds) {
        continue;
      }

      const northWest = project(bounds.north, bounds.west, 0);
      const southEast = project(bounds.south, bounds.east, 0);
      const area = (southEast.x - northWest.x) * (southEast.y - northWest.y);

      if (area > bestArea) {
        best = bounds;
        bestArea = area;
      }
    }

    return best;
  }

  private dataBounds(): Bounds | null {
    if (this.options.country !== null) {
      return this.countryBounds();
    }

    const overlay = this.layers.overlay.shapes.map((shape) => shape.feature);
    const valued = this.layers.land.shapes
      .filter((shape) => shape.key !== null && Number.isFinite(this.values[shape.key]))
      .map((shape) => shape.feature);
    const shapes = featureBounds([...overlay, ...valued]);
    const corners = [
      ...(shapes ? [[shapes.west, shapes.south], [shapes.east, shapes.north]] : []),
      ...this.markers.map(({ data }) => [Number(data.lng), Number(data.lat)]),
    ];

    if (corners.length === 0) {
      return null;
    }

    return {
      west: Math.min(...corners.map(([lng]) => lng)),
      south: Math.min(...corners.map(([, lat]) => lat)),
      east: Math.max(...corners.map(([lng]) => lng)),
      north: Math.max(...corners.map(([, lat]) => lat)),
    };
  }

  /* ------------------------------------------------------------------ */
  /* Rendering                                                          */
  /* ------------------------------------------------------------------ */

  private measure(): void {
    const rect = this.viewport.getBoundingClientRect();

    this.width = rect.width;
    this.height = rect.height;
    this.zoom = this.clampZoom(this.zoom);
    this.normalizeCenter();

    if (this.fitWhenReady) {
      this.fit();
    }

    this.render();
  }

  private render(): void {
    if (this.frame) {
      return;
    }

    this.frame = requestAnimationFrame(() => {
      this.frame = 0;
      this.ensureLevels();
      this.ensureCities();
      this.drawWorld();
      this.drawCities();
      this.drawLabels();
      this.drawMarkers();
      this.placePopup();
    });
  }

  /**
   * The shapes are projected once per whole zoom, and moved and scaled as a group in between,
   * so a pan never re-projects them. At low zoom the world is drawn three times side by side;
   * further in, only the copy across the antimeridian the centre is close to.
   */
  private drawWorld(): void {
    const z = Math.round(this.zoom);
    const world = worldSize(z);
    const shifts = z <= 3 ? [-world, 0, world] : this.center.lng > 90 ? [0, world] : this.center.lng < -90 ? [-world, 0] : [0];
    const key = shifts.join(',');

    if (this.projectedZoom !== z || this.projectedShifts !== key) {
      this.projectedZoom = z;
      this.projectedShifts = key;

      if (this.options.graticule) {
        this.setGraticule(z);
      }

      Object.values(this.layers).forEach((layer) => this.projectLayer(layer, z, shifts));
      this.projectZones(z, shifts);
    }

    const scale = Math.pow(2, this.zoom - z);
    const center = project(this.center.lat, this.center.lng, z);

    this.world.setAttribute('transform', `matrix(${scale} 0 0 ${scale} ${this.width / 2 - center.x * scale} ${this.height / 2 - center.y * scale})`);
    this.pattern.setAttribute('patternTransform', `scale(${1 / scale})`);
  }

  private setGraticule(z: number): void {
    const layer = this.layers.graticule;

    layer.group.replaceChildren();
    layer.level = 0;
    layer.shapes = this.graticuleFeatures(z).map((feature) => {
      const el = document.createElementNS(SVG, 'path');
      el.setAttribute('class', layer.shapeClass);
      layer.group.appendChild(el);

      return { el, feature, layer, key: null, name: null, aliases: [] };
    });
  }

  private projectLayer(layer: Layer, z: number, shifts: number[]): void {
    const ring = (coordinates: Array<[number, number]>, shift: number, close: boolean): string => {
      let path = '';

      coordinates.forEach(([lng, lat], index) => {
        const point = project(lat, lng, z);
        path += `${index === 0 ? 'M' : 'L'}${(point.x + shift).toFixed(1)} ${point.y.toFixed(1)}`;
      });

      return close && path ? `${path}Z` : path;
    };

    for (const shape of layer.shapes) {
      let d = '';

      for (const shift of shifts) {
        shape.feature.polygons.forEach((polygon) => polygon.forEach((coordinates) => (d += ring(coordinates, shift, true))));
        shape.feature.lines.forEach((coordinates) => (d += ring(coordinates, shift, false)));
      }

      shape.el.setAttribute('d', d);

      if (layer.name === 'land' && this.options.country !== null && shape.key === this.options.country) {
        this.zoneClip.setAttribute('d', d);
        this.zoneOutline.setAttribute('d', this.options.zones && this.cities.length > 0 ? d : '');
      }
    }
  }

  /**
   * Colour each shape by its value, the higher the denser, mixed with the land; a shape
   * without a value keeps the look's land (or dots).
   */
  private paint(): void {
    const shapes = [
      ...this.layers.land.shapes,
      ...this.layers.overlay.shapes,
      ...this.layers.divisions.shapes,
      ...this.layers.children.shapes,
    ];
    const values = shapes
      .map((shape) => this.lookup(this.values, shape))
      .filter((value): value is number => Number.isFinite(value));
    const min = Math.min(...values);
    const max = Math.max(...values);
    const dots = this.style.land === 'dots';

    for (const shape of shapes) {
      const value = this.lookup(this.values, shape);
      const color = this.lookup(this.colors, shape);

      shape.el.classList.toggle('is-selected', shape.aliases.some((handle) => this.selected.has(handle)));

      // A colour of its own (a status) wins over the scale of values.
      if (color !== undefined) {
        shape.el.classList.add('has-value');
        shape.el.style.fill = this.cssColor(color);

        continue;
      }

      if (value === undefined || !Number.isFinite(value)) {
        shape.el.classList.remove('has-value');

        if (dots && shape.layer.name === 'land') {
          shape.el.style.fill = `url(#${this.patternId})`;
        } else {
          shape.el.style.removeProperty('fill');
        }

        continue;
      }

      const share = max > min ? (value - min) / (max - min) : 1;

      shape.el.classList.add('has-value');
      shape.el.style.fill = `color-mix(in oklab, var(--nk-map-fill) ${Math.round(22 + share * 66)}%, var(--nk-map-land))`;
    }
  }

  private buildLabels(shapes: Shape[]): void {
    this.landLabels.forEach((label) => label.el.remove());

    // Focused on a country, its cities (or its divisions) carry the labels.
    if (!this.options.labels || this.options.country !== null) {
      this.landLabels = [];
      this.combineLabels();

      return;
    }

    this.landLabels = shapes
      .filter((shape) => shape.name !== null)
      .map((shape) => {
        const properties = shape.feature.properties;
        const box = featureBounds([shape.feature]);
        const lng = Number(properties.LABEL_X ?? (box ? (box.west + box.east) / 2 : NaN));
        const lat = Number(properties.LABEL_Y ?? (box ? (box.south + box.north) / 2 : NaN));
        const el = document.createElement('div');

        el.className = 'nk-map-label';
        el.textContent = shape.name;
        el.hidden = true;
        this.labelPane.appendChild(el);

        return {
          el,
          at: { lat, lng },
          from: Number(properties.MIN_LABEL ?? 2),
          rank: Number(properties.LABELRANK ?? 9),
          width: this.textWidth(shape.name ?? ''),
        };
      })
      .filter((label) => Number.isFinite(label.at.lat) && Number.isFinite(label.at.lng))
      .sort((a, b) => a.rank - b.rank || a.from - b.from);

    this.combineLabels();
  }

  /**
   * The divisions' labels, at the point the server chose inside each: their code or name,
   * the larger divisions placed first. An opened division gives way to its own divisions.
   */
  private buildDivisionLabels(): void {
    this.divisionLabels.forEach((label) => label.el.remove());

    const mode = this.options.divisions?.labels ?? false;
    const shapes = mode === false ? [] : [
      ...(this.drilled ? [] : this.layers.divisions.shapes),
      ...this.layers.children.shapes,
    ];

    this.divisionLabels = shapes
      .map((shape) => {
        const properties = shape.feature.properties;
        const text = shape.layer.name === 'divisions' && mode === 'code' && properties.code ? String(properties.code) : shape.name ?? '';
        const box = featureBounds([shape.feature]);
        const el = document.createElement('div');

        el.className = `nk-map-label is-division${shape.layer.name === 'children' ? ' is-child' : ''}`;
        el.textContent = text;
        el.hidden = true;
        this.labelPane.appendChild(el);

        return {
          el,
          at: { lat: Number(properties.LABEL_Y), lng: Number(properties.LABEL_X) },
          from: 0,
          // Larger first: the rank is the negative area of the box.
          rank: box ? -Math.abs((box.east - box.west) * (box.north - box.south)) : 0,
          width: this.textWidth(text),
        };
      })
      .filter((label) => label.el.textContent !== '' && Number.isFinite(label.at.lat) && Number.isFinite(label.at.lng));

    this.combineLabels();
  }

  private combineLabels(): void {
    this.labels = [...this.divisionLabels, ...this.landLabels].sort((a, b) => a.rank - b.rank || a.from - b.from);
  }

  /**
   * Dots are placed with `translate`, not `transform`: their hover `scale` composes outside
   * `transform`, and would otherwise scale the offset too and throw the dot across the map.
   */
  private drawCities(): void {
    for (const city of this.cities) {
      const point = this.toViewport(city.at.lat, city.at.lng);
      city.el.style.translate = `${point.x}px ${point.y}px`;
    }
  }

  /**
   * Show the labels the zoom allows, most important first, whole inside the map and none
   * overlapping another: the countries', then the cities' (biggest first, beside their dot,
   * on its left when the right has no room, clear of every dot).
   */
  private drawLabels(): void {
    const placed: Box[] = [];
    const fits = (box: Box): boolean =>
      box.left >= 0 && box.right <= this.width && box.top >= 0 && box.bottom <= this.height &&
      !placed.some((other) => box.left < other.right && box.right > other.left && box.top < other.bottom && box.bottom > other.top);
    const cityPoints = this.cities.map((city) => this.toViewport(city.at.lat, city.at.lng));

    this.cities.forEach((city, index) => {
      const point = cityPoints[index];
      const half = city.size / 2;

      placed.push({ left: point.x - half, top: point.y - half, right: point.x + half, bottom: point.y + half });
    });

    for (const label of this.labels) {
      const point = this.toViewport(label.at.lat, label.at.lng);
      const box = {
        left: point.x - label.width / 2,
        top: point.y - LABEL_HEIGHT / 2,
        right: point.x + label.width / 2,
        bottom: point.y + LABEL_HEIGHT / 2,
      };
      const visible = this.zoom + 0.6 >= label.from && fits(box);

      label.el.hidden = !visible;

      if (visible) {
        placed.push(box);
        label.el.style.transform = `translate3d(${point.x}px, ${point.y}px, 0)`;
      }
    }

    this.cities.forEach((city, index) => {
      const point = cityPoints[index];
      const gap = city.size / 2 + 3;
      const top = point.y - LABEL_HEIGHT / 2;
      const right = { left: point.x + gap, top, right: point.x + gap + city.labelWidth, bottom: top + LABEL_HEIGHT };
      const left = { left: point.x - gap - city.labelWidth, top, right: point.x - gap, bottom: top + LABEL_HEIGHT };
      const box = !this.options.labels ? null : fits(right) ? right : fits(left) ? left : null;

      city.label.hidden = box === null;

      if (box) {
        placed.push(box);
        city.label.style.transform = `translate3d(${box.left}px, ${point.y}px, 0)`;
      }
    });
  }

  private drawMarkers(): void {
    for (const { data, el } of this.markers) {
      const point = this.toViewport(Number(data.lat), Number(data.lng));
      el.style.translate = `${point.x}px ${point.y}px`;
    }
  }

  private markerElement(data: MapMarker): HTMLButtonElement {
    const el = document.createElement('button');
    const color = data.color ?? 'primary';

    el.type = 'button';
    el.className = 'nk-map-marker';
    el.style.setProperty('--nk-map-marker', PALETTE.includes(color) ? `var(--color-${color}-500, var(--color-${color}))` : color);
    el.setAttribute('aria-label', data.label ?? `${data.lat}, ${data.lng}`);
    this.markerPane.appendChild(el);

    return el;
  }

  private openPopup(marker: MapMarker): void {
    const text = marker.popup ?? marker.label;

    if (!text) {
      return;
    }

    this.popupAt = { lat: Number(marker.lat), lng: Number(marker.lng) };
    this.popup.textContent = text;
    this.popup.hidden = false;
    this.placePopup();
  }

  private closePopup(): void {
    this.popupAt = null;
    this.popup.hidden = true;
  }

  private placePopup(): void {
    if (!this.popupAt) {
      return;
    }

    const point = this.toViewport(this.popupAt.lat, this.popupAt.lng);
    this.popup.style.transform = `translate3d(${point.x}px, ${point.y}px, 0)`;
  }

  private hover(shape: Shape | null, at: Point | null): void {
    if (this.hovered !== shape) {
      this.hovered?.el.classList.remove('is-hover');
      shape?.el.classList.add('is-hover');

      // Brought to the front of its layer so its outline is drawn whole.
      if (shape) {
        shape.layer.group.appendChild(shape.el);
      }

      this.hovered = shape;
    }

    if (!shape || !at || !shape.name) {
      this.tooltip.hidden = true;

      return;
    }

    const hasValues = Object.keys(this.values).length > 0;
    const value = this.lookup(this.values, shape);
    const figure = value !== undefined && Number.isFinite(value) ? this.numbers.format(value) : this.options.noData;
    const division = shape.layer.name === 'divisions' || shape.layer.name === 'children';
    const detail = this.lookup(this.details, shape);

    if (division || detail !== undefined) {
      const code = shape.feature.properties.code;
      const title = (detail && typeof detail === 'object' && !Array.isArray(detail) ? detail.title : undefined)
        ?? (code ? `${shape.name} (${code})` : shape.name);
      const lines = detail === undefined ? [] : typeof detail === 'string' ? [detail] : Array.isArray(detail) ? detail : detail.lines ?? [];
      const rows = [...(hasValues ? [figure] : []), ...lines];
      const heading = document.createElement('div');

      heading.className = 'nk-map-tooltip-title';
      heading.textContent = title;

      this.tooltip.replaceChildren(heading, ...rows.map((line) => {
        const row = document.createElement('div');

        row.className = 'nk-map-tooltip-line';
        row.textContent = String(line);

        return row;
      }));
      this.tooltip.classList.toggle('is-rich', rows.length > 0);
    } else {
      this.tooltip.classList.remove('is-rich');
      this.tooltip.textContent = hasValues ? `${shape.name} · ${figure}` : shape.name;
    }

    this.tooltip.hidden = false;
    this.placeTooltip(at);
  }

  /**
   * Beside the pointer, below and to the right, or flipped to the other side where the
   * tooltip would leave the map (which clips it).
   */
  private placeTooltip(at: Point): void {
    const gap = { x: 12, y: 16 };
    const width = this.tooltip.offsetWidth;
    const height = this.tooltip.offsetHeight;
    let x = at.x + gap.x;
    let y = at.y + gap.y;

    if (x + width > this.width - 8) {
      x = Math.max(8, at.x - gap.x - width);
    }

    if (y + height > this.height - 8) {
      y = Math.max(8, at.y - gap.x - height);
    }

    this.tooltip.style.margin = '0';
    this.tooltip.style.transform = `translate3d(${x}px, ${y}px, 0)`;
  }

  /** The first entry of a record the shape answers to, by key, code or name. */
  private lookup<T>(record: Record<string, T>, shape: Shape): T | undefined {
    for (const handle of shape.aliases) {
      if (record[handle] !== undefined) {
        return record[handle];
      }
    }

    return undefined;
  }

  private byHandle<T>(record: Record<string, T>): Record<string, T> {
    return Object.fromEntries(Object.entries(record ?? {}).map(([key, value]) => [String(key).toUpperCase(), value]));
  }

  /** A palette name (`success`, `red`, `red-500`) as its theme colour, anything else as written. */
  private cssColor(color: string): string {
    const match = /^([a-z]+)(?:-(\d{2,3}))?$/.exec(color.trim());

    if (!match || ['transparent', 'currentcolor', 'inherit', 'none'].includes(match[1])) {
      return color;
    }

    return match[2] ? `var(--color-${match[1]}-${match[2]})` : `var(--color-${match[1]}-500, var(--color-${match[1]}))`;
  }

  /** Open a division: its own divisions load, the map frames it. */
  private drill(shape: Shape): void {
    const template = this.options.divisions?.drill;
    const key = String(shape.feature.properties.key ?? shape.key ?? '');

    if (!template || key === '') {
      return;
    }

    this.drilled?.el.classList.remove('is-drilled');
    this.drilled = shape;
    shape.el.classList.add('is-drilled');
    this.viewport.dataset.drilled = '';

    const children = this.layers.children;

    children.group.replaceChildren();
    children.shapes = [];
    children.level = -1;
    children.levels = [{ source: template.replace('__KEY__', encodeURIComponent(key)), from: 0 }];

    this.buildDivisionLabels();

    const bounds = featureBounds([shape.feature]);

    if (bounds) {
      this.fit(bounds, 24, this.options.maxZoom);
    }

    this.emit('drill', { key, name: shape.name });
    this.render();
  }

  private undrill(): void {
    if (!this.drilled) {
      return;
    }

    this.drilled.el.classList.remove('is-drilled');
    this.drilled = null;
    delete this.viewport.dataset.drilled;

    const children = this.layers.children;

    children.group.replaceChildren();
    children.shapes = [];
    children.level = -1;
    children.levels = [];

    this.buildDivisionLabels();
    this.hover(null, null);
    this.fit();
    this.emit('undrill', {});
    this.render();
  }

  private showCityTooltip(city: City, at: Point): void {
    const value = this.cityValues[city.key];
    const hasValues = Object.keys(this.cityValues).length > 0;
    const figure = Number.isFinite(value) ? this.numbers.format(value) : this.options.noData;

    this.tooltip.textContent = hasValues ? `${city.name} · ${figure}` : city.region ? `${city.name} · ${city.region}` : city.name;
    this.tooltip.hidden = false;
    this.placeTooltip(at);
  }

  private featureKey(feature: GeoFeature): string | null {
    for (const key of this.options.valueKeys) {
      const value = feature.properties[key];

      if (value !== undefined && value !== null && value !== '' && String(value) !== '-99') {
        return String(value).toUpperCase();
      }
    }

    return null;
  }

  private featureName(feature: GeoFeature): string | null {
    for (const key of this.options.nameKeys) {
      const value = feature.properties[key];

      if (typeof value === 'string' && value !== '') {
        return value;
      }
    }

    return null;
  }

  private credit(credit: MapOptions['credit']): void {
    if (!credit) {
      return;
    }

    const pane = this.pane('nk-map-credit');

    if (credit.url) {
      const link = document.createElement('a');

      link.href = credit.url;
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      link.textContent = credit.text;
      pane.appendChild(link);
    } else {
      pane.textContent = credit.text;
    }
  }

  private pane(className: string): HTMLDivElement {
    const el = document.createElement('div');

    el.className = className;
    this.viewport.appendChild(el);

    return el;
  }

  /* ------------------------------------------------------------------ */
  /* Motion                                                             */
  /* ------------------------------------------------------------------ */

  private reducedMotion(): boolean {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  private animateZoom(target: number, anchor: Point): void {
    cancelAnimationFrame(this.motion);

    const from = this.zoom;
    const to = this.clampZoom(target);
    const at = this.toLatLng(anchor);
    const start = performance.now();
    const reduced = this.reducedMotion();

    const step = (now: number) => {
      const progress = reduced ? 1 : Math.min(1, (now - start) / ZOOM_DURATION);
      const eased = 1 - Math.pow(1 - progress, 3);

      this.zoomAround(from + (to - from) * eased, anchor, at);
      this.render();

      if (progress < 1) {
        this.motion = requestAnimationFrame(step);
      } else {
        this.moved();
      }
    };

    this.motion = requestAnimationFrame(step);
  }

  /** Keep sliding after a quick drag, slowing down. */
  private glide(velocity: Point): void {
    if (this.reducedMotion() || Math.hypot(velocity.x, velocity.y) < 0.15) {
      this.moved();

      return;
    }

    let last = performance.now();
    let speed = { ...velocity };

    const step = (now: number) => {
      const elapsed = now - last;
      last = now;

      const center = project(this.center.lat, this.center.lng, this.zoom);
      this.center = unproject(center.x - speed.x * elapsed, center.y - speed.y * elapsed, this.zoom);
      this.normalizeCenter();
      this.render();

      const decay = Math.pow(0.94, elapsed / 16);
      speed = { x: speed.x * decay, y: speed.y * decay };

      if (Math.hypot(speed.x, speed.y) > 0.02) {
        this.motion = requestAnimationFrame(step);
      } else {
        this.moved();
      }
    };

    this.motion = requestAnimationFrame(step);
  }

  /** Tell the page where the map rests, once it has stopped moving. */
  private moved(): void {
    window.clearTimeout(this.moveTimer);
    this.moveTimer = window.setTimeout(() => this.emit('move', this.view()), 120);
  }

  /* ------------------------------------------------------------------ */
  /* Input                                                              */
  /* ------------------------------------------------------------------ */

  private point(event: { clientX: number; clientY: number }): Point {
    const rect = this.viewport.getBoundingClientRect();

    return { x: event.clientX - rect.left, y: event.clientY - rect.top };
  }

  private listen(): void {
    const options = { signal: this.listeners.signal };
    const viewport = this.viewport;

    viewport.addEventListener('pointerdown', (event) => this.pointerDown(event), options);
    viewport.addEventListener('pointermove', (event) => this.pointerMove(event), options);
    viewport.addEventListener('pointerup', (event) => this.pointerUp(event), options);
    viewport.addEventListener('pointercancel', (event) => this.pointerUp(event), options);
    viewport.addEventListener('pointerleave', () => this.hover(null, null), options);
    viewport.addEventListener('wheel', (event) => this.wheel(event), { ...options, passive: false });
    viewport.addEventListener('dblclick', (event) => {
      event.preventDefault();
      this.animateZoom(this.zoom + (event.shiftKey ? -1 : 1), this.point(event));
    }, options);
    viewport.addEventListener('keydown', (event) => this.key(event), options);
  }

  private pointerDown(event: PointerEvent): void {
    if ((event.pointerType === 'mouse' && event.button !== 0) || (event.target as Element)?.closest?.('.nk-map-popup, .nk-map-credit')) {
      return;
    }

    cancelAnimationFrame(this.motion);
    this.viewport.setPointerCapture(event.pointerId);
    this.pointers.set(event.pointerId, this.point(event));
    this.startGesture(event.target);
    this.hover(null, null);
  }

  private startGesture(target: EventTarget | null): void {
    const points = [...this.pointers.values()];
    const middle = points.length > 1
      ? { x: (points[0].x + points[1].x) / 2, y: (points[0].y + points[1].y) / 2 }
      : points[0];

    this.gesture = {
      target: this.gesture?.target ?? target,
      start: middle,
      startCenter: project(this.center.lat, this.center.lng, this.zoom),
      startZoom: this.zoom,
      startDistance: points.length > 1 ? Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y) : 0,
      anchor: points.length > 1 ? this.toLatLng(middle) : null,
      moved: this.gesture?.moved ?? false,
      last: middle,
      lastTime: performance.now(),
      velocity: { x: 0, y: 0 },
    };
  }

  private pointerMove(event: PointerEvent): void {
    const at = this.point(event);

    if (!this.gesture) {
      const city = event.pointerType === 'mouse' ? this.cityByElement.get(event.target as Element) ?? null : null;

      if (city) {
        this.hover(null, null);
        this.showCityTooltip(city, at);

        return;
      }

      const shape = event.pointerType === 'mouse' ? this.shapeByElement.get(event.target as Element) ?? null : null;
      this.hover(shape, shape ? at : null);

      return;
    }

    if (!this.pointers.has(event.pointerId)) {
      return;
    }

    this.pointers.set(event.pointerId, at);

    const gesture = this.gesture;
    const points = [...this.pointers.values()];

    if (points.length > 1 && gesture.anchor) {
      const middle = { x: (points[0].x + points[1].x) / 2, y: (points[0].y + points[1].y) / 2 };
      const distance = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);

      gesture.moved = true;
      this.zoomAround(gesture.startZoom + Math.log2(distance / Math.max(gesture.startDistance, 1)), middle, gesture.anchor);
      this.render();

      return;
    }

    const dx = at.x - gesture.start.x;
    const dy = at.y - gesture.start.y;

    if (!gesture.moved && Math.hypot(dx, dy) < 4) {
      return;
    }

    gesture.moved = true;
    this.viewport.classList.add('is-dragging');
    this.center = unproject(gesture.startCenter.x - dx, gesture.startCenter.y - dy, this.zoom);
    this.normalizeCenter();

    const now = performance.now();
    const elapsed = Math.max(now - gesture.lastTime, 1);
    gesture.velocity = {
      x: gesture.velocity.x * 0.6 + ((at.x - gesture.last.x) / elapsed) * 0.4,
      y: gesture.velocity.y * 0.6 + ((at.y - gesture.last.y) / elapsed) * 0.4,
    };
    gesture.last = at;
    gesture.lastTime = now;
    this.render();
  }

  private pointerUp(event: PointerEvent): void {
    if (!this.pointers.delete(event.pointerId) || !this.gesture) {
      return;
    }

    if (this.pointers.size > 0) {
      // One finger left after a pinch: carry on panning from it.
      this.startGesture(null);

      return;
    }

    const gesture = this.gesture;
    this.gesture = null;
    this.viewport.classList.remove('is-dragging');

    if (gesture.moved) {
      const idle = performance.now() - gesture.lastTime > 80;
      this.glide(idle ? { x: 0, y: 0 } : gesture.velocity);

      return;
    }

    this.click(gesture.target, this.point(event));
  }

  private click(target: EventTarget | null, at: Point): void {
    const element = target as Element | null;
    const marker = element?.closest?.('.nk-map-marker') as HTMLElement | null;

    if (marker) {
      const data = this.markers.find(({ el }) => el === marker)?.data;

      if (data) {
        this.openPopup(data);
        this.emit('marker-click', { ...data });
      }

      return;
    }

    const city = element ? this.cityByElement.get(element) : undefined;

    if (city) {
      this.emit('city-click', {
        name: city.name,
        value: this.cityValues[city.key] ?? null,
        population: city.population,
        region: city.region,
        lat: city.at.lat,
        lng: city.at.lng,
        properties: city.properties,
      });

      return;
    }

    this.closePopup();

    const shape = element ? this.shapeByElement.get(element) : undefined;
    const position = this.toLatLng(at);

    if (shape) {
      this.emit('feature-click', {
        layer: shape.layer.name,
        key: shape.key,
        name: shape.name,
        value: this.lookup(this.values, shape) ?? null,
        properties: shape.feature.properties,
      });

      if (shape.layer.name === 'divisions' || shape.layer.name === 'children') {
        const properties = shape.feature.properties;

        this.emit('division-click', {
          key: properties.key ?? shape.key,
          code: properties.code ?? null,
          name: shape.name,
          level: properties.level ?? null,
          parent: properties.parent ?? null,
          value: this.lookup(this.values, shape) ?? null,
          properties,
        });

        if (shape.layer.name === 'divisions') {
          this.drill(shape);
        }
      }
    }

    this.emit('click', { lat: position.lat, lng: wrapLongitude(position.lng) });
  }

  private wheel(event: WheelEvent): void {
    // A pinch on a trackpad arrives as a wheel event with ctrlKey, and always zooms.
    if (!this.options.scrollZoom && !event.ctrlKey) {
      return;
    }

    event.preventDefault();
    cancelAnimationFrame(this.motion);

    const pixels = event.deltaY * (event.deltaMode === 1 ? 32 : event.deltaMode === 2 ? this.height : 1);
    const at = this.point(event);

    this.zoomAround(this.zoom - pixels / (event.ctrlKey ? 100 : 400), at, this.toLatLng(at));
    this.render();
    this.moved();
  }

  private key(event: KeyboardEvent): void {
    const step = 96;
    const pan: Record<string, Point> = {
      ArrowLeft: { x: -step, y: 0 },
      ArrowRight: { x: step, y: 0 },
      ArrowUp: { x: 0, y: -step },
      ArrowDown: { x: 0, y: step },
    };

    if (event.key === '+' || event.key === '=') {
      event.preventDefault();
      this.zoomIn();
    } else if (event.key === '-' || event.key === '_') {
      event.preventDefault();
      this.zoomOut();
    } else if (event.key === 'Escape') {
      this.closePopup();
      this.undrill();
    } else if (pan[event.key]) {
      event.preventDefault();

      const center = project(this.center.lat, this.center.lng, this.zoom);
      this.center = unproject(center.x + pan[event.key].x, center.y + pan[event.key].y, this.zoom);
      this.normalizeCenter();
      this.render();
      this.moved();
    }
  }
}
