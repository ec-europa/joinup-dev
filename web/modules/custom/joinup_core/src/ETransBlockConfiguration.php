<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\collection\Entity\GlossaryTermInterface;
use Drupal\custom_page\Entity\CustomPageInterface;
use Drupal\joinup_discussion\Entity\DiscussionInterface;
use Drupal\joinup_document\Entity\DocumentInterface;
use Drupal\joinup_event\Entity\EventInterface;
use Drupal\joinup_news\Entity\NewsInterface;

/**
 * Provides the Webtools eTrans block configuration for routes and bundles.
 *
 * We offer machine translation for various content pages, using the Webtools
 * eTrans service. Since every page type has a different layout we need a unique
 * configuration for each, detailing the HTML elements that should be translated
 * and the ones that have to be excluded from translation.
 *
 * Some suggestions, inspired by the Webtools eTrans documentation:
 *   - Only translate the actual content, avoid translating generic elements
 *     that occur on multiple pages, such as the site navigation, header,
 *     footer, pagers, share links etc.
 *   - Do not translate elements which are mainly intended for linking to other
 *     pages, such as tiles, attachments, external links etc.
 *   - Avoid translating names of any kind if possible.
 *   - Only translate titles if they are part of the content, do not translate
 *     generic titles such as "About" or "Overview".
 *
 * @see \Drupal\oe_webtools_etrans\Plugin\Block\ETransBlock
 */
class ETransBlockConfiguration {

  /**
   * Maps the Webtools eTrans block configuration to routes and bundles.
   */
  public const CONFIG_MAPPING = [
    'bundles' => [
      CustomPageInterface::class => [
        'include' => 'h1.page__title,.page__content',
        'exclude' => '.action-link__wrapper',
      ],
      DiscussionInterface::class => [
        'include' => '.page__title-wrapper,.page__content,.comments-section',
        'exclude' => '.page__title-wrapper .details__element > a,.action-link__wrapper,.comment-item__details a',
      ],
      DocumentInterface::class => [
        'include' => '.page__title-wrapper,.page__content,.fieldset--separated .page__content',
        'exclude' => '.page__title-wrapper .details__element > a,.fieldset__field--files,.action-link__wrapper',
      ],
      EventInterface::class => [
        'include' => 'h1.page__title,.page__title-wrapper > .details:nth-of-type(1),.page__title-wrapper > .details:nth-of-type(2) > .details__element:nth-of-type(2),.page__content,.fieldset--separated .page__content',
        'exclude' => '.action-link__wrapper',
      ],
      GlossaryTermInterface::class => [
        'include' => '.field_glossary--synonyms > div div:first-child,.field_glossary--definition',
      ],
      NewsInterface::class => [
        'include' => '.page__title-wrapper,.page__content',
        'exclude' => '.page__title-wrapper .details__element > a,.action-link__wrapper',
      ],
    ],
    'routes' => [
      // The "About" page of a collection or solution.
      'entity.rdf_entity.about_page' => [
        'include' => 'h1.page__title,.page__content',
      ],

      // The "Glossary" page of a collection.
      'collection.glossary_page' => [
        'include' => '.view-glossary',
        'exclude' => 'h3,.views-field-title-1,.views-field-field-glossary-synonyms',
      ],
    ],
  ];

}
