<?php

declare(strict_types=1);

namespace Four\LexwareApiClient\DTO;

readonly class EventSubscription
{
    // Available event types
    public const EVENT_INVOICE_CREATED                   = 'invoice.created';
    public const EVENT_INVOICE_CHANGED                   = 'invoice.changed';
    public const EVENT_INVOICE_DELETED                   = 'invoice.deleted';
    public const EVENT_INVOICE_STATUS_CHANGED            = 'invoice.status.changed';
    public const EVENT_CONTACT_CREATED                   = 'contact.created';
    public const EVENT_CONTACT_CHANGED                   = 'contact.changed';
    public const EVENT_CONTACT_DELETED                   = 'contact.deleted';
    public const EVENT_ARTICLE_CREATED                   = 'article.created';
    public const EVENT_ARTICLE_CHANGED                   = 'article.changed';
    public const EVENT_ARTICLE_DELETED                   = 'article.deleted';
    public const EVENT_CREDIT_NOTE_CREATED               = 'credit-note.created';
    public const EVENT_CREDIT_NOTE_STATUS_CHANGED        = 'credit-note.status.changed';
    public const EVENT_PAYMENT_CHANGED                   = 'payment.changed';
    public const EVENT_QUOTATION_CREATED                 = 'quotation.created';
    public const EVENT_QUOTATION_STATUS_CHANGED          = 'quotation.status.changed';
    public const EVENT_ORDER_CONFIRMATION_CREATED        = 'order-confirmation.created';
    public const EVENT_ORDER_CONFIRMATION_STATUS_CHANGED = 'order-confirmation.status.changed';
    public const EVENT_DELIVERY_NOTE_CREATED             = 'delivery-note.created';
    public const EVENT_DELIVERY_NOTE_STATUS_CHANGED      = 'delivery-note.status.changed';
    public const EVENT_DUNNING_CREATED                   = 'dunning.created';

    /**
     * The API takes exactly one event type per subscription; pass a single-element array
     * or use {@see EventSubscription::for()}.
     *
     * @param string[] $eventTypes
     */
    public function __construct(
        public readonly string $callbackUrl,
        public readonly array $eventTypes,
        /** @deprecated Not part of the Lexware API; ignored. */
        public readonly bool $active = true,
        /** Read-only: set by fromArray() from the API response. */
        public readonly ?string $subscriptionId = null,
    ) {
    }

    public static function for(string $eventType, string $callbackUrl): self
    {
        return new self($callbackUrl, [$eventType]);
    }

    /**
     * @return array{eventType: string, callbackUrl: string}
     * @throws \InvalidArgumentException unless exactly one event type is set
     */
    public function toApiArray(): array
    {
        if (count($this->eventTypes) !== 1) {
            throw new \InvalidArgumentException(sprintf(
                'A Lexware event subscription takes exactly one event type, %d given; create one subscription per event type.',
                count($this->eventTypes),
            ));
        }

        return [
            'eventType'   => (string) array_values($this->eventTypes)[0],
            'callbackUrl' => $this->callbackUrl,
        ];
    }

    /**
     * Creates an instance of EventSubscription from an API response.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $eventType = $data['eventType'] ?? null;

        return new self(
            callbackUrl: is_string($data['callbackUrl'] ?? null) ? $data['callbackUrl'] : '',
            eventTypes: is_string($eventType) ? [$eventType] : [],
            subscriptionId: is_string($data['subscriptionId'] ?? null) ? $data['subscriptionId'] : null,
        );
    }
}
