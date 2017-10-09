<?php

namespace Drupal\commerce_multisafepay;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Class MultiSafepayClientBase.
 *
 * @package Drupal\commerce_multisafepay
 */
abstract class MultiSafepayClientBase implements MultiSafepayClientInterface {
  const API_TEST_URL = 'https://testapi.multisafepay.com/v1/json/';
  const API_LIVE_URL = 'https://api.multisafepay.com/v1/json/';
  protected $client;
  protected $logger;
  protected $options;

  /**
   * MultiSafepayClientBase constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   Guzzle client.
   * @param \Psr\Log\LoggerInterface $logger
   *   Watchdog logger.
   */
  public function __construct(ClientInterface $client, LoggerInterface $logger) {
    $this->client = $client;
    $this->logger = $logger;
  }

  /**
   * Handle the request.
   *
   * @param string $http_method
   *   Http method.
   * @param string $method
   *   Api method.
   * @param array $data
   *   Data array.
   *
   * @return array
   *   Response.
   *
   * @throws \Exception
   *   Exception.
   */
  public function handleRequest($http_method, $method, array $data = []) {
    $options = $this->buildOptions($http_method, $data);

    try {
      $request = $this->client->request($http_method, $method, $options);
    }
    catch (\Exception $e) {
      $this->logger->critical($e->getMessage());
      throw new \Exception($e->getMessage());
    }

    $response = json_decode($request->getBody(), TRUE);

    return $response;
  }

  /**
   * Build options needed for the request.
   *
   * @param string $http_method
   *   Http method.
   * @param array $data
   *   Data array.
   *
   * @return array
   *   Options.
   */
  protected function buildOptions($http_method, array $data) {
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
   * Set the mandatory options for the request.
   *
   * @param string $api_key
   *   The api key.
   * @param string $mode
   *   Api mode.
   */
  public function setOptions($api_key, $mode = 'test') {
    $this->options = [
      'base_uri' => $mode === 'live' ? self::API_LIVE_URL : self::API_TEST_URL,
      'headers' => [
        'api_key' => $api_key,
      ],
    ];
  }

}
