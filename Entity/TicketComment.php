<?php

namespace Kieran\Support\Entity;
	
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class TicketComment extends Entity
{

	public function canView($error=null)
	{
		return $this->Ticket->canView();
	}

	public $attachmentHash = null;

	public function hasSaveableChanges()
	{
		return (
			$this->status_change ||
			$this->attachmentHash ||
			$this->message ||
			$this->Ticket->isChanged('assigned_user_id')
		);
	}

	protected function _preSave()
	{
		if (!$this->hasSaveableChanges())
		{
			$this->error(\XF::phrase('please_enter_valid_message'), 'message');
		}
	}

	public function _postSave()
	{
		$this->writeIpLog();

		if ($this->attachmentHash)
		{
			$this->associateAttachments($this->attachmentHash);
		}

		if ($this->status_change) {
			$this->app()->fire('ticket_update_status', [$this]);
		}
	}

	protected function associateAttachments($hash)
	{
		/** @var \XF\Service\Attachment\Preparer $inserter */
		$inserter = $this->app()->service('XF:Attachment\Preparer');
		$associated = $inserter->associateAttachmentsWithContent($hash, 'ticket_message', $this->ticket_comment_id);
		if ($associated)
		{
			$this->fastUpdate('attach_count', $this->attach_count + $associated);
		}
	}

	protected function writeIpLog()
	{
		$ip = $this->app()->request()->getIp();
		if (!$this->user_id)
		{
			return;
		}

		/** @var \XF\Repository\IP $ipRepo */
		$ipRepo = $this->repository('XF:Ip');
		$ipEnt = $ipRepo->logIp($this->user_id, $ip, 'ticket_comment', $this->ticket_comment_id);
		if ($ipEnt)
		{
			$this->fastUpdate('ip_id', $ipEnt->ip_id);
		}
	}

	public function isAttachmentEmbedded($attachmentId)
	{
		return false;
	}

	public static function getStructure(Structure $structure)
	{
		$structure->table = 'xf_kieran_support_ticket_comment';
		$structure->shortName = 'Kieran\Support:TicketComment';
		$structure->primaryKey = 'ticket_comment_id';
		$structure->columns = [
			'ticket_comment_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => false, 'changeLog' => false],
			'ticket_id' =>  ['type' => self::UINT, 'nullable' => false],
			'user_id' =>  ['type' => self::UINT, 'required' => true],
			'ip_id' => ['type' => self::UINT, 'default' => 0],
			'message' => ['type' => self::STR, 'default' => ''],
			'message_state' =>  ['type' => self::STR, 'default' => 'visible',
				'allowedValues' => ['visible', 'hidden', 'deleted']
			],
			'comment_date' => ['type' => self::UINT, 'default' => \XF::$time],
			'status_change' =>  ['type' => self::STR, 'default' => ''],
			'assigned_user_id' => ['type' => self::UINT, 'default' => 0],
			'is_ticket' => ['type' => self::BOOL, 'default' => false],
			'attach_count' => ['type' => self::UINT, 'max' => 65535, 'forced' => true, 'default' => 0],
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
			'AssignedUser' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => [
					['user_id', '=', '$assigned_user_id']
				],
				'primary' => true
			],
			'Attachments' => [
				'entity' => 'XF:Attachment',
				'type' => self::TO_MANY,
				'conditions' => [
					['content_type', '=', 'ticket_message'],
					['content_id', '=', '$ticket_comment_id']
				],
				'with' => 'Data',
				'order' => 'attach_date'
			],
			'DeletionLog' => [
				'entity' => 'XF:DeletionLog',
				'type' => self::TO_ONE,
				'conditions' => [
					['content_type', '=', 'ticket'],
					['content_id', '=', '$ticket_comment_id']
				],
				'primary' => true
			],
			'CustomFields' => [
				'entity' => 'Kieran\Support:TicketFieldValue',
				'type' => self::TO_MANY,
				'conditions' => 'ticket_id',
				'key' => 'field_id'
			],
			'status' => [
				'entity' => 'Kieran\Support:Status',
				'type' => self::TO_ONE,
				'conditions' => [
					['status_id', '=', '$status_change'],
				],
				'primary' => true
			],
			'FieldValues' => [
				'entity' => 'Kieran\Support:TicketFieldValue',
				'type' => self::TO_MANY,
				'conditions' => 'ticket_comment_id',
				'primary' => true
			],
		];
		
		return $structure;
	}
}