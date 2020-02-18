<?php

namespace Kieran\Support\Entity;
    
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class TicketTypeStatus extends Entity
{

    public static function getStructure(Structure $structure)
	{
        $structure->table = 'xf_kieran_support_ticket_type_status';
        $structure->shortName = 'Kieran\Support:TicketTypeStatus';
        $structure->primaryKey = ['status_id', 'type_id'];
        $structure->columns = [
			'status_id' => ['type' => self::STR, 'required' => true, 'nullable' => false, 'maxLength' => 25],
			'type_id' => ['type' => self::STR, 'required' => true, 'nullable' => false],
        ];
        
        return $structure;
    }
}