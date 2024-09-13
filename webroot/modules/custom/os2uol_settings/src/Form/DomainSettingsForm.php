<?php

declare(strict_types=1);

namespace Drupal\os2uol_settings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

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
  protected function getDefaultPageNode($nid) {
    if ($nid && is_numeric($nid)) {
      return Node::load($nid);
    }
    return NULL;
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

    $form['font'] = [
      '#type' => 'select',
      '#title' => $this->t('Font'),
      '#options' => os2uol_settings_get_available_fonts(),
      '#default_value' => $config->get('font'),
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

    $form['text_positive_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text color'),
      '#default_value' => $config->get('text_positive_color'),
    ];

    $form['site_tracking_script'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Site tracking script'),
      '#default_value' => $config->get('site_tracking_script'),
    ];

    $form['free_course_application_reference'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Free course application page'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $this->getDefaultPageNode($config->get('free_course_application_reference')),
      '#tags' => FALSE,
    ];

    $form['email_signature'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Email signature'),
      '#default_value' => $config->get('email_signature')['value'] ?? '',
      '#format' => $config->get('email_signature')['format'] ?? 'full_html',
      '#description' => $this->t('Enter the HTML for the email signature. This will be used as a token in email sendouts.'),
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
      ->set('font', $form_state->getValue('font'))
      ->set('primary_background_color', $form_state->getValue('primary_background_color'))
      ->set('primary_background_text_color', $form_state->getValue('primary_background_text_color'))
      ->set('secondary_background_color', $form_state->getValue('secondary_background_color'))
      ->set('secondary_background_text_color', $form_state->getValue('secondary_background_text_color'))
      ->set('text_positive_color', $form_state->getValue('text_positive_color'))
      ->set('text_negative_color', $form_state->getValue('text_negative_color'))
      ->set('site_tracking_script', $form_state->getValue('site_tracking_script'))
      ->set('free_course_application_reference', $form_state->getValue('free_course_application_reference'))
      ->set('email_signature', $form_state->getValue('email_signature'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
