<?php

namespace Drupal\commerce_multisafepay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_multisafepay\MultiSafepayClientInterface;
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
   * MultiSafepayClient.
   *
   * @var \Drupal\commerce_multisafepay\MultiSafepayClient
   */
  protected $multiSafepayClient;

  /**
   * Constructs a new PaymentGatewayBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   *   The payment type manager.
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   *   The payment method type manager.
   * @param \Drupal\commerce_multisafepay\MultiSafepayClientInterface $multisafepay_client
   *   The MultiSafepay client.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
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
   * The default configuration.
   *
   * @return array
   *   Default configuration.
   */
  public function defaultConfiguration() {
    return [
      'api_key' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * Build the configuration form.
   *
   * @param array $form
   *   The configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The configuration form.
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
   * Submit the configuration.
   *
   * @param array $form
   *   The configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['api_key'] = $values['api_key'];
    }
  }

  /**
   * The onNotify method.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
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
        'remote_state' => $remote_state,
      ]);
      $payment->save();

      return new JsonResponse('OK');
    }
  }

  /**
   * Map the remote state to a commerce payment transition.
   *
   * @param string $remote_state
   *   The remote state.
   *
   * @return string
   *   The transition string.
   */
  protected function mapRemoteStateToTransition($remote_state) {
    $states = [
      'completed' => 'capture',
      'cancelled' => 'void',
      'expired' => 'expire',
      'refunded' => 'refund',
      'partial_refunded' => 'partially_refund',
    ];

    return $states[$remote_state];
  }

}
