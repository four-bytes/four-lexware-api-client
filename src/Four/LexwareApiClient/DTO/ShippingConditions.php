<?php

declare(strict_types=1);

namespace Four\LexwareApiClient\DTO;

/**
 * Shipping conditions of an invoice, order confirmation or dunning.
 */
readonly class ShippingConditions
{
    /** A service is supplied on shippingDate. */
    public const TYPE_SERVICE = 'service';
    /** A service is supplied between shippingDate and shippingEndDate. */
    public const TYPE_SERVICE_PERIOD = 'serviceperiod';
    /** A product is delivered on shippingDate. */
    public const TYPE_DELIVERY = 'delivery';
    /** A product is delivered between shippingDate and shippingEndDate. */
    public const TYPE_DELIVERY_PERIOD = 'deliveryperiod';
    /** No shipping date. */
    public const TYPE_NONE = 'none';

    public function __construct(
        public string $shippingType = self::TYPE_DELIVERY,
        public ?string $shippingDate = null,
        public ?string $shippingEndDate = null,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function toApiArray(): array
    {
        $data = ['shippingType' => $this->shippingType];

        if ($this->shippingDate !== null) {
            $data['shippingDate'] = $this->shippingDate;
        }

        if ($this->shippingEndDate !== null) {
            $data['shippingEndDate'] = $this->shippingEndDate;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            shippingType: isset($data['shippingType']) ? (string) $data['shippingType'] : self::TYPE_DELIVERY,
            shippingDate: isset($data['shippingDate']) ? (string) $data['shippingDate'] : null,
            shippingEndDate: isset($data['shippingEndDate']) ? (string) $data['shippingEndDate'] : null,
        );
    }
}
