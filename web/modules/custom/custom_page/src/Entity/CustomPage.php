<?php

declare(strict_types = 1);

namespace Drupal\custom_page\Entity;

use Drupal\collection\Entity\NodeCommunityContentTrait;
use Drupal\joinup_bundle_class\LogoTrait;
use Drupal\joinup_publication_date\Entity\EntityPublicationTimeTrait;
use Drupal\node\Entity\Node;

/**
 * Entity class for custom page node entities.
 */
class CustomPage extends Node implements CustomPageInterface {

  use EntityPublicationTimeTrait;
  use LogoTrait;
  use NodeCommunityContentTrait;

  /**
   * {@inheritdoc}
   */
  public function getLogoFieldName(): string {
    return 'field_custom_page_logo';
  }

}
