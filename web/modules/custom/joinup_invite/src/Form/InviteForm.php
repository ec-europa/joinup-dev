<?php

namespace Drupal\joinup_invite\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\og\MembershipManager;
use Drupal\og\OgAccessInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\rdf_entity\UriEncoder;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to add a user as facilitator of a rdf entity group.
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
  public function buildForm(array $form, FormStateInterface $form_state, RdfInterface $rdf_entity = NULL) {
    $form_state->set('group', $rdf_entity);

    $form['filter_container'] = [
      '#type' => 'container',
      '#title' => $this->t('Search Users'),
    ];

    $form['filter_container']['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email or name'),
      '#default_value' => $form_state->getValue('email') ?: '',
      '#autocomplete_route_name' => 'joinup_invite.user_auto_complete',
      '#autocomplete_route_parameters' => [
        '_og_entity_type_id' => $rdf_entity->getEntityTypeId(),
        $rdf_entity->getEntityTypeId() => UriEncoder::encodeUrl($rdf_entity->id()),
      ],
    ];

    $form['filter_container']['filter_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#name' => 'op_filter',
      '#submit' => ['::filterSubmit'],
    ];

    if ($filter = $form_state->getValue('email')) {
      $form['results_container'] = [
        '#type' => 'container',
        '#title' => $this->t('Results'),
      ];

      $rows = $this->getRows($filter);
      $form['results_container']['users'] = [
        '#type' => 'tableselect',
        '#title' => $this->t('Users'),
        '#header' => $this->getHeader(),
        '#options' => $rows,
        '#multiple' => TRUE,
        '#js_select' => FALSE,
        '#attributes' => [
          'class' => ['tableheader-processed'],
        ],
        '#empty' => $this->t('No users found.'),
      ];

      if (!empty($rows)) {
        $form['results_container']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Add facilitators'),
        ];
      }
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
    return [['data' => $this->t('Personal Info')]];
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
  protected function getRows($filter) {
    if ($user = user_load_by_mail($filter)) {
      $users = [$user];
    }
    else {
      $results = $this->entityTypeManager->getStorage('user')->getQuery('OR')
        ->condition('mail', $filter, 'CONTAINS')
        ->condition('field_user_first_name', $filter, 'CONTAINS')
        ->condition('field_user_family_name', $filter, 'CONTAINS')
        ->execute();
      /** @var \Drupal\user\UserInterface[] $users */
      $users = $this->entityTypeManager->getStorage('user')->loadMultiple($results);
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $triggering_element = $form_state->getTriggeringElement();
    $users = empty($form_state->getValue('users')) ? [] : array_filter($form_state->getValue('users'));
    if ($triggering_element['#name'] !== 'op_filter' && empty($users)) {
      $form_state->setErrorByName('users', $this->t('You must select at least one user'));
    }
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
