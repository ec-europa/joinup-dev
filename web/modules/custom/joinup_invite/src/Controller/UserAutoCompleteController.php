<?php

namespace Drupal\joinup_invite\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxy;
use Drupal\og\MembershipManager;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class that handles an auto complete by first name, family name or email.
 */
class UserAutoCompleteController extends ControllerBase {

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\og\MembershipManager definition.
   *
   * @var \Drupal\og\MembershipManager
   */
  protected $membershipManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountProxy $current_user, EntityTypeManager $entity_type_manager, MembershipManager $og_membership_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $og_membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * Returns auto completed user entries.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the auto complete suggestions.
   */
  public function userAutoComplete(Request $request) {
    $param = $request->query->get('q');
    $values = [];
    if ($param) {
      $results = $this->entityTypeManager->getStorage('user')->getQuery('OR')
        ->condition('mail', $param, 'CONTAINS')
        ->condition('field_user_first_name', $param, 'CONTAINS')
        ->condition('field_user_family_name', $param, 'CONTAINS')
        ->execute();
      /** @var \Drupal\user\UserInterface[] $users */
      $users = $this->entityTypeManager->getStorage('user')->loadMultiple($results);

      foreach ($users as $user) {
        $values[] = ['value' => $user->getEmail(), 'label' => $this->getAccountName($user)];
      }
    }
    return new JsonResponse($values);
  }

  /**
   * Returns a full name of the user with his email as suffix in parenthesis.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user object.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A string version of user's full name.
   */
  protected function getAccountName(UserInterface $user) {
    return $this->t('@name (@email)', [
      '@name' => $user->get('full_name')->value,
      '@email' => $user->getEmail(),
    ]);
  }

}
