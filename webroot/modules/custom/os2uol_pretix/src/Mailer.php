<?php

namespace Drupal\os2uol_pretix;

/**
 * Mailer.
 */
class Mailer {
  const PRETIX_EVENT_ORDER_PAID_TEMPLATE = 'ulf_pretix_event_order_paid_template';
  const PRETIX_EVENT_ORDER_CANCELED_TEMPLATE = 'ulf_pretix_event_order_canceled_template';

  /**
   * Create an instance.
   */
  public static function create() {
    return new static();
  }

  /**
   * Render mail.
   *
   * @param string $key
   *   The key.
   * @param array $message
   *   The message.
   * @param array $params
   *   The params.
   */
  public function render($key, array &$message, array $params) {
    $template = variable_get($key, '');

    switch ($key) {
      case self::PRETIX_EVENT_ORDER_PAID_TEMPLATE:
      case self::PRETIX_EVENT_ORDER_CANCELED_TEMPLATE:
        $message['subject'] = $params['subject'] ?? $key;
        $message['body'] = '<p>' . $template . '</p>';
        if (isset($params['content'])) {
          $message['body'] .= $params['content'];
        }
        break;
    }
  }

  /**
   * Send mail.
   *
   * @see drupal_mail()
   */
  public function send($key, $to, $language, array $params, $from = NULL, $send = TRUE) {
    return drupal_mail('ulf_pretix', $key, $to, $language, $params, $from, $send);
  }

  /**
   * Get mail templates for module mail_edit.
   *
   * @param string $mailkey
   *   The mail key.
   * @param object $language
   *   The language.
   *
   * @return array
   *   The mail template.
   */
  public static function getMailTemplate($mailkey, $language) {
    switch ($mailkey) {
      case Mailer::PRETIX_EVENT_ORDER_PAID_TEMPLATE:
        return 'da' === $language->language
          ? [
            'subject' => 'Ny bestilling af [node:title] på [site:name]',
            'body' => <<<'BODY'
En ny bestilling på <a href="[node:url]">[node:title]</a> er blevet afgivet på <a href="[site:url]">[site:name]</a>:

{{[pretix_order:lines:count]#
[pretix_order:lines:#0:name]
Startdato: [pretix_order:lines:#0:date_from|date('Y-m-d')]
Antal: [pretix_order:lines:#0:quantity]
Tilgængelighed: [pretix_order:lines:#0:availability]
Spørgsmål: [pretix_order:lines:#0:questions]
Stykpris: [pretix_order:lines:#0:item_price|number_format(2, ',', '.')]
Samlet pris: [pretix_order:lines:#0:total_price|number_format(2, ',', '.')]

}}

Venlig hilsen
[site:name]
BODY
          ] : [
            'subject' => 'New order for [node:title] on [site:name]',
            'body' => <<<'BODY'
An order for <a href="[node:url]">[node:title]</a> has been placed on <a href="[site:url]">[site:name]</a>:

{{[pretix_order:lines:count]#
[pretix_order:lines:#0:name]
Start time: [pretix_order:lines:#0:date_from|date('Y-m-d')]
Quantity: [pretix_order:lines:#0:quantity]
Availability: [pretix_order:lines:#0:availability]
Questions: [pretix_order:lines:#0:questions]
Item price: [pretix_order:lines:#0:item_price|number_format(2, ',', '.')]
Total price: [pretix_order:lines:#0:total_price|number_format(2, ',', '.')]

}}

Best regards,
[site:name]
BODY
          ];

      case Mailer::PRETIX_EVENT_ORDER_CANCELED_TEMPLATE:
        return 'da' === $language->language
          ? [
            'subject' => 'Bestilling af [node:title] på [site:name] annulleret',
            'body' => <<<'BODY'
En bestilling af <a href="[node:url]">[node:title]</a> på <a href="[site:url]">[site:name]</a> er blevet annulleret:

{{[pretix_order:lines:count]#
[pretix_order:lines:#0:name]
Startdato: [pretix_order:lines:#0:date_from|date('Y-m-d')]
Antal: [pretix_order:lines:#0:quantity]
Tilgængelighed: [pretix_order:lines:#0:availability]
Spørgsmål: [pretix_order:lines:#0:questions]
Stykpris: [pretix_order:lines:#0:item_price|number_format(2, ',', '.')]
Samlet pris: [pretix_order:lines:#0:total_price|number_format(2, ',', '.')]

}}

Venlig hilsen
[site:name]
BODY
          ] : [
            'subject' => 'Order for [node:title] on [site:name] canceled',
            'body' => <<<'BODY'
An order for <a href="[node:url]">[node:title]</a> has been placed on <a href="[site:url]">[site:name]</a>:

{{[pretix_order:lines:count]#
[pretix_order:lines:#0:name]
Start time: [pretix_order:lines:#0:date_from|date('Y-m-d')]
Quantity: [pretix_order:lines:#0:quantity]
Availability: [pretix_order:lines:#0:availability]
Questions: [pretix_order:lines:#0:questions]
Item price: [pretix_order:lines:#0:item_price|number_format(2, ',', '.')]
Total price: [pretix_order:lines:#0:total_price|number_format(2, ',', '.')]

}}

Best regards,
[site:name]
BODY
          ];
    }
  }

}
