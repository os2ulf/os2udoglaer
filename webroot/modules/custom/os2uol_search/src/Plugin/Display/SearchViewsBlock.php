<?php

namespace Drupal\os2uol_search\Plugin\Display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Plugin\views\display\Block;

class SearchViewsBlock extends Block {

  /**
   * Runtime block instance configuration for renderers that bypass ViewsBlock.
   *
   * @var array
   */
  protected array $runtimeBlockConfiguration = [];

  /**
   * {@inheritdoc}
   */
  public function blockForm(ViewsBlock $block, array &$form, FormStateInterface $form_state) {
    $form = parent::blockForm($block, $form, $form_state);

    $block_configuration = $block->getConfiguration();

    if ($this->view->id() === 'content_search') {
      $form['use_random'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('View in random order', [], ['context' => 'os2uol_search']),
        '#default_value' => $block_configuration['use_random'] ?? 0,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    parent::blockSubmit($block, $form, $form_state);

    if ($this->view->id() === 'content_search') {
      $block->setConfigurationValue('use_random', (bool) $form_state->getValue('use_random'));
      $form_state->unsetValue('use_random');
    }
  }

  /**
   * Injects block instance configuration before the view is executed.
   *
   * @param array $configuration
   *   The block configuration.
   */
  public function setRuntimeBlockConfiguration(array $configuration): void {
    $this->runtimeBlockConfiguration = $configuration;
  }

  /**
   * Returns a runtime block configuration value.
   *
   * @param string $key
   *   The configuration key.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The configuration value.
   */
  protected function getRuntimeBlockConfigurationValue(string $key, mixed $default = NULL): mixed {
    return $this->runtimeBlockConfiguration[$key] ?? $default;
  }

  /**
   * Returns whether random ordering is enabled for the block instance.
   */
  protected function useRandom(): bool {
    return (bool) $this->getRuntimeBlockConfigurationValue('use_random', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function preExecute() {
    parent::preExecute();

    if ($this->view->id() === 'content_search') {
      if (!$this->useRandom()) {
        $view = $this->view;

        $exposed_input = $view->getExposedInput();

        if (empty($exposed_input['sort_by']) || $exposed_input['sort_by'] == 'random') {
          $exposed_input['sort_by'] = 'created';
          $view->setExposedInput($exposed_input);
        }
      }
    }
  }

}
