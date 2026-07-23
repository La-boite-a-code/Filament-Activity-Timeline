<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Data;

/**
 * One line of a change list, already labeled and formatted, ready to be shown.
 */
final readonly class RenderedChange
{
    public function __construct(
        public string $attribute,
        public string $label,
        public ?string $old,
        public string $new,
        public bool $hasOld,
    ) {}
}
