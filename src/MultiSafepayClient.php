<?php

namespace Drupal\commerce_multisafepay;

/**
 * Class MultiSafepayClient.
 *
 * @package Drupal\commerce_multisafepay
 */
final class MultiSafepayClient extends MultiSafepayClientBase implements MultiSafepayClientInterface {
  /**
   * {@inheritdoc}
   */
  public function createOrder(array $data) {
    $response = $this->handleRequest('POST', 'orders', $data);

    if (empty($response['data']['payment_url'])) {
      return NULL;
    }

    return $response['data']['payment_url'];
  }

  /**
   * {@inheritdoc}
   */
  public function loadOrder($order_id) {
    $method = sprintf('orders/%s', $order_id);
    $response = $this->handleRequest('GET', $method);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function createRefund($order_id, $amount, $currency) {
    $method = sprintf('orders/%s/refunds', $order_id);
    $data = ['amount' => $amount, 'currency' => $currency, 'type' => 'refund'];
    $response = $this->handleRequest('POST', $method, $data);

    return $response;
  }

}
