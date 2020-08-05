<?php

declare(strict_types = 1);

namespace Drupal\eif\Plugin\views\argument_validator;

use Drupal\eif\EifInterface;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a argument validator plugin for the EIF category.
 *
 * @ViewsArgumentValidator(
 *   id = "eif_category",
 *   title = @Translation("EIF Category"),
 * )
 */
class EifCategoryArgumentValidator extends ArgumentValidatorPluginBase {

  /**
   * The EIF helper service.
   *
   * @var \Drupal\eif\EifInterface
   */
  protected $eifHelper;

  /**
   * Constructs a new plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\eif\EifInterface $eif_helper
   *   The EIF helper service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EifInterface $eif_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eifHelper = $eif_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eif.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateArgument($arg): bool {
    $categories = $this->eifHelper->getEifCategories();
    return isset($categories[$arg]);
  }

}
