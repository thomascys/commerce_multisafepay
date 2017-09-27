<?php

namespace Drupal\commerce_multisafepay\Plugin\Commerce\PaymentGateway;


use Drupal\commerce_multisafepay\MultiSafepayClientInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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
   * @var \Drupal\commerce_multisafepay\MultiSafepayClient
   */
  protected $multiSafepayClient;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, MultiSafepayClientInterface $multisafepay_client, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->multiSafepayClient = $multisafepay_client;
    $this->multiSafepayClient->setOptions($configuration['api_key'], $configuration['mode']);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('commerce_multisafepay.client'),
      $container->get('datetime.time')
    );
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
  public function onCancel(OrderInterface $order, Request $request) {
    parent::onCancel($order, $request);
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function onNotify(Request $request) {
    $remote_id = $request->get('transactionid');
    // Return early if there's no transaction id.
    if (empty($remote_id)) {
      return new JsonResponse('Error 500', 500);
    }
    // Load the remote order.
    $remote_order = $this->multiSafepayClient->loadOrder($remote_id);
    $remote_state = $remote_order['data']['status'];

    // Try to load the payment entity.
    /** @var \Drupal\commerce_payment\PaymentStorage $payment_storage */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    $payment = $payment_storage->loadByRemoteId($remote_id);
    if ($payment) {
      $transition = $payment->getState()->getWorkflow()->getTransition($this->mapRemoteStateToTransition($remote_state));
      $payment->getState()->applyTransition($transition);
      $payment->setRemoteState($remote_state);
      $payment->save();

      return new JsonResponse('OK');
    }
    else {
      // Create a new payment.
      $amount = $remote_order['data']['amount'];
      $currency = $remote_order['data']['currency'];
      $order_id = $remote_order['data']['order_id'];

      $payment = $payment_storage->create([
        'state' => 'authorization',
        'amount' => new Price($amount, $currency),
        'payment_gateway' => $this->entityId,
        'order_id' => $order_id,
        'remote_id' => $remote_id,
      ]);
      $payment->setRemoteState($remote_state);

      $payment->save();

      return new JsonResponse('OK');
    }
  }

  /**
   * @param $remote_state
   *
   * @return string
   */
  protected function mapRemoteStateToTransition($remote_state) {
    $states = [
      'completed' => 'capture',
      'cancelled' => 'void',
      'expired' => 'expire',
      'refunded' => 'refund',
      'partial_refunded' => 'partially_refund'
    ];

    return $states[$remote_state];
  }
}
