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

		return $visitor->hasPermission('support', 'view_' . $this->type_id);
	}

	public function canCreate($error=null)
	{
		$visitor = \XF::visitor();

		if (!$visitor->user_id)
		{
			return false;
		}
	
		return $visitor->hasPermission('support', 'create_' . $this->type_id);
	}

	public function canProcess()
	{
		$visitor = \XF::visitor();

		if (!$visitor->user_id)
		{
			return false;
		}
	
		return $visitor->hasPermission('support', 'process_' . $this->type_id);
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
				return $visitor->hasPermission('support', 'soft_delete_' . $this->type_id);
			} else if ($delete_type == 'hard') {
				return $visitor->hasPermission('support', 'hard_delete_' . $this->type_id);
			} else {
				return false;
			}
		}
	}

    public $PermissionTypes = [
        'create',
        'process',
        'view',
        'soft_delete',
        'hard_delete'
    ];

	public function _preDelete() {

		foreach ($this->PermissionTypes as $perm) {
			$perm = $this->em()->find('XF:Permission', [
				'permission_group_id' => 'support',
				'permission_id' => $perm . '_' . $this->type_id
            ]);
            
			if ($perm) {
				$perm->delete();
			}
		}
    }

    public function checkAndCreatePermissions() {

		foreach ($this->PermissionTypes as $key => $perm) {

			$permCheck = $this->em()->find('XF:Permission', [
				'permission_group_id' => 'support',
                'permission_id' => $perm . '_' . $this->type_id,
                'addon_id' => 'Kieran/Support'
            ]);
            
			if (!$permCheck) {
				$permission = $this->em()->create('XF:Permission');
				$permission->permission_id = $perm . '_' . $this->type_id;
				$permission->permission_group_id = 'support';
				$permission->permission_type = 'flag';
				$permission->interface_group_id = 'supportTicket';
				$permission->depend_permission_id = '';
				$permission->display_order = 100 + ($this->type_id * 10) + $key;
				$permission->addon_id = 'Kieran/Support';
                $permission->save();
                
                $title = $permission->getMasterPhrase();
                $title->phrase_text = 'Can ' . str_replace('_', ' ', $perm) . ' ticket type ' . $this->name;
                $title->save();
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