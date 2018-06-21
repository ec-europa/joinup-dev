<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity_provenance\ProvenanceHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a step plugin that updates the provenance activity records.
 *
 * @PipelineStep(
 *   id = "provenance_activity",
 *   label = @Translation("Provenance activity"),
 * )
 */
class ProvenanceActivity extends JoinupFederationStepPluginBase {

  /**
   * The RDF entity provenance helper service.
   *
   * @var \Drupal\rdf_entity_provenance\ProvenanceHelperInterface
   */
  protected $provenanceHelper;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Creates a new pipeline step plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql
   *   The SPARQL database connection.
   * @param \Drupal\rdf_entity_provenance\ProvenanceHelperInterface $rdf_entity_provenance_helper
   *   The RDF entity provenance helper service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, Connection $sparql, ProvenanceHelperInterface $rdf_entity_provenance_helper, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->provenanceHelper = $rdf_entity_provenance_helper;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sparql_endpoint'),
      $container->get('rdf_entity_provenance.provenance_helper'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $current_user_id = $this->currentUser->id();

    // Create or update provenance activity records for blacklisted entities.
    $blacklist = $this->getPersistentDataValue('blacklist');
    $activities = $this->provenanceHelper->loadOrCreateEntitiesActivity($blacklist);
    foreach ($activities as $id => $activity) {
      $activity
        // Set the last user that federated this entity as owner.
        ->setOwnerId($current_user_id)
        ->set('provenance_enabled', FALSE)
        ->save();
    }

    // Create or update provenance activity records for saved entities.
    $entities = array_keys($this->getPersistentDataValue('entities'));
    $activities = $this->provenanceHelper->loadOrCreateEntitiesActivity($entities);
    foreach ($activities as $id => $activity) {
      $activity
        // Set the last user that federated this entity as owner.
        ->setOwnerId($current_user_id)
        ->set('provenance_enabled', TRUE)
        ->save();
    }
  }

}
