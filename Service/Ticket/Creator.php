<?php

namespace Kieran\Support\Service\Ticket;

use Kieran\Support\Ticket;
use Kieran\Support\TicketComment;
use XF\Entity\User;
use XF\Mvc\Entity\Entity;

class Creator extends \XF\Service\AbstractService
{
	use \XF\Service\ValidateAndSavableTrait;

	/** @var Ticket */
	protected $ticket;

	/** @var TicketComment */
	protected $comment;

	/** @var CommentPreparer */
	protected $commentPreparer;

	/** @var User */
	protected $user;

	public function __construct(\XF\App $app, $ticket_type, $ticket_title)
	{
		parent::__construct($app);

		$this->user = \XF::visitor();

		$this->createTicket($ticket_type, $ticket_title);
		$this->setupComment();
		$this->setDefaults();
	}
	
	private function createTicket($ticket_type, $ticket_title)
	{
		$ticket = $this->em()->create('Kieran\Support:Ticket');
		$ticket->type_id = $ticket_type;
        $ticket->ticket_title = $ticket_title;

		$this->ticket = $ticket;
	}

	protected function setDefaults()
	{
		$time = \XF::$time;
		$user = $this->user;

		if (!$this->ticket->ticket_id)
		{
			$this->ticket->ticket_date = $time;
		}

        $this->ticket->user_id = $user->user_id;

		$this->ticket->last_modified_date = $time;
		$this->ticket->last_modified_user_id = $user->user_id;
		if ($this->ticket->status_id != 'assigned')
		{
			$this->ticket->status_id = 'new';
			$this->ticket->state = 'visible';
			$this->comment->status_change = 'new';
		}

		$this->commentPreparer->setUser($user);
	}

	protected function setupComment()
	{
		$this->comment = $this->ticket->getNewComment();
		$this->comment->is_ticket = true;
		$this->commentPreparer = $this->service('Kieran\Support:Ticket\CommentPreparer', $this->comment);

		$this->ticket->addCascadedSave($this->comment);
	}

	public function getCommentPreparer()
	{
		return $this->commentPreparer;
	}

	public function setMessage($message, $format = true)
	{
		return $this->commentPreparer->setMessage($message, $format);
	}

	public function getTicket() {
		return $this->ticket;
	}

	public function getComment() {
		return $this->comment;
	}

	public function setAttachmentHash($hash)
	{
		return $this->commentPreparer->setAttachmentHash($hash);
	}

	protected function _validate()
	{
		$this->ticket->preSave();
		return $this->ticket->getErrors();
	}

	protected function _save()
	{
		$ticket = $this->ticket;

		$db = $this->db();
		$db->beginTransaction();

		// comment will be saved now if applicable
		$ticket->save(true, false);
		
		$db->commit();

		return $ticket;
	}
}