<?php

namespace Drupal\commerce_multisafepay\Plugin\Commerce\PaymentGateway;


use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the MultiSafepay payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "multisafepay",
 *   label = "MultiSafepay",
 *   display_label = "MultiSafepay",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_multisafepay\PluginForm\OffsiteForm\MultiSafepayPaymentOffsiteForm",
 *   }
 * )
 */
class MultiSafepay extends OffsitePaymentGatewayBase {
  /**
   * MultiSafepayGateway constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   * @param \Drupal\Component\Datetime\TimeInterface $time
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
  }

  /**
   * @return int
   */
  public function defaultConfiguration() {
    return [
      'api_key' => ''
    ] + parent::defaultConfiguration();
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $this->configuration['api_key'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['api_key'] = $values['api_key'];
    }
  }

  /**
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @param \Symfony\Component\HttpFoundation\Request $request
   */
  public function onReturn(OrderInterface $order, Request $request) {
    parent::onReturn($order, $request);
  }

  /**
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   * @param \Symfony\Component\HttpFoundation\Request $request
   */
  public function onCancel(OrderInterface $order, Request $request) {
    parent::onCancel($order, $request);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   */
  public function onNotify(Request $request) {
    parent::onNotify($request);
  }

}
