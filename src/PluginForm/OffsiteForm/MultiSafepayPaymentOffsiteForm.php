<?php

namespace Drupal\commerce_multisafepay\PluginForm\OffsiteForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MultiSafepayPaymentOffsiteForm.
 *
 * @package Drupal\commerce_multisafepay\PluginForm\OffsiteForm
 */
class MultiSafepayPaymentOffsiteForm extends PaymentOffsiteForm {

  /**
   * The MultiSafepay client.
   *
   * @var \Drupal\commerce_multisafepay\MultiSafepayClient
   */
  protected $multiSafepayClient;

  /**
   * MultiSafepayPaymentOffsiteForm constructor.
   */
  public function __construct() {
    $this->multiSafepayClient = \Drupal::service('commerce_multisafepay.client');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $payment_gateway_configuration = $payment_gateway_plugin->getConfiguration();

    $api_key = $payment_gateway_configuration['api_key'];
    $mode = $payment_gateway_configuration['mode'];

    $this->multiSafepayClient->setOptions($api_key, $mode);

    $country_code = strtoupper($payment->getOrder()->getStore()->getAddress()->getCountryCode());
    $language_code = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $locale = sprintf('%s_%s', $language_code, $country_code);

    $data = [
      'type' => 'redirect',
      'order_id' => $payment->getOrderId(),
      'currency' => $payment->getAmount()->getCurrencyCode(),
      'amount' => $payment->getAmount()->getNumber() * 100,
      'gateway' => NULL,
      'description' => sprintf('order %s', $payment->getOrderId()),
      'payment_options' => [
        'notification_url' => $payment_gateway_plugin->getNotifyUrl()->toString(),
        'redirect_url' => $form['#return_url'],
        'cancel_url' => $form['#cancel_url'],
        'close_window' => TRUE,
      ],
      'customer' => [
        'locale' => $locale,
      ],
    ];

    $redirect_url = $this->multiSafepayClient->createOrder($data);

    return $this->buildRedirectForm($form, $form_state, $redirect_url, [], self::REDIRECT_GET);;
  }

}
