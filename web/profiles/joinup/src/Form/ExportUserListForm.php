<?php

declare(strict_types = 1);

namespace Drupal\joinup\Form;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\csv_serialization\Encoder\CsvEncoder;
use Drupal\joinup_user\EntityAuthorshipHelperInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a form to export the list of users to CSV.
 */
class ExportUserListForm extends FormBase {

  /**
   * Defines the fields that will be exported, with their i/o processors.
   */
  protected const EXPORTED_FIELDS = [
    'Username' => [
      'input' => ['getFieldValues', ['name']],
      'output' => ['getFirstValue', 'formatString'],
    ],
    'First name' => [
      'input' => ['getFieldValues', ['field_user_first_name']],
      'output' => ['getFirstValue', 'formatString'],
    ],
    'Surname' => [
      'input' => ['getFieldValues', ['field_user_family_name']],
      'output' => ['getFirstValue', 'formatString'],
    ],
    'Email' => [
      'input' => ['getFieldValues', ['mail']],
      'output' => ['getFirstValue', 'formatString'],
    ],
    'Status' => [
      'input' => ['getFieldValues', ['status']],
      'output' => ['getFirstValue', 'formatStatus'],
    ],
    'Roles' => [
      'input' => ['getFieldValues', ['roles']],
      'output' => ['formatRoles'],
    ],
    'Registration' => [
      'input' => ['getFieldValues', ['created']],
      'output' => ['getFirstValue', 'formatDate'],
    ],
    'Last access' => [
      'input' => ['getFieldValues', ['access']],
      'output' => ['getFirstValue', 'formatDate'],
    ],
    'Author' => [
      'input' => ['getIsAuthor', []],
      'output' => ['formatBoolean'],
    ],
    'Profile' => [
      'input' => ['getFieldValues', ['uid']],
      'output' => ['getFirstValue', 'formatProfileLink'],
    ],
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity authorship helper service.
   *
   * @var \Drupal\joinup_user\EntityAuthorshipHelperInterface
   */
  protected $entityAuthorshipHelper;

  /**
   * The filesystem service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * Constructs an ExportUserListForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\joinup_user\EntityAuthorshipHelperInterface $entityAuthorshipHelper
   *   The entity authorship helper service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The filesystem service.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfTokenGenerator
   *   The CSRF token generator service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityAuthorshipHelperInterface $entityAuthorshipHelper, FileSystemInterface $fileSystem, CsrfTokenGenerator $csrfTokenGenerator) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityAuthorshipHelper = $entityAuthorshipHelper;
    $this->fileSystem = $fileSystem;
    $this->csrfTokenGenerator = $csrfTokenGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('joinup_user.entity_authorship_helper'),
      $container->get('file_system'),
      $container->get('csrf_token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'joinup_export_user_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filename'),
      '#default_value' => 'userlist-' . date('Y-m-d') . '.csv',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve all user IDs, but exclude the anonymous user.
    $user_ids = $this->getUserStorage()->getQuery()->execute();
    unset($user_ids[0]);

    // Split up the work in batches of 250 users.
    $form_class = $this;
    $batch_operations = array_map(function ($user_ids_batch) use ($form_class) {
      return [[$form_class, 'collectUserData'], [$user_ids_batch]];
    }, array_chunk($user_ids, 250));

    // After the batch process is finished we will redirect to a controller that
    // will serve the CSV file as a download. We need to pass the filename to
    // use in the download in the session.
    // @see batch_set()
    $_SESSION['export_filename'] = $form_state->getValue('filename');
    $batch = [
      'title' => 'Exporting user list',
      'init_message' => 'Starting collection of user data.',
      'operations' => $batch_operations,
      'finished' => [$this, 'redirectToDownload'],
    ];
    batch_set($batch);
  }

  /**
   * Batch command to collect data from user accounts to include in the report.
   *
   * @param string[] $user_ids
   *   A batch of user IDs to process.
   * @param array $context
   *   The batch context.
   */
  public function collectUserData(array $user_ids, array &$context): void {
    $results = [];
    foreach ($this->getUserStorage()->loadMultiple($user_ids) as $user) {
      foreach (self::EXPORTED_FIELDS as $id => $field) {
        // Get the data from the input processor.
        list($method, $arguments) = $field['input'];
        array_unshift($arguments, $user);
        $results[$user->id()][$id] = $this->$method(...$arguments);
      }
    }

    // Compile the results as a table.
    $headers = array_keys(self::EXPORTED_FIELDS);
    $rows = [];
    foreach ($results as $result) {
      $columns = [];
      foreach (self::EXPORTED_FIELDS as $id => $field) {
        // Run the data through the output processors.
        $output = $result[$id];
        foreach ($field['output'] as $method) {
          $output = $this->$method($output);
        }
        $columns[] = $output;
      }
      $rows[] = array_combine($headers, $columns);
    }

    // Convert the data into CSV format.
    $data = (new CsvEncoder())->encode($rows, 'csv');

    // Create a temporary file to store the result in, if it doesn't exist yet.
    if (empty($context['results'])) {
      $context['results'] = [
        $this->fileSystem->tempnam('temporary://', 'file'),
      ];
    }
    // If the file has been created in a previous batch this means the CSV
    // headers have already been output. Strip them from the result so they are
    // not duplicated in the file.
    else {
      $data = preg_replace('/^.+\n/', "\n", $data);
    }

    if (file_put_contents($context['results'][0], $data, FILE_APPEND) === FALSE) {
      $this->messenger()->addError($this->t('The CSV file could not be created.'));
      throw new HttpException(500);
    }
  }

  /**
   * Batch finished callback. Formats the user list as a CSV file.
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
  public function redirectToDownload(bool $success, array $results, array $operations): Response {
    if (!$success) {
      $this->messenger()->addError('An error occurred during the export of the user data.');
      throw new HttpException(500);
    }

    // Redirect to a controller that will serve the CSV file as a download. We
    // need to pass the temporary file that contains the export in a session
    // variable. Also pass a CSRF token to protect against session tampering.
    // @see batch_set()
    $_SESSION['temp_filename'] = $results[0];
    $_SESSION['csrf_token'] = $this->csrfTokenGenerator->get($results[0]);
    return new RedirectResponse(Url::fromRoute('joinup.export_user_list_download')->toString());
  }

  /**
   * Input processor; returns the content of the given field on the user entity.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to check.
   * @param string $field_name
   *   The field name for which to return the content.
   *
   * @return array
   *   An array containing the field content.
   */
  protected function getFieldValues(UserInterface $user, string $field_name): array {
    $values = [];
    foreach ($user->get($field_name) as $field_item) {
      $values[] = $field_item->getValue();
    }
    return $values;
  }

  /**
   * Input processor; returns whether the user is an author.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to check.
   *
   * @return bool
   *   TRUE if the user has created any published community content or RDF
   *   content.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if an error occurred during the retrieval of the authorship
   *   information from Search API.
   */
  protected function getIsAuthor(UserInterface $user): bool {
    return !empty($this->entityAuthorshipHelper->getEntityIdsAuthoredByUser($user->id(), ['published']));
  }

  /**
   * Output processor; returns the first value of a multivalue array.
   *
   * @param array $value
   *   The multivalue array.
   *
   * @return mixed
   *   The first value.
   */
  protected function getFirstValue(array $value) {
    return reset($value)['value'];
  }

  /**
   * Output processor; returns the passed in value as a simple string.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string
   *   The value as a string.
   */
  protected function formatString($value): string {
    return (string) $value;
  }

  /**
   * Output processor; returns the passed in value as a status string.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string
   *   Either 'active', or 'blocked'.
   */
  protected function formatStatus($value): string {
    return $value ? 'active' : 'blocked';
  }

  /**
   * Output processor; returns a comma separated list of roles.
   *
   * @param array $values
   *   The values.
   *
   * @return string
   *   A comma separated list of roles.
   */
  protected function formatRoles(array $values): string {
    $values = array_map(function (array $value) {
      return $value['target_id'];
    }, $values);
    sort($values);
    return implode(',', $values);
  }

  /**
   * Output processor; returns a formatted date.
   *
   * @param mixed $value
   *   The value.
   *
   * @return string
   *   The date, or 'N/A' if no value was passed.
   */
  protected function formatDate($value): string {
    if (!$value) {
      return 'N/A';
    }

    return date('Y-m-d h:i:s', (int) $value);
  }

  /**
   * Output processor; returns the passed in value as a boolean Yes/No.
   *
   * @param bool $value
   *   The value.
   *
   * @return string
   *   Either 'Yes', or 'No'.
   */
  protected function formatBoolean(bool $value): string {
    return $value ? 'Yes' : 'No';
  }

  /**
   * Output processor; returns a link to the user profile.
   *
   * @param bool $value
   *   The user ID.
   *
   * @return string
   *   An absolute URL to the user profile.
   */
  protected function formatProfileLink($value): string {
    return Url::fromRoute('entity.user.canonical', ['user' => (int) $value])->setAbsolute()->toString();
  }

  /**
   * Returns the user storage.
   *
   * @return \Drupal\user\UserStorageInterface
   *   The user storage.
   */
  protected function getUserStorage(): UserStorageInterface {
    return $this->entityTypeManager->getStorage('user');
  }

}
