<?php

namespace Drupal\os2uol_application_forms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class GdprAnonymizationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gdpr_anonymization_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['os2uol_application_forms.gdpr_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('os2uol_application_forms.gdpr_settings');

    // Field to configure the number of days after Afviklingsdato to anonymize personal data.
    $form['anonymization_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of days for data anonymization'),
      '#description' => $this->t('Specify the number of days after the Afviklingsdato to anonymize personal data (Name, Email, Phone Number)'),
      '#default_value' => $config->get('anonymization_days') ?? 365,
      '#min' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('os2uol_application_forms.gdpr_settings')
      ->set('anonymization_days', $form_state->getValue('anonymization_days'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
