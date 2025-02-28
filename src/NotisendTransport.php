<?php

namespace Alezhu\LaravelNotisend;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;

class NotisendTransport extends AbstractTransport
{

    public function __construct(
        protected Factory         $httpClient,
        protected array           $config,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface          $logger = null,
    )
    {
        parent::__construct($dispatcher, $logger);
    }

    public function __toString()
    {
        return 'notisend';
    }

    protected function doSend(SentMessage $message): void
    {
        $base_url = $this->config[NotisendConsts::host->name];
        $token = $this->config[NotisendConsts::api_token->name];
        $payment = $this->config[NotisendConsts::payment->name];

        $originalMessage = $message->getOriginalMessage();
        if (!($originalMessage instanceof Message)) {
            throw new TransportException(sprintf('Unable to send message of type %s', get_class($message)));
        }
        $email = MessageConverter::toEmail($originalMessage);
        $from_email = $email->getFrom()[0]->getAddress();
        $to = $email->getTo()[0]->getAddress();
        $subject = $email->getSubject();
        $text = $email->getTextBody();
        $html = $email->getHtmlBody();
        $payload = array_filter([
            'from_email' => $from_email,
            'to' => $to,
            'subject' => $subject,
            'text' => $text,
            'html' => $html,
            'payment' => $payment,
        ]);
        $request = $this->httpClient->baseUrl($base_url)
            ->withToken($token)
            ->asJson();
        $attachments = $email->getAttachments();
        if ($attachments && count($attachments) > 0) {
            foreach ($attachments as $attachment) {
                $request->attach(
                    name: $attachment->getName(),
                    contents: $attachment->getBody(),
                    filename: $attachment->getFilename(),
                    headers: $attachment->getHeaders()->toArray(),
                );
            }
        }

        try {
            $response = $request->post('/email/messages', $payload);
            if (!( 200 <= $response->status() && $response->status() < 300)) {
                $responseMsg = $response->getBody()->getContents();
                throw  new TransportException(sprintf('Error returns from Notisend:%s', $responseMsg), $response->status());
            }
            
            $result = $response->json();
            if($id = $result['id']) {
                $originalMessage->getHeaders()->addTextHeader('X-Notisend-MessageId', $id);
            }
            if($status = $result['status']) {
                $originalMessage->getHeaders()->addTextHeader('X-Notisend-Status', $status);
            }
        } catch (ConnectionException $e) {
            throw  new TransportException(sprintf('Error during send message via Notisend: %s', $e->getMessage()), $e->getCode(), $e);
        }

    }
}