<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Support;

/**
 * Fills a sentence template with the values carried by a PresentationContext.
 *
 * Values are inserted as plain text; the Blade layer escapes the final string
 * on output, so a hostile causer name or property value can never inject HTML.
 */
final class EventSentenceRenderer
{
    /**
     * @param  array<string, string|int>  $extra  Extra named variables, for example custom event properties.
     */
    public function render(string $template, PresentationContext $context, array $extra = []): string
    {
        // Resolve ":property.path" tokens first so their values are treated as
        // data and never as further templates.
        $rendered = preg_replace_callback(
            '/:property\.([A-Za-z0-9_\.]+)/',
            fn (array $matches): string => $this->stringify($context->property($matches[1])),
            $template,
        );

        $replacements = [
            ':changes_count' => (string) $context->changesCount(),
            ':subject_title' => $context->subjectTitle(),
            ':subject_label' => $context->subjectLabel(),
            ':subject' => $context->subject(),
            ':causer' => $context->causerName(),
            ':event' => $context->entry()->event,
            ':date' => $context->date(),
        ];

        foreach ($extra as $key => $value) {
            $replacements[':'.$key] = (string) $value;
        }

        return strtr($rendered ?? $template, $replacements);
    }

    protected function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn ($item): string => $this->stringify($item), $value));
        }

        return is_scalar($value) ? (string) $value : '';
    }
}
