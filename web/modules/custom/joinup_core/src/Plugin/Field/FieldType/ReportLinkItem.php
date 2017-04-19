<?php

namespace Drupal\joinup_core\Plugin\Field\FieldType;

use Drupal\link\Plugin\Field\FieldType\LinkItem;

/**
 * Variant of the 'link' field intended for reporting inappropriate content.
 *
 * @FieldType(
 *   id = "report_link",
 *   label = @Translation("Report"),
 *   description = @Translation("A link that can be used to report inappropriate content."),
 *   default_widget = "link_default",
 *   default_formatter = "link",
 *   constraints = {
 *     "LinkType" = {},
 *     "LinkAccess" = {},
 *     "LinkExternalProtocols" = {},
 *     "LinkNotExistingInternal" = {}
 *   }
 * )
 */
class ReportLinkItem extends LinkItem {

  /**
   * Whether or not the value has been calculated.
   *
   * @var bool
   */
  protected $isCalculated = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    $this->ensureCalculated();
    return parent::__get($name);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensureCalculated();
    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $this->ensureCalculated();
    return parent::getValue();
  }

  /**
   * Makes sure that the value is populated.
   *
   * Normal fields get their data from the database and are populated if there
   * is data available. Since this is a computed field we need to make sure
   * there is always data available ourselves.
   *
   * This trick has been borrowed from issue #2846554 which does the same for
   * the PathItem field.
   *
   * @todo Remove this when issue #2392845 is fixed.
   *
   * @see https://www.drupal.org/node/2392845
   * @see https://www.drupal.org/node/2846554
   */
  protected function ensureCalculated() {
    if (!$this->isCalculated) {
      $entity = $this->getEntity();
      if (!$entity->isNew()) {
        $url = $this->getEntity()->toUrl()->toString();
        $value = [
          'uri' => 'internal:/contact?category=report&uri=' . $url,
          'title' => t('Report'),
        ];
        $this->setValue($value);
      }
      $this->isCalculated = TRUE;
    }
  }

}
