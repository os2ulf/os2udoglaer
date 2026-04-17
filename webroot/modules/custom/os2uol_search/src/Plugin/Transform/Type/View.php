<?php

namespace Drupal\os2uol_search\Plugin\Transform\Type;

use Drupal\transform_api_views\Plugin\Transform\Type\View as TransformApiViewType;
use Drupal\transform_api_views\Transform\ViewTransform;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Custom view transformation type with block runtime configuration support.
 */
class View extends TransformApiViewType {

  /**
   * {@inheritdoc}
   */
  protected function buildView(ViewTransform $view_transform): ViewExecutable {
    $view = Views::getView($view_transform->getViewId());

    $view->setDisplay($view_transform->getDisplayId());
    $view->setArguments($view_transform->getArguments());

    $display_handler = $view->display_handler;
    $options = $view_transform->getOptions();

    // Extension of the original class
    if (method_exists($display_handler, 'setRuntimeBlockConfiguration')) {
      $display_handler->setRuntimeBlockConfiguration($options);
    }

    $items_per_page = $view_transform->getOption('items_per_page');

    if (!empty($items_per_page) && $items_per_page !== 'none') {
      $view->setItemsPerPage($items_per_page);
    }

    $view->preExecute();
    $view->execute();

    return $view;
  }

}
