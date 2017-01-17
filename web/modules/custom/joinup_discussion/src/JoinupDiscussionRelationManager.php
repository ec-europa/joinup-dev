<?php

namespace Drupal\joinup_discussion;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\og\MembershipManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service to manage relations for the discussion node bundle.
 */
class JoinupDiscussionRelationManager implements ContainerInjectionInterface {

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a JoinupDiscussionRelationManager object.
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
   * Retrieves the parent of the discussion node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $discussion
   *   The discussion node.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The rdf entity the discussion belongs to, or NULL when no group is found.
   */
  public function getDiscussionParent(EntityInterface $discussion) {
    if ($discussion->bundle() !== 'discussion') {
      return NULL;
    }

    $groups = $this->membershipManager->getGroups($discussion);

    if (empty($groups['rdf_entity'])) {
      return NULL;
    }

    return reset($groups['rdf_entity']);
  }

}
