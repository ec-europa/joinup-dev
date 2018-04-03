<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin\rdf_etl\Step;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rdf_entity\RdfGraphHandlerInterface;
use Drupal\rdf_etl\Plugin\RdfEtlStepPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a step that creates provenance activity entities for related data.
 *
 * @RdfEtlStep(
 *  id = "attach_provenance_data",
 *  label = @Translation("Attach provenance data to the entities"),
 * )
 */
class AttachProvenanceData extends RdfEtlStepPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManger;

  /**
   * The SPARQL database connection.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Connection
   */
  protected $sparql;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManger = $entity_type_manager;
    $this->sparql = Database::getConnection('default', 'sparql_default');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array &$data): void {
    // The following query fetches items that have a type, i.e. entities, but
    // not those that have the skos:inScheme or skos:topConceptOf property as
    // these are handled as taxonomy terms and terms are maintained locally.
    $query = <<<QUERY
SELECT ?subject
FROM <@sink_graph>
WHERE {
  ?subject a ?bundle_mapping .
  FILTER NOT EXISTS {?subject <http://www.w3.org/2004/02/skos/core#inScheme> ?object} .
  FILTER NOT EXISTS {?subject <http://www.w3.org/2004/02/skos/core#topConceptOf> ?object} .
}
QUERY;

    $sink = $this->getConfiguration()['sink_graph'];
    $query = str_replace('@sink_graph', $sink, $query);
    $results = $this->sparql->query($query);
    $activities = [];
    foreach ($results as $result) {
      $uri = $result->subject->getUri();
      $activities[$uri] = $this->entityTypeManger->getStorage('rdf_entity')->create([
        'rid' => 'provenance_activity',
        'provenance_entity' => $uri,
        'provenance_enabled' => TRUE,
        'provenance_started' => \Drupal::time()->getRequestTime(),
      ]);
    }

    // The entities in the graph are not stored in the database.
    // Store the activities in the data array in order to save them along with
    // the imported entities.
    $data['activities'] = $activities;
  }

}
