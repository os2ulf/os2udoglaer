<?php

namespace Drupal\transform_api_preview\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TransformPreviewRedirectForm extends ContentEntityForm {

  /**
   * The transform preview service.
   *
   * @var \Drupal\transform_api_preview\TransformPreview
   */
  protected $transformPreview;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->transformPreview = $container->get('transform_api_preview');
    return $form;
  }

  public function getFormId() {
    return 'transform_api_preview__frontend';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $uuid = $this->transformPreview->generate($this->getEntity());
    $url = $this->transformPreview->getUrl($uuid, 'full');
    return new TrustedRedirectResponse($url);
  }
}
