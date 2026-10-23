import { onAlpineInit } from '../../../runtime/alpine';
import { NativeMap, type Levels, type MapMarker, type MapStyle } from './NativeMap';

/**
 * `neura::map`: the Alpine side of the native map. The viewport is left alone by Livewire
 * (`wire:ignore`); the markers and values travel in the root's `data-map` attribute, which a
 * Livewire update rewrites and the map reads again.
 */

interface MapConfig {
  center: [number, number];
  zoom: number;
  minZoom: number;
  maxZoom: number;
  style: string;
  styles: Record<string, MapStyle>;
  land: Levels | null;
  lakes: Levels | null;
  overlay: Levels | null;
  country: string | null;
  cities: Levels | null;
  zones: boolean;
  graticule: boolean;
  labels: boolean;
  fit: boolean;
  scrollZoom: boolean;
  valueKeys: string[];
  nameKeys: string[];
  locale: string;
  noData: string;
  credit: { text: string; url: string | null } | null;
}

interface MapData {
  markers: MapMarker[];
  values: Record<string, number>;
  cityValues: Record<string, number>;
}

function readData(el: HTMLElement): MapData {
  try {
    const data = JSON.parse(el.dataset.map ?? '{}');

    const record = (value: unknown): Record<string, number> =>
      value && typeof value === 'object' && !Array.isArray(value) ? (value as Record<string, number>) : {};

    return {
      markers: Array.isArray(data.markers) ? data.markers : [],
      values: record(data.values),
      cityValues: record(data.cityValues),
    };
  } catch {
    return { markers: [], values: {}, cityValues: {} };
  }
}

if (typeof document !== 'undefined') {
  onAlpineInit(() => {
    window.Alpine.data('neuraMap', (config: MapConfig) => {
      let map: NativeMap | null = null;
      let observer: MutationObserver | null = null;

      return {
        style: config.style,
        loading: false,
        failed: false,

        init(): void {
          const root = (this as any).$el as HTMLElement;
          const viewport = (this as any).$refs.viewport as HTMLElement;
          const data = readData(root);

          map = new NativeMap(
            viewport,
            {
              center: { lat: Number(config.center[0]), lng: Number(config.center[1]) },
              zoom: Number(config.zoom),
              minZoom: Number(config.minZoom),
              maxZoom: Number(config.maxZoom),
              style: config.styles[this.style],
              land: config.land,
              lakes: config.lakes,
              overlay: config.overlay,
              country: config.country,
              cities: config.cities,
              zones: config.zones,
              graticule: config.graticule,
              labels: config.labels,
              scrollZoom: config.scrollZoom,
              valueKeys: config.valueKeys,
              nameKeys: config.nameKeys,
              locale: config.locale,
              noData: config.noData,
              credit: config.credit,
            },
            (event, detail) => {
              if (event === 'busy') {
                this.loading = Boolean(detail.busy);
              } else if (event === 'error') {
                this.failed = true;
              }

              (this as any).$dispatch(`map-${event}`, detail);
            },
          );

          map.setValues(data.values);
          map.setCityValues(data.cityValues);
          map.setMarkers(data.markers);

          // A focused country is framed on its mainland once its shape has loaded.
          if (config.fit || config.country) {
            map.fit();
          }

          observer = new MutationObserver(() => {
            const next = readData(root);

            map?.setValues(next.values);
            map?.setCityValues(next.cityValues);
            map?.setMarkers(next.markers);
          });
          observer.observe(root, { attributes: true, attributeFilter: ['data-map'] });
        },

        destroy(): void {
          observer?.disconnect();
          map?.destroy();
          map = null;
        },

        zoomIn(): void {
          map?.zoomIn();
        },

        zoomOut(): void {
          map?.zoomOut();
        },

        fit(): void {
          map?.fit();
        },

        useStyle(name: string): void {
          if (!config.styles[name] || name === this.style) {
            return;
          }

          this.style = name;
          map?.setStyle(config.styles[name]);
          (this as any).$dispatch('map-style', { style: name });
        },
      };
    });
  });
}
