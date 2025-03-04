<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Alezhu\LaravelNotisend\Tests;

use Alezhu\LaravelNotisend\NotisendConsts;
use Alezhu\LaravelNotisend\NotisendTransport;
use Faker\Generator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Mail\Message;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\UnstructuredHeader;
use Symfony\Component\Mime\RawMessage;

class NotisendTransportTest extends TestCase
{
    private array $config;
    private Generator $faker;
    private Factory|MockInterface $httpClient;
    private NotisendTransport $instance;
    private string $token;

    public function test_send_should_set_notisend_headers(): void
    {

        $email = $this->_createEmail();

        $request = Mockery::mock(PendingRequest::class);
        $response = Mockery::mock(Response::class);

        $this->_prepareHttpClient($request);

        $request->expects('post')
            ->with('/email/messages',[
                'from_email' => $email->getFrom()[0]->getAddress(),
                'to' => $email->getTo()[0]->getAddress(),
                'subject' => $email->getSubject(),
                'text' => $email->getTextBody(),
                'payment' => $this->config['payment'],
            ])
            ->andReturn($response);

        $response->allows('status')
            ->andReturn(201);

        $mockHeaders = [
            'id' =>  $this->faker->randomNumber(9),
            'status' =>  $this->faker->words(5, true),
        ];
        $response->expects('json')
            ->andReturn($mockHeaders);

        $result = $this->instance->send($email);

        /** @var Message $origMessage */
        $origMessage = $result->getOriginalMessage();
        $headers = $origMessage->getHeaders();
        /** @var UnstructuredHeader $xMessageId */
        $xMessageId = $headers->get('X-Notisend-MessageId');
        self::assertEquals($mockHeaders['id'], $xMessageId->getValue());
        /** @var UnstructuredHeader $xStatus */
        $xStatus = $headers->get('X-Notisend-Status');
        self::assertEquals($mockHeaders['status'], $xStatus->getValue());

    }

    public function test_send_should_process_attachments(): void
    {
        $email = $this->_createEmail();
        $content = $this->faker->text();
        $name = $this->faker->word() .  '.' . $this->faker->fileExtension();
        $mime = $this->faker->mimeType();
        $email->attach($content,$name,$mime);

        $request = Mockery::mock(PendingRequest::class);
        $response = Mockery::mock(Response::class);

        $this->_prepareHttpClient($request);

        $request->expects('attach')
            ->with($name,$content,$name,[])
            ->andReturns();

        $request->expects('post')
            ->with('/email/messages',[
                'from_email' => $email->getFrom()[0]->getAddress(),
                'to' => $email->getTo()[0]->getAddress(),
                'subject' => $email->getSubject(),
                'text' => $email->getTextBody(),
                'payment' => $this->config['payment'],
            ])
            ->andReturn($response);

        $response->allows('status')
            ->andReturn(201);

        $mockHeaders = [
            'id' =>  $this->faker->randomNumber(9),
            'status' =>  $this->faker->words(5, true),
        ];
        $response->expects('json')
            ->andReturn($mockHeaders);


        $result = $this->instance->send($email);

        /** @var Message $origMessage */
        $origMessage = $result->getOriginalMessage();
        $headers = $origMessage->getHeaders();
        /** @var UnstructuredHeader $xMessageId */
        $xMessageId = $headers->get('X-Notisend-MessageId');
        self::assertEquals($mockHeaders['id'], $xMessageId->getValue());
        /** @var UnstructuredHeader $xStatus */
        $xStatus = $headers->get('X-Notisend-Status');
        self::assertEquals($mockHeaders['status'], $xStatus->getValue());

    }

    public function test_send_should_throw_exception_on_ConnectionException()
    {
        $prev = new ConnectionException($this->faker->text(), $this->faker->numberBetween(1, 100));
        $ex = new TransportException('Error during send message via Notisend: ' . $prev->getMessage(), $prev->getCode(), $prev);
        $this->expectExceptionObject($ex);

        $request = Mockery::mock(PendingRequest::class);

        $this->_prepareHttpClient($request);

        $request->expects('post')
            ->withAnyArgs()
            ->andThrow($prev);

        $email = $this->_createEmail();
        $this->instance->send($email);
    }

    public function test_send_should_throw_exception_on_bad_response()
    {
        $responseMsg = $this->faker->text();
        $statusCode = $this->faker->numberBetween(400, 499);
        $ex = new TransportException(sprintf('Error returns from Notisend:%s', $responseMsg), $statusCode);
        $this->expectExceptionObject($ex);

        $request = Mockery::mock(PendingRequest::class);
        $response = Mockery::mock(Response::class);

        $this->_prepareHttpClient($request);

        $request->expects('post')
            ->withAnyArgs()
            ->andReturn($response);

        $response->allows('status')
            ->andReturn($statusCode);
        $responseBody = Mockery::mock(StreamInterface::class);
        $response->expects('getBody')
            ->andReturn($responseBody);
        $responseBody->expects('getContents')->andReturn($responseMsg);

        $email = $this->_createEmail();
        $this->instance->send($email);
    }

    public function test_send_should_throw_exception_when_pass_not_valid_message()
    {
        $ex = new TransportException('Unable to send message of type Symfony\Component\Mailer\SentMessage');
        $this->expectExceptionObject($ex);
        $this->instance->send(new RawMessage($this->faker->text()), new Envelope(new Address($this->faker->email()), [new Address($this->faker->email())]));
    }

    public function test_stringify_should_return_notisend()
    {
        $result = '' . $this->instance;
        self::assertSame($result, 'notisend');
    }

    protected function _createEmail(): Email
    {
        $result = new Email();
        $result->text($this->faker->text());
        $result->subject($this->faker->words(3, true));
        $result->to($this->faker->email());
        $result->from($this->faker->email());
        return $result;
    }

    protected function _prepareHttpClient(PendingRequest | MockInterface $request): void
    {
        $this->httpClient->expects('baseUrl')
            ->with('https://api.notisend.ru/v1')
            ->andReturn($request);
        $request->expects('withToken')
            ->with($this->token)
            ->andReturnSelf();

        $request->expects('asJson')
            ->withNoArgs()
            ->andReturnSelf();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = \Faker\Factory::create();
        $this->token = $this->faker->uuid();
        $this->httpClient = Mockery::mock(Factory::class);
        $this->config = array_merge(require __DIR__ . '/../config/notisend.php', [NotisendConsts::API_TOKEN => $this->token]);
        $this->instance = new NotisendTransport($this->httpClient, $this->config);
    }

}