<?php

namespace Drupal\joinup\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\user\Form\UserMultipleCancelConfirm as CoreUserMultipleCancelConfirm;
use Drupal\user\PrivateTempStoreFactory;
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
   * The relation manager service.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $relationManager;

  /**
   * Constructs a new UserMultipleCancelConfirm.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\joinup_core\JoinupRelationManager $relation_manager
   *   The Joinup relation manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, UserStorageInterface $user_storage, EntityManagerInterface $entity_manager, JoinupRelationManager $relation_manager) {
    parent::__construct($temp_store_factory, $user_storage, $entity_manager);

    $this->relationManager = $relation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('entity.manager'),
      $container->get('joinup_core.relations_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Loop through all the accounts that are going to be deleted and check
    // if they are sole owners of collections.
    $build = [];
    foreach (Element::children($form['accounts']) as $user_id) {
      /** @var \Drupal\user\Entity\User $account */
      $account = $this->userStorage->load($user_id);
      $collections = $this->relationManager->getCollectionsWhereSoleOwner($account);

      if ($collections) {
        $build[$account->id()] = [
          'warning' => [
            '#markup' => $this->t('User @name cannot be deleted as it is currently the sole owner of these collections:', [
              '@name' => $account->getAccountName(),
            ]),
          ],
          'collections' => [
            '#theme' => 'item_list',
            '#items' => array_map(function (EntityInterface $collection) {
              return $collection->toLink($collection->label());
            }, $collections),
          ],
        ];
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
    }

    return $form;
  }

}
