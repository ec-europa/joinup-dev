<?php

namespace Drupal\joinup_invite\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\og\Entity\OgRole;
use Drupal\og\MembershipManager;
use Drupal\og\OgAccessInterface;
use Drupal\rdf_entity\UriEncoder;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class InviteForm.
 */
class InviteForm extends FormBase {

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The og membership manager service.
   *
   * @var \Drupal\og\MembershipManager
   */
  protected $ogMembershipManager;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a new InviteForm object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManager $og_membership_manager
   *   The og membership manager service.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   */
  public function __construct(CurrentRouteMatch $current_route_match, EntityTypeManager $entity_type_manager, MembershipManager $og_membership_manager, OgAccessInterface $og_access) {
    $this->currentRouteMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->ogMembershipManager = $og_membership_manager;
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('og.membership_manager'),
      $container->get('og.access')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invite_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = $this->currentRouteMatch->getRouteObject()->getOption('_og_entity_type_id');
    $group = $this->currentRouteMatch->getParameter($entity_type_id);
    $form_state->set('group', $group);

    $form['filter_container'] = [
      '#type' => 'container',
      '#title' => t('Search Users'),
      '#weight' => 0,
    ];

    $form['filter_container']['email'] = [
      '#type' => 'textfield',
      '#title' => t('Email or name'),
      '#default_value' => $form_state->getValue('email') ?: '',
      '#autocomplete_route_name' => 'joinup_invite.user_auto_complete',
      '#autocomplete_route_parameters' => [
        '_og_entity_type_id' => $group->getEntityTypeId(),
        $group->getEntityTypeId() => UriEncoder::encodeUrl($group->id()),
      ],
      '#weight' => 0,
    ];

    $form['filter_container']['filter_submit'] = [
      '#type' => 'submit',
      '#value' => 'Filter',
      '#submit' => ['::filterSubmit'],
      '#weight' => 1,
    ];

    if ($filter = $form_state->getValue('email')) {
      $form['results_container'] = [
        '#type' => 'container',
        '#title' => t('Results'),
        '#weight' => 1,
      ];

      $form['results_container']['users'] = [
        '#type' => 'tableselect',
        '#title' => t('Users'),
        '#header' => $this->getHeader(),
        '#options' => $this->getRows($filter),
        '#multiple' => TRUE,
        '#js_select' => FALSE,
        '#attributes' => [
          'class' => ['tableheader-processed'],
        ],
        '#empty' => 'No users found.',
        '#weight' => 15,
      ];

      $form['results_container']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Add facilitators'),
        '#weight' => 20,
      ];
    }

    return $form;
  }

  /**
   * Returns the header of the main table.
   *
   * @return array
   *   The header.
   */
  protected function getHeader() {
    return [['data' => 'Personal Info']];
  }

  /**
   * Return the rows.
   *
   * @param string $filter
   *   The filter.
   *
   * @return array
   *   The rows of the tableselect.
   */
  private function getRows($filter) {
    if ($user = user_load_by_mail($filter)) {
      $users = [$user];
    }
    else {
      $results = $this->entityTypeManager->getStorage('user')->getQuery('OR')
        ->condition('mail', '%' . $filter . '%', 'LIKE')
        ->condition('field_user_first_name', '%' . $filter . '%', 'LIKE')
        ->condition('field_user_family_name', '%' . $filter . '%', 'LIKE')
        ->execute();
      /** @var \Drupal\user\UserInterface[] $users */
      $users = User::loadMultiple($results);
    }

    $rows = [];
    foreach ($users as $user) {
      $name = $this->getAccountName($user);
      $rows[$user->id()] = [
        'title' => ['data' => ['#title' => $name]],
        0 => $name,
      ];
    }
    return $rows;
  }

  /**
   * Returns a full name of the user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user object.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A string version of user's full name.
   */
  protected function getAccountName(UserInterface $user) {
    $first_name = empty($user->get('field_user_first_name')->first()->value) ? '' : $user->get('field_user_first_name')->first()->value;
    $family_name = empty($user->get('field_user_family_name')->first()->value) ? '' : $user->get('field_user_family_name')->first()->value;

    return $this->t('@name (@email)', [
      '@name' => implode(' ', [$first_name, $family_name]),
      '@email' => $user->getEmail(),
    ]);
  }

  /**
   * Form submission handler for the filter submit.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function filterSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $users = $form_state->getValue('users');
    $group = $form_state->get('group');
    $facilitator_role = OgRole::loadByGroupAndName($group, 'facilitator');

    foreach ($users as $uid) {
      $user = User::load($uid);
      $membership = $this->ogMembershipManager->getMembership($group, $user);
      if (empty($membership)) {
        $membership = $this->ogMembershipManager->createMembership($group, $user);
      }
      $membership->addRole($facilitator_role);
      $membership->save();
    }

    $form_state->setRedirect('entity.rdf_entity.member_overview', [
      'rdf_entity' => $group->id(),
    ]);
  }

}
