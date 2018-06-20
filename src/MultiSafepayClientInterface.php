<?php

namespace Drupal\commerce_multisafepay;

/**
 * Interface MultiSafepayClientInterface.
 *
 * @package Drupal\commerce_multisafepay
 */
interface MultiSafepayClientInterface {

  /**
   * Create the MultiSafepay order.
   *
   * @param array $data
   *   Array needed to create an order.
   *
   * @return null|string
   *   Return the payment url.
   */
  public function createOrder(array $data);

  /**
   * Load a MultiSafepay order.
   *
   * @param string $order_id
   *   The order id.
   *
   * @return array
   *   The order array.
   */
  public function loadOrder($order_id);

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
  public function createRefund($order_id, $amount, $currency);
}
