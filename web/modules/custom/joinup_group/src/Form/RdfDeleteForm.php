<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\rdf_entity\Form\RdfDeleteForm as OriginalForm;
use Drupal\rdf_entity\RdfInterface;

/**
 * Prevents deletion of collections if there is an existing child solution.
 */
class RdfDeleteForm extends OriginalForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    $entity = $this->getEntity();
    if ($entity->bundle() !== 'collection' || empty($this->getAffiliates($entity))) {
      return $this->t('Are you sure you want to delete @type %name?', [
        '@type' => $entity->get('rid')->entity->getSingularLabel(),
        '%name' => $entity->label(),
      ]);
    }

    return $this->t('The collection %collection cannot be deleted because it contains the following solutions:', [
      '%collection' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    $entity = $this->getEntity();
    if ($entity->bundle() !== 'collection' || empty($this->getAffiliates($entity))) {
      return parent::getDescription();
    }

    return $this->t('You can delete your solutions or transfer them to another collection.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    $entity = $this->getEntity();
    if (empty($entity) || $entity->isNew() || $entity->bundle() !== 'collection') {
      return $form;
    }

    $affiliates = $this->getAffiliates($entity);
    if (empty($affiliates)) {
      return $form;
    }

    $list = array_map(function (RdfInterface $solution): Link {
      return $solution->toLink($solution->label());
    }, $affiliates);

    $form['solutions'] = [
      '#theme' => 'item_list',
      '#items' => $list,
      '#weight' => -1,
    ];
    unset($form['actions']['submit']);

    return $form;
  }

  /**
   * Returns the entities that are affiliated with the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which to return the affiliates.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The list of affiliated entities.
   */
  protected function getAffiliates(ContentEntityInterface $entity): array {
    return $entity->get('field_ar_affiliates')->referencedEntities();
  }

}
