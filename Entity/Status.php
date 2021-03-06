<?php

namespace Kieran\Support\Entity;
    
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Status extends Entity
{

	public function canUse($user=null)
	{
		if ($user == null) {
			$user = \XF::visitor();
		}

		if (!$user->user_id)
		{
			return false;
		}

		return count(array_intersect(array_merge($user->secondary_group_ids, [$user->user_group_id]), $this->groups));
	}

	public function canDelete()
	{
		return !$this->getRelationOrDefault('Tickets', false)->count();
	}

	public function isFieldSelected($field_id)
	{
		return isset($this->fields[$field_id]);
	}

	public function getFields()
	{
		$fields = $this->finder('Kieran\Support:Field')->where('content_type', 'status')->where('content_id', $this->status_id)->fetch();
		$a = [];
		foreach ($fields as $field) {
			$a[] = $field['field_id'];
		}
		$a = $this->finder('Kieran\Support:TicketField')->where('field_id', $a)->fetch();

		foreach ($a as $key => $value) {
			if ($value->field_type == 'bbcode') {
				unset($a[$key]);
			}
		}

		return $a;
	}

    public static function getStructure(Structure $structure)
	{
        $structure->table = 'xf_kieran_support_ticket_status';
        $structure->shortName = 'Kieran\Support:Status';
        $structure->primaryKey = 'status_id';
        $structure->columns = [
			'status_id' => ['type' => self::STR, 'nullable' => false, 'match' => 'alphanumeric', 'unique' => 'kieran_suport_status_ids_must_be_unique'],
			'name' => ['type' => self::STR, 'maxLength' => 100, 'required' => true],
			'message' => ['type' => self::STR, 'maxLength' => 200, 'default' => ''],
			'state' => ['type' => self::STR, 'default' => 'visible', 
				'allowedValues' => ['visible', 'locked', 'hidden', 'awaiting', 'closed', 'deleted']
			],
            'enabled' => ['type' => self::UINT, 'default' => 0],
            'undeletable' => ['type' => self::UINT, 'default' => 0],
			'groups' => ['type' => self::LIST_COMMA, 'default' => [],
				'list' => ['type' => 'posint', 'unique' => true, 'sort' => SORT_NUMERIC]
			],
        ];
        $structure->getters = [
			'fields' => true
		];
		$structure->relations = [
			'Tickets' => [
				'entity' => 'Kieran\Support:Ticket',
				'type' => self::TO_MANY,
				'conditions' => 'status_id',
				'primary' => true
			],
		];
        
        return $structure;
    }
}