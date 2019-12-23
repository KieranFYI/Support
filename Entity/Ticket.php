<?php

namespace Kieran\Support\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Ticket extends Entity
{

	public function addWatcher($user_id) {
		if ($this->isWatching($user_id)) {
			return;
		}
		$watcher = $this->em()->create('Kieran\Support:Watcher');
		$watcher->ticket_id = $this->ticket_id;
		$watcher->user_id = $user_id;
		$watcher->save();
	}

	public function removeWatcher($user_id) {
		if (!$this->isWatching($user_id)) {
			return;
		}
		$perm = $this->em()->find('Kieran\Support:Watcher', [
			'user_id' => $user_id,
			'ticket_id' => $this->ticket_id
		]);
		$perm->delete();
	}

	public function isWatching($user_id=false) {

		if (!$user_id) {
			$visitor = \XF::visitor();
			$user_id = $visitor->user_id;
		}	

		if (!$user_id)
		{
			return false;
		}

		foreach ($this->watchers as $watcher) {
			if ($watcher->user_id == $user_id) {
				return true;
			}
		}

		return false;
	}

	public function findLastCommentBy($user_id) {
		return $this->getTicketCommentRepo()->findLastCommentBy($user_id, $this->ticket_id);
	}

	public function notifyWatchers($current_user = false) {

		if ($current_user) {
			$current_user = $this->em()->find('XF:User', $current_user);
		}

		foreach ($this->watchers as $watcher) {

			if (!$watcher->hasPermission('support', $this->TicketType->permission_view)
				|| $current_user->user_id == $watcher->user_id) {
				continue;
			}

			$alertRepo = $this->repository('XF:UserAlert');
			$alertRepo->alert(
				$watcher,
				$current_user ? $current_user->user_id : 0,
				$current_user ? $current_user->username : '',
				'ticket_message', 
				$this->ticket_id,
				$this->user_id == $watcher->user_id ? 'updated' : 'watched', 
				[]
			);
		}

	}

	public function canView($error=null)
	{

		if ($this->status->state == 'deleted') {
			return false;
		}

		if ($this->user_id == \XF::visitor()->user_id) {
			return true;
		}

		return $this->TicketType->canView();
	}

	public function canProcess()
	{

		if ($this->status->state == 'deleted') {
			return false;
		}

		return $this->TicketType->canProcess();
	}

	public function canDelete($delete_type)
	{
		$delete_type = strtolower($delete_type);

		return $this->TicketType->canDelete($delete_type);
	}

	public function getUsersWhoCanHandleTicket($ticket_type = null)
	{
		if ($ticket_type === null)
		{
			$ticket_type = $this->TicketType;
		}

		return $this->getTicketRepo()->findUsersWithPermission('support', $ticket_type->permission_process, true);
	}

	public function getNewComment()
	{
		$comment = $this->_em->create('Kieran\Support:TicketComment');

		$comment->ticket_id = $this->_getDeferredValue(function()
			{
				return $this->ticket_id;
			}, 'save'
		);

		return $comment;
	}

	public function isClosed()
	{
		return $this->state == 'closed';
	}


	public static function getStructure(Structure $structure)
	{
		$structure->table = 'xf_kieran_support_ticket';
		$structure->shortName = 'Kieran\Support:Ticket';
		$structure->primaryKey = 'ticket_id';
		$structure->columns = [
			'ticket_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => false, 'changeLog' => false],
			'user_id' =>  ['type' => self::UINT, 'required' => true],
			'type_id' => ['type' => self::UINT, 'default' => 'other'],
			'ticket_title' => ['type' => self::STR, 'default' => ''],
			'status_id' =>  ['type' => self::STR, 'default' => 'new'],
			'state' =>  ['type' => self::STR, 'default' => 'visible',
				'allowedValues' => ['visible', 'hidden', 'deleted','locked', 'awaiting', 'closed', 'deleted']
			],
			'assigned_user_id' => ['type' => self::UINT, 'default' => 0],
			'comment_count' => ['type' => self::UINT, 'forced' => true, 'default' => 0],
			'last_modified_date' => ['type' => self::UINT, 'default' => \XF::$time],
			'last_modified_user_id' => ['type' => self::UINT, 'default' => 0],
			'ticket_date' => ['type' => self::UINT, 'default' => \XF::$time],
		];
		$structure->getters = [
			'draft_reply' => true,
			'watchers' => true,
		];
		$structure->relations = [
			'User' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => [
					['user_id', '=', '$user_id']
				],
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
			'LastModifiedUser' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => [
					['user_id', '=', '$last_modified_user_id']
				],
				'primary' => true
			],
			'Comments' => [
				'entity' => 'Kieran\Support:TicketComment',
				'type' => self::TO_MANY,
				'conditions' => [ 
					['ticket_id', '=', '$ticket_id'],
					['message_state', '!=', 'deleted'],
				],
				'order' => ['ticket_comment_id', 'asc']
			],
			'DraftReplies' => [
				'entity' => 'XF:Draft',
				'type' => self::TO_MANY,
				'conditions' => [
					['draft_key', '=', 'ticket-comment-', '$ticket_id']
				],
				'key' => 'user_id'
			],
			'DeletionLog' => [
				'entity' => 'XF:DeletionLog',
				'type' => self::TO_ONE,
				'conditions' => [
					['content_type', '=', 'support_ticket'],
					['content_id', '=', '$ticket_id']
				],
				'primary' => true
			],
			'TicketType' => [
				'entity' => 'Kieran\Support:TicketType',
				'type' => self::TO_ONE,
				'conditions' => 'type_id',
				'primary' => true
			],
			'status' => [
				'entity' => 'Kieran\Support:Status',
				'type' => self::TO_ONE,
				'conditions' => 'status_id',
				'primary' => true
			],
			'Watcher' => [
				'entity' => 'Kieran\Support:Watcher',
				'type' => self::TO_MANY,
				'conditions' => 'ticket_id',
				'primary' => true
			]
		];

		return $structure;
	}

	public function getWatchers()
	{
		$watchers = $this->finder('Kieran\Support:Watcher')->where('ticket_id', $this->ticket_id)->fetch();
		$a = [$this->em()->find('XF:User', $this->user_id)];
		foreach ($watchers as $watcher) {
			$a[] = $this->em()->find('XF:User', $watcher['user_id']);
		}
		return $a;
	}
	
	public function getDraftReply()
	{
		return \XF\Draft::createFromEntity($this, 'DraftReplies');
	}

	/**
	 * @return \Kieran\Support\Repository\Ticket
	 */
	protected function getTicketRepo()
	{
		return $this->repository('Kieran\Support:Ticket');
	}

	protected function getTicketCommentRepo()
	{
		return $this->repository('Kieran\Support:TicketComment');
	}

	protected function getTicketTypeRepo()
	{
		return $this->repository('Kieran\Support:TicketType');
	}
}
