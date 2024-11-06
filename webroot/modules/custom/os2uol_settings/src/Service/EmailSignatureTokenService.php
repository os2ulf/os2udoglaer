<?php
namespace Drupal\os2uol_settings\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

class EmailSignatureTokenService {
  protected $configFactory;

  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  public function getEmailSignature() {
    $config = $this->configFactory->get('os2uol_settings.settings');
    return $config->get('email_signature');
  }
}
