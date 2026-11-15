<?php

declare(strict_types=1);

namespace Four\LexwareApiClient\DTO;

readonly class Quotation
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
        /** @deprecated shippingConditions are not part of the Lexware API for this voucher type; ignored. */
        public readonly ?string $shippingConditionsDate = null,
        public readonly ?PaymentConditions $paymentConditions = null,
        public readonly ?string $language = null,
        public readonly ?string $taxSubType = null,
        public readonly ?string $taxTypeNote = null,
        public readonly ?string $title = null,
        public readonly ?string $printLayoutId = null,
        public readonly ?string $addressSupplement = null,
        /** @var array<string, mixed>|null */
        public readonly ?array $totalPrice = null,
        public readonly ?string $expirationDate = null,
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

        if ($this->language !== null) {
            $data['language'] = $this->language;
        }

        if ($this->taxSubType !== null) {
            $data['taxConditions']['taxSubType'] = $this->taxSubType;
        }

        if ($this->taxTypeNote !== null) {
            $data['taxConditions']['taxTypeNote'] = $this->taxTypeNote;
        }

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        if ($this->printLayoutId !== null) {
            $data['printLayoutId'] = $this->printLayoutId;
        }

        if ($this->addressSupplement !== null) {
            $data['address']['supplement'] = $this->addressSupplement;
        }

        if ($this->totalPrice !== null) {
            $data['totalPrice'] = $this->totalPrice;
        }

        if ($this->expirationDate !== null) {
            $data['expirationDate'] = $this->expirationDate;
        }

        return $data;
    }

    /**
     * Creates an instance of Quotation from an API response.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $address = $data['address'] ?? [];
        $lineItems = array_map(
            static fn(array $item) => InvoiceLineItem::fromArray($item),
            $data['lineItems'] ?? [],
        );

        return new self(
            voucherDate:        $data['voucherDate'] ?? '',
            addressName:        $address['name'] ?? '',
            addressStreet:      $address['street'] ?? '',
            addressCity:        $address['city'] ?? '',
            addressZip:         $address['zip'] ?? '',
            addressCountryCode: $address['countryCode'] ?? '',
            lineItems:          $lineItems,
            taxType:            $data['taxConditions']['taxType'] ?? 'gross',
            contactId:          $address['contactId'] ?? null,
            introduction:       $data['introduction'] ?? null,
            remark:             $data['remark'] ?? null,
            paymentConditionId: null,
            shippingConditionsDate: $data['shippingConditions']['shippingDate'] ?? null,
            paymentConditions: is_array($data['paymentConditions'] ?? null) ? PaymentConditions::fromArray($data['paymentConditions']) : null,
            language: $data['language'] ?? null,
            taxSubType: $data['taxConditions']['taxSubType'] ?? null,
            taxTypeNote: $data['taxConditions']['taxTypeNote'] ?? null,
            title: $data['title'] ?? null,
            printLayoutId: $data['printLayoutId'] ?? null,
            addressSupplement: $address['supplement'] ?? null,
            totalPrice: $data['totalPrice'] ?? null,
            expirationDate: $data['expirationDate'] ?? null,
        );
    }
}
