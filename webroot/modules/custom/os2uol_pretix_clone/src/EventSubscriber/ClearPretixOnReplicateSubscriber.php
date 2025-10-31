<?php

namespace Drupal\os2uol_pretix_clone\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\node\NodeInterface;
use Drupal\replicate\Events\ReplicatorEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

final class ClearPretixOnReplicateSubscriber implements EventSubscriberInterface {

    public static function getSubscribedEvents(): array {
        return [
            ReplicatorEvents::REPLICATE_ALTER => 'onReplicateAlter',
        ];
    }

    public function onReplicateAlter(Event $event): void {
        $entity = null;
        if (method_exists($event, 'getEntity')) {
            $entity = $event->getEntity();
        }

        if (!$entity instanceof NodeInterface) {
            return;
        }

        foreach ($entity->getFieldDefinitions() as $fieldName => $definition) {
            $type = (string) $definition->getType();
            $looksPretix = str_contains($type, 'pretix') || str_contains($fieldName, 'pretix');
            if ($looksPretix && $entity->hasField($fieldName)) {
                $this->clearField($entity, $fieldName);
            }
        }
    }

    private function clearField(ContentEntityInterface $entity, string $fieldName): void {
        $field = $entity->get($fieldName);
        $def = $field->getFieldDefinition();
        $storage = $def->getFieldStorageDefinition();
        $type = $def->getType();

        // Clear entity/reference-like fields first.
        if (in_array($type, ['entity_reference', 'entity_reference_revisions', 'dynamic_entity_reference'], true)) {
            $field->setValue([]);
            return;
        }

        if ($storage->isMultiple()) {
            $field->setValue([]);
            return;
        }

        $entity->set($fieldName, null);
    }

}
