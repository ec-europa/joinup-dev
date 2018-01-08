<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\og\MembershipManagerInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to add a member with a certain role in a rdf entity group.
 */
class InviteToGroupForm extends InviteFormBase {

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
  protected function getSubmitButtonText() : TranslatableMarkup {
    return $this->t('Add members');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RdfInterface $rdf_entity = NULL) {
    $form_state->set('group', $rdf_entity);

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
    $group = $form_state->get('group');
    $role_id = implode('-', [
      $group->getEntityTypeId(),
      $group->bundle(),
      $form_state->getValue('role'),
    ]);
    $role = $this->entityTypeManager->getStorage('og_role')->load($role_id);

    foreach ($users as $user) {
      $membership = $this->ogMembershipManager->getMembership($group, $user);
      if (empty($membership)) {
        $membership = $this->ogMembershipManager->createMembership($group, $user);
      }
      $membership->addRole($role);
      $membership->save();
    }

    drupal_set_message($this->t('Your settings have been saved.'), 'status', TRUE);
    $form_state->setRedirect('entity.rdf_entity.member_overview', [
      'rdf_entity' => $group->id(),
    ]);
  }

}
