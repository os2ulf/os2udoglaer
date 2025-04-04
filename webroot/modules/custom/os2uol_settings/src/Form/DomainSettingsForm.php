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

    $form['tabs'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'tab_theme',
    ];

    $form['tab_theme'] = [
      '#type' => 'details',
      '#title' => $this->t('Theme'),
      '#group' => 'tabs',
    ];

    $form['tab_theme']['logo'] = [
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

    $form['tab_theme']['favicon'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Favicon'),
      // Default value must be an array of file IDs.
      '#default_value' => [$config->get('favicon') ?? 0],
      '#upload_location' => 'public://favicons/',
      '#upload_validators' => [
        'file_validate_extensions' => ['ico'],
      ],
      '#description' => $this->t('Upload a favicon (ICO). This will be used as the site icon in browsers.'),
    ];

    $form['tab_theme']['font'] = [
      '#type' => 'select',
      '#title' => $this->t('Font'),
      '#options' => os2uol_settings_get_available_fonts(),
      '#default_value' => $config->get('font'),
    ];

    $form['tab_theme']['primary_background_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Primary background color'),
      '#default_value' => $config->get('primary_background_color'),
    ];

    $form['tab_theme']['primary_background_text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Primary background text color'),
      '#default_value' => $config->get('primary_background_text_color'),
    ];

    $form['tab_theme']['secondary_background_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secondary background color'),
      '#default_value' => $config->get('secondary_background_color'),
    ];

    $form['tab_theme']['secondary_background_text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secondary background text color'),
      '#default_value' => $config->get('secondary_background_text_color'),
    ];

    $form['tab_theme']['tertiary_background_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tertiary background color'),
      '#default_value' => $config->get('tertiary_background_color'),
    ];

    $form['tab_theme']['tertiary_background_text_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tertiary background text color'),
      '#default_value' => $config->get('tertiary_background_text_color'),
    ];

    $form['tab_theme']['text_positive_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text color'),
      '#default_value' => $config->get('text_positive_color'),
    ];

    $form['tab_scripts'] = [
      '#type' => 'details',
      '#title' => $this->t('Scripts'),
      '#group' => 'tabs',
    ];

    $form['tab_scripts']['site_cookie_script'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Site cookie script'),
      '#default_value' => $config->get('site_cookie_script'),
      '#description' => $this->t('Insert Cookiebot or CookieInformation scripts here.'),
    ];

    $form['tab_scripts']['site_tracking_script'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Site tracking script'),
      '#default_value' => $config->get('site_tracking_script'),
      '#description' => $this->t('Insert site tracking scripts here. E.g. Siteimprove, etc.'),
    ];

    $form['tab_emails'] = [
      '#type' => 'details',
      '#title' => $this->t('Emails'),
      '#group' => 'tabs',
    ];

    $form['tab_emails']['email_signature'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Email signature'),
      '#default_value' => $config->get('email_signature')['value'] ?? '',
      '#format' => $config->get('email_signature')['format'] ?? 'basic_html',
      '#description' => $this->t('Enter the HTML for the email signature. This will be used as a token in email sendouts.'),
    ];

    $form['tab_emails']['from_reply_to_email'] = [
      '#type' => 'email',
      '#title' => $this->t('From and Reply-To Email'),
      '#default_value' => $config->get('from_reply_to_email') ?? '',
      '#description' => $this->t('This email address will be used as the From and Reply-To address for this domain.'),
    ];

    $form['tab_pretix'] = [
      '#type' => 'details',
      '#title' => $this->t('Pretix'),
      '#group' => 'tabs',
    ];

    $form['tab_pretix']['pretix_order_placed_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order placed subject'),
      '#default_value' => $config->get('pretix_order_placed_subject') ?? '',
      '#description' => $this->t('Enter the subject for order placed e-mails.'),
    ];

    $form['tab_pretix']['pretix_order_placed_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Order placed message'),
      '#default_value' => $config->get('pretix_order_placed_message')['value'] ?? '',
      '#format' => $config->get('pretix_order_placed_message')['format'] ?? 'basic_html',
      '#description' => $this->t('Enter the HTML for order placed e-mails.'),
    ];

    $form['tab_pretix']['pretix_order_canceled_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order canceled subject'),
      '#default_value' => $config->get('pretix_order_canceled_subject') ?? '',
      '#description' => $this->t('Enter the subject for order canceled e-mails.'),
    ];

    $form['tab_pretix']['pretix_order_canceled_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Order canceled message'),
      '#default_value' => $config->get('pretix_order_canceled_message')['value'] ?? '',
      '#format' => $config->get('pretix_order_canceled_message')['format'] ?? 'basic_html',
      '#description' => $this->t('Enter the HTML for order canceled e-mails.'),
    ];

    $form['tab_pretix']['pretix_token_help_mails'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['node', 'user', 'pretix_order'],
      '#global_types' => FALSE,
    ];

    $form['tab_pretix']['pretix_order_line'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Order lines template'),
      '#default_value' => $config->get('pretix_order_line')['value'] ?? '',
      '#format' => $config->get('pretix_order_line')['format'] ?? 'basic_html',
      '#description' => $this->t('Enter the HTML for order lines in e-mails.'),
    ];

    $form['tab_pretix']['pretix_token_help_lines'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['node', 'user', 'pretix_order_line'],
      '#global_types' => FALSE,
    ];

    $form['tab_pretix']['pretix_question'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Question template'),
      '#default_value' => $config->get('pretix_question')['value'] ?? '',
      '#format' => $config->get('pretix_order_lines')['format'] ?? 'basic_html',
      '#description' => $this->t('Enter the HTML for questions in e-mails.'),
    ];

    $form['tab_pretix']['pretix_token_help_question'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['node', 'user', 'pretix_question'],
      '#global_types' => FALSE,
    ];

    $form['tab_free_course_request'] = [
      '#type' => 'details',
      '#title' => $this->t('Free course request'),
      '#group' => 'tabs',
    ];

    $form['tab_free_course_request']['free_course_application_reference'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Free course application page'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $this->getDefaultPageNode($config->get('free_course_application_reference')),
      '#tags' => FALSE,
      '#description' => $this->t('Reference the free course application page for use on courses.'),
    ];

    $form['tab_free_course_request']['ufcr_receipt'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Receipt text'),
      '#default_value' => $config->get('ufcr_receipt')['value'] ?? '',
      '#format' => $config->get('ufcr_receipt')['format'] ?? 'basic_html',
      '#description' => $this->t('Write a "Receipt" message.'),
    ];

    $form['tab_free_course_request']['free_course_email_signature'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Free Course Email Signature'),
      '#default_value' => $config->get('free_course_email_signature')['value'] ?? '',
      '#format' => $config->get('free_course_email_signature')['format'] ?? 'basic_html',
      '#description' => $this->t('Enter the HTML for the free course email signature. This will be used in email sendouts related to free courses.'),
    ];

    $form['tab_transport_pool'] = [
      '#type' => 'details',
      '#title' => $this->t('Transport pool request'),
      '#group' => 'tabs',
    ];

    $form['tab_transport_pool']['transport_pool_application_reference'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Transport pool application page'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $this->getDefaultPageNode($config->get('transport_pool_application_reference')),
      '#tags' => FALSE,
      '#description' => $this->t('Reference the transport pool application page for use on courses.'),
    ];

    $form['tab_transport_pool']['free_choice'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Free choice of transport'),
      '#default_value' => $config->get('free_choice')['value'] ?? '',
      '#format' => $config->get('free_choice')['format'] ?? 'basic_html',
      '#description' => $this->t('Write a "Free choice of transport" message.'),
    ];

    $form['tab_transport_pool']['course_not_found'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Course not found'),
      '#default_value' => $config->get('course_not_found')['value'] ?? '',
      '#format' => $config->get('course_not_found')['format'] ?? 'basic_html',
      '#description' => $this->t('Write a "Course not found" message.'),
    ];

    $form['tab_transport_pool']['district_1'] = [
      '#type' => 'text_format',
      '#title' => $this->t('District 1'),
      '#default_value' => $config->get('district_1')['value'] ?? '',
      '#format' => $config->get('district_1')['format'] ?? 'basic_html',
      '#description' => $this->t('Write a "District 1" message".'),
    ];

    $form['tab_transport_pool']['district_2'] = [
      '#type' => 'text_format',
      '#title' => $this->t('District 2'),
      '#default_value' => $config->get('district_2')['value'] ?? '',
      '#format' => $config->get('district_2')['format'] ?? 'basic_html',
      '#description' => $this->t('Write a "District 2" message".'),
    ];

    $form['tab_transport_pool']['district_3'] = [
      '#type' => 'text_format',
      '#title' => $this->t('District 3'),
      '#default_value' => $config->get('district_3')['value'] ?? '',
      '#format' => $config->get('district_3')['format'] ?? 'basic_html',
      '#description' => $this->t('Write a "District 3" message".'),
    ];

    $form['tab_transport_pool']['district_4'] = [
      '#type' => 'text_format',
      '#title' => $this->t('District 4'),
      '#default_value' => $config->get('district_4')['value'] ?? '',
      '#format' => $config->get('district_4')['format'] ?? 'basic_html',
      '#description' => $this->t('Write a "District 4" message".'),
    ];

    $form['tab_transport_pool']['district_5'] = [
      '#type' => 'text_format',
      '#title' => $this->t('District 5'),
      '#default_value' => $config->get('district_5')['value'] ?? '',
      '#format' => $config->get('district_5')['format'] ?? 'basic_html',
      '#description' => $this->t('Write a "District 5" message".'),
    ];

    $form['tab_transport_pool']['no_district'] = [
      '#type' => 'text_format',
      '#title' => $this->t('No district'),
      '#default_value' => $config->get('no_district')['value'] ?? '',
      '#format' => $config->get('no_district')['format'] ?? 'basic_html',
      '#description' => $this->t('Write a "No district" message.'),
    ];

    $form['tab_transport_pool']['denied_distance'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Denied for 6th grade+ because distance to course is less than 6 km'),
      '#default_value' => $config->get('denied_distance')['value'] ?? '',
      '#format' => $config->get('denied_distance')['format'] ?? 'basic_html',
      '#description' => $this->t('Write a "Denied for 6th grade+ because distance to course is less than 6 km" message.'),
    ];

    $form['tab_transport_pool']['denied_private'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Denied for private institution requesting funding for public only courses'),
      '#default_value' => $config->get('denied_private')['value'] ?? '',
      '#format' => $config->get('denied_private')['format'] ?? 'basic_html',
      '#description' => $this->t('Write a "Denied for private institution requesting funding for public only courses" message.'),
    ];

    $form['tab_transport_pool']['confirmation'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Request confirmed'),
      '#default_value' => $config->get('confirmation')['value'] ?? '',
      '#format' => $config->get('confirmation')['format'] ?? 'basic_html',
      '#description' => $this->t('Write a "Request confirmed" message.'),
    ];

    $form['tab_transport_pool']['transport_pool_email_signature'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Transport Pool Email Signature'),
      '#default_value' => $config->get('transport_pool_email_signature')['value'] ?? '',
      '#format' => $config->get('transport_pool_email_signature')['format'] ?? 'basic_html',
      '#description' => $this->t('Enter the HTML for the transport pool email signature. This will be used in email sendouts related to transport pools.'),
    ];

    $form['tab_theater_refund'] = [
      '#type' => 'details',
      '#title' => $this->t('Theater refund request'),
      '#group' => 'tabs',
    ];

    $form['tab_theater_refund']['tr_receipt'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Receipt text'),
      '#default_value' => $config->get('tr_receipt')['value'] ?? '',
      '#format' => $config->get('tr_receipt')['format'] ?? 'basic_html',
      '#description' => $this->t('Write a "Receipt text" message.'),
    ];

    $form['tab_theater_refund']['theater_refund_email_signature'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Theater Refund Email Signature'),
      '#default_value' => $config->get('theater_refund_email_signature')['value'] ?? '',
      '#format' => $config->get('theater_refund_email_signature')['format'] ?? 'basic_html',
      '#description' => $this->t('Enter the HTML for the theater refund email signature. This will be used in email sendouts related to theater refunds.'),
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

    $favicon = $form_state->getValue('favicon');
    if (!empty($favicon)) {
      $file = \Drupal\file\Entity\File::load(reset($favicon));
      $file->setPermanent();
      $file->save();
    }

    $config
      ->set('logo', reset($logo))
      ->set('favicon', reset($favicon))
      ->set('font', $form_state->getValue('font'))
      ->set('primary_background_color', $form_state->getValue('primary_background_color'))
      ->set('primary_background_text_color', $form_state->getValue('primary_background_text_color'))
      ->set('secondary_background_color', $form_state->getValue('secondary_background_color'))
      ->set('secondary_background_text_color', $form_state->getValue('secondary_background_text_color'))
      ->set('tertiary_background_color', $form_state->getValue('tertiary_background_color'))
      ->set('tertiary_background_text_color', $form_state->getValue('tertiary_background_text_color'))
      ->set('text_positive_color', $form_state->getValue('text_positive_color'))
      ->set('text_negative_color', $form_state->getValue('text_negative_color'))
      ->set('site_tracking_script', $form_state->getValue('site_tracking_script'))
      ->set('site_cookie_script', $form_state->getValue('site_cookie_script'))
      ->set('email_signature', $form_state->getValue('email_signature'))
      ->set('free_course_application_reference', $form_state->getValue('free_course_application_reference'))
      ->set('ufcr_receipt', $form_state->getValue('ufcr_receipt'))
      ->set('transport_pool_application_reference', $form_state->getValue('transport_pool_application_reference'))
      ->set('free_choice', $form_state->getValue('free_choice'))
      ->set('course_not_found', $form_state->getValue('course_not_found'))
      ->set('district_1', $form_state->getValue('district_1'))
      ->set('district_2', $form_state->getValue('district_2'))
      ->set('district_3', $form_state->getValue('district_3'))
      ->set('district_4', $form_state->getValue('district_4'))
      ->set('district_5', $form_state->getValue('district_5'))
      ->set('no_district', $form_state->getValue('no_district'))
      ->set('denied_distance', $form_state->getValue('denied_distance'))
      ->set('denied_private', $form_state->getValue('denied_private'))
      ->set('confirmation', $form_state->getValue('confirmation'))
      ->set('tr_receipt', $form_state->getValue('tr_receipt'))
      ->set('from_reply_to_email', $form_state->getValue('from_reply_to_email'))
      ->set('free_course_email_signature', $form_state->getValue('free_course_email_signature'))
      ->set('transport_pool_email_signature', $form_state->getValue('transport_pool_email_signature'))
      ->set('theater_refund_email_signature', $form_state->getValue('theater_refund_email_signature'))
      ->set('pretix_order_placed_subject', $form_state->getValue('pretix_order_placed_subject'))
      ->set('pretix_order_placed_message', $form_state->getValue('pretix_order_placed_message'))
      ->set('pretix_order_canceled_subject', $form_state->getValue('pretix_order_canceled_subject'))
      ->set('pretix_order_canceled_message', $form_state->getValue('pretix_order_canceled_message'))
      ->set('pretix_order_line', $form_state->getValue('pretix_order_line'))
      ->set('pretix_question', $form_state->getValue('pretix_question'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
