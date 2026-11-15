<?php

declare(strict_types=1);

namespace Four\LexwareApiClient\Tests\DTO;

use Four\LexwareApiClient\DTO\Invoice;
use Four\LexwareApiClient\DTO\OrderConfirmation;
use Four\LexwareApiClient\DTO\PaymentConditions;
use Four\LexwareApiClient\DTO\Quotation;
use PHPUnit\Framework\TestCase;

class PaymentConditionsTest extends TestCase
{
    public function testToApiArrayMatchesDocumentedShape(): void
    {
        $conditions = new PaymentConditions(
            paymentTermDuration: 30,
            paymentTermLabel: '10 Tage - 3 %, 30 Tage netto',
            discountPercentage: 3.0,
            discountRange: 10,
        );

        self::assertSame([
            'paymentTermLabel'          => '10 Tage - 3 %, 30 Tage netto',
            'paymentTermDuration'       => 30,
            'paymentDiscountConditions' => ['discountPercentage' => 3.0, 'discountRange' => 10],
        ], $conditions->toApiArray());
    }

    public function testToApiArrayOmitsUnsetFields(): void
    {
        self::assertSame(['paymentTermDuration' => 14], (new PaymentConditions(paymentTermDuration: 14))->toApiArray());
        self::assertSame([], (new PaymentConditions())->toApiArray());
    }

    public function testFromArrayAcceptsPaymentConditionTemplate(): void
    {
        // sample from GET /payment-conditions
        $conditions = PaymentConditions::fromArray([
            'id'                        => '3fcc62d1-0925-456d-890b-779b56e7289e',
            'paymentTermLabelTemplate'  => '10 Tage - 3 %, 30 Tage netto',
            'paymentTermDuration'       => 30,
            'paymentDiscountConditions' => ['discountRange' => 10, 'discountPercentage' => 3.00],
            'organizationDefault'       => true,
        ]);

        self::assertSame([
            'paymentTermDuration'       => 30,
            'paymentDiscountConditions' => ['discountPercentage' => 3.0, 'discountRange' => 10],
        ], $conditions->toApiArray());
    }

    public function testVouchersSendPaymentConditionsAndNeverAnId(): void
    {
        $conditions = new PaymentConditions(paymentTermDuration: 14);
        $common = [
            'voucherDate' => '2026-11-15', 'addressName' => 'A', 'addressStreet' => 'S', 'addressCity' => 'C',
            'addressZip' => '1', 'addressCountryCode' => 'DE', 'lineItems' => [],
        ];

        foreach ([
            new Invoice(...$common, paymentConditionId: 'legacy-id', paymentConditions: $conditions),
            new Quotation(...$common, paymentConditions: $conditions),
            new OrderConfirmation(...$common, paymentConditions: $conditions),
        ] as $voucher) {
            self::assertSame(['paymentTermDuration' => 14], $voucher->toApiArray()['paymentConditions']);
        }
    }

    public function testEmptyPaymentConditionsAreNotSent(): void
    {
        $invoice = new Invoice('2026-11-15', 'A', 'S', 'C', '1', 'DE', [], paymentConditions: new PaymentConditions());

        self::assertArrayNotHasKey('paymentConditions', $invoice->toApiArray());
    }
}
