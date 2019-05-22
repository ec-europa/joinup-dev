<?php

declare(strict_types = 1);

namespace Drupal\oe_newsroom_newsletter\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\oe_newsroom_newsletter\Exception\BadResponseException;
use Drupal\oe_newsroom_newsletter\Exception\EmailAddressAlreadySubscribedException;
use Drupal\oe_newsroom_newsletter\Exception\InvalidEmailAddressException;
use Drupal\oe_newsroom_newsletter\SubscriberFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form that allows a user to subscribe to a newsletter.
 */
class NewsletterSubscribeForm extends FormBase {

  /**
   * The subscriber factory.
   *
   * @var \Drupal\oe_newsroom_newsletter\SubscriberFactoryInterface
   */
  protected $subscriberFactory;

  /**
   * Constructs a NewsletterSubscribeForm.
   */
  public function __construct(SubscriberFactory $subscriberFactory) {
    $this->subscriberFactory = $subscriberFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('oe_newsroom_newsletter.subscriber_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oe_newsroom_newsletter_subscribe';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $universe = '', string $service_id = '') {
    $form['#prefix'] = '<div id="newsroom-newsletter-subscription-form">';
    $form['#suffix'] = '</div>';

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail address'),
      '#required' => TRUE,
      '#default_value' => $this->currentUser()->getEmail(),
    ];

    $form['universe'] = [
      '#type' => 'value',
      '#value' => $universe,
    ];

    $form['service_id'] = [
      '#type' => 'value',
      '#value' => $service_id,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Subscribe'),
        '#ajax' => [
          'callback' => '::submitFormCallback',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $universe = $form_state->getValue('universe');
    $service_id = $form_state->getValue('service_id');
    try {
      $this->subscriberFactory->get()->subscribe($email, $universe, $service_id);
      $this->messenger()->addStatus($this->t('Thank you for subscribing to our newsletter.'));
      $this->getLogger('oe_newsroom_newsletter')->info('@email subscribed to the newsletter with service ID @service_id and universe @universe.', [
        '@email' => $email,
        '@universe' => $universe,
        '@service_id' => $service_id,
      ]);
    }
    catch (BadResponseException $e) {
      $this->messenger()->addError($this->t('An error occurred. Please try again later.'));
      $this->getLogger('oe_newsroom_newsletter')->error('Exception thrown while subscribing @email to the newsletter with service ID @service_id and universe @universe: @exception.', [
        '@email' => $email,
        '@universe' => $universe,
        '@service_id' => $service_id,
        '@exception' => $e->getMessage(),
      ]);
    }
    catch (InvalidEmailAddressException $e) {
      $this->messenger()->addError($this->t('E-mail address is invalid.'));
      $this->getLogger('oe_newsroom_newsletter')->notice('Newsroom rejected invalid email address @email for the newsletter with service ID @service_id and universe @universe: @exception.', [
        '@email' => $email,
        '@universe' => $universe,
        '@service_id' => $service_id,
        '@exception' => $e->getMessage(),
      ]);
    }
    catch (EmailAddressAlreadySubscribedException $e) {
      $this->messenger()->addStatus($this->t('You are already subscribed to our newsletter.'));
      $this->getLogger('oe_newsroom_newsletter')->notice('@email is already registered to the newsletter with service ID @service_id and universe @universe.', [
        '@email' => $email,
        '@universe' => $universe,
        '@service_id' => $service_id,
      ]);
    }
  }

  /**
   * Ajax callback to update the subscription form after it is submitted.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response object.
   */
  public function submitFormCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#newsroom-newsletter-subscription-form', $form));
    }
    else {
      $messages = ['#type' => 'status_messages'];
      $response->addCommand(new HtmlCommand('#newsroom-newsletter-subscription-form', $messages));
    }

    return $response;
  }

}
