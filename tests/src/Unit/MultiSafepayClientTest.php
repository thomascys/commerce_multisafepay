<?php

namespace Drupal\Tests\commerce_multisafepay\Unit;

use Drupal\commerce_multisafepay\MultiSafepayClient;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * @group commerce_multisafepay
 */
class MultiSafepayClientTest extends UnitTestCase {

  /**
   * Test createOrder method.
   */
  public function testCreateOrder() {
    $body = json_encode([
      'data' => [
        'payment_url' => 'http://www.google.be',
      ],
    ]);

    $mock = new MockHandler([
      new Response(200, [], $body),
      new RequestException("Error Communicating with MultiSafepay", new Request('GET', 'orders')),
    ]);

    $handler = HandlerStack::create($mock);

    $client = new Client(['handler' => $handler]);
    $logger = $this->getMockBuilder('Drupal\Core\Logger\LoggerChannel')
      ->disableOriginalConstructor()
      ->getMock();
    $multiSafepayClient = new MultiSafepayClient($client, $logger);
    $multiSafepayClient->setOptions('key', 'test');

    $data = [
      'test' => 'test',
    ];
    $payment_url = $multiSafepayClient->createOrder($data);

    $this->assertEquals('http://www.google.be', $payment_url);

    $this->setExpectedException('Exception', 'Error Communicating with MultiSafepay');
    $multiSafepayClient->createOrder($data);

  }

  /**
   * Test loadOrder method.
   */
  public function testLoadOrder() {
    $order_id = 10;

    $order = json_encode([
      'data' => [
        'order_id' => $order_id,
      ],
    ]);

    $mock = new MockHandler([
      new Response(200, [], $order),
    ]);

    $handler = HandlerStack::create($mock);

    $client = new Client(['handler' => $handler]);
    $logger = $this->getMockBuilder('Drupal\Core\Logger\LoggerChannel')
      ->disableOriginalConstructor()
      ->getMock();
    $multiSafepayClient = new MultiSafepayClient($client, $logger);
    $multiSafepayClient->setOptions('key', 'test');

    $order = $multiSafepayClient->loadOrder($order_id);
    $this->assertEquals($order_id, $order['data']['order_id']);

  }

}
