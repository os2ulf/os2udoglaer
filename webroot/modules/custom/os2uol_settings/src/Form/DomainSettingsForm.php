<?php

declare(strict_types=1);

namespace Drupal\os2uol_settings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure OS2UOL Domain settings.
 */
final class DomainSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'os2uol_settings_domain_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['os2uol_settings.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('os2uol_settings.settings');

    $form['logo'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Logo'),
      // Default value must be an array of file IDs.
      '#default_value' => [$config->get('logo') ?? 0],
      '#upload_location' => 'public://domain_logos',
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'png svg'],
      ],
      '#description' => 'Upload a logo in PNG or SVG format.',
    ];

    $form['primary_background_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Primary background color'),
      '#default_value' => $config->get('primary_background_color'),
    ];

    $form['primary_background_text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Primary background text color'),
      '#default_value' => $config->get('primary_background_text_color'),
    ];

    $form['secondary_background_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secondary background color'),
      '#default_value' => $config->get('secondary_background_color'),
    ];

    $form['secondary_background_text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secondary background text color'),
      '#default_value' => $config->get('secondary_background_text_color'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('os2uol_settings.settings');

    $logo = $form_state->getValue('logo');
    if (!empty($logo)) {
      $file = \Drupal\file\Entity\File::load(reset($logo));
      $file->setPermanent();
      $file->save();
    }

    $config
      ->set('logo', reset($logo))
      ->set('primary_background_color', $form_state->getValue('primary_background_color'))
      ->set('primary_background_text_color', $form_state->getValue('primary_background_text_color'))
      ->set('secondary_background_color', $form_state->getValue('secondary_background_color'))
      ->set('secondary_background_text_color', $form_state->getValue('secondary_background_text_color'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
