<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\joinup_group\JoinupGroupManagerInterface;
use Drupal\user\Form\UserMultipleCancelConfirm as CoreUserMultipleCancelConfirm;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides the default implementation of the form to cancel multiple users.
 *
 * Deletion of a user account will be denied when the user is the sole owner of
 * a collection.
 */
class UserMultipleCancelConfirm extends CoreUserMultipleCancelConfirm {

  /**
   * The group manager service.
   *
   * @var \Drupal\joinup_group\JoinupGroupManagerInterface
   */
  protected $groupManager;

  /**
   * Constructs a new UserMultipleCancelConfirm.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\joinup_group\JoinupGroupManagerInterface $joinup_group_manager
   *   The Joinup group manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, UserStorageInterface $user_storage, EntityTypeManagerInterface $entity_type_manager, JoinupGroupManagerInterface $joinup_group_manager) {
    parent::__construct($temp_store_factory, $user_storage, $entity_type_manager);
    $this->groupManager = $joinup_group_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('entity_type.manager'),
      $container->get('joinup_group.group_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Loop through all the accounts that are going to be deleted and check
    // if they are sole owners of groups.
    $build = [];
    foreach (Element::children($form['accounts']) as $user_id) {
      /** @var \Drupal\user\Entity\User $account */
      $account = $this->userStorage->load($user_id);
      if (empty($account)) {
        throw new \RuntimeException("User with id {$user_id} was not found.");
      }
      $groups = $this->groupManager->getGroupsWhereSoleOwner($account);

      if ($groups) {
        $build[$account->id()] = [
          'warning' => [
            '#markup' => $this->t('User @name cannot be deleted as they are currently the sole owner of these groups:', [
              '@name' => $account->getDisplayName(),
            ]),
          ],
        ];

        foreach ($groups as $group) {
          $group_data[$group->bundle()][] = $group->toLink($group->label());
        }

        $rdf_storage = $this->entityTypeManager->getStorage('rdf_type');
        foreach (['collection', 'solution'] as $bundle) {
          $bundle_type = $rdf_storage->load($bundle);
          if (!empty($group_data[$bundle])) {
            $build[$account->id()][$bundle] = [
              '#theme' => 'item_list',
              '#title' => $bundle_type->getCountLabel(count($group_data[$bundle])),
              '#items' => $group_data[$bundle],
            ];
          }
        }
      }
    }

    if (!empty($build)) {
      $build['actions'] = [
        '#type' => 'actions',
        // @see \Drupal\Core\Form\ConfirmFormBase::buildForm()
        'cancel' => ConfirmFormHelper::buildCancelLink($this, $this->getRequest()),
      ];
      // Remove the 'This action cannot be undone as the user is unable to
      // delete the user at this point.
      unset($form['description']);
      $form += $build;
      // No access to the 'Cancel' button should be given if there is at least
      // one user that is a sole owner of a collection.
      $form['actions']['submit']['#access'] = FALSE;
    }

    return $form;
  }

}
