<?php

namespace Drupal\joinup\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\joinup_core\JoinupRelationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller that allows to toggle a boolean field on or off.
 *
 * To use the route provided, some parameters need to be specified in the route
 * definition, under the "defaults" section:
 * - field_name: the name of the field itself.
 * - value: the value to set for the field.
 * - message: the message to show to the user upon completion.
 */
class BooleanFieldToggleController extends ControllerBase {

  /**
   * The Joinup relation manager.
   *
   * @var \Drupal\joinup_core\JoinupRelationManagerInterface
   */
  protected $relationManager;

  /**
   * Instantiates a new SiteFeatureController object.
   *
   * @param \Drupal\joinup_core\JoinupRelationManagerInterface $relationManager
   *   The Joinup relation manager.
   */
  public function __construct(JoinupRelationManagerInterface $relationManager) {
    $this->relationManager = $relationManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('joinup_core.relations_manager')
    );
  }

  /**
   * Route callback that sets the entity field to the specified value.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being processed.
   * @param string $field_name
   *   The name of the boolean field.
   * @param bool $value
   *   The value to set in the field.
   * @param string $message
   *   The message to show to the user.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function doToggle(ContentEntityInterface $entity, $field_name, $value, $message) {
    global $lalalala_testing;
    $lalalala_testing = 'value: ' . (string) (int) $value;
    $entity->set($field_name, $value)->save();

    // Passing a variable to the t() function triggers a warning, but in this
    // case our message is not really dynamic. Core does the same for the
    // "_title" route parameter.
    // @see \Drupal\Core\Controller\TitleResolver::getTitle()
    // @codingStandardsIgnoreLine
    drupal_set_message($this->t($message, [
      '@bundle' => $entity->get($entity->getEntityType()->getKey('bundle'))->entity->label(),
      '%title' => $entity->label(),
    ]));

    return $this->getRedirect($entity);
  }

  /**
   * Access check for a toggle route.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being featured.
   * @param string $field_name
   *   The name of the boolean field.
   * @param bool $value
   *   The value to set in the field.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function routeAccess(ContentEntityInterface $entity, $field_name, $value) {
    return AccessResult::allowedIf($entity->hasField($field_name) && (bool) $entity->get($field_name)->value !== $value);
  }

  /**
   * Returns a response to redirect the user to the proper page.
   *
   * For nodes, the redirect will be to the collection/solution to which they
   * belong.
   * For collections/solutions, the redirect will be to their canonical page.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity being handled.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response to the node collection.
   */
  protected function getRedirect(ContentEntityInterface $entity) {
    if ($entity->getEntityTypeId() === 'rdf_entity') {
      $redirect = $entity->toUrl();
    }
    else {
      $redirect = $this->relationManager->getParent($entity)->toUrl();
    }

    return $this->redirect($redirect->getRouteName(), $redirect->getRouteParameters());
  }

}
