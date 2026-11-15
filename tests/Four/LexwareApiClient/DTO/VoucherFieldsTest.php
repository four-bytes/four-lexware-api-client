<?php

declare(strict_types=1);

namespace Four\LexwareApiClient\Tests\DTO;

use Four\LexwareApiClient\DTO\CreditNote;
use Four\LexwareApiClient\DTO\DeliveryNote;
use Four\LexwareApiClient\DTO\Dunning;
use Four\LexwareApiClient\DTO\Invoice;
use Four\LexwareApiClient\DTO\InvoiceLineItem;
use Four\LexwareApiClient\DTO\OrderConfirmation;
use Four\LexwareApiClient\DTO\Quotation;
use Four\LexwareApiClient\DTO\ShippingConditions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Writable fields per sales voucher type, as documented at developers.lexware.io.
 */
class VoucherFieldsTest extends TestCase
{
    private const BASE = [
        'voucherDate' => '2026-11-15T00:00:00.000+01:00', 'addressName' => 'Muster GmbH', 'addressStreet' => 'Hauptstraße 5',
        'addressCity' => 'Berlin', 'addressZip' => '10115', 'addressCountryCode' => 'DE', 'lineItems' => [],
    ];

    private const COMMON = [
        'language' => 'en', 'taxSubType' => 'distanceSales', 'taxTypeNote' => 'Steuerschuldnerschaft des Leistungsempfängers',
        'title' => 'Rechnung', 'printLayoutId' => 'layout-1', 'addressSupplement' => 'Hinterhaus',
    ];

    /**
     * @return array<string, array{class-string, array<string, mixed>, list<string>, list<string>}>
     */
    public static function voucherProvider(): array
    {
        $shipping = new ShippingConditions(ShippingConditions::TYPE_SERVICE_PERIOD, '2026-11-01', '2026-11-30');
        $total = ['currency' => 'EUR', 'totalDiscountPercentage' => 5];

        return [
            'invoice'            => [Invoice::class, ['shippingConditions' => $shipping], ['shippingConditions'], []],
            'quotation'          => [Quotation::class, ['totalPrice' => $total, 'expirationDate' => '2026-12-15'], ['totalPrice', 'expirationDate'], ['shippingConditions']],
            'credit note'        => [CreditNote::class, ['totalPrice' => $total], ['totalPrice'], ['shippingConditions', 'paymentConditions']],
            'order confirmation' => [OrderConfirmation::class, ['totalPrice' => $total, 'shippingConditions' => $shipping], ['totalPrice', 'shippingConditions'], []],
            'delivery note'      => [DeliveryNote::class, ['taxType' => 'net'], [], ['shippingConditions', 'totalPrice', 'paymentConditions']],
            'dunning'            => [Dunning::class, ['shippingConditions' => $shipping], ['shippingConditions'], ['totalPrice', 'paymentConditions']],
        ];
    }

    /**
     * @param class-string $class
     * @param array<string, mixed> $extra
     * @param list<string> $expectedKeys
     * @param list<string> $absentKeys
     */
    #[DataProvider('voucherProvider')]
    public function testDocumentedFieldsAreSent(string $class, array $extra, array $expectedKeys, array $absentKeys): void
    {
        $voucher = new $class(...self::BASE, ...self::COMMON, ...$extra);
        /** @var array<string, mixed> $data */
        $data = $voucher->toApiArray();

        self::assertSame('en', $data['language']);
        self::assertSame('Rechnung', $data['title']);
        self::assertSame('layout-1', $data['printLayoutId']);
        self::assertSame('Hinterhaus', $data['address']['supplement']);
        self::assertSame('distanceSales', $data['taxConditions']['taxSubType']);
        self::assertSame('Steuerschuldnerschaft des Leistungsempfängers', $data['taxConditions']['taxTypeNote']);
        foreach ($expectedKeys as $key) {
            self::assertArrayHasKey($key, $data);
        }
        foreach ($absentKeys as $key) {
            self::assertArrayNotHasKey($key, $data);
        }
    }

    public function testLegacyShippingDateIsIgnoredWhereUndocumented(): void
    {
        foreach ([Quotation::class, CreditNote::class, DeliveryNote::class] as $class) {
            $voucher = new $class(...self::BASE, shippingConditionsDate: '2026-11-20');

            self::assertArrayNotHasKey('shippingConditions', $voucher->toApiArray(), $class);
        }
    }

    public function testShippingConditionsTakePrecedenceOverLegacyDate(): void
    {
        $invoice = new Invoice(...self::BASE, shippingConditionsDate: '2026-11-20', shippingConditions: new ShippingConditions(ShippingConditions::TYPE_NONE));

        self::assertSame(['shippingType' => 'none'], $invoice->toApiArray()['shippingConditions']);
    }

    public function testLegacyShippingDateStillWorksWhereDocumented(): void
    {
        $invoice = new Invoice(...self::BASE, shippingConditionsDate: '2026-11-20');

        self::assertSame(['shippingDate' => '2026-11-20', 'shippingType' => 'delivery'], $invoice->toApiArray()['shippingConditions']);
    }

    public function testDeliveryNoteSendsTaxConditionsOnlyWhenSet(): void
    {
        self::assertArrayNotHasKey('taxConditions', (new DeliveryNote(...self::BASE))->toApiArray());
        self::assertSame(['taxType' => 'net'], (new DeliveryNote(...self::BASE, taxType: 'net'))->toApiArray()['taxConditions']);
        self::assertArrayNotHasKey('taxConditions', (new DeliveryNote(...self::BASE, taxSubType: 'distanceSales'))->toApiArray());
    }

    public function testFromArrayParsesNewFields(): void
    {
        $response = [
            'language' => 'en', 'title' => 'Angebot', 'printLayoutId' => 'layout-1', 'expirationDate' => '2026-12-15T00:00:00.000+01:00',
            'address' => ['name' => 'Muster GmbH', 'supplement' => 'Hinterhaus'],
            'taxConditions' => ['taxType' => 'net', 'taxSubType' => 'electronicServices', 'taxTypeNote' => 'Hinweis'],
            'totalPrice' => ['currency' => 'EUR', 'totalNetAmount' => 100.0],
            'shippingConditions' => ['shippingType' => 'serviceperiod', 'shippingDate' => '2026-11-01', 'shippingEndDate' => '2026-11-30'],
        ];

        $quotation = Quotation::fromArray($response);
        self::assertSame('en', $quotation->language);
        self::assertSame('Angebot', $quotation->title);
        self::assertSame('Hinterhaus', $quotation->addressSupplement);
        self::assertSame('electronicServices', $quotation->taxSubType);
        self::assertSame('2026-12-15T00:00:00.000+01:00', $quotation->expirationDate);
        self::assertSame(['currency' => 'EUR', 'totalNetAmount' => 100.0], $quotation->totalPrice);

        $invoice = Invoice::fromArray($response);
        self::assertSame('serviceperiod', $invoice->shippingConditions->shippingType);
        self::assertSame('2026-11-30', $invoice->shippingConditions->shippingEndDate);

        self::assertSame('net', DeliveryNote::fromArray($response)->taxType);
        self::assertSame('layout-1', Dunning::fromArray($response)->printLayoutId);
        self::assertSame('Hinweis', CreditNote::fromArray($response)->taxTypeNote);
    }

    public function testLineItemArticleIdAndExplicitGrossPrice(): void
    {
        $item = new InvoiceLineItem('Vinyl LP', 2, 'Stück', 16.81, 19.0, InvoiceLineItem::TYPE_MATERIAL, articleId: 'article-1', unitGrossPrice: 20.0);
        $data = $item->toApiArray();

        self::assertSame('article-1', $data['id']);
        self::assertSame(20.0, $data['unitPrice']['grossAmount']);

        $parsed = InvoiceLineItem::fromArray($data);
        self::assertSame('article-1', $parsed->articleId);
        self::assertSame(20.0, $parsed->unitGrossPrice);

        $derived = (new InvoiceLineItem('Versand', 1, 'Stück', 10.0, 19.0))->toApiArray();
        self::assertArrayNotHasKey('id', $derived);
        self::assertSame(11.9, $derived['unitPrice']['grossAmount']);
    }

    public function testShippingConditionsRoundTrip(): void
    {
        $conditions = ShippingConditions::fromArray(['shippingType' => 'deliveryperiod', 'shippingDate' => '2026-11-01', 'shippingEndDate' => '2026-11-07']);

        self::assertSame(
            ['shippingType' => 'deliveryperiod', 'shippingDate' => '2026-11-01', 'shippingEndDate' => '2026-11-07'],
            $conditions->toApiArray(),
        );
        self::assertSame(['shippingType' => 'delivery'], ShippingConditions::fromArray([])->toApiArray());
    }
}
