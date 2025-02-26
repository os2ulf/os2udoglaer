<?php

namespace Drupal\os2uol_moderation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure OS2UOL Moderation email settings for this site.
 */
class PurgeEmailSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['os2uol_moderation.email_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2uol_moderation_email_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('os2uol_moderation.email_settings');

    $form['tabs'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'first_warning',
    ];

    $form['first_warning'] = [
      '#type' => 'details',
      '#title' => $this->t('First Warning Email'),
      '#group' => 'tabs',
    ];

    $form['first_warning']['first_warning_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#description' => $this->t('Subject for the first warning email.'),
      '#default_value' => $config->get('first_warning_subject'),
    ];

    $form['first_warning']['first_warning_email'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Email'),
      '#description' => $this->t('Email template for the first warning.'),
      '#default_value' => $config->get('first_warning_email'),
    ];

    $form['second_warning'] = [
      '#type' => 'details',
      '#title' => $this->t('Second Warning Email'),
      '#group' => 'tabs',
    ];

    $form['second_warning']['second_warning_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#description' => $this->t('Subject for the second warning email.'),
      '#default_value' => $config->get('second_warning_subject'),
    ];

    $form['second_warning']['second_warning_email'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Email'),
      '#description' => $this->t('Email template for the second warning.'),
      '#default_value' => $config->get('second_warning_email'),
    ];

    return parent::buildForm($form, $form_state) + $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('os2uol_moderation.email_settings')
      ->set('first_warning_subject', $form_state->getValue('first_warning_subject'))
      ->set('first_warning_email', $form_state->getValue('first_warning_email')['value'])
      ->set('second_warning_subject', $form_state->getValue('second_warning_subject'))
      ->set('second_warning_email', $form_state->getValue('second_warning_email')['value'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}
