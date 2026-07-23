<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Presentation;

use Closure;

/**
 * Declarative description of how a single attribute should be labeled and how
 * its old and new values should be rendered. This object only holds intent;
 * the ValueFormatter turns that intent into a safe, readable string.
 *
 * The attribute name is not stored here on purpose: it is the array key inside
 * ModelPresentation::attributes(), which keeps declarations terse.
 */
final class AttributePresentation
{
    protected ?string $label = null;

    protected AttributeFormat $format = AttributeFormat::Text;

    /**
     * @var array<string, mixed>
     */
    protected array $parameters = [];

    protected ?Closure $formatUsing = null;

    protected ?Closure $maskUsing = null;

    protected ?Closure $relationResolver = null;

    protected bool $hidden = false;

    protected bool $redacted = false;

    public function __construct(?string $label = null)
    {
        $this->label = $label;
    }

    public static function make(?string $label = null): static
    {
        return new self($label);
    }

    // -----------------------------------------------------------------
    // Labeling
    // -----------------------------------------------------------------

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    // -----------------------------------------------------------------
    // Formats
    // -----------------------------------------------------------------

    public function formatUsing(Closure $callback): static
    {
        $this->format = AttributeFormat::Custom;
        $this->formatUsing = $callback;

        return $this;
    }

    public function boolean(?string $trueLabel = null, ?string $falseLabel = null): static
    {
        $this->format = AttributeFormat::Boolean;
        $this->parameters = ['true' => $trueLabel, 'false' => $falseLabel];

        return $this;
    }

    public function date(?string $format = null): static
    {
        $this->format = AttributeFormat::Date;
        $this->parameters = ['format' => $format];

        return $this;
    }

    public function dateTime(?string $format = null): static
    {
        $this->format = AttributeFormat::DateTime;
        $this->parameters = ['format' => $format];

        return $this;
    }

    public function money(string $currency = 'EUR', ?string $locale = null): static
    {
        $this->format = AttributeFormat::Money;
        $this->parameters = ['currency' => $currency, 'locale' => $locale];

        return $this;
    }

    public function enum(string $enum): static
    {
        $this->format = AttributeFormat::Enum;
        $this->parameters = ['enum' => $enum];

        return $this;
    }

    public function list(string $glue = ', '): static
    {
        $this->format = AttributeFormat::Listing;
        $this->parameters = ['glue' => $glue];

        return $this;
    }

    public function json(): static
    {
        $this->format = AttributeFormat::Json;

        return $this;
    }

    /**
     * @param  array<array-key, string>  $map
     */
    public function map(array $map): static
    {
        $this->format = AttributeFormat::Map;
        $this->parameters = ['map' => $map];

        return $this;
    }

    // -----------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------

    public function relationship(string $relation, string $titleAttribute = 'name'): static
    {
        $this->format = AttributeFormat::Relationship;
        $this->parameters = ['relation' => $relation, 'titleAttribute' => $titleAttribute];

        return $this;
    }

    public function relationshipUsing(Closure $callback): static
    {
        $this->format = AttributeFormat::Relationship;
        $this->relationResolver = $callback;

        return $this;
    }

    // -----------------------------------------------------------------
    // Sensitivity
    // -----------------------------------------------------------------

    public function hidden(bool $condition = true): static
    {
        $this->hidden = $condition;

        return $this;
    }

    public function redacted(bool $condition = true): static
    {
        $this->redacted = $condition;

        return $this;
    }

    public function maskUsing(Closure $callback): static
    {
        $this->maskUsing = $callback;

        return $this;
    }

    // -----------------------------------------------------------------
    // Accessors used by the ValueFormatter
    // -----------------------------------------------------------------

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getFormat(): AttributeFormat
    {
        return $this->format;
    }

    public function getParameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    public function getFormatCallback(): ?Closure
    {
        return $this->formatUsing;
    }

    public function getMaskCallback(): ?Closure
    {
        return $this->maskUsing;
    }

    public function getRelationResolver(): ?Closure
    {
        return $this->relationResolver;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function isRedacted(): bool
    {
        return $this->redacted;
    }

    public function isRelationship(): bool
    {
        return $this->format === AttributeFormat::Relationship;
    }
}
