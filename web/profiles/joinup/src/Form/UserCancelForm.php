<?php

namespace Drupal\joinup\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\og\MembershipManagerInterface;
use Drupal\user\Form\UserCancelForm as CoreUserCancelForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides the default form implementation to handle group memberships.
 *
 * Deletion of a user account will be denied when the user is the sole owner of
 * a collection.
 */
class UserCancelForm extends CoreUserCancelForm {

  /**
   * The role ID for a collection owner.
   */
  const COLLECTION_OWNER_ROLE = 'rdf_entity-collection-administrator';

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The relation manager service.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $relationManager;

  /**
   * Instantiates a new UserCancelForm class.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\og\MembershipManagerInterface $membershipManager
   *   The OG membership manager.
   * @param \Drupal\joinup_core\JoinupRelationManager $relation_manager
   *   The Joinup relation manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, MembershipManagerInterface $membershipManager = NULL, JoinupRelationManager $relation_manager = NULL) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);

    // Replicate the behaviour of the parent implementation.
    $this->membershipManager = $membershipManager ?: \Drupal::service('og.membership_manager');
    $this->relationManager = $relation_manager ?: \Drupal::service('joinup_core.relations_manager');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('og.membership_manager'),
      $container->get('joinup_core.relations_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $memberships = $this->relationManager->getUserMembershipsByRole($this->entity, self::COLLECTION_OWNER_ROLE);

    // Prepare a list of collections where the user is the sole owner.
    $collections = [];
    if ($memberships) {
      foreach ($memberships as $membership) {
        $group = $membership->getGroup();
        $owners = $this->relationManager->getGroupOwners($group);
        if (count($owners) === 1 && array_key_exists($this->entity->id(), $owners)) {
          $collections[$group->id()] = $group;
        }
      }
    }

    if (!empty($collections)) {
      $form = [
        'collections' => [
          '#theme' => 'item_list',
          '#items' => array_map(function (EntityInterface $group) {
            return $group->toLink($group->label());
          }, $collections),
          '#weight' => 0,
        ],
        'actions' => [
          '#type' => 'actions',
          // @see \Drupal\Core\Entity\ContentEntityConfirmFormBase::actions()
          'cancel' => ConfirmFormHelper::buildCancelLink($this, $this->getRequest()),
          '#weight' => 10,
        ],
      ];

      // Show a nicer message when the user is trying to delete its own account.
      if ($this->currentUser()->id() === $this->entity->id()) {
        $form['pre_warning'] = [
          '#markup' => $this->t('Dear @name,<br />when processing your request to delete your account, we noticed that you are the sole owner of these collections:', [
            '@name' => $this->entity->get('field_user_first_name')->value,
          ]),
          '#weight' => -5,
        ];
        $form['post_warning'] = [
          '#markup' => $this->t('Before removing this account, please verify and take action to modify the owner of the collections mentioned above.'),
          '#weight' => 5,
        ];
      }
      else {
        $form['warning'] = [
          '#markup' => $this->t('User @name cannot be deleted as it is currently the sole owner of these collections:', [
            '@name' => $this->entity->getAccountName(),
          ]),
          '#weight' => -10,
        ];
      }

      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
