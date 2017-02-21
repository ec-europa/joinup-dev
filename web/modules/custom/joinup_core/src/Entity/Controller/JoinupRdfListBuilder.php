<?php

namespace Drupal\joinup_core\Entity\Controller;

use Drupal\rdf_entity\Entity\Controller\RdfListBuilder;

/**
 * Provides a workaround for 'asset_release' and 'solution' RDF entity bundles.
 *
 * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3126
 *
 * @todo Remove this class when ISAICP-3126 gets in.
 */
class JoinupRdfListBuilder extends RdfListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $request = \Drupal::request();
    $rdf_storage = $this->getStorage();
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    /** @var \Drupal\rdf_entity\Entity\Query\Sparql\Query $query */
    $query = $rdf_storage->getQuery();

    // If a graph type is set in the url, validate it, and use it in the query.
    $graph = $request->get('graph');
    if (!empty($graph)) {
      $definitions = $rdf_storage->getGraphDefinitions();
      if (isset($definitions[$graph])) {
        // Use the graph to build the list.
        $query->setGraphType([$graph]);
      }
    }
    else {
      $query->setGraphType($rdf_storage->getGraphHandler()->getEntityTypeEnabledGraphs());
    }

    if ($rid = $request->get('rid') ?: NULL) {
      $rid = in_array($rid, array_keys($bundle_info->getBundleInfo('rdf_entity'))) ? [$rid] : NULL;
    }

    $query->condition('rid', $rid, 'IN');
    // Special treatment for 'solution' and 'asset_release'.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3126
    if ($rid[0] === 'asset_release') {
      $query->exists('field_isr_is_version_of');
    }
    elseif ($rid[0] === 'solution') {
      $query->notExists('field_isr_is_version_of');
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    $header = $this->buildHeader();
    $query->tableSort($header);

    return $query->execute();
  }

}
