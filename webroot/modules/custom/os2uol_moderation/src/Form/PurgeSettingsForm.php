<?php

namespace Drupal\os2uol_moderation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure OS2UOL Moderation settings for this site.
 */
class PurgeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['os2uol_moderation.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2uol_moderation_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('os2uol_moderation.settings');

    $form['days'] = [
      '#type' => 'number',
      '#title' => $this->t('Days'),
      '#description' => $this->t('Number of days after which nodes in the "trash" state should be purged.'),
      '#default_value' => $config->get('days') ?? 30,
      '#min' => 1,
    ];

    return parent::buildForm($form, $form_state) + $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('os2uol_moderation.settings')
      ->set('days', $form_state->getValue('days'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
