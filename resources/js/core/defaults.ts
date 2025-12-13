import type { NeuraKitTheme } from './types';

export const DEFAULT_THEME = {
  colors: {
    primary: 'neutral',
    secondary: 'slate',
    success: 'green',
    warning: 'amber',
    danger: 'red',
    info: 'blue',
  },
  radius: {
    field: 'lg',
    box: 'lg',
    button: 'lg',
    badge: 'md',
    input: 'lg',
    card: 'lg',
    modal: 'xl',
    dropdown: 'lg',
  },
  font: {
    sans: "'Instrument Sans', ui-sans-serif, system-ui, sans-serif",
  },
} as const;
  