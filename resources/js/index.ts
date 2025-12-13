// Vite plugin
export { neuraKitPlugin as default } from './vite/plugin';

// Preset / utilities
export * from './preset';

// Core (advanced users)
export * from './core/colors';
export * from './core/theme';
export * from './core/defaults';

// Globals (side effects - initializes window objects)
import './app'