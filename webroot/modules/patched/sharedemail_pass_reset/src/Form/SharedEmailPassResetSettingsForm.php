<?php

namespace Drupal\sharedemail_pass_reset\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sharedemail_pass_reset\SharedEmailPassResetConstants;

/**
 * Class SharedEmailPassResetSettingsForm.
 *
 * @package Drupal\sharedemail_pass_reset\Form
 */
class SharedEmailPassResetSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sharedemail_pass_reset_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sharedemail_pass_reset.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('sharedemail_pass_reset.settings');

    // Shared email strategy radios field.
    $form['sharedemail_pass_reset_strategy'] = [
      '#title' => $this->t('Shared E-mail Password Reset strategy'),
      '#type' => 'radios',
      '#options' => [
        SharedEmailPassResetConstants::STRATEGY_CONDITIONAL => $this->t('Ask only for username if there are multiple accounts registered with the email address'),
        SharedEmailPassResetConstants::STRATEGY_USERNAME => $this->t('Ask for username only'),
        SharedEmailPassResetConstants::STRATEGY_ALL => $this->t('Ask for both email address and username'),
      ],
      '#default_value' => $config->get('sharedemail_pass_reset_strategy') ?? SharedEmailPassResetConstants::STRATEGY_CONDITIONAL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the preferences.
    $this
      ->config('sharedemail_pass_reset.settings')
      ->set('sharedemail_pass_reset_strategy', $form_state->getValue('sharedemail_pass_reset_strategy'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
