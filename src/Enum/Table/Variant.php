<?php

namespace Neura\Kit\Enum\Table;

enum Variant: string
{
    case DEFAULT = 'default';
    case STRIPED = 'striped';
    case MINIMAL = 'minimal';
    case FLAT = 'flat';
    case BORDERED = 'bordered';
    case ELEVATED = 'elevated';
}
