<?php

namespace Carnival\Admin\Core\Manager;

use Carnival\Entity\Language;
use Lampion\Database\Query;
use Lampion\Entity\EntityManager;
use Lampion\Http\Url;
use stdClass;

class Translation {

    public static function getTranslatables(object $entity) {
        $em = new EntityManager();
        $entityName = get_class($entity);

        $translatables = [];

        foreach(array_keys((array)$entity) as $key) {
            if (
                isset($em->metadata($entityName)->{$key}->translatable) &&
                $em->metadata($entityName)->{$key}->translatable == 'true'
            ) {
                $translatables[] = $key;
            }
        }

        return $translatables;
    }

    public static function hasTranslatables(object $entity) {
        $em = new EntityManager();
        $entityName = get_class($entity);

        foreach(array_keys((array)$entity) as $key) {
            if (
                isset($em->metadata($entityName)->{$key}->translatable) &&
                $em->metadata($entityName)->{$key}->translatable == 'true'
            ) {
                return true;
            }
        }

        return false;
    }

    public static function isTranslatable(string $fieldName, string $entityName) {
        $em = new EntityManager();

        if (
            isset($em->metadata($entityName)->{$fieldName}->translatable) &&
            $em->metadata($entityName)->{$fieldName}->translatable == 'true'
        ) {            
            return true;
        }

        return false;
    }

    public static function isParent(object $entity) {
        $child = Query::select('translations', ['child_id'], [
            'entity_name' => get_class($entity),
            'child_id'    => $entity->id
        ])[0];

        $parent = Query::select('translations', ['parent_id'], [
            'entity_name' => get_class($entity),
            'parent_id'   => $entity->id
        ])[0];

        if(isset($parent['parent_id']) && $parent['parent_id'] == $child['child_id']) {
            return true;
        }

        if(!empty($child)) {
            return false;
        }

        return true;
    }

    public static function getChildren(object $parent) {
        $em = new EntityManager();

        $q = Query::select('translations', ['*'], [
            'entity_name' => get_class($parent),
            'parent_id'   => $parent->id
        ]);

        if(empty($q[0]['child_id'])) {
            return [];
        }

        $returnObj = new stdClass();

        foreach($q as $value) {
            $language = $em->find(Language::class, $value['language_id']);

            $returnObj->{$language->code} = $em->find($value['entity_name'], $value['child_id']);
        }

        return $returnObj;
    }
    
    public static function deleteChildren(object $parent) {
        $em = new EntityManager();

        foreach(self::getChildren($parent) as $child) {
            $em->destroy($child);

            Query::delete('translations', [
                'child_id'    => $child->id,
                'entity_name' => get_class($child)
            ]);
        }
    }

    public static function getTranslation(object $parent, object $language) {
        $em = new EntityManager();

        $childId = Query::select('translations', ['child_id'], [
            'entity_name' => get_class($parent),
            'parent_id'   => $parent->id,
            'language_id' => $language->id
        ])[0]['child_id'];
    
        return $em->find(get_class($parent), $childId);
    }

    public function deleteGet($request, $response) {
        $em = new EntityManager();

        $language = $em->find(Language::class, $request->query('id'));
        $translations = Query::select('translations', ['*'], [
            'language_id' => $language->id
        ]);

        if(!empty($translations[0])) {
            foreach($translations as $translation) {
                $entity = $em->find($translation['entity_name'], $translation['child_id']);
    
                $em->destroy($entity);
                Query::delete('translations', [ 'child_id' => $translation['child_id'] ]);
            }
        }

        $em->destroy($language);

        if(!$request->isAjax()) {
            Url::redirect('Language', [
                'success' => 'delete'
            ]);
        }

        else {
            $response->json([
                'href' => Url::link('Language', [
                    'success' => 'delete'
                ])
            ]);
        }
    }

}