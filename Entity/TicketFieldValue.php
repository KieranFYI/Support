<?php

namespace Kieran\Support\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int user_id
 * @property string field_id
 * @property string field_value
 */
class TicketFieldValue extends Entity
{
	public static function getStructure(Structure $structure)
	{
		$structure->table = 'xf_kieran_support_ticket_field_value';
		$structure->shortName = 'Kieran\Support:TicketFieldValue';
		$structure->primaryKey = ['ticket_comment_id', 'field_id'];
		$structure->columns = [
			'ticket_comment_id' => ['type' => self::UINT, 'required' => true],
			'field_id' => ['type' => self::STR, 'maxLength' => 25,
				'match' => 'alphanumeric'
			],
			'field_value' => ['type' => self::STR, 'default' => '']
		];
		$structure->getters = [];
		$structure->relations = [];

		return $structure;
	}
}