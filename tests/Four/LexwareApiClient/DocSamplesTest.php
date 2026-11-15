<?php

declare(strict_types=1);

namespace Four\LexwareApiClient\Tests;

use Four\LexwareApiClient\DTO\Article;
use Four\LexwareApiClient\DTO\Contact;
use Four\LexwareApiClient\DTO\ContactPerson;
use Four\LexwareApiClient\DTO\CreditNote;
use Four\LexwareApiClient\DTO\DeliveryNote;
use Four\LexwareApiClient\DTO\DownloadedFile;
use Four\LexwareApiClient\DTO\Dunning;
use Four\LexwareApiClient\DTO\EventSubscription;
use Four\LexwareApiClient\DTO\Invoice;
use Four\LexwareApiClient\DTO\OrderConfirmation;
use Four\LexwareApiClient\DTO\PagedResponse;
use Four\LexwareApiClient\DTO\Payment;
use Four\LexwareApiClient\DTO\Quotation;
use Four\LexwareApiClient\DTO\Voucher;
use Four\LexwareApiClient\LexwareClient;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * One test per operation documented at https://developers.lexware.io/docs/ (deprecated /document
 * endpoints excluded). Responses are the documentation's sample responses (tests/Fixtures/docs/);
 * operations without a sample use the documented status code or binary body.
 */
class DocSamplesTest extends TestCase
{
    private const ID = 'e9066f04-8cc7-4616-93f8-ac9ecc8479c8';

    private MockClient $http;
    private LexwareClient $client;
    private static string $uploadFile = '';

    public static function setUpBeforeClass(): void
    {
        self::$uploadFile = (string) tempnam(sys_get_temp_dir(), 'lexware-doc-');
        file_put_contents(self::$uploadFile, '%PDF-1.4 sample');
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$uploadFile);
    }

    protected function setUp(): void
    {
        $this->http = new MockClient();
        $psr17 = new Psr17Factory();
        $this->client = new LexwareClient('test-api-key', $this->http, $psr17, $psr17);
    }

    /**
     * @return iterable<string, array{string, string, ?string, \Closure(LexwareClient): mixed, ?\Closure(mixed): void}>
     */
    public static function operations(): iterable
    {
        $id = self::ID;

        // Articles
        yield 'POST /articles' => ['POST', '/v1/articles', 'articles-create', static fn(LexwareClient $c) => $c->articles()->create(self::article()), self::hasId()];
        yield 'GET /articles/{id}' => ['GET', "/v1/articles/$id", 'articles-get', static fn(LexwareClient $c) => $c->articles()->get($id), self::hasKey('title')];
        yield 'PUT /articles/{id}' => ['PUT', "/v1/articles/$id", 'articles-update', static fn(LexwareClient $c) => $c->articles()->update($id, self::article()), self::hasId()];
        yield 'DELETE /articles/{id}' => ['DELETE', "/v1/articles/$id", null, static fn(LexwareClient $c) => $c->articles()->delete($id), null];
        yield 'GET /articles?filter' => ['GET', '/v1/articles', 'articles-list', static fn(LexwareClient $c) => $c->articles()->list(0, ['type' => 'PRODUCT']), self::paged()];

        // Contacts
        yield 'POST /contacts' => ['POST', '/v1/contacts', 'contacts-create', static fn(LexwareClient $c) => $c->contacts()->create(self::contact()), self::isUuid()];
        yield 'GET /contacts/{id}' => ['GET', "/v1/contacts/$id", 'contacts-get', static fn(LexwareClient $c) => $c->contacts()->get($id), self::hasKey('roles')];
        yield 'PUT /contacts/{id}' => ['PUT', "/v1/contacts/$id", 'contacts-create', static fn(LexwareClient $c) => $c->contacts()->update($id, self::contact()), null];
        yield 'GET /contacts?filter' => ['GET', '/v1/contacts', 'contacts-list', static fn(LexwareClient $c) => $c->contacts()->list(0, ['name' => 'Haufe']), self::paged()];

        yield 'GET /countries' => ['GET', '/v1/countries', 'countries-list', static fn(LexwareClient $c) => $c->countries()->list(), self::nonEmptyList()];

        // Credit notes
        yield 'POST /credit-notes' => ['POST', '/v1/credit-notes', 'credit-notes-create', static fn(LexwareClient $c) => $c->creditNotes()->create(self::voucher(CreditNote::class), true), self::hasId()];
        yield 'POST /credit-notes?precedingSalesVoucherId' => ['POST', '/v1/credit-notes', 'credit-notes-create', static fn(LexwareClient $c) => $c->creditNotes()->pursue($id, self::voucher(CreditNote::class)), self::hasId()];
        yield 'GET /credit-notes/{id}' => ['GET', "/v1/credit-notes/$id", 'credit-notes-get', static fn(LexwareClient $c) => $c->creditNotes()->get($id), self::hasKey('voucherNumber')];
        yield 'GET /credit-notes/{id}/file' => ['GET', "/v1/credit-notes/$id/file", null, static fn(LexwareClient $c) => $c->creditNotes()->downloadFile($id), self::pdf()];

        // Delivery notes
        yield 'POST /delivery-notes' => ['POST', '/v1/delivery-notes', 'delivery-notes-create', static fn(LexwareClient $c) => $c->deliveryNotes()->create(self::voucher(DeliveryNote::class)), self::hasId()];
        yield 'POST /delivery-notes?precedingSalesVoucherId' => ['POST', '/v1/delivery-notes', 'delivery-notes-create', static fn(LexwareClient $c) => $c->deliveryNotes()->pursue($id, self::voucher(DeliveryNote::class)), self::hasId()];
        yield 'GET /delivery-notes/{id}' => ['GET', "/v1/delivery-notes/$id", 'delivery-notes-get', static fn(LexwareClient $c) => $c->deliveryNotes()->get($id), self::hasKey('voucherNumber')];
        yield 'GET /delivery-notes/{id}/file' => ['GET', "/v1/delivery-notes/$id/file", null, static fn(LexwareClient $c) => $c->deliveryNotes()->downloadFile($id), self::pdf()];

        // Down payment invoices
        yield 'GET /down-payment-invoices/{id}' => ['GET', "/v1/down-payment-invoices/$id", 'down-payment-invoices-get', static fn(LexwareClient $c) => $c->downPaymentInvoices()->get($id), self::hasKey('voucherNumber')];
        yield 'GET /down-payment-invoices/{id}/file' => ['GET', "/v1/down-payment-invoices/$id/file", null, static fn(LexwareClient $c) => $c->downPaymentInvoices()->downloadFile($id), self::pdf()];

        // Dunnings
        yield 'POST /dunnings?precedingSalesVoucherId (create)' => ['POST', '/v1/dunnings', 'dunnings-create', static fn(LexwareClient $c) => $c->dunnings()->create(self::voucher(Dunning::class), $id), self::hasId()];
        yield 'POST /dunnings?precedingSalesVoucherId (pursue)' => ['POST', '/v1/dunnings', 'dunnings-create', static fn(LexwareClient $c) => $c->dunnings()->pursue($id, self::voucher(Dunning::class)), self::hasId()];
        yield 'GET /dunnings/{id}' => ['GET', "/v1/dunnings/$id", 'dunnings-get', static fn(LexwareClient $c) => $c->dunnings()->get($id), self::hasKey('relatedVouchers')];
        yield 'GET /dunnings/{id}/file' => ['GET', "/v1/dunnings/$id/file", null, static fn(LexwareClient $c) => $c->dunnings()->downloadFile($id), self::pdf()];

        // Event subscriptions
        yield 'POST /event-subscriptions' => ['POST', '/v1/event-subscriptions', 'event-subscriptions-create', static fn(LexwareClient $c) => $c->eventSubscriptions()->create(EventSubscription::for('contact.changed', 'https://example.org/webhook')), self::hasId()];
        yield 'GET /event-subscriptions/{id}' => ['GET', "/v1/event-subscriptions/$id", 'event-subscriptions-get', static fn(LexwareClient $c) => $c->eventSubscriptions()->get($id), self::hasKey('eventType')];
        yield 'GET /event-subscriptions' => ['GET', '/v1/event-subscriptions', 'event-subscriptions-list', static fn(LexwareClient $c) => $c->eventSubscriptions()->list(), self::hasKey('content')];
        yield 'DELETE /event-subscriptions/{id}' => ['DELETE', "/v1/event-subscriptions/$id", null, static fn(LexwareClient $c) => $c->eventSubscriptions()->delete($id), null];

        // Files
        yield 'POST /files' => ['POST', '/v1/files', 'files-upload', static fn(LexwareClient $c) => $c->files()->uploadIncoming(self::$uploadFile), self::isUuid()];
        yield 'GET /files/{id}' => ['GET', "/v1/files/$id", null, static fn(LexwareClient $c) => $c->files()->download($id), self::pdf()];

        // Invoices
        yield 'POST /invoices' => ['POST', '/v1/invoices', 'invoices-create', static fn(LexwareClient $c) => $c->invoices()->create(self::voucher(Invoice::class), true), self::hasId()];
        yield 'POST /invoices?precedingSalesVoucherId' => ['POST', '/v1/invoices', 'invoices-create', static fn(LexwareClient $c) => $c->invoices()->pursue($id, self::voucher(Invoice::class)), self::hasId()];
        yield 'GET /invoices/{id}' => ['GET', "/v1/invoices/$id", 'invoices-get', static fn(LexwareClient $c) => $c->invoices()->get($id), self::hasKey('voucherNumber')];
        yield 'GET /invoices/{id}/file' => ['GET', "/v1/invoices/$id/file", null, static fn(LexwareClient $c) => $c->invoices()->downloadFile($id), self::pdf()];

        // Order confirmations
        yield 'POST /order-confirmations' => ['POST', '/v1/order-confirmations', 'order-confirmations-create', static fn(LexwareClient $c) => $c->orderConfirmations()->create(self::voucher(OrderConfirmation::class)), self::hasId()];
        yield 'POST /order-confirmations?precedingSalesVoucherId' => ['POST', '/v1/order-confirmations', 'order-confirmations-create', static fn(LexwareClient $c) => $c->orderConfirmations()->pursue($id, self::voucher(OrderConfirmation::class)), self::hasId()];
        yield 'GET /order-confirmations/{id}' => ['GET', "/v1/order-confirmations/$id", 'order-confirmations-get', static fn(LexwareClient $c) => $c->orderConfirmations()->get($id), self::hasKey('voucherNumber')];
        yield 'GET /order-confirmations/{id}/file' => ['GET', "/v1/order-confirmations/$id/file", null, static fn(LexwareClient $c) => $c->orderConfirmations()->downloadFile($id), self::pdf()];

        // Lookups
        yield 'GET /payment-conditions' => ['GET', '/v1/payment-conditions', 'payment-conditions-list', static fn(LexwareClient $c) => $c->paymentConditions()->list(), self::nonEmptyList()];
        yield 'GET /payments/{voucherId}' => ['GET', "/v1/payments/$id", 'payments-get', static fn(LexwareClient $c) => $c->payments()->get($id), static function (mixed $r): void {
            self::assertInstanceOf(Payment::class, $r);
            self::assertSame(1337.0, $r->openAmount);
            self::assertSame(Payment::STATUS_OPEN_REVENUE, $r->paymentStatus);
        }];
        yield 'GET /posting-categories' => ['GET', '/v1/posting-categories', 'posting-categories-list', static fn(LexwareClient $c) => $c->postingCategories()->list(), self::nonEmptyList()];
        yield 'GET /print-layouts' => ['GET', '/v1/print-layouts', 'print-layouts-list', static fn(LexwareClient $c) => $c->printLayouts()->list(), self::nonEmptyList()];
        yield 'GET /profile' => ['GET', '/v1/profile', 'profile-get', static fn(LexwareClient $c) => $c->profile()->get(), self::hasKey('companyName')];

        // Quotations
        yield 'POST /quotations' => ['POST', '/v1/quotations', 'quotations-create', static fn(LexwareClient $c) => $c->quotations()->create(self::voucher(Quotation::class)), self::hasId()];
        yield 'GET /quotations/{id}' => ['GET', "/v1/quotations/$id", 'quotations-get', static fn(LexwareClient $c) => $c->quotations()->get($id), self::hasKey('voucherNumber')];
        yield 'GET /quotations/{id}/file' => ['GET', "/v1/quotations/$id/file", null, static fn(LexwareClient $c) => $c->quotations()->downloadFile($id), self::pdf()];

        // Recurring templates
        yield 'GET /recurring-templates/{id}' => ['GET', "/v1/recurring-templates/$id", 'recurring-templates-get', static fn(LexwareClient $c) => $c->recurringTemplates()->get($id), self::hasKey('recurringTemplateSettings')];
        yield 'GET /recurring-templates' => ['GET', '/v1/recurring-templates', 'recurring-templates-list', static fn(LexwareClient $c) => $c->recurringTemplates()->list(0, 1, 'nextExecutionDate,ASC'), self::hasKey('content')];

        yield 'GET /voucherlist' => ['GET', '/v1/voucherlist', 'voucherlist-list', static fn(LexwareClient $c) => $c->voucherlist()->list(['voucherType' => 'invoice', 'voucherStatus' => 'open']), self::paged()];

        // Vouchers
        yield 'POST /vouchers' => ['POST', '/v1/vouchers', 'vouchers-create', static fn(LexwareClient $c) => $c->vouchers()->create(self::bookkeepingVoucher()), self::isUuid()];
        yield 'GET /vouchers/{id}' => ['GET', "/v1/vouchers/$id", 'vouchers-get', static fn(LexwareClient $c) => $c->vouchers()->get($id), self::hasKey('voucherItems')];
        yield 'PUT /vouchers/{id}' => ['PUT', "/v1/vouchers/$id", 'vouchers-create', static fn(LexwareClient $c) => $c->vouchers()->update($id, self::bookkeepingVoucher()), self::hasId()];
        yield 'GET /vouchers?voucherNumber' => ['GET', '/v1/vouchers', 'vouchers-find-by-number', static fn(LexwareClient $c) => $c->vouchers()->findByNumber('123-456'), self::hasKey('content')];
        yield 'POST /vouchers/{id}/files' => ['POST', "/v1/vouchers/$id/files", 'files-upload', static fn(LexwareClient $c) => $c->files()->uploadToVoucher($id, self::$uploadFile), self::isUuid()];
    }

    /**
     * @param \Closure(LexwareClient): mixed $call
     * @param ?\Closure(mixed): void $assert
     */
    #[DataProvider('operations')]
    public function testDocumentedOperation(string $method, string $path, ?string $fixture, \Closure $call, ?\Closure $assert): void
    {
        if ($fixture !== null) {
            $this->http->addResponse(new Response(200, ['Content-Type' => 'application/json'], self::fixture($fixture)));
        } elseif ($method === 'DELETE') {
            $this->http->addResponse(new Response(204));
        } else {
            $this->http->addResponse(new Response(200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="RE0001.pdf"',
            ], '%PDF-1.4 sample'));
        }

        $result = $call($this->client);

        $request = $this->http->getLastRequest();
        self::assertNotFalse($request);
        self::assertSame($method, $request->getMethod());
        self::assertSame($path, $request->getUri()->getPath());
        if ($assert !== null) {
            $assert($result);
        }
    }

    /**
     * The DTOs' fromArray() against the documented response shapes.
     */
    public function testVoucherDtosParseDocumentedResponses(): void
    {
        $invoice = Invoice::fromArray(self::json('invoices-get'));
        self::assertNotSame('', $invoice->voucherDate);
        self::assertNotSame('', $invoice->addressName);
        self::assertNotEmpty($invoice->lineItems);
        self::assertSame(self::json('invoices-get')['taxConditions']['taxType'], $invoice->taxType);
        self::assertSame(self::json('invoices-get')['electronicDocumentProfile'] ?? null, $invoice->electronicDocumentProfile);
        self::assertNotNull($invoice->paymentConditions);

        foreach ([
            'quotations-get'          => Quotation::class,
            'credit-notes-get'        => CreditNote::class,
            'delivery-notes-get'      => DeliveryNote::class,
            'dunnings-get'            => Dunning::class,
        ] as $fixture => $class) {
            $data = self::json($fixture);
            $dto = $class::fromArray($data);
            self::assertSame($data['voucherDate'], $dto->voucherDate, $fixture);
            self::assertSame($data['address']['name'] ?? '', $dto->addressName, $fixture);
            self::assertCount(count($data['lineItems'] ?? []), $dto->lineItems, $fixture);
        }

        $voucher = Voucher::fromArray(self::json('vouchers-get'));
        self::assertNotSame('', $voucher->voucherNumber);
        self::assertNotEmpty($voucher->voucherItems);
    }

    public function testPagedResponseParsesDocumentedPage(): void
    {
        $page = PagedResponse::fromArray(self::json('voucherlist-list'));

        self::assertNotEmpty($page->content);
        self::assertGreaterThanOrEqual(count($page->content), $page->totalElements);
    }

    // --- helpers ---

    private static function fixture(string $name): string
    {
        return (string) file_get_contents(__DIR__ . '/../../Fixtures/docs/' . $name . '.json');
    }

    /** @return array<string, mixed> */
    private static function json(string $name): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode(self::fixture($name), true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    private static function article(): Article
    {
        return new Article('Lexware buchhaltung Premium 2024', 'PRODUCT', 'Download-Code', 61.90);
    }

    private static function contact(): Contact
    {
        return new Contact(person: new ContactPerson(firstName: 'Inge', lastName: 'Musterfrau'));
    }

    private static function bookkeepingVoucher(): Voucher
    {
        return new Voucher('purchaseinvoice', '123-456', '2023-06-28', 242.31, 38.69, self::ID);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private static function voucher(string $class): object
    {
        return new $class('2023-02-22T00:00:00.000+01:00', 'Bike & Ride GmbH & Co. KG', 'Musterstraße 42', 'Freiburg', '79112', 'DE', []);
    }

    /** @return \Closure(mixed): void */
    private static function hasId(): \Closure
    {
        return static function (mixed $r): void {
            self::assertIsArray($r);
            self::assertIsString($r['id'] ?? null);
        };
    }

    /** @return \Closure(mixed): void */
    private static function hasKey(string $key): \Closure
    {
        return static function (mixed $r) use ($key): void {
            self::assertIsArray($r);
            self::assertArrayHasKey($key, $r);
        };
    }

    /** @return \Closure(mixed): void */
    private static function isUuid(): \Closure
    {
        return static fn(mixed $r) => self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', (string) $r);
    }

    /** @return \Closure(mixed): void */
    private static function nonEmptyList(): \Closure
    {
        return static function (mixed $r): void {
            self::assertIsArray($r);
            self::assertNotEmpty($r);
        };
    }

    /** @return \Closure(mixed): void */
    private static function paged(): \Closure
    {
        return static function (mixed $r): void {
            self::assertInstanceOf(PagedResponse::class, $r);
            self::assertNotEmpty($r->content);
        };
    }

    /** @return \Closure(mixed): void */
    private static function pdf(): \Closure
    {
        return static function (mixed $r): void {
            self::assertInstanceOf(DownloadedFile::class, $r);
            self::assertSame('application/pdf', $r->contentType);
            self::assertSame('RE0001.pdf', $r->fileName);
        };
    }
}
