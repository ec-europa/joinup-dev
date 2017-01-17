<?php

namespace Drupal\joinup_document;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\og\MembershipManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service to manage relations for the document node bundle.
 */
class JoinupDocumentRelationManager implements ContainerInjectionInterface {

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a JoinupDocumentRelationManager object.
   *
   * @param \Drupal\og\MembershipManagerInterface $membershipManager
   *   The OG membership manager service.
   */
  public function __construct(MembershipManagerInterface $membershipManager) {
    $this->membershipManager = $membershipManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.membership_manager')
    );
  }

  /**
   * Retrieves the parent of the document node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $document
   *   The document node.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The rdf entity the document belongs to, or NULL when no group is found.
   */
  public function getDocumentParent(EntityInterface $document) {
    if ($document->bundle() !== 'document') {
      return NULL;
    }

    $groups = $this->membershipManager->getGroups($document);

    if (empty($groups['rdf_entity'])) {
      return NULL;
    }

    return reset($groups['rdf_entity']);
  }

  /**
   * Returns the appropriate workflow to use for the document entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $document
   *    The document entity.
   * @return string
   *    The id of the workflow to use.
   */
  public function getDocumentWorkflow(EntityInterface $document){
    $parent = $this->getDocumentParent($document);
    if (empty($parent)) {
      return 'document_pre_moderated';
    }

    if ($parent->bundle() === 'solution') {
      // Solutions are only post_moderated as they only have facilitators.
      return 'document_post_moderated';
    }
    else if ($parent->bundle() === 'collection') {
      $moderation = $parent->field_ar_moderation->value;
      $workflow_id = $moderation == TRUE ? 'document_pre_moderated' : 'document_post_moderated';
      return $workflow_id;
    }

    // By default, pre_moderation is selected.
    return 'document_pre_moderated';
  }

}
