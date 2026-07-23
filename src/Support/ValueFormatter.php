<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Support;

use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use LaBoiteACode\FilamentActivityTimeline\Presentation\AttributeFormat;
use LaBoiteACode\FilamentActivityTimeline\Presentation\AttributePresentation;
use Throwable;
use UnitEnum;

/**
 * Turns a single raw attribute value into a readable, plain string. It never
 * emits HTML; escaping is the responsibility of the Blade layer. Relationship
 * resolution is handled one level up because it needs database access, so this
 * class stays free of queries and is trivial to unit test.
 */
final class ValueFormatter
{
    public const TRANSLATION_NAMESPACE = 'filament-activity-timeline::timeline';

    public function __construct(
        protected Translator $translator,
        protected ?string $dateFormat = null,
        protected ?string $timezone = null,
        protected ?int $truncate = 120,
    ) {}

    /**
     * @param  array{snapshot?: string|null}  $options
     */
    public function format(mixed $value, ?AttributePresentation $attribute = null, array $options = []): string
    {
        if ($attribute?->isRedacted()) {
            return $this->token('redacted');
        }

        $snapshot = $options['snapshot'] ?? null;
        if (is_string($snapshot) && $snapshot !== '') {
            return $this->mask($this->truncate($snapshot), $attribute);
        }

        if ($callback = $attribute?->getFormatCallback()) {
            $result = $callback($value);

            return $this->mask($this->truncate($this->scalarToString($result)), $attribute);
        }

        $display = $this->formatByType($value, $attribute);

        return $this->mask($display, $attribute);
    }

    protected function formatByType(mixed $value, ?AttributePresentation $attribute): string
    {
        $format = $attribute?->getFormat() ?? AttributeFormat::Text;

        // Boolean is handled before the null guard so a real "false" reads as
        // "No" instead of "Not set".
        if ($format === AttributeFormat::Boolean) {
            return $this->formatBoolean($value, $attribute);
        }

        if ($value === null) {
            return $this->token('null');
        }

        return match ($format) {
            AttributeFormat::Date => $this->formatDate($value, $attribute, false),
            AttributeFormat::DateTime => $this->formatDate($value, $attribute, true),
            AttributeFormat::Money => $this->formatMoney($value, $attribute),
            AttributeFormat::Enum => $this->formatEnum($value, $attribute),
            AttributeFormat::Listing => $this->formatList($value, $attribute),
            AttributeFormat::Json => $this->formatJson($value),
            AttributeFormat::Map => $this->formatMap($value, $attribute),
            default => $this->formatText($value),
        };
    }

    protected function formatText(mixed $value): string
    {
        if (is_string($value) && $value === '') {
            return $this->token('empty');
        }

        if (is_bool($value)) {
            return $this->token($value ? 'true' : 'false');
        }

        if (is_array($value)) {
            return $this->formatList($value, null);
        }

        return $this->truncate($this->scalarToString($value));
    }

    protected function formatBoolean(mixed $value, ?AttributePresentation $attribute): string
    {
        if ($value === null) {
            return $this->token('null');
        }

        $bool = $this->toBoolean($value);

        $custom = $bool
            ? $attribute?->getParameter('true')
            : $attribute?->getParameter('false');

        if (is_string($custom) && $custom !== '') {
            return $custom;
        }

        return $this->token($bool ? 'true' : 'false');
    }

    protected function formatDate(mixed $value, ?AttributePresentation $attribute, bool $withTime): string
    {
        $date = $this->toDate($value);

        if ($date === null) {
            return $this->truncate($this->scalarToString($value));
        }

        $format = $attribute?->getParameter('format')
            ?? $this->dateFormat
            ?? ($withTime ? 'd/m/Y H:i' : 'd/m/Y');

        return $date->translatedFormat($format);
    }

    protected function formatMoney(mixed $value, ?AttributePresentation $attribute): string
    {
        if (! is_numeric($value)) {
            return $this->truncate($this->scalarToString($value));
        }

        $currency = (string) ($attribute?->getParameter('currency') ?? 'EUR');
        $locale = $attribute?->getParameter('locale');

        return (string) Number::currency((float) $value, $currency, is_string($locale) ? $locale : null);
    }

    protected function formatEnum(mixed $value, ?AttributePresentation $attribute): string
    {
        $enum = $attribute?->getParameter('enum');

        if (! is_string($enum) || ! enum_exists($enum)) {
            return $this->formatText($value);
        }

        $case = $this->resolveEnumCase($value, $enum);

        if ($case === null) {
            return $this->formatText($value);
        }

        if ($case instanceof HasLabel) {
            $label = $case->getLabel();

            if ($label instanceof Htmlable) {
                return $label->toHtml();
            }

            if ($label !== null) {
                return (string) $label;
            }
        }

        return $case->name;
    }

    protected function resolveEnumCase(mixed $value, string $enum): ?UnitEnum
    {
        if ($value instanceof $enum) {
            return $value;
        }

        try {
            if (is_subclass_of($enum, BackedEnum::class) && (is_int($value) || is_string($value))) {
                return $enum::tryFrom($value);
            }

            foreach ($enum::cases() as $case) {
                if ($case->name === $value) {
                    return $case;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    protected function formatList(mixed $value, ?AttributePresentation $attribute): string
    {
        $items = $this->toArray($value);

        if ($items === []) {
            return $this->token('list_empty');
        }

        $glue = (string) ($attribute?->getParameter('glue') ?? ', ');

        $parts = array_map(
            fn (mixed $item): string => is_scalar($item) || $item === null
                ? $this->scalarToString($item)
                : $this->formatJson($item),
            $items,
        );

        return $this->truncate(implode($glue, $parts));
    }

    protected function formatJson(mixed $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $this->truncate($encoded === false ? '' : $encoded);
    }

    protected function formatMap(mixed $value, ?AttributePresentation $attribute): string
    {
        $map = $attribute?->getParameter('map', []);

        if (is_array($map) && array_key_exists($this->scalarToString($value), $map)) {
            return (string) $map[$this->scalarToString($value)];
        }

        if (is_array($map) && is_scalar($value) && array_key_exists($value, $map)) {
            return (string) $map[$value];
        }

        return $this->formatText($value);
    }

    protected function mask(string $display, ?AttributePresentation $attribute): string
    {
        $callback = $attribute?->getMaskCallback();

        if ($callback === null) {
            return $display;
        }

        return (string) $callback($display);
    }

    protected function truncate(string $value): string
    {
        if ($this->truncate === null || $this->truncate <= 0) {
            return $value;
        }

        return Str::limit($value, $this->truncate);
    }

    protected function scalarToString(mixed $value): string
    {
        if ($value === null) {
            return $this->token('null');
        }

        if (is_bool($value)) {
            return $this->token($value ? 'true' : 'false');
        }

        if (is_array($value)) {
            return $this->formatJson($value);
        }

        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if (is_object($value) && ! method_exists($value, '__toString')) {
            return $this->formatJson($value);
        }

        return (string) $value;
    }

    /**
     * @return list<mixed>
     */
    protected function toArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return array_values($decoded);
            }

            return $value === '' ? [] : [$value];
        }

        return $value === null ? [] : [$value];
    }

    protected function toDate(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::parse($value);

            return $this->timezone !== null ? $date->setTimezone($this->timezone) : $date;
        } catch (Throwable) {
            return null;
        }
    }

    protected function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return ! in_array(strtolower($value), ['', '0', 'false', 'no', 'off', 'null'], true);
        }

        return (bool) $value;
    }

    protected function token(string $key): string
    {
        return (string) $this->translator->get(self::TRANSLATION_NAMESPACE.'.values.'.$key);
    }
}
