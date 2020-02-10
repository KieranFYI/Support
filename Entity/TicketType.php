<?php

namespace Kieran\Support\Entity;
    
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class TicketType extends Entity
{

	public function isFieldSelected($field_id)
	{
		return isset($this->fields[$field_id]);
	}

	public function isStatusSelected($status)
	{
		return isset($this->statuses[$status]);
	}

	public function canView($user=null)
	{
		if ($user == null) {
			$user = \XF::visitor();
		}

		if (!$user->user_id)
		{
			return false;
		}

		return count(array_intersect(array_merge($user->secondary_group_ids, [$user->user_group_id]), $this->groups_view));
	}

	public function canCreate($user=null)
	{
		if ($user == null) {
			$user = \XF::visitor();
		}

		if (!$user->user_id)
		{
			return false;
		}
	
		return count(array_intersect(array_merge($user->secondary_group_ids, [$user->user_group_id]), $this->groups_create));
	}

	public function canProcess($user=null)
	{
		if ($user == null) {
			$user = \XF::visitor();
		}

		if (!$user->user_id)
		{
			return false;
		}
	
		return count(array_intersect(array_merge($user->secondary_group_ids, [$user->user_group_id]), $this->groups_process));
	}

	public function canDelete($delete_type = null)
	{
		if ($delete_type === null) {
			return !$this->getRelationOrDefault('Tickets', false)->count();
		} else {
			$user = \XF::visitor();
			$delete_type = strtolower($delete_type);

			if (!$user->user_id)
			{
				return false;
			}
			
			if ($delete_type == 'soft') {
				return count(array_intersect(array_merge($user->secondary_group_ids, [$user->user_group_id]), $this->groups_delete_soft));
			} else if ($delete_type == 'hard') {
				return count(array_intersect(array_merge($user->secondary_group_ids, [$user->user_group_id]), $this->groups_delete_hard));
			} else {
				return false;
			}
		}
	}
	
	public function getDraftReply()
	{
		return \XF\Draft::createFromEntity($this, 'DraftReplies');
	}

	public function getFields()
	{
		$fields = $this->finder('Kieran\Support:Field')->where('content_type', 'type')->where('content_id', $this->type_id)->fetch();
		$a = [];
		foreach ($fields as $field) {
			$a[] = $field['field_id'];
		}
		
		$a = $this->finder('Kieran\Support:TicketField')->where('field_id', $a)->order('display_order', 'asc')->fetch();

		foreach ($a as $key => $value) {
			if ($value->field_type == 'bbcode') {
				unset($a[$key]);
			}
		}

		return $a;
	}

	public function getStatuses()
	{
		$fields = $this->finder('Kieran\Support:TicketTypeStatus')->where('type_id', $this->type_id)->fetch();
		$a = [2];
		foreach ($fields as $field) {
			$a[] = $field['status_id'];
		}
		
		return $this->finder('Kieran\Support:Status')->where('status_id', $a)->fetch();
	}

    public static function getStructure(Structure $structure)
	{
        $structure->table = 'xf_kieran_support_ticket_type';
        $structure->shortName = 'Kieran\Support:TicketType';
        $structure->primaryKey = 'type_id';
        $structure->columns = [
			'type_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => false, 'changeLog' => false],
			'name' => ['type' => self::STR, 'required' => true],
            'description' => ['type' => self::STR, 'default' => ''],
            'enabled' => ['type' => self::UINT, 'default' => 0],
            'hide_title' => ['type' => self::UINT, 'default' => 0],
            'hide_message' => ['type' => self::UINT, 'default' => 0],
			'require_priority' => ['type' => self::UINT, 'default' => 0],
			'groups_view' => ['type' => self::LIST_COMMA, 'default' => [],
				'list' => ['type' => 'posint', 'unique' => true, 'sort' => SORT_NUMERIC]
			],
			'groups_create' => ['type' => self::LIST_COMMA, 'default' => [],
				'list' => ['type' => 'posint', 'unique' => true, 'sort' => SORT_NUMERIC]
			],
			'groups_process' => ['type' => self::LIST_COMMA, 'default' => [],
				'list' => ['type' => 'posint', 'unique' => true, 'sort' => SORT_NUMERIC]
			],
			'groups_delete_soft' => ['type' => self::LIST_COMMA, 'default' => [],
				'list' => ['type' => 'posint', 'unique' => true, 'sort' => SORT_NUMERIC]
			],
			'groups_delete_hard' => ['type' => self::LIST_COMMA, 'default' => [],
				'list' => ['type' => 'posint', 'unique' => true, 'sort' => SORT_NUMERIC]
			]
        ];
        $structure->getters = [
			'draft_reply' => true,
			'fields' => true,
			'statuses' => true
		];
		$structure->relations = [
			'DraftReplies' => [
				'entity' => 'XF:Draft',
				'type' => self::TO_MANY,
				'conditions' => [
					['draft_key', '=', 'type-comment-', '$type_id']
				],
				'key' => 'user_id'
			],
			'Tickets' => [
				'entity' => 'Kieran\Support:Ticket',
				'type' => self::TO_MANY,
				'conditions' => 'type_id',
				'primary' => true
			],
		];
        
        return $structure;
    }
}