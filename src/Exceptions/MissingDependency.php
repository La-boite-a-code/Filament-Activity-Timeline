<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Exceptions;

final class MissingDependency extends ActivityTimelineException
{
    public static function forSpatie(): self
    {
        return new self(
            'The Spatie activity source requires "spatie/laravel-activitylog". '.
            'Install it with: composer require spatie/laravel-activitylog',
        );
    }

    public static function package(string $source, string $package): self
    {
        return new self(
            "The [{$source}] activity source requires \"{$package}\". ".
            "Install it with: composer require {$package}",
        );
    }
}
