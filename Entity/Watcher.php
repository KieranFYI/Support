<?php

namespace Kieran\Support\Entity;
    
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Watcher extends Entity
{

    public static function getStructure(Structure $structure)
	{
        $structure->table = 'xf_kieran_support_ticket_watcher';
        $structure->shortName = 'Kieran\Support:Watcher';
        $structure->primaryKey = ['ticket_id', 'user_id'];
        $structure->columns = [
			'ticket_id' => ['type' => self::UINT, 'required' => true, 'nullable' => false,],
			'user_id' => ['type' => self::UINT, 'required' => true, 'nullable' => false,],
        ];
        $structure->getters = [
		];
		$structure->relations = [
			'Ticket' => [
				'entity' => 'Kieran\Support:Ticket',
				'type' => self::TO_ONE,
				'conditions' => 'ticket_id',
				'primary' => true
			],
			'User' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => 'user_id',
				'primary' => true
			],
		];
        
        return $structure;
    }
}