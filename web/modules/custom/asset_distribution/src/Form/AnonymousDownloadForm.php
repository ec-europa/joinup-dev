<?php

namespace Drupal\asset_distribution\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\EventSubscriber\AjaxResponseSubscriber;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail address'),
      '#description' => $this->t('Your e-mail address as <em>john.doe@domain.com</em>'),
      '#maxlength' => 180,
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'wrapper' => $form['#id'],
      ],
    ];
    // The cancel button is rendered after the submit button to leave the latter
    // as default action.
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
