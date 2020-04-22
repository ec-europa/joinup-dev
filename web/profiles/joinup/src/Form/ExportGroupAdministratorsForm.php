<?php

declare(strict_types = 1);

namespace Drupal\joinup\Form;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\csv_serialization\Encoder\CsvEncoder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a form to export the list of group facilitators and administrators.
 */
class ExportGroupAdministratorsForm extends FormBase {

  const GROUP_ADMINISTRATION_CACHE_TAG = 'group_administration_list';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'joinup_export_group_administrators';
  }

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The default cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * Constructs an ExportUserListForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default cache bin.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\Time $time
   *   The time service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, CacheBackendInterface $cache, DateFormatter $date_formatter, Time $time) {
    $this->entityTypeManager = $entityTypeManager;
    $this->cacheStorage = $cache;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('cache.default'),
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['help'] = [
      '#markup' => $this->t('Press generate data to generate the list.'),
    ];

    $form['actions'] = [
      '#type' => 'container',
    ];

    if ($data = $this->cacheStorage->get(self::GROUP_ADMINISTRATION_CACHE_TAG)) {
      $form['help']['#markup'] = $this->t('Last dump was created at :date and will be removed by :expire', [
        ':date' => $this->dateFormatter->format($data->created),
        ':expire' => $this->dateFormatter->format($data->expire),
      ]);

      $form['actions']['download_link'] = [
        '#type' => 'submit',
        '#value' => $this->t('Download'),
        '#submit' => ['::downloadCsv'],
      ];

      $form_state->set('download_data', array_values($data->data));
    }

    $button_value = empty($data) ? 'Generate data' : 'Regenerate data';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t($button_value),
      '#submit' => ['::generateData'],
    ];

    return $form;
  }

  /**
   * Generated the user list data in a batch process.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function generateData(array &$form, FormStateInterface $form_state) {
    $membership_query = $this->entityTypeManager->getStorage('og_membership')->getQuery();
    $membership_or = $membership_query->orConditionGroup()
      ->condition('roles', 'rdf_entity-collection-facilitator')
      ->condition('roles', 'rdf_entity-solution-facilitator');
    $membership_ids = $this->entityTypeManager->getStorage('og_membership')
      ->getQuery()
      // We are only exporting facilitators and administrators but all
      // administrators are also facilitators. Adding a condition that the
      // membership must have the facilitator role means that administrators are
      // also retrieved.
      ->condition($membership_or)
      ->condition('state', 'active')
      ->execute();

    $batch_operations = [];
    foreach ($membership_ids as $membership_id) {
      $batch_operations[] = [
        [$this, 'collectMembershipData'],
        [$membership_id],
      ];
    }

    if ($batch_operations) {
      $batch = [
        'title' => 'Exporting group administrators list',
        'init_message' => 'Starting collection of user data.',
        'operations' => $batch_operations,
        'finished' => [$this, 'storeToCache'],
      ];
      batch_set($batch);
    }
  }

  /**
   * Downloads the CSV file of the user list.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function downloadCsv(array &$form, FormStateInterface $form_state) {
    $csv_encoder = new CsvEncoder();
    $data = $csv_encoder->encode($form_state->get('download_data'), 'csv');

    $headers = [
      'Content-Type' => 'text/csv',
      'Content-Disposition' => 'attachment;filename=user_list.csv',
    ];

    $response = new Response($data, 200, $headers);
    $form_state->setResponse($response);
  }

  /**
   * Process a membership and collect necessary data.
   *
   * @param string $membership_id
   *   The membership id that will be processed during the batch operation.
   * @param array $context
   *   The batch context.
   */
  public function collectMembershipData(string $membership_id, array &$context): void {
    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = $this->entityTypeManager->getStorage('og_membership')->load($membership_id);
    $group = $membership->getGroup();
    $user = $membership->getOwner();
    if (empty($group) || empty($user)) {
      // The group and/or the user are not available which means that the
      // membership is orphaned and will be removed in the next cron run.
      return;
    }

    $user_name = joinup_user_get_display_name($user);
    $user_url = $user->toUrl()->setAbsolute()->toString();
    $group_label = $group->label();
    $group_url = $group->toUrl()->setAbsolute()->toString();
    $is_administrator = (string) ($membership->hasRole("rdf_entity-{$group->bundle()}-administrator") ? $this->t('Yes') : $this->t('No'));

    $context['results']['data'][$membership->id()] = [
      'User name' => $user_name,
      'User url' => $user_url,
      'User email' => $user->getEmail(),
      'Group ID' => $group->id(),
      'Group label' => $group_label,
      'Group url' => $group_url,
      'Is administrator' => $is_administrator,
    ];
  }

  /**
   * Stores the data into the cache.
   *
   * @param bool $success
   *   Whether the batch ended without a fatal error.
   * @param array $results
   *   The result set.
   * @param array $operations
   *   The remaining operations.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A redirect response to the page that serves the CSV file as a download.
   */
  public function storeToCache(bool $success, array $results, array $operations): Response {
    // Set the expiration time to 1 day.
    $expire = $this->time->getRequestTime() + 3600 * 24;

    // The cache entry varies per the og membership list, the user list and the
    // rdf entity list cache tag.
    $this->cacheStorage->set(self::GROUP_ADMINISTRATION_CACHE_TAG, $results['data'], $expire);
    $this->messenger()->addMessage('Data have been rebuilt.');

    // Handle the file here.
    return new RedirectResponse(Url::fromRoute('joinup.group_administrators_export')->toString());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
