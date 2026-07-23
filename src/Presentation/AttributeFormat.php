<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Presentation;

/**
 * The known ways a single attribute value can be turned into a readable string.
 * The behavior for each case lives in the ValueFormatter, not here, so this
 * enum stays a pure description of intent.
 */
enum AttributeFormat: string
{
    case Text = 'text';
    case Boolean = 'boolean';
    case Date = 'date';
    case DateTime = 'datetime';
    case Money = 'money';
    case Enum = 'enum';
    case Listing = 'listing';
    case Json = 'json';
    case Map = 'map';
    case Relationship = 'relationship';
    case Custom = 'custom';
}
