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
      '#title' => $this->t('Purge Days'),
      '#description' => $this->t('Days after which nodes in the trash should be purged.'),
      '#default_value' => $config->get('days') ?? 30,
      '#min' => 1,
    ];
    $form['first_warning'] = [
      '#type' => 'number',
      '#title' => $this->t('First Warning Interval (days)'),
      '#default_value' => $config->get('first_warning') ?? 14,
    ];
    $form['second_warning'] = [
      '#type' => 'number',
      '#title' => $this->t('Second Warning Interval (days)'),
      '#default_value' => $config->get('second_warning') ?? 7,
    ];
    $form['unpublish_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Unpublish Interval (days)'),
      '#default_value' => $config->get('unpublish_interval') ?? 430,
    ];

    return parent::buildForm($form, $form_state) + $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('os2uol_moderation.settings')
      ->set('days', $form_state->getValue('days'))
      ->set('first_warning', $form_state->getValue('first_warning'))
      ->set('second_warning', $form_state->getValue('second_warning'))
      ->set('unpublish_interval', $form_state->getValue('unpublish_interval'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
