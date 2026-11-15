<?php

declare(strict_types=1);

namespace Four\LexwareApiClient\DTO;

readonly class Dunning
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
        public readonly ?string $language = null,
        public readonly ?string $taxSubType = null,
        public readonly ?string $taxTypeNote = null,
        public readonly ?string $title = null,
        public readonly ?string $printLayoutId = null,
        public readonly ?string $addressSupplement = null,
        public readonly ?ShippingConditions $shippingConditions = null,
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
            'lineItems'      => array_map(
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

        if ($this->shippingConditions !== null) {
            $data['shippingConditions'] = $this->shippingConditions->toApiArray();
        }

        return $data;
    }

    /**
     * Creates an instance of Dunning from an API response.
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
            language: $data['language'] ?? null,
            taxSubType: $data['taxConditions']['taxSubType'] ?? null,
            taxTypeNote: $data['taxConditions']['taxTypeNote'] ?? null,
            title: $data['title'] ?? null,
            printLayoutId: $data['printLayoutId'] ?? null,
            addressSupplement: $address['supplement'] ?? null,
            shippingConditions: is_array($data['shippingConditions'] ?? null) ? ShippingConditions::fromArray($data['shippingConditions']) : null,
        );
    }
}
