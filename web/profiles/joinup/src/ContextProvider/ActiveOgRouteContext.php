<?php

namespace Drupal\joinup\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\GroupTypeManager;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\Og;

/**
 * Retrieves the current OG group from entities available in the route.
 */
class ActiveOgRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a new ActiveOgRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Drupal\og\GroupTypeManager $group_type_manager
   *   The group manager object.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The membership manager.
   */
  public function __construct(RouteMatchInterface $route_match, GroupTypeManager $group_type_manager, MembershipManagerInterface $membership_manager) {
    $this->groupTypeManager = $group_type_manager;
    $this->routeMatch = $route_match;
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = new ContextDefinition('entity', $this->t('Active group'), FALSE);
    $value = NULL;

    foreach ($this->routeMatch->getParameters() as $parameter) {
      if ($parameter instanceof EntityInterface) {
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        $entity = $parameter;
        $entity_type = $entity->getEntityTypeId();
        $entity_bundle = $entity->bundle();

        // If this entity is a group, we stop looking for other matches.
        if ($this->groupTypeManager->isGroup($entity_type, $entity_bundle)) {
          $value = $entity;
        }
        elseif (Og::isGroupContent($entity_type, $entity_bundle)) {
          // If this entity is a group content, we fetch the groups this entity
          // belongs to and get the first one in the list.
          // This makes the context not really reliable when multiple groups are
          // available, but in Joinup this will always return a single value.
          $groups = $this->membershipManager->getGroups($entity);
          if (!empty($groups)) {
            $first_entity_type_groups = reset($groups);
            $value = reset($first_entity_type_groups);
          }
        }
      }
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);

    $result['og'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('og', $this->t('Active organic group from route')));
    return ['og' => $context];
  }

}
