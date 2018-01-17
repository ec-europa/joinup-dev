<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\og\MembershipManagerInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to add a member with a certain role in a rdf entity group.
 */
class InviteToGroupForm extends InviteFormBase {

  /**
   * The group where to invite users.
   *
   * @var \Drupal\rdf_entity\RdfInterface
   */
  protected $rdfEntity;

  /**
   * The og membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $ogMembershipManager;

  /**
   * Constructs a new InviteToGroupForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManagerInterface $og_membership_manager
   *   The og membership manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MembershipManagerInterface $og_membership_manager) {
    parent::__construct($entityTypeManager);

    $this->ogMembershipManager = $og_membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invite_to_group_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getSubmitButtonText(): TranslatableMarkup {
    return $this->t('Add members');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelButtonUrl(): Url {
    return new Url('entity.rdf_entity.member_overview', [
      'rdf_entity' => $this->rdfEntity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RdfInterface $rdf_entity = NULL) {
    $this->rdfEntity = $rdf_entity;

    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Role'),
      '#required' => TRUE,
      '#options' => [
        'member' => $this->t('Member'),
        'facilitator' => $this->t('Facilitator'),
      ],
      '#default_value' => 'member',
    ];

    return parent::build($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $users = $this->getUserList($form_state);
    $role_id = implode('-', [
      $this->rdfEntity->getEntityTypeId(),
      $this->rdfEntity->bundle(),
      $form_state->getValue('role'),
    ]);
    $role = $this->entityTypeManager->getStorage('og_role')->load($role_id);

    foreach ($users as $user) {
      $membership = $this->ogMembershipManager->getMembership($this->rdfEntity, $user);
      if (empty($membership)) {
        $membership = $this->ogMembershipManager->createMembership($this->rdfEntity, $user);
      }
      $membership->addRole($role);
      $membership->save();
    }

    drupal_set_message($this->t('Successfully added the role %role to the selected users.', [
      '%role' => $role->label(),
    ]));
    $form_state->setRedirect('entity.rdf_entity.member_overview', [
      'rdf_entity' => $this->rdfEntity->id(),
    ]);
  }

}
