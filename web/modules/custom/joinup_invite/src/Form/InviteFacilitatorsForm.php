<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\og\MembershipManager;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to add a user as facilitator of a rdf entity group.
 */
class InviteFacilitatorsForm extends InviteFormBase {

  /**
   * The og membership manager service.
   *
   * @var \Drupal\og\MembershipManager
   */
  protected $ogMembershipManager;

  /**
   * Constructs a new InviteForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManager $og_membership_manager
   *   The og membership manager service.
   */
  public function __construct(EntityTypeManager $entity_type_manager, MembershipManager $og_membership_manager) {
    parent::__construct($entity_type_manager);

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
    return 'invite_facilitators_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getSubmitButtonText() : TranslatableMarkup {
    return $this->t('Add facilitators');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RdfInterface $rdf_entity = NULL) {
    $form_state->set('group', $rdf_entity);
    return parent::build($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $users = array_filter($form_state->getValue('users'));
    $group = $form_state->get('group');
    $role_id = $group->getEntityTypeId() . '-' . $group->bundle() . '-facilitator';
    $facilitator_role = $this->entityTypeManager->getStorage('og_role')->load($role_id);

    foreach ($users as $uid) {
      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      $membership = $this->ogMembershipManager->getMembership($group, $user);
      if (empty($membership)) {
        $membership = $this->ogMembershipManager->createMembership($group, $user);
      }
      $membership->addRole($facilitator_role);
      $membership->save();
    }

    drupal_set_message($this->t('Your settings have been saved.'), 'status', TRUE);
    $form_state->setRedirect('entity.rdf_entity.member_overview', [
      'rdf_entity' => $group->id(),
    ]);
  }

}
