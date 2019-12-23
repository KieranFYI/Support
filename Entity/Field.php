<?php

namespace Kieran\Support\Entity;
    
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Field extends Entity
{
	
    public static function getStructure(Structure $structure)
	{
        $structure->table = 'xf_kieran_support_field';
        $structure->shortName = 'Kieran\Support:Field';
        $structure->primaryKey = ['content_id', 'field_id', 'content_type'];
        $structure->columns = [
			'content_id' => ['type' => self::STR, 'required' => true, 'nullable' => false, 'maxLength' => 25],
			'field_id' => ['type' => self::STR, 'required' => true, 'nullable' => false, 'maxLength' => 25],
			'content_type' => ['type' => self::STR, 'required' => true, 'nullable' => false, 'maxLength' => 25]
        ];
		$structure->relations = [
			'Field' => [
				'entity' => 'Kieran\Support:TicketField',
				'type' => self::TO_ONE,
				'conditions' => 'user_id',
				'primary' => true
			],
		];
        
        return $structure;
    }
}