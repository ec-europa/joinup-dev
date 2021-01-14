<?php

declare(strict_types = 1);

namespace Drupal\contact_information\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\joinup_workflow\EntityWorkflowStateTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Bundle class for the 'contact_information' bundle.
 */
class ContactInformation extends Rdf implements ContactInformationInterface {

  use EntityWorkflowStateTrait;
  use JoinupBundleClassFieldAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function getWorkflowStateFieldName(): string {
    return 'field_ci_state';
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedGroup(): ?GroupInterface {
    $query = $this->entityTypeManager()->getStorage('rdf_entity')->getQuery();
    $condition_or = $query->orConditionGroup();
    // Contact entities are also referenced by asset releases but the same
    // entity will also be referenced by the solution itself so there is no need
    // to check them.
    $condition_or->condition('field_ar_contact_information', $this->id());
    $condition_or->condition('field_is_contact_information', $this->id());
    $query->condition($condition_or);
    // Due to the quirky way in which a SPARQL query is built, we will not only
    // get the data that matches the fields we request, but also other data that
    // references our entity with the RDF type `dcat#contactPoint`. The best way
    // to get the data we need is to add an additional condition that limits the
    // results on the target bundles. This will make sure only the graphs for
    // collections and solutions are queried, instead of querying every possible
    // graph.
    $query->condition('rid', JoinupGroupHelper::GROUP_BUNDLES, 'IN');
    $ids = $query->execute();

    if (empty($ids)) {
      return NULL;
    }

    $id = reset($ids);
    $group = $this->entityTypeManager()->getStorage('rdf_entity')->load($id);
    return $group instanceof GroupInterface ? $group : NULL;
  }

}
