<?php

namespace Drupal\contact_form;

/**
 * Define events for the contact form module.
 */
final class ContactFormEvents {

  /**
   * An event to be fired when a contact form is submitted.
   *
   * @Event
   *
   * @var string
   */
  const CONTACT_FORM_EVENT = 'contact_form.notify';

}
