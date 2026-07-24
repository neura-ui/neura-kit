<?php

namespace Neura\Kit\Packs\EventCalendar;

use Neura\Kit\Packs\BasePack;

class Size extends BasePack
{
    /**
     * Density tokens for the event calendar.
     *
     * Every branch lists complete class literals so Tailwind can scan them.
     * `hourHeight` is a plain pixel value consumed by the Alpine time grid.
     */
    public static function default(): array
    {
        return [
            'sm' => [
                'hourHeight' => 44,
                'toolbar' => 'px-2.5 py-2 sm:px-3',
                'title' => 'text-sm sm:text-base',
                'weekday' => 'px-1.5 py-1.5 text-[11px]',
                'monthCell' => 'p-1 gap-0.5',
                'dayNumber' => 'size-5 text-[11px]',
                'event' => 'px-1 py-0.5 text-[11px]',
                'eventDot' => 'size-1.5',
                'eventTime' => 'text-[10px]',
                'colHeader' => 'py-1.5',
                'colHeaderDay' => 'size-7 text-xs',
                'gutter' => 'w-12 sm:w-14',
                'hourLabel' => 'text-[10px]',
                'timedEvent' => 'px-1 py-0.5 text-[11px]',
                'agendaRow' => 'gap-2.5 px-1.5 py-2.5 sm:gap-4',
                'agendaDate' => 'w-14 sm:w-16',
                'agendaDayNum' => 'size-8 text-base',
            ],
            'md' => [
                'hourHeight' => 56,
                'toolbar' => 'px-3 py-2.5 sm:px-4',
                'title' => 'text-base sm:text-lg',
                'weekday' => 'px-2 py-2 text-xs',
                'monthCell' => 'p-1.5 gap-1',
                'dayNumber' => 'size-6 text-xs',
                'event' => 'px-1 py-0.5 text-xs',
                'eventDot' => 'size-1.5',
                'eventTime' => 'text-[11px]',
                'colHeader' => 'py-2',
                'colHeaderDay' => 'size-8 text-sm',
                'gutter' => 'w-14 sm:w-16',
                'hourLabel' => 'text-[11px]',
                'timedEvent' => 'px-1.5 py-1 text-xs',
                'agendaRow' => 'gap-3 px-2 py-3 sm:gap-5',
                'agendaDate' => 'w-16 sm:w-20',
                'agendaDayNum' => 'size-9 text-lg',
            ],
            'lg' => [
                'hourHeight' => 72,
                'toolbar' => 'px-4 py-3 sm:px-5',
                'title' => 'text-lg sm:text-xl',
                'weekday' => 'px-2.5 py-2.5 text-sm',
                'monthCell' => 'p-2 gap-1.5',
                'dayNumber' => 'size-7 text-sm',
                'event' => 'px-1.5 py-1 text-sm',
                'eventDot' => 'size-2',
                'eventTime' => 'text-xs',
                'colHeader' => 'py-2.5',
                'colHeaderDay' => 'size-9 text-base',
                'gutter' => 'w-16 sm:w-20',
                'hourLabel' => 'text-xs',
                'timedEvent' => 'px-2 py-1.5 text-sm',
                'agendaRow' => 'gap-4 px-2.5 py-3.5 sm:gap-6',
                'agendaDate' => 'w-20 sm:w-24',
                'agendaDayNum' => 'size-10 text-xl',
            ],
        ];
    }
}
