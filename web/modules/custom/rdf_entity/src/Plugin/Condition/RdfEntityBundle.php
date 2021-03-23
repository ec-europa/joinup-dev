<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'RDF entity bundle' condition plugin.
 *
 * @Condition(
 *   id = "rdf_entity_bundle",
 *   label = @Translation("RDF entity bundle"),
 *   context_definitions = {
 *     "rdf_entity" = @ContextDefinition("entity:rdf_entity", label = @Translation("RDF entity")),
 *   },
 * )
 */
class RdfEntityBundle extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The rdf type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $rdfTypeStorage;

  /**
   * Constructs a new RdfEntityBundle condition plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $rdf_type_storage
   *   The rdf type storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigEntityStorageInterface $rdf_type_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->rdfTypeStorage = $rdf_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('rdf_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['bundles' => []] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $bundles = $this->rdfTypeStorage->loadMultiple();
    foreach ($bundles as $bundle) {
      $options[$bundle->id()] = $bundle->label();
    }
    $form['bundles'] = [
      '#title' => $this->t('RDF entity bundles'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $this->configuration['bundles'],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['bundles'] = array_filter($form_state->getValue('bundles'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (count($this->configuration['bundles']) > 1) {
      $bundles = $this->configuration['bundles'];
      $last = array_pop($bundles);
      $bundles = implode(', ', $bundles);
      return $this->t('The rdf entity bundle is @bundles or @last', [
        '@bundles' => $bundles,
        '@last' => $last,
      ]);
    }
    $bundle = reset($this->configuration['bundles']);
    return $this->t('The rdf entity bundle is @bundle', ['@bundle' => $bundle]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['bundles']) && !$this->isNegated()) {
      return TRUE;
    }
    $rdf_entity = $this->getContextValue('rdf_entity');
    return !empty($this->configuration['bundles'][$rdf_entity->getType()]);
  }

}
