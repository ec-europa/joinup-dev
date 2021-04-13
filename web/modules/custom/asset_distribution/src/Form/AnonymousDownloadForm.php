<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Retrieves the e-mail from anonymous users that are downloading distributions.
 */
class AnonymousDownloadForm extends FormBase {

  /**
   * The RDF entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $sparqlStorage;

  /**
   * The download_event entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $eventStorage;

  /**
   * The file usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * The file being downloaded.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * Instantiates a new AnonymousDownloadForm object.
   *
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $sparql_storage
   *   The RDF entity storage.
   * @param \Drupal\Core\Entity\ContentEntityStorageInterface $event_storage
   *   The download event entity storage.
   * @param \Drupal\file\FileUsage\FileUsageInterface $file_usage
   *   The file usage service.
   */
  public function __construct(SparqlEntityStorageInterface $sparql_storage, ContentEntityStorageInterface $event_storage, FileUsageInterface $file_usage) {
    $this->sparqlStorage = $sparql_storage;
    $this->eventStorage = $event_storage;
    $this->fileUsage = $file_usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('rdf_entity'),
      $container->get('entity_type.manager')->getStorage('download_event'),
      $container->get('file.usage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_download_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?FileInterface $file = NULL) {
    $this->file = $file;

    $form['#id'] = Html::getId($this->getFormId());

    $form['notice'] = [
      '#markup' => $this->t('If you do not have a Joinup account, please take some time to create one, at <a href=":register">this page</a>. It will allow you to fully exploit the functionalities of Joinup to create new content, contribute to existing one and collaborate with other users.<br />If you do not want to create a Joinup account, please select any of the following options and provide your email address if you want to be kept updated on the status of the solution. Your personal data will be treated in compliance with the <a href=":legal">legal notice</a>', [
        ':register' => Url::fromRoute('user.register')->toString(),
        ':legal' => Url::fromRoute('entity.entity_legal_document.canonical', ['entity_legal_document' => 'legal_notice'])->toString(),
      ]),
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail address'),
      '#description' => $this->t('Your e-mail address as <em>john.doe@domain.com</em>'),
      '#maxlength' => 180,
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#value' => $this->t('No thanks') ,
      // This button will be used only to close the modal, so it doesn't have
      // any submit callback.
      '#submit' => [],
      '#attributes' => [
        'class' => ['dialog-cancel'],
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        // Enable ajax submit for the whole form by specifying the wrapper.
        // Since the submit callback returns an ajax response, there is no need
        // to provide an ajax callback. The wrapper will be used in case of
        // validation errors.
        'wrapper' => $form['#id'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Prevent any non-Javascript submits.
    if (!$this->getRequest()->request->get(AjaxResponseSubscriber::AJAX_REQUEST_PARAMETER)) {
      throw new BadRequestHttpException();
    }

    $usages = $this->fileUsage->listUsage($this->file);

    // Normally, only one distribution is allowed to use a file and only
    // distributions call this code.
    if (empty($usages['file']['rdf_entity'])) {
      throw new \RuntimeException('No distributions were found using the file with ID ' . $file->id());
    }
    if (count($usages['file']['rdf_entity']) > 1) {
      throw new \RuntimeException('More than one distributions were found for the file with ID ' . $file->id());
    }
    $distribution = $this->sparqlStorage->load(key($usages['file']['rdf_entity']));

    /** @var \Drupal\solution\Entity\SolutionInterface|\Drupal\asset_release\Entity\AssetReleaseInterface $parent */
    $parent = $distribution->getParent();
    $event = $this->eventStorage->create([
      'uid' => 0,
      'mail' => $form_state->getValue('email'),
      'file' => $this->file->id(),
      'parent_entity_type' => $parent->getEntityTypeId(),
      'parent_entity_id' => $parent->id(),
    ]);
    $event->save();

    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $form_state->setResponse($response);
  }

}
