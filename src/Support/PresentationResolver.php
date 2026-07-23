<?php

declare(strict_types=1);

namespace LaBoiteACode\FilamentActivityTimeline\Support;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use LaBoiteACode\FilamentActivityTimeline\Contracts\HasActivityTimelinePresentation;
use LaBoiteACode\FilamentActivityTimeline\Contracts\ProvidesActivityTitle;
use LaBoiteACode\FilamentActivityTimeline\Data\RenderedChange;
use LaBoiteACode\FilamentActivityTimeline\Data\RenderedEntry;
use LaBoiteACode\FilamentActivityTimeline\Data\TimelineEntry;
use LaBoiteACode\FilamentActivityTimeline\Presentation\AttributePresentation;
use LaBoiteACode\FilamentActivityTimeline\Presentation\ModelPresentation;
use LaBoiteACode\FilamentActivityTimeline\Registries\PresentationRegistry;
use LaBoiteACode\FilamentActivityTimeline\Timeline;
use Throwable;

/**
 * Turns a normalized TimelineEntry into a fully resolved RenderedEntry, applying
 * the documented resolution priority (local override, then global registry,
 * then the model contract, then the Filament resource, then conventions).
 *
 * This is where the "semantic" promise of the package is delivered.
 */
final class PresentationResolver
{
    protected const NS = ValueFormatter::TRANSLATION_NAMESPACE;

    /**
     * @var array<string, ModelPresentation>
     */
    protected array $presentationCache = [];

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected Timeline $timeline,
        protected PresentationRegistry $registry,
        protected ValueFormatter $formatter,
        protected RelationResolver $relations,
        protected EventSentenceRenderer $sentences,
        protected Translator $translator,
        protected array $config = [],
    ) {}

    /**
     * @param  list<TimelineEntry>  $entries
     * @return list<RenderedEntry>
     */
    public function render(array $entries): array
    {
        $this->preloadRelations($entries);

        return array_map(fn (TimelineEntry $entry): RenderedEntry => $this->renderEntry($entry), $entries);
    }

    public function renderEntry(TimelineEntry $entry): RenderedEntry
    {
        $changes = $this->changes($entry);
        $context = $this->context($entry, $changes);

        return new RenderedEntry(
            entry: $entry,
            key: $entry->key(),
            event: $entry->event,
            sentence: $this->sentence($entry, $context),
            eventLabel: $this->eventLabel($entry->event),
            icon: $this->eventIcon($entry->event),
            color: $this->eventColor($entry->event),
            causerName: $context->causerName(),
            causerAvatar: $this->causerAvatar($entry->causer),
            relativeDate: $entry->occurredAt->diffForHumans(),
            absoluteDate: $this->absoluteDate($entry),
            description: $this->description($entry),
            changes: $changes,
            debug: $this->timeline->hasDebug() ? $this->debug($entry) : null,
        );
    }

    // -----------------------------------------------------------------
    // Effective presentation (priority merge)
    // -----------------------------------------------------------------

    public function effectivePresentation(?string $modelClass): ModelPresentation
    {
        $cacheKey = $modelClass ?? '';

        if (isset($this->presentationCache[$cacheKey])) {
            return $this->presentationCache[$cacheKey];
        }

        $presentation = ModelPresentation::make();

        if ($modelClass !== null) {
            $presentation = $this->resourcePresentation($modelClass)->mergeOnTopOf($presentation);

            if (is_a($modelClass, HasActivityTimelinePresentation::class, true)) {
                $presentation = $modelClass::activityTimelinePresentation()->mergeOnTopOf($presentation);
            }

            if ($registered = $this->registry->getModel($modelClass)) {
                $presentation = $registered->mergeOnTopOf($presentation);
            }
        }

        $presentation = $this->timeline->presentation()->mergeOnTopOf($presentation);

        return $this->presentationCache[$cacheKey] = $presentation;
    }

    protected function resourcePresentation(string $modelClass): ModelPresentation
    {
        $presentation = ModelPresentation::make();

        $resource = $this->timeline->presentation()->getResource()
            ?? $this->registry->getModel($modelClass)?->getResource();

        if ($resource === null || ! class_exists($resource)) {
            return $presentation;
        }

        try {
            if (method_exists($resource, 'getModelLabel')) {
                $presentation->label((string) $resource::getModelLabel());
            }

            if (method_exists($resource, 'getPluralModelLabel')) {
                $presentation->pluralLabel((string) $resource::getPluralModelLabel());
            }

            if (method_exists($resource, 'getRecordTitleAttribute')) {
                $attribute = $resource::getRecordTitleAttribute();

                if (is_string($attribute) && $attribute !== '') {
                    $presentation->recordTitleAttributes([$attribute]);
                }
            }
        } catch (Throwable) {
            // Resource discovery is opportunistic and must never break rendering.
        }

        return $presentation;
    }

    // -----------------------------------------------------------------
    // Labels
    // -----------------------------------------------------------------

    public function modelLabel(?string $modelClass, bool $plural = false, ?TimelineEntry $entry = null): string
    {
        $presentation = $this->effectivePresentation($modelClass);

        $label = $plural ? $presentation->getPluralLabel() : $presentation->getLabel();

        if ($label === null && $entry !== null) {
            $snapshot = Arr::get($entry->properties, 'presentation.subject_label');

            if (is_string($snapshot) && $snapshot !== '') {
                $label = $plural ? Str::plural($snapshot) : $snapshot;
            }
        }

        if ($label !== null) {
            return $label;
        }

        if ($modelClass === null) {
            return (string) $this->translator->get(self::NS.'.subject.unknown');
        }

        $human = Str::headline(class_basename($modelClass));

        return $plural ? Str::plural($human) : $human;
    }

    // -----------------------------------------------------------------
    // Record title (the business name of the subject)
    // -----------------------------------------------------------------

    public function recordTitle(TimelineEntry $entry): ?string
    {
        $presentation = $this->effectivePresentation($entry->subjectType);
        $subject = $entry->subject;

        if ($subject !== null) {
            if ($resolver = $presentation->getRecordTitleResolver()) {
                $title = $resolver($subject);

                if (filled($title)) {
                    return (string) $title;
                }
            }

            if ($subject instanceof ProvidesActivityTitle) {
                $title = $subject->getActivityTimelineTitle();

                if (filled($title)) {
                    return (string) $title;
                }
            }

            foreach ($presentation->getRecordTitleAttributes() as $attribute) {
                $value = $subject->getAttribute($attribute);

                if (filled($value)) {
                    return (string) $value;
                }
            }
        }

        $snapshot = Arr::get($entry->properties, 'presentation.subject_title');

        return is_string($snapshot) && $snapshot !== '' ? $snapshot : null;
    }

    public function subjectName(TimelineEntry $entry): string
    {
        $title = $this->recordTitle($entry);

        if ($title !== null) {
            return $title;
        }

        return (string) $this->translator->get(self::NS.'.subject.fallback', [
            'label' => $this->modelLabel($entry->subjectType, false, $entry),
            'id' => $entry->subjectId ?? '?',
        ]);
    }

    // -----------------------------------------------------------------
    // Causer
    // -----------------------------------------------------------------

    public function causerName(?Model $causer): string
    {
        if ($resolver = $this->timeline->getCauserNameResolver()) {
            $name = $resolver($causer);

            if (filled($name)) {
                return (string) $name;
            }
        }

        if ($causer === null) {
            $configured = Arr::get($this->config, 'system_causer.label');

            return filled($configured)
                ? (string) $configured
                : (string) $this->translator->get(self::NS.'.causer.system');
        }

        foreach (['name', 'full_name', 'title', 'label', 'email'] as $attribute) {
            $value = $causer->getAttribute($attribute);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return (string) $this->translator->get(self::NS.'.causer.unknown');
    }

    public function causerAvatar(?Model $causer): ?string
    {
        if ($resolver = $this->timeline->getCauserAvatarResolver()) {
            $avatar = $resolver($causer);

            return filled($avatar) ? (string) $avatar : null;
        }

        if ($causer === null) {
            return null;
        }

        foreach (['avatar_url', 'avatar', 'profile_photo_url'] as $attribute) {
            $value = $causer->getAttribute($attribute);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    // -----------------------------------------------------------------
    // Event presentation
    // -----------------------------------------------------------------

    public function eventLabel(string $event): string
    {
        if ($label = $this->timeline->getEventLabel($event)) {
            return $label;
        }

        if ($registered = $this->registry->getEvent($event)?->getLabel()) {
            return $registered;
        }

        $key = self::NS.'.events.'.$event;

        if ($this->translator->has($key)) {
            return (string) $this->translator->get($key);
        }

        return Str::headline(str_replace(['.', '_', '-'], ' ', $event));
    }

    public function eventIcon(string $event): ?string
    {
        return $this->timeline->getEventIcon($event)
            ?? $this->registry->getEvent($event)?->getIcon()
            ?? $this->stringOrNull(Arr::get($this->config, "events.{$event}.icon"));
    }

    public function eventColor(string $event): ?string
    {
        return $this->timeline->getEventColor($event)
            ?? $this->registry->getEvent($event)?->getColor()
            ?? $this->stringOrNull(Arr::get($this->config, "events.{$event}.color"));
    }

    // -----------------------------------------------------------------
    // Sentence
    // -----------------------------------------------------------------

    public function sentence(TimelineEntry $entry, PresentationContext $context): string
    {
        if ($formatter = $this->timeline->getTitleFormatter()) {
            return (string) $formatter($entry, $context);
        }

        $presentation = $this->effectivePresentation($entry->subjectType);

        if ($callback = $presentation->getEventSentenceCallback($entry->event)) {
            return (string) $callback($entry, $context);
        }

        $template = $presentation->getEventSentence($entry->event)
            ?? $this->registry->getEvent($entry->event)?->getSentence()
            ?? $this->translationSentence($entry->event);

        return $this->sentences->render($template, $context, $this->eventExtraProperties($entry));
    }

    protected function translationSentence(string $event): string
    {
        $key = self::NS.'.sentences.'.$event;

        if ($this->translator->has($key)) {
            return (string) $this->translator->get($key);
        }

        return (string) $this->translator->get(self::NS.'.sentences.default');
    }

    /**
     * @return array<string, string|int>
     */
    protected function eventExtraProperties(TimelineEntry $entry): array
    {
        $event = $this->registry->getEvent($entry->event);

        if ($event === null) {
            return [];
        }

        $extra = [];

        foreach ($event->getPropertyBindings() as $name => $path) {
            $value = Arr::get($entry->properties, $path);

            if (is_scalar($value)) {
                $extra[$name] = $value;
            }
        }

        return $extra;
    }

    // -----------------------------------------------------------------
    // Changes
    // -----------------------------------------------------------------

    /**
     * @return list<RenderedChange>
     */
    public function changes(TimelineEntry $entry): array
    {
        $changeSet = $entry->changes();
        $presentation = $this->effectivePresentation($entry->subjectType);
        $globallyHidden = $this->globallyHiddenAttributes();

        $rendered = [];

        foreach ($changeSet->keys() as $attribute) {
            if (in_array($attribute, $globallyHidden, true)) {
                continue;
            }

            $attributePresentation = $presentation->getAttribute($attribute);

            if ($attributePresentation?->isHidden()) {
                continue;
            }

            $hasOld = array_key_exists($attribute, $changeSet->old);

            $rendered[] = new RenderedChange(
                attribute: $attribute,
                label: $attributePresentation?->getLabel() ?? Str::headline($attribute),
                old: $hasOld
                    ? $this->formatAttribute($entry, $attribute, $changeSet->oldValue($attribute), $attributePresentation, 'old')
                    : null,
                new: $this->formatAttribute($entry, $attribute, $changeSet->newValue($attribute), $attributePresentation, 'new'),
                hasOld: $hasOld,
            );
        }

        return $rendered;
    }

    protected function formatAttribute(
        TimelineEntry $entry,
        string $attribute,
        mixed $value,
        ?AttributePresentation $presentation,
        string $side,
    ): string {
        $snapshot = Arr::get(
            $entry->properties,
            "presentation.attributes.{$attribute}.".($side === 'old' ? 'old_label' : 'new_label'),
        );

        if ($presentation?->isRelationship()) {
            if (is_string($snapshot) && $snapshot !== '') {
                return $snapshot;
            }

            $title = $this->resolveRelation($entry, $attribute, $value, $presentation);

            if ($title !== null) {
                return $title;
            }

            if ($value === null) {
                return $this->formatter->format(null, null);
            }

            return '#'.$this->scalar($value);
        }

        return $this->formatter->format($value, $presentation, [
            'snapshot' => is_string($snapshot) ? $snapshot : null,
        ]);
    }

    protected function resolveRelation(
        TimelineEntry $entry,
        string $attribute,
        mixed $value,
        AttributePresentation $presentation,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if ($resolver = $presentation->getRelationResolver()) {
            $resolved = $resolver($value);

            return filled($resolved) ? (string) $resolved : null;
        }

        if (! $this->relations->isEnabled()) {
            return null;
        }

        $relation = $presentation->getParameter('relation');
        $titleAttribute = $presentation->getParameter('titleAttribute', 'name');

        if (! is_string($relation)) {
            return null;
        }

        $relatedClass = $this->relatedClassFor($entry->subjectType, $relation);

        if ($relatedClass === null || ! (is_int($value) || is_string($value))) {
            return null;
        }

        return $this->relations->resolve($relatedClass, (string) $titleAttribute, $value);
    }

    /**
     * Group every relation identifier of the current page and load the titles
     * with one query per relation, keeping the render free of N+1 queries.
     *
     * @param  list<TimelineEntry>  $entries
     */
    public function preloadRelations(array $entries): void
    {
        if (! $this->relations->isEnabled()) {
            return;
        }

        $buckets = [];

        foreach ($entries as $entry) {
            $presentation = $this->effectivePresentation($entry->subjectType);
            $changeSet = $entry->changes();

            foreach ($changeSet->keys() as $attribute) {
                $attributePresentation = $presentation->getAttribute($attribute);

                if (! $attributePresentation?->isRelationship() || $attributePresentation->getRelationResolver()) {
                    continue;
                }

                $relation = $attributePresentation->getParameter('relation');

                if (! is_string($relation)) {
                    continue;
                }

                $relatedClass = $this->relatedClassFor($entry->subjectType, $relation);

                if ($relatedClass === null) {
                    continue;
                }

                $titleAttribute = (string) $attributePresentation->getParameter('titleAttribute', 'name');
                $bucketKey = $relatedClass.'|'.$titleAttribute;

                foreach ([$changeSet->oldValue($attribute), $changeSet->newValue($attribute)] as $id) {
                    if (is_int($id) || is_string($id)) {
                        $buckets[$bucketKey]['class'] = $relatedClass;
                        $buckets[$bucketKey]['attribute'] = $titleAttribute;
                        $buckets[$bucketKey]['ids'][] = $id;
                    }
                }
            }
        }

        foreach ($buckets as $bucket) {
            $this->relations->preload($bucket['class'], $bucket['attribute'], array_values(array_unique($bucket['ids'])));
        }
    }

    protected function relatedClassFor(?string $subjectType, string $relation): ?string
    {
        if ($subjectType === null || ! class_exists($subjectType)) {
            return null;
        }

        try {
            $instance = new $subjectType;

            if (! $instance instanceof Model || ! method_exists($instance, $relation)) {
                return null;
            }

            $relationInstance = $instance->{$relation}();

            if ($relationInstance instanceof Relation) {
                return $relationInstance->getRelated()::class;
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    // -----------------------------------------------------------------
    // Context, description, dates and debug
    // -----------------------------------------------------------------

    /**
     * @param  list<RenderedChange>  $changes
     */
    public function context(TimelineEntry $entry, array $changes): PresentationContext
    {
        $newValues = [];
        $oldValues = [];

        foreach ($changes as $change) {
            $newValues[$change->attribute] = $change->new;

            if ($change->old !== null) {
                $oldValues[$change->attribute] = $change->old;
            }
        }

        return new PresentationContext(
            entry: $entry,
            causerName: $this->causerName($entry->causer),
            subjectTitle: $this->subjectName($entry),
            subjectLabel: $this->modelLabel($entry->subjectType, false, $entry),
            date: $this->absoluteDate($entry),
            newValues: $newValues,
            oldValues: $oldValues,
        );
    }

    protected function description(TimelineEntry $entry): ?string
    {
        $description = $entry->description;

        if ($description === null || $description === '') {
            return null;
        }

        // Spatie stores the event name as the description by default; that is
        // already conveyed by the sentence, so it is not shown twice.
        if ($description === $entry->event) {
            return null;
        }

        return $description;
    }

    protected function absoluteDate(TimelineEntry $entry): string
    {
        $format = $this->stringOrNull(Arr::get($this->config, 'date_format')) ?? 'd/m/Y H:i';

        return $entry->occurredAt->translatedFormat($format);
    }

    /**
     * @return array<string, mixed>
     */
    public function debug(TimelineEntry $entry): array
    {
        return [
            'model_label' => $this->modelLabel($entry->subjectType, false, $entry),
            'record_title' => $this->recordTitle($entry) ?? '(fallback)',
            'source' => $this->timeline->getSource(),
            'renderer' => $this->timeline->getTitleFormatter() !== null ? 'callback' : 'template',
            'properties' => array_keys($entry->properties),
        ];
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * @return list<string>
     */
    protected function globallyHiddenAttributes(): array
    {
        $hidden = Arr::get($this->config, 'hidden_attributes', []);

        return is_array($hidden) ? array_values(array_filter($hidden, 'is_string')) : [];
    }

    protected function scalar(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    protected function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
