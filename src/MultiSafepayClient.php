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
   */
  public function createOrder(array $data) {
    $response = $this->handleRequest('POST', '/orders', $data);
  }
}