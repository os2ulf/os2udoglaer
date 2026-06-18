<?php

namespace Drupal\os2uol_moderation\Plugin\views\filter;

use Drupal\content_moderation\Plugin\views\filter\ModerationStateFilter;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Views;

/**
 * Provides a moderation state filter tailored for entity revision tables.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("revision_moderation_state_filter")]
class RevisionModerationStateFilter extends ModerationStateFilter {

  /**
   * The content moderation state field revision table name.
   */
  const MODERATION_STATE_REVISION_TABLE = 'content_moderation_state_field_revision';

  /**
   * {@inheritdoc}
   *
   * Mirrors the parent implementation but forces the workflow/state conditions
   * to be evaluated against the {content_moderation_state_field_revision}
   * table for the LATEST revision of the entity, so the filter reflects the
   * current latest revision moderation state (not just any historical
   * revision).
   */
  protected function opSimple() {
    if (empty($this->value)) {
      return;
    }

    $entity_type = $this->entityTypeManager->getDefinition($this->getEntityType());

    // Resolve the view's base entity data table alias (e.g. node_field_data).
    // The moderation join and bundle conditions must both be anchored here,
    // because $this->table points at a revision data table that is never
    // actually joined into the query by this filter.
    $base_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
    $base_table_alias = $this->query->ensureTable($base_table, $this->relationship);

    // Join the moderation state revision table for the latest revision only.
    $moderation_table_alias = $this->getRevisionModerationTableAlias($base_table_alias);

    $bundle_condition = NULL;
    if ($entity_type->hasKey('bundle')) {
      // Get a list of bundles that are being moderated by the workflows
      // configured in this filter.
      $workflow_ids = $this->getWorkflowIds();
      $moderated_bundles = [];
      foreach ($this->bundleInfo->getBundleInfo($this->getEntityType()) as $bundle_id => $bundle) {
        if (isset($bundle['workflow']) && in_array($bundle['workflow'], $workflow_ids, TRUE)) {
          $moderated_bundles[] = $bundle_id;
        }
      }

      // If we have a list of moderated bundles, restrict the query to show
      // only entities in those bundles.
      if ($moderated_bundles) {
        $bundle_condition = $this->view->query->getConnection()->condition('AND');
        $bundle_condition->condition("$base_table_alias.{$entity_type->getKey('bundle')}", $moderated_bundles, 'IN');
      }
      // Otherwise, force the query to return an empty result.
      else {
        $this->query->addWhereExpression($this->options['group'], '1 = 0');
        return;
      }
    }

    if ($this->operator === 'in') {
      $operator = '=';
    }
    else {
      $operator = '<>';
    }

    // The values are strings composed from the workflow ID and the state ID,
    // so we need to create a complex WHERE condition.
    $field = $this->view->query->getConnection()->condition('OR');
    foreach ((array) $this->value as $value) {
      [$workflow_id, $state_id] = explode('-', $value, 2);

      $and = $this->view->query->getConnection()->condition('AND');
      $and
        ->condition("$moderation_table_alias.workflow", $workflow_id, '=')
        ->condition("$moderation_table_alias.$this->realField", $state_id, $operator);

      $field->condition($and);
    }

    if ($bundle_condition) {
      // The query must match the bundle AND the workflow/state conditions.
      $bundle_condition->condition($field);
      $this->query->addWhere($this->options['group'], $bundle_condition);
    }
    else {
      $this->query->addWhere($this->options['group'], $field);
    }
  }

  /**
   * Ensures the content_moderation_state_field_revision table is joined.
   *
   * Joins the {content_moderation_state_field_revision} table directly to the
   * view's base entity table on the entity ID and restricts the join to the
   * latest revision of the entity (the row whose revision id equals the
   * MAX(revision id) for that entity in the entity revision table). This
   * guarantees the workflow/state conditions are evaluated against the latest
   * revision's moderation state, regardless of which revision happens to be
   * the default one.
   *
   * @param string|null $base_table_alias
   *   (optional) Alias of the view's base entity data table. Computed if not
   *   provided.
   *
   * @return string
   *   The alias of the {content_moderation_state_field_revision} table that
   *   should be used for the workflow/state conditions.
   */
  protected function getRevisionModerationTableAlias($base_table_alias = NULL) {
    $entity_type = $this->entityTypeManager->getDefinition($this->getEntityType());
    $id_key = $entity_type->getKey('id');
    $revision_key = $entity_type->getKey('revision');
    $revision_table = $entity_type->getRevisionTable();

    if ($base_table_alias === NULL) {
      $base_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
      $base_table_alias = $this->query->ensureTable($base_table, $this->relationship);
    }

    $configuration = [
      'table' => self::MODERATION_STATE_REVISION_TABLE,
      'field' => 'content_entity_id',
      'left_table' => $base_table_alias,
      'left_field' => $id_key,
      'extra' => [
        [
          'field' => 'content_entity_type_id',
          'value' => $entity_type->id(),
        ],
      ],
    ];
    if ($entity_type->isTranslatable()) {
      $configuration['extra'][] = [
        'field' => $entity_type->getKey('langcode'),
        'left_field' => $entity_type->getKey('langcode'),
      ];
    }

    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $alias = $this->query->addRelationship(self::MODERATION_STATE_REVISION_TABLE, $join, $base_table_alias);

    // Restrict the joined moderation rows to the latest revision of each
    // entity. Without this, all historical revisions would match and the
    // workflow/state filter would return entities whose latest revision is
    // NOT in the selected state (as long as some historical revision is).
    $connection = $this->view->query->getConnection();
    $subquery = $connection->select($revision_table, 'rev');
    $subquery->addExpression("MAX(rev.$revision_key)", 'max_rev');
    $subquery->where("rev.$id_key = $base_table_alias.$id_key");
    $this->query->addWhere(
      $this->options['group'],
      "$alias.content_entity_revision_id",
      $subquery,
      'IN'
    );

    return $alias;
  }

}
