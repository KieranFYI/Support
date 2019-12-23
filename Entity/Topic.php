<?php

namespace Kieran\Support\Entity;
	
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Topic extends Entity
{

	public function getSlug() {

		$slug = iconv('UTF-8', 'ASCII//TRANSLIT', $this->title);
		$slug = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $slug);
		$slug = preg_replace("/[\/_|+ -]+/", '-', $slug);
		$slug = strtolower(trim($slug, '-'));

		return $this->topic_id . '-' . $slug;
	}

	public static function getStructure(Structure $structure)
	{
		$structure->table = 'xf_kieran_support_topic';
		$structure->shortName = 'Kieran\Support:Topic';
		$structure->primaryKey = 'topic_id';
		$structure->columns = [
			'topic_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => false, 'changeLog' => false],
			'parent_topic_id' => ['type' => self::UINT, 'default' => 0],
			'title' => ['type' => self::STR, 'required' => true],
			'description' => ['type' => self::STR, 'default' => ''],
			'display_order' => ['type' => self::UINT, 'default' => 1],
			'view_power_required' => ['type' => self::UINT, 'default' => 0],
			'icon' => ['type' => self::STR, 'default' => 'fa-file-alt']
		];
        $structure->getters = [
			'slug' => true,
		];
		$structure->relations = [
			'Parent' => [
				'entity' => 'Kieran\Support:Topic',
				'type' => self::TO_ONE,
				'conditions' => [
					['topic_id', '=', '$parent_topic_id'],
				],
			],
			'Children' => [
				'entity' => 'Kieran\Support:Topic',
				'type' => self::TO_MANY,
				'conditions' => [
					['parent_topic_id', '=', '$topic_id'],
					['display_order', '>', '0'],
				],
			],
			'Articles' => [
				'entity' => 'Kieran\Support:Article',
				'type' => self::TO_MANY,
				'conditions' => [
					['topic_id', '=', '$topic_id'],
					['display_order', '>', '0'],
				]
			]
		];
		
		return $structure;
	}
}