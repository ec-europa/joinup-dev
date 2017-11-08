<?php

namespace Drupal\solution\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\OgMembershipInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirmation form for transferring the solution ownership.
 */
class TransferSolutionOwnershipConfirmForm extends ConfirmFormBase {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionPluginManager;

  /**
   * The OG membership storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $membershipStorage;

  /**
   * The user private tempstore.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The membership of the user that receives the ownership.
   *
   * @var \Drupal\og\OgMembershipInterface|null
   */
  protected $membership = NULL;

  /**
   * Constructs a new confirmation form object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Action\ActionManager $action_plugin_manager
   *   The action plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager interface.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(AccountInterface $current_user, RendererInterface $renderer, ActionManager $action_plugin_manager, EntityTypeManagerInterface $entity_type_manager, PrivateTempStoreFactory $temp_store_factory) {
    $this->currentUser = $current_user;
    $this->renderer = $renderer;
    $this->actionPluginManager = $action_plugin_manager;
    $this->membershipStorage = $entity_type_manager->getStorage('og_membership');
    $this->tempStore = $temp_store_factory->get('joinup_transfer_solution_ownership');

    if ($data = $this->tempStore->get($this->currentUser->id())) {
      if (!$this->membership = OgMembership::load($data['membership'])) {
        throw new \RuntimeException("Invalid membership with ID '{$data['membership']}'");
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('renderer'),
      $container->get('plugin.manager.action'),
      $container->get('entity_type.manager'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to transfer the ownership of %solution solution to %user?', [
      '%solution' => $this->membership->getGroup()->label(),
      '%user' => $this->membership->getOwner()->getDisplayName(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $current_owners = array_map(function (OgMembershipInterface $membership) {
      if ($membership->getOwnerId() === $this->currentUser->id()) {
        return $this->t('@user (you)', ['@user' => $membership->getOwner()->getDisplayName()]);
      }
      return $membership->getOwner()->getDisplayName();
    }, $this->getSolutionOwnerMembeships());

    $args = [
      '%solution' => $this->membership->getGroup()->label(),
      '%user' => $this->membership->getOwner()->getDisplayName(),
    ];

    $build = [];
    $build[] = [
      '#prefix' => '<h2>',
      '#markup' => $this->t('Warning!'),
      '#suffix' => '</h2>',
    ];
    if ($current_owners) {
      $build[] = [
        '#prefix' => '<p>',
        '#markup' => $this->formatPlural(count($current_owners),
          "The user %first_user will lose the ownership of the %solution solution.",
          "The following users will lose the ownership of the %solution solution:",
          $args + ['%first_user' => reset($current_owners)]
        ),
        '#suffix' => '</p>',
      ];
      if (count($current_owners) > 1) {
        $build[] = [
          '#theme' => 'item_list',
          '#items' => $current_owners,
        ];
      }
    }
    $build[] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('The user %user will be the new owner of %solution solution.', $args),
      '#suffix' => '</p>',
    ];
    $build[] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('This action cannot be undone.'),
      '#suffix' => '</p>',
    ];

    return $this->renderer->render($build);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->tempStore->delete($this->currentUser->id());

    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    $solution = $this->membership->getGroup();
    $memberships = $this->getSolutionOwnerMembeships();
    // Revoke 'owner' role from existing solution owners but grant them the
    // facilitator role, if missed.
    $facilitator = OgRole::loadByGroupAndName($solution, 'facilitator');
    foreach ($memberships as $membership) {
      $membership->revokeRoleById('rdf_entity-solution-administrator');
      // Add the facilitator role, if missed.
      if (!$membership->hasRole('rdf_entity-solution-facilitator')) {
        $membership->addRole($facilitator);
      }
      $membership->save();
    }

    // Add the 'owner' role.
    $role = OgRole::loadByGroupAndName($solution, 'administrator');
    $this->membership->addRole($role)->save();

    // Make this user also author of the solution.
    $solution->skip_notification = TRUE;
    $solution->setOwner($this->membership->getOwner())->save();

    // @todo Send a notification to the new owner in ISAICP-4111.
    $former_owners = array_map(function (OgMembershipInterface $membership) {
      return $membership->getOwner()->getDisplayName();
    }, $memberships);

    $args = [
      '%solution' => $this->membership->getGroup()->label(),
      '%user' => $this->membership->getOwner()->getDisplayName(),
      '@users' => implode(', ', $former_owners),
    ];
    switch (count($former_owners)) {
      case 0:
        $message = $this->t('Ownership of %solution solution granted to %user.');
        break;

      case 1:
        $message = $this->t('Ownership of %solution solution transferred from user @users to %user.', $args);
        break;

      default:
        $message = $this->t('Ownership of %solution solution transferred from users @users to %user.', $args);
    }
    drupal_set_message($message);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'joinup_transfer_solution_ownership_confirm';
  }

  /**
   * Provide the access policy for the route that shows this form.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function access() {
    // Probably we try to reuse a form that has been already used.
    if (!$this->membership) {
      return AccessResult::forbidden();
    }
    /** @var \Drupal\solution\Plugin\Action\TransferSolutionOwnershipOwnershipAction $action */
    $action = $this->actionPluginManager->createInstance('joinup_transfer_solution_ownership');
    return $action->access($this->membership, $this->currentUser, TRUE);
  }

  /**
   * Returns a list of memberships of the solution owners.
   *
   * @return \Drupal\og\OgMembershipInterface[]
   *   The memberships of the actual solution.
   */
  protected function getSolutionOwnerMembeships() {
    $ids = $this->membershipStorage->getQuery()
      ->condition('type', OgMembership::TYPE_DEFAULT)
      ->condition('entity_type', 'rdf_entity')
      ->condition('entity_id', $this->membership->getGroupId())
      ->condition('state', OgMembership::STATE_ACTIVE)
      ->condition('roles', 'rdf_entity-solution-administrator')
      ->execute();
    return OgMembership::loadMultiple($ids);
  }

}
