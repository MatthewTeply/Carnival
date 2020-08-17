<?php

namespace Carnival\Admin\Core\Manager;

use Carnival\Entity\Timeline as TimelineEntity;
use Lampion\Database\Query;
use Lampion\Entity\EntityManager;
use Lampion\Http\Url;
use Lampion\Language\Translator;

class Timeline {

    public static function set(TimelineEntity $timeline) {
        $em = new EntityManager();

        $em->persist($timeline);
    }

    public static function get(TimelineEntity $timeline) {
        $em = new EntityManager();
        $translator = new Translator($_SESSION['Lampion']['lang']);

        if($timeline->entity_id) {
            $timeline->entity = $em->find((string)$timeline->entity_name, $timeline->entity_id);
        }

        $timeline->title = $translator->read('timeline')->get($timeline->title);

        $entityName = explode('\\', $timeline->entity_name);
        $entityName = end($entityName);

        switch($timeline->content) {
            case 'new':
                $timeline->content = $timeline->user . ' ' . $translator->read('timeline')->get('created');
                break;
            case 'edit':
                $timeline->content = $timeline->user . ' ' . $translator->read('timeline')->get('edited');
                break;
            case 'delete':
                $timeline->content = $timeline->user . ' ' . $translator->read('timeline')->get('deleted');
                break;
            default:
                break;
        }

        if(!$timeline->entity) {
            $q = Query::select('timeline_trash', ['name', 'entity_id'], [
                'entity_name' => $timeline->entity_name,
                'entity_id'   => $timeline->entity_id
            ])[0];

            $timeline->content .= ' <span class="text-danger">' . $q['name'] . ' (#' . $q['entity_id'] . ')</span>';
        }

        else {
            $timeline->content .= ' <a href="' . Url::link($entityName . '/show', ['id' => $timeline->entity_id]) . '">' . $timeline->entity . ' (#' . $timeline->entity_id . ')</a>';
        }


        return $timeline;
    }

    public static function trashEntity(object $entity) {
        Query::insert('timeline_trash', [
            'entity_name' => get_class($entity),
            'entity_id'   => $entity->id,
            'name'        => $entity
        ]);
    }

}