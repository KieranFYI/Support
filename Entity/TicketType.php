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

	public function canView($error=null)
	{
		$visitor = \XF::visitor();

		if (!$visitor->user_id)
		{
			return false;
		}

		return $visitor->hasPermission('support', $this->permission_view);
	}

	public function canCreate($error=null)
	{
		$visitor = \XF::visitor();

		if (!$visitor->user_id)
		{
			return false;
		}
	
		return $visitor->hasPermission('support', $this->permission_create);
	}

	public function canProcess()
	{
		$visitor = \XF::visitor();

		if (!$visitor->user_id)
		{
			return false;
		}
	
		return $visitor->hasPermission('support', $this->permission_process);
	}

	public function canDelete($delete_type = null)
	{
		if ($delete_type === null) {
			return !$this->getRelationOrDefault('Tickets', false)->count();
		} else {
			$visitor = \XF::visitor();
			$delete_type = strtolower($delete_type);

			if (!$visitor->user_id)
			{
				return false;
			}
			
			if ($delete_type == 'soft') {
				return $visitor->hasPermission('support', $this->permission_soft_delete);
			} else if ($delete_type == 'hard') {
				return $visitor->hasPermission('support', $this->permission_hard_delete);
			} else {
				return false;
			}
		}
	}

	public function _preDelete() {

		$types = ['create', 'process', 'view', 'soft_delete', 'hard_delete'];
		foreach ($types as $perm) {
			$perm = $this->em()->find('XF:Permission', [
				'permission_group_id' => 'support',
				'permission_id' => $perm . '_' . strtolower(str_replace(' ', '_', $this->name))
			]);
			if ($perm) {
				$perm->delete();
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
            'permission_create' => ['type' => self::STR, 'default' => ''],
            'permission_process' => ['type' => self::STR, 'default' => ''],
            'permission_view' => ['type' => self::STR, 'default' => ''],
            'permission_soft_delete' => ['type' => self::STR, 'default' => ''],
            'permission_hard_delete' => ['type' => self::STR, 'default' => ''],
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