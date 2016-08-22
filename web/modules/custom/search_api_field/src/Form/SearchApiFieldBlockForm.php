<?php

namespace Drupal\search_api_field\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the search form for the search api page block.
 */
class SearchApiFieldBlockForm extends FormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new SearchBlockForm.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(LanguageManagerInterface $language_manager, RendererInterface $renderer) {
    $this->languageManager = $language_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_field_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $args = array()) {

    $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();

    $default_value = '';
    if (isset($args['keys'])) {
      $default_value = $args['keys'];
    }
    elseif ($search_value = $this->getRequest()->get('keys')) {
      $default_value = $search_value;
    }

    $form['keys'] = array(
      '#type' => 'search',
      '#title' => $this->t('Search', array(), array('langcode' => $langcode)),
      '#title_display' => 'invisible',
      '#size' => 15,
      '#default_value' => $default_value,
      '#attributes' => array(
        'title' => $this->t(
          'Enter the terms you wish to search for.',
          array(),
          array('langcode' => $langcode)
        ),
      ),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search', array(), array('langcode' => $langcode)),
    );
    $form['#method'] = 'get';
    $form['actions']['submit']['#name'] = '';

    $this->renderer->addCacheableDependency($form, $langcode);

    return $form;
  }

}
