<?php

namespace Drupal\commerce_multisafepay;

/**
 * Class MultiSafepayClient.
 *
 * @package Drupal\commerce_multisafepay
 */
final class MultiSafepayClient extends MultiSafepayClientBase {

  /**
   * Create the MultiSafepay order.
   *
   * @param array $data
   *   Array needed to create an order.
   *
   * @return null|string
   *   Return the payment url.
   */
  public function createOrder(array $data) {
    $response = $this->handleRequest('POST', 'orders', $data);

    if (empty($response['data']['payment_url'])) {
      return NULL;
    }

    return $response['data']['payment_url'];
  }

  /**
   * Load a MultiSafepay order.
   *
   * @param string $order_id
   *   The order id.
   *
   * @return array
   *   The order array.
   */
  public function loadOrder($order_id) {
    $method = sprintf('orders/%s', $order_id);
    $response = $this->handleRequest('GET', $method);

    return $response;
  }

  /**
   * Refund a MultiSafepay order.
   *
   * @param string $order_id
   *   The order id.
   * @param string $amount
   *   The amount.
   * @param string $currency
   *   The currency.
   *
   * @return array
   *   The order array.
   */
  public function createRefund($order_id, $amount, $currency) {
    $method = sprintf('orders/%s/refunds', $order_id);
    $data = ['amount' => $amount, 'currency' => $currency, 'type' => 'refund'];
    $response = $this->handleRequest('POST', $method, $data);

    return $response;
  }

}
