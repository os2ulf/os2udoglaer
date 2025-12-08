<?php

namespace Drupal\transform_api_preview\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransformPreviewSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->entityTypeManager = $container->get('entity_type.manager');
    $form->routerBuilder = $container->get('router.builder');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['transform_api_preview.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'transform_api_preview__settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $content_entity_types = array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $entity_type) {
      return $entity_type instanceof ContentEntityTypeInterface;
    });
    $form['entity_types']  = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Preview entity types'),
      '#description' => $this->t('Enable preview for the following entity types'),
      '#options' => array_map(function (EntityTypeInterface $entity_type) {
        return $entity_type->getLabel();
      }, $content_entity_types),
      '#default_value' => $this->config('transform_api_preview.settings')->get('entity_types') ?? [],
    ];

    $form['front_end_url'] = [
      '#type' => 'textfield',
      '#title' => t('Base URL for the frontend'),
      '#description' => $this->t('The scheme and domain for the frontend for redirects to the preview on the frontend. Example https://frontend-domain/'),
      '#default_value' => $this->config('transform_api_preview.settings')->get('front_end_url'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('transform_api_preview.settings')
      ->set('entity_types', array_values(array_filter($form_state->getValue('entity_types'))))
      ->set('front_end_url', $form_state->getValue('front_end_url'))
      ->save();
    // @todo This should be done through a config save subscriber and it should
    // also invalidate the render/local tasks cache.
    $this->routerBuilder->setRebuildNeeded();
    Cache::invalidateTags(['entity_types', 'views_data']);
  }

}
