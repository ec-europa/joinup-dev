<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\joinup_invite\InvitationMessageHelperInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to add a member with a certain role in a rdf entity group.
 */
abstract class GroupFormBase extends InviteFormBase {

  /**
   * The OG role to assign if the invitation is accepted.
   *
   * @var \Drupal\og\OgRoleInterface
   */
  protected $role;

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
   * @param \Drupal\joinup_invite\InvitationMessageHelperInterface $invitation_message_helper
   *   The invitation message helper service.
   * @param \Drupal\og\MembershipManagerInterface $og_membership_manager
   *   The og membership manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, InvitationMessageHelperInterface $invitation_message_helper, MembershipManagerInterface $og_membership_manager) {
    parent::__construct($entityTypeManager, $invitation_message_helper);
    $this->ogMembershipManager = $og_membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('joinup_invite.invitation_message_helper'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getCancelButtonUrl(): Url {
    return new Url('entity.rdf_entity.member_overview', [
      'rdf_entity' => $this->entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?RdfInterface $rdf_entity = NULL) {
    $this->entity = $rdf_entity;
    $form = parent::build($form, $form_state);

    $form['role'] = [
      '#type' => 'select',
      '#title' => $this->t('Role'),
      '#required' => TRUE,
      '#options' => [
        'member' => $this->t('Member'),
        'author' => $this->t('Author'),
        'facilitator' => $this->t('Facilitator'),
      ],
      '#default_value' => 'member',
    ];

    return $form;
  }

}
