<?php

declare(strict_types=1);

namespace Four\LexwareApiClient\DTO;

readonly class OrderConfirmation
{
    /**
     * @param InvoiceLineItem[] $lineItems
     */
    public function __construct(
        public readonly string $voucherDate,
        public readonly string $addressName,
        public readonly string $addressStreet,
        public readonly string $addressCity,
        public readonly string $addressZip,
        public readonly string $addressCountryCode,
        public readonly array $lineItems,
        public readonly string $taxType = 'gross',
        public readonly ?string $contactId = null,
        public readonly ?string $introduction = null,
        public readonly ?string $remark = null,
        /** @deprecated Not part of the Lexware API (paymentConditions has no id); ignored. Use $paymentConditions. */
        public readonly ?string $paymentConditionId = null,
        public readonly ?string $shippingConditionsDate = null,
        public readonly ?string $deliveryTerms = null,
        public readonly ?PaymentConditions $paymentConditions = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $data = [
            'voucherDate' => $this->voucherDate,
            'address'     => [
                'name'        => $this->addressName,
                'street'      => $this->addressStreet,
                'city'        => $this->addressCity,
                'zip'         => $this->addressZip,
                'countryCode' => $this->addressCountryCode,
            ],
            'lineItems'     => array_map(
                static fn(InvoiceLineItem $item) => $item->toApiArray(),
                $this->lineItems,
            ),
            'taxConditions' => [
                'taxType' => $this->taxType,
            ],
        ];

        if ($this->contactId !== null) {
            $data['address']['contactId'] = $this->contactId;
        }

        if ($this->introduction !== null) {
            $data['introduction'] = $this->introduction;
        }

        if ($this->remark !== null) {
            $data['remark'] = $this->remark;
        }

        if ($this->paymentConditions !== null && $this->paymentConditions->toApiArray() !== []) {
            $data['paymentConditions'] = $this->paymentConditions->toApiArray();
        }

        if ($this->shippingConditionsDate !== null) {
            $data['shippingConditions'] = [
                'shippingDate' => $this->shippingConditionsDate,
                'shippingType' => 'delivery',
            ];
        }

        if ($this->deliveryTerms !== null) {
            $data['deliveryTerms'] = $this->deliveryTerms;
        }

        return $data;
    }
}
