<?php

declare(strict_types = 1);

namespace Drupal\joinup_acceptance\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\message_digest\Traits\MessageDigestTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form allowing to deliver digest messages.
 */
class DeliverDigestMessagesForm extends FormBase {

  use MessageDigestTrait;

  /**
   * The queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The queue manager.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * Constructs a new form instance.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue service.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_manager
   *   The queue manager.
   */
  public function __construct(QueueFactory $queue_factory, QueueWorkerManagerInterface $queue_manager) {
    $this->queueFactory = $queue_factory;
    $this->queueManager = $queue_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('queue'),
      $container->get('plugin.manager.queue_worker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['deliver'] = [
      '#type' => 'submit',
      '#value' => $this->t('Deliver message digests'),
      '#submit' => [[$this, 'deliverDigestMessages']],
    ];
    return $form;
  }

  /**
   * Provides a submit handler for digest messages delivery button.
   *
   * @param array $form
   *   The form API render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state API object.
   */
  public function deliverDigestMessages(array &$form, FormStateInterface $form_state): void {
    $this->expireDigestMessages();
    do {
      $this->expireMessageDigestNotifiers();
      message_digest_cron();
      $this->processQueue();
    } while ($this->countAllUndeliveredDigestMessages());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'deliver_digest_messages';
  }

  /**
   * Processes 'message_digest' queue.
   */
  protected function processQueue() {
    $this->queueFactory->get('message_digest')->createQueue();
    $queue_worker = $this->queueManager->createInstance('message_digest');
    $queue = $this->queueFactory->get('message_digest');
    while ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (RequeueException $e) {
        // The worker requested the task be immediately requeued.
        $queue->releaseItem($item);
      }
      catch (SuspendQueueException $e) {
        // If the worker indicates there is a problem with the whole queue,
        // release the item and skip to the next queue.
        $queue->releaseItem($item);
        watchdog_exception('message_digest', $e);
      }
      catch (\Exception $e) {
        // In case of any other kind of exception, log it and leave the item
        // in the queue to be processed again later.
        watchdog_exception('message_digest', $e);
      }
    }
  }

}
