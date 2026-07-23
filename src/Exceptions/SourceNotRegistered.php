<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Exceptions;

final class SourceNotRegistered extends ActivityTimelineException
{
    /**
     * @param  list<string>  $available
     */
    public static function make(string $name, array $available = []): self
    {
        $known = $available === []
            ? 'no sources are registered'
            : 'registered sources are: '.implode(', ', $available);

        return new self("The activity source [{$name}] is not registered ({$known}).");
    }
}
