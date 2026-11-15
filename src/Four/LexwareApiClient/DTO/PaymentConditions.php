<?php

declare(strict_types=1);

namespace Four\LexwareApiClient\DTO;

/**
 * Payment conditions of an invoice, quotation or order confirmation.
 *
 * Omitted on a voucher, Lexware applies the organization's or contact's default conditions.
 */
readonly class PaymentConditions
{
    public function __construct(
        public ?int $paymentTermDuration = null,
        public ?string $paymentTermLabel = null,
        public ?float $discountPercentage = null,
        public ?int $discountRange = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $data = [];

        if ($this->paymentTermLabel !== null) {
            $data['paymentTermLabel'] = $this->paymentTermLabel;
        }

        if ($this->paymentTermDuration !== null) {
            $data['paymentTermDuration'] = $this->paymentTermDuration;
        }

        if ($this->discountPercentage !== null || $this->discountRange !== null) {
            $data['paymentDiscountConditions'] = array_filter([
                'discountPercentage' => $this->discountPercentage,
                'discountRange'      => $this->discountRange,
            ], static fn(mixed $value): bool => $value !== null);
        }

        return $data;
    }

    /**
     * Creates payment conditions from a voucher's `paymentConditions` object or from a template
     * returned by `paymentConditions()->list()` (its `paymentTermLabelTemplate` is not copied).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $discount = is_array($data['paymentDiscountConditions'] ?? null) ? $data['paymentDiscountConditions'] : [];

        return new self(
            paymentTermDuration: isset($data['paymentTermDuration']) ? (int) $data['paymentTermDuration'] : null,
            paymentTermLabel: isset($data['paymentTermLabel']) ? (string) $data['paymentTermLabel'] : null,
            discountPercentage: isset($discount['discountPercentage']) ? (float) $discount['discountPercentage'] : null,
            discountRange: isset($discount['discountRange']) ? (int) $discount['discountRange'] : null,
        );
    }
}
