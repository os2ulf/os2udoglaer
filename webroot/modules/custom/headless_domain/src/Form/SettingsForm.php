<?php

declare(strict_types=1);

namespace Drupal\headless_domain\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Headless domain simple sitemap settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'headless_domain_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['headless_domain.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();

    $form['domain_urls'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Domain URLs'),
      '#tree' => TRUE,
    ];

    foreach ($domains as $domain) {
      $form['domain_urls'][$domain->id()] = [
        '#type' => 'textfield',
        '#title' => $domain->label(),
        '#default_value' => $this->config('headless_domain.settings')->get($domain->id()),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('headless_domain.settings')
      ->setData($form_state->getValue('domain_urls'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
