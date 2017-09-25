<?php

namespace Drupal\commerce_multisafepay;

/**
 * Class MultiSafepayClient
 *
 * @package Drupal\commerce_multisafepay
 */
final class MultiSafepayClient extends MultiSafepayClientBase {
  /**
   * @param array $data
   *
   * @return null|string
   */
  public function createOrder(array $data) {
    $response = $this->handleRequest('POST', 'orders', $data);

    if(empty($response['data']['payment_url'])) {
      return NULL;
    }

    return $response['data']['payment_url'];
  }

  /**
   * @param string $order_id
   *
   * @return null|string
   */
  public function loadOrder($order_id) {
    $method = sprintf('orders/%s', $order_id);
    $response = $this->handleRequest('GET', $method);

    return $response;
  }
}