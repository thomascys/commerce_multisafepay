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
   * @return null
   */
  public function createOrder(array $data) {
    $response = $this->handleRequest('POST', 'orders', $data);

    if(empty($response['data']['payment_url'])) {
      return NULL;
    }

    return $response['data']['payment_url'];
  }
}