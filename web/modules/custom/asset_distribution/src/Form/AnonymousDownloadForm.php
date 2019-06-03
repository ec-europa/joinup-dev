<?php

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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Retrieves the e-mail from anonymous users that are downloading distributions.
 */
class AnonymousDownloadForm extends FormBase {

  /**
   * The download_event entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $eventStorage;

  /**
   * The file being downloaded.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * Instantiates a new AnonymousDownloadForm object.
   *
   * @param \Drupal\Core\Entity\ContentEntityStorageInterface $event_storage
   *   The download event entity storage.
   */
  public function __construct(ContentEntityStorageInterface $event_storage) {
    $this->eventStorage = $event_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('download_event')
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
  public function buildForm(array $form, FormStateInterface $form_state, FileInterface $file = NULL) {
    $this->file = $file;

    $form['#id'] = Html::getId($this->getFormId());

    $form['notice'] = [
      '#markup' => $this->t('If you do not have a Joinup account, please take some time to create one, at <a href=":register">this page</a>. It will allow you to fully exploit the functionalities of Joinup to create new content, contribute to existing one and collaborate with other users.<br />If you do not want to create a Joinup account, please select any of the following options and provide your email address if you want to be kept updated on the status of the solution. Your personal data will be treated in compliance with the <a href=":legal">legal notice</a>', [
        ':register' => Url::fromRoute('user.register')->toString(),
        ':legal' => Url::fromRoute('page_manager.page_view_legal_notice_legal_notice-block_display-0')->toString(),
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

    $event = $this->eventStorage->create([
      'uid' => 0,
      'mail' => $form_state->getValue('email'),
      'file' => $this->file->id(),
    ]);
    $event->save();

    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $form_state->setResponse($response);
  }

}
