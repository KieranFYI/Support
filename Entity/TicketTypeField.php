<?php

namespace Kieran\Support\Entity;
    
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class TicketTypeField extends Entity
{
	
    public static function getStructure(Structure $structure)
	{
        $structure->table = 'xf_kieran_support_ticket_type_field';
        $structure->shortName = 'Kieran\Support:TicketTypeField';
        $structure->primaryKey = ['type_id', 'field_id'];
        $structure->columns = [
			'type_id' => ['type' => self::UINT, 'required' => true, 'nullable' => false,],
			'field_id' => ['type' => self::STR, 'required' => true, 'nullable' => false, 'maxLength' => 25],
        ];
		$structure->relations = [
			'TicketType' => [
				'entity' => 'Kieran\Support:TicketType',
				'type' => self::TO_ONE,
				'conditions' => 'type_id',
				'primary' => true
			],
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