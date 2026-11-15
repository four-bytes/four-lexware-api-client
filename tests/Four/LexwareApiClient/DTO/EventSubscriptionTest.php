<?php

declare(strict_types=1);

namespace Four\LexwareApiClient\Tests\DTO;

use Four\LexwareApiClient\DTO\EventSubscription;
use PHPUnit\Framework\TestCase;

class EventSubscriptionTest extends TestCase
{
    public function testToApiArrayMatchesDocumentedRequest(): void
    {
        $subscription = EventSubscription::for(EventSubscription::EVENT_CONTACT_CHANGED, 'https://example.org/webhook');

        self::assertSame(
            ['eventType' => 'contact.changed', 'callbackUrl' => 'https://example.org/webhook'],
            $subscription->toApiArray(),
        );
    }

    public function testSingleElementArrayKeepsWorking(): void
    {
        $subscription = new EventSubscription(callbackUrl: 'https://example.org/webhook', eventTypes: ['invoice.created'], active: false);

        self::assertSame(['eventType' => 'invoice.created', 'callbackUrl' => 'https://example.org/webhook'], $subscription->toApiArray());
    }

    public function testMultipleEventTypesAreRejected(): void
    {
        $subscription = new EventSubscription('https://example.org/webhook', ['invoice.created', 'payment.changed']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly one event type, 2 given');

        $subscription->toApiArray();
    }

    public function testNoEventTypeIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new EventSubscription('https://example.org/webhook', []))->toApiArray();
    }

    public function testFromArrayParsesDocumentedResponse(): void
    {
        // element of GET /event-subscriptions (docs sample)
        $subscription = EventSubscription::fromArray([
            'subscriptionId' => '49aa2f76-c51a-4df3-ae83-3a103d781494',
            'organizationId' => 'aa93e8a8-2aa3-470b-b914-caad8a255dd8',
            'createdDate'    => '2023-04-11T12:20:00.000+02:00',
            'eventType'      => 'contact.changed',
            'callbackUrl'    => 'https://example.org/webhook',
        ]);

        self::assertSame('49aa2f76-c51a-4df3-ae83-3a103d781494', $subscription->subscriptionId);
        self::assertSame(['contact.changed'], $subscription->eventTypes);
        self::assertSame('https://example.org/webhook', $subscription->callbackUrl);
    }

    public function testFromArrayWithMissingFields(): void
    {
        $subscription = EventSubscription::fromArray([]);

        self::assertSame('', $subscription->callbackUrl);
        self::assertSame([], $subscription->eventTypes);
        self::assertNull($subscription->subscriptionId);
    }
}
