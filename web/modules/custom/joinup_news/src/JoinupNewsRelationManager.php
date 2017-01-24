<?php

namespace Drupal\joinup_news;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\og\MembershipManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service to manage relations for the news node bundle.
 */
class JoinupNewsRelationManager implements ContainerInjectionInterface {

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a JoinupNewsRelationManager object.
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
   * Retrieves the parent of the news node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $news
   *   The news node.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The rdf entity the news belongs to, or NULL when no group is found.
   */
  public function getNewsParent(EntityInterface $news) {
    if ($news->bundle() !== 'news') {
      return NULL;
    }

    $groups = $this->membershipManager->getGroups($news);

    if (empty($groups['rdf_entity'])) {
      return NULL;
    }

    return reset($groups['rdf_entity']);
  }

}
