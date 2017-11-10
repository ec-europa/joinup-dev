<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for forms that allow to search for users to invite.
 */
abstract class InviteFormBase extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new InviteFormBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $form, FormStateInterface $form_state) {
    $form['filter_container'] = [
      '#type' => 'container',
      '#title' => $this->t('Search Users'),
    ];

    $form['filter_container']['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email or name'),
      '#default_value' => $form_state->getValue('email') ?: '',
      '#autocomplete_route_name' => 'joinup_invite.user_auto_complete',
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
          '#value' => $this->getSubmitButtonText(),
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
  protected function getHeader() : array {
    return [
      'name' => [
        'data' => $this->t('User'),
      ],
    ];
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
  protected function getRows(string $filter) : array {
    if ($user = user_load_by_mail($filter)) {
      $users = [$user];
    }
    else {
      $results = $this->entityTypeManager->getStorage('user')->getQuery('OR')
        ->condition('mail', $filter, 'CONTAINS')
        ->condition('field_user_first_name', $filter, 'CONTAINS')
        ->condition('field_user_family_name', $filter, 'CONTAINS')
        ->sort('field_user_first_name')
        ->sort('field_user_family_name')
        ->range(0, 50)
        ->execute();
      /** @var \Drupal\user\UserInterface[] $users */
      $users = $this->entityTypeManager->getStorage('user')->loadMultiple($results);
    }

    $rows = [];
    foreach ($users as $user) {
      $name = $this->getAccountName($user);
      $rows[$user->id()] = [
        'title' => ['data' => ['#title' => $name]],
        'name' => $name,
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
   * Returns a full name of the user with his email as suffix in parenthesis.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user object.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A string version of user's full name.
   */
  protected function getAccountName(UserInterface $user) : TranslatableMarkup {
    return $this->t('@name (@email)', [
      '@name' => $user->get('full_name')->value,
      '@email' => $user->getEmail(),
    ]);
  }

  /**
   * Returns the text for the form submit button.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The button text.
   */
  abstract protected function getSubmitButtonText() : TranslatableMarkup;

}
