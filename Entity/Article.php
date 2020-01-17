<?php

namespace Kieran\Support\Entity;
    
use XF;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Article extends Entity
{

    public function canView() {
        return count(array_intersect($this->groups, array_merge(XF::visitor()->secondary_group_ids, [XF::visitor()->user_group_id]))) > 0;
    }

    public function getSlug() {

        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $this->title);
        $slug = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $slug);
        $slug = preg_replace("/[\/_|+ -]+/", '-', $slug);
        $slug = strtolower(trim($slug, '-'));

        return $this->article_id . '-' . $slug;
    }

    public static function getStructure(Structure $structure)
	{
        $structure->table = 'xf_kieran_support_article';
        $structure->shortName = 'Kieran\Support:Article';
        $structure->primaryKey = 'article_id';
        $structure->columns = [
			'article_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => false, 'changeLog' => false],
            'topic_id' =>  ['type' => self::UINT, 'nullable' => false],
			'title' => ['type' => self::STR, 'required' => true],
            'description' => ['type' => self::STR, 'default' => ''],
            'display_order' => ['type' => self::UINT, 'default' => 1],
			'groups' => ['type' => self::LIST_COMMA, 'default' => [],
				'list' => ['type' => 'posint', 'unique' => true, 'sort' => SORT_NUMERIC]
			],

            'type' =>  ['type' => self::STR, 'default' => 'bbcode',
                'allowedValues' => ['bbcode', 'link']
            ],
            'message' => ['type' => self::STR, 'default' => ''],

            'is_faq' => ['type' => self::UINT, 'default' => 0],
            'callback_class' => ['type' => self::STR, 'default' => ''],
            'callback_method' => ['type' => self::STR, 'default' => ''],
        ];
        $structure->getters = [
            'slug' => true,
        ];
		$structure->relations = [
            'Topic' => [
                'entity' => 'Kieran\Support:Topic',
                'type' => self::TO_ONE,
                'conditions' => 'topic_id',
                'primary' => true
            ]
		];
        
        return $structure;
    }
}