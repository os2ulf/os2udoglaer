<?php

namespace Drupal\os2uol_pretix\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\os2uol_pretix\PretixEventManager;
use Drupal\user\EntityOwnerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PretixSettingsForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'node_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    //dpm($form);
    return $form;
  }

  /**
   * @inheritDoc
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    return [
      'submit' => $actions['submit']
    ];
  }

}
