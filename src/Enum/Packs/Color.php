<?php

namespace Neura\Kit\Enum\Packs;

enum Color: string
{
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case DANGER = 'danger';
    case INFO = 'info';

    case RED = 'red';
    case ORANGE = 'orange';
    case AMBER = 'amber';
    case YELLOW = 'yellow';
    case LIME = 'lime';
    case GREEN = 'green';
    case EMERALD = 'emerald';
    case TEAL = 'teal';
    case CYAN = 'cyan';
    case SKY = 'sky';
    case BLUE = 'blue';
    case INDIGO = 'indigo';
    case VIOLET = 'violet';
    case PURPLE = 'purple';
    case FUCHSIA = 'fuchsia';
    case PINK = 'pink';
    case ROSE = 'rose';

    public static function semantic(): array
    {
        return [
            self::PRIMARY,
            self::SECONDARY,
            self::SUCCESS,
            self::WARNING,
            self::DANGER,
            self::INFO,
        ];
    }

    public static function tailwind(): array
    {
        return [
            self::RED,
            self::ORANGE,
            self::AMBER,
            self::YELLOW,
            self::LIME,
            self::GREEN,
            self::EMERALD,
            self::TEAL,
            self::CYAN,
            self::SKY,
            self::BLUE,
            self::INDIGO,
            self::VIOLET,
            self::PURPLE,
            self::FUCHSIA,
            self::PINK,
            self::ROSE,
        ];
    }
}
