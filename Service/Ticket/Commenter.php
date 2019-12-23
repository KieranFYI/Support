<?php

namespace Kieran\Support\Service\Ticket;

use Kieran\Support\Entity\Ticket;
use Kieran\Support\Entity\TicketComment;

class Commenter extends \XF\Service\AbstractService
{
	use \XF\Service\ValidateAndSavableTrait;

	/**
	 * @var Ticket
	 */
	protected $ticket;

	/**
	 * @var TicketComment
	 */
	protected $comment;

	/**
	 * @var CommentPreparer
	 */
	protected $commentPreparer;

	protected $alertComment;
	protected $sendAlert = false;

	public function __construct(\XF\App $app, Ticket $ticket)
	{
		parent::__construct($app);

		$this->ticket = $ticket;
		$this->comment = $ticket->getNewComment();
		$this->comment->addCascadedSave($this->ticket);
		$this->commentPreparer = $this->service('Kieran\Support:Ticket\CommentPreparer', $this->comment);
		$this->setCommentDefaults();
	}

	public function getTicket()
	{
		return $this->ticket;
	}

	public function getComment()
	{
		return $this->comment;
	}

	public function getCommentPreparer()
	{
		return $this->commentPreparer;
	}

	protected function setCommentDefaults()
	{
		$visitor = \XF::visitor();

		$this->commentPreparer->setUser($visitor);
		$this->comment->is_ticket = false;
		$this->comment->comment_date = \XF::$time;

		$this->ticket->last_modified_date = time();
		$this->ticket->last_modified_user_id = $visitor->user_id;

		if ($this->ticket->isClosed())
		{
			$this->setTicketStatus('open');
		}
	}

	public function setTicketStatus($status = null)
	{
		$status =  $this->finder('Kieran\Support:Status')->where('status_id', $status)->fetchOne();
	
		if ($status)
		{
			$this->ticket->state = $status->state;
			$this->ticket->status_id = $status->status_id;

			$this->comment->status_change = $status->status_id;
		}
	}

	public function setTicketAssignedTo($assigned_user_id = 0)
	{
		$this->ticket->assigned_user_id = $assigned_user_id;

		$this->comment->assigned_user_id = $assigned_user_id;
	}

	public function setTicketState($state = null)
	{
		$this->ticket->state = $state;

		$this->comment->status_change = $state;
	}

	public function setupClosedAlert($alertComment)
	{
		$this->alertComment = $alertComment;
		$this->sendAlert = true;
	}

	public function setMessage($message, $format = true)
	{
		return $this->commentPreparer->setMessage($message, $format);
	}

	public function setAttachmentHash($hash)
	{
		$this->commentPreparer->setAttachmentHash($hash);
	}

	public function hasSaveableChanges()
	{
		return $this->comment->hasSaveableChanges();
	}

	protected function _validate()
	{
		$this->comment->preSave();
		return $this->comment->getErrors();
	}

	protected function _save()
	{
		$comment = $this->comment;
		$ticket = $this->ticket;

		$db = $this->db();
		$db->beginTransaction();

		// This will save the ticket, also.
		$comment->save(true, false);
		
		if ($comment->message)
		{
			$ticket->fastUpdate('comment_count', $ticket->comment_count + 1);
		}

		$db->commit();

		return $comment;
	}
}