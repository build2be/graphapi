<?php

/**
 * @file
 * Contains \Drupal\system\Form\SiteInformationForm.
 */

namespace Drupal\graphapi\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\Context\ContextInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\Context\ContextInterface $context
   *   The configuration context used for this configuration object.
   */
  public function __construct(ConfigFactory $config_factory) {
    parent::__construct($config_factory);

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'graphapi_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('graphapi.site');

    $form['graphviz'] = array(
      '#type' => 'details',
      '#title' => t('Graphviz settings'),
    );
    $form['graphviz']['graphviz_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to Graphviz dot executable'),
      '#default_value' => $config->get('graphviz_path'),
      '#required' => TRUE
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('graphapi.site')
      ->set('graphviz_path', $form_state['values']['graphviz_path'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
