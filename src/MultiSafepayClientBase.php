<?php

namespace Drupal\commerce_multisafepay;


use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class MultiSafepayClientBase
 *
 * @package Drupal\commerce_multisafepay
 */
abstract class MultiSafepayClientBase {
  const API_TEST_URL = 'https://testapi.multisafepay.com/v1/json/';
  const API_LIVE_URL = 'https://api.multisafepay.com/v1/json/';
  protected $client;
  protected $logger;
  protected $options;

  /**
   * MultiSafepayClientBase constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(ClientInterface $client, LoggerInterface $logger) {
    $this->client = $client;
    $this->logger = $logger;
  }

  /**
   * @param string $http_method
   * @param string $method
   * @param array $data
   *
   * @return mixed
   * @throws \Exception
   */
  public function handleRequest(string $http_method, string $method, array $data) {
    $options = $this->buildOptions($http_method, $data);
    try {
      $request = $this->client->request($http_method, $method, $options);
    }
    catch (\Exception $error) {
      $message = 'Received no response from MultiSafepay!';
      $this->logger->critical($message);
      throw new \Exception($message);
    }

    $response = json_decode($request->getBody(), TRUE);

    return $response;
  }

  /**
   * @param string $http_method
   * @param array $data
   *
   * @return int
   */
  protected function buildOptions(string $http_method, array $data) {
    switch ($http_method) {
      case 'POST':
        $options = $this->options + ['form_params' => $data];
        break;

      case 'GET':
      default:
        $options = $this->options + ['query' => $data];
    }
    return $options;
  }

  /**
   * @param string $api_key
   * @param string $mode
   */
  public function setOptions(string $api_key, string $mode = 'test') {
    $this->options = [
      'base_uri' => $mode === 'live' ? self::API_LIVE_URL : self::API_TEST_URL,
      'headers' => [
        'api_key' => $api_key
      ],
    ];
  }
}