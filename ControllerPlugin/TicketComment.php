<?php

namespace Kieran\Support\ControllerPlugin;

use XF\Mvc\Entity\Entity;
use Kieran\Support\Entity\Ticket;
use \Kieran\Support\Service\Ticket\Commenter;

class TicketComment extends \XF\ControllerPlugin\AbstractPlugin
{

	protected function setupTicketComment(Ticket $ticket, $status = null, $priority = null, $assigned_user_id = 0)
	{
		$message = $this->plugin('XF:Editor')->fromInput('message');

		$commenter = $this->service('Kieran\Support:Ticket\Commenter', $ticket);

		if ($message)
		{
			$commenter->setMessage($message);
        }
        
        if ($priority) {
            $commenter->setTicketPriority($priority);
        }

		if ($status)
		{
			$commenter->setTicketStatus($status);
		} else {
			if ($commenter->getTicket()->status == 'new' 
			 && $commenter->getTicket()->user_id != \XF::visitor()->user_id) {

				$commenter->getTicket()->status_id = 'open';
			} else if ($commenter->getTicket()->state == 'awaiting'
					&& $commenter->getTicket()->user_id == \XF::visitor()->user_id) {
				$commenter->setTicketStatus('open');
			}
		}

		if ($assigned_user_id) {
			$commenter->setTicketAssignedTo($assigned_user_id);
		}

		$commenter->setAttachmentHash($this->filter('attachment_hash', 'str'));

		return $commenter;
	}

	protected function finalizeTicketComment(Commenter $commenter)
	{
		$commenter->getTicket()->addWatcher($commenter->getComment()->user_id);
		$commenter->getTicket()->notifyWatchers($commenter->getComment()->user_id);

		$ticket = $commenter->getTicket();
		$ticket->draft_reply->delete();

		$fields = $this->filter('custom_fields', 'array');
		foreach ($fields as $key => $value) {
			if (is_array($value)) {
				$value = serialize($value);
			}
			
			$field = $this->em()->create('Kieran\Support:TicketFieldValue');
			$field->ticket_comment_id = $creator->getComment()->ticket_comment_id;
			$field->field_id = $key;
			$field->field_value = $value;
			$field->save();
		}
	
	}

	public function actionTicketComment(Ticket $ticket, $status = null, $priority = null, $assigned_user_id = 0)
	{
		$commenter = $this->setupTicketComment($ticket, $status, $priority, $assigned_user_id);

		if ($commenter->hasSaveableChanges())
		{
			if (!$commenter->validate($errors))
			{
				return $this->error($errors);
			}

			$comment = $commenter->save();
			$this->finalizeTicketComment($commenter);

			return $this->redirect($this->router()->buildLink('support/tickets', $commenter->getTicket()) . '#comment-' . $comment->ticket_comment_id);
		}
		else
		{
			return $this->redirect($this->router()->buildLink('support/tickets', $commenter->getTicket()));
		}
	}

	public function actionLockUnlock(Ticket $ticket)
	{

		$commenter = $this->service('Kieran\Support:Ticket\Commenter', $ticket);
		if ($commenter->getTicket()->status->state == 'locked') {
			$commenter->setTicketStatus('open');
		} else {
			$commenter->setTicketStatus('locked');
		}
		
		$comment = $commenter->save();

		$this->finalizeTicketComment($commenter);
		return $this->redirect($this->router()->buildLink('support/tickets', $ticket) . '#comment-' . $comment->ticket_comment_id);
	}

	public function actionSoftDelete(Ticket $ticket, $reason)
	{

		$commenter = $this->service('Kieran\Support:Ticket\Commenter', $ticket);
		$commenter->setTicketStatus('hidden');
		$commenter->setMessage($reason);

		$comment = $commenter->save();

		$this->finalizeTicketComment($commenter);
		return $this->redirect($this->router()->buildLink('support/tickets', $ticket), \XF::phrase('kieran_support_ticket_hide'));
	}

	public function actionRestore(Ticket $ticket)
	{

		$commenter = $this->service('Kieran\Support:Ticket\Commenter', $ticket);
		$commenter->setTicketStatus('open');
		
		$comment = $commenter->save();

		$this->finalizeTicketComment($commenter);
		return $this->redirect($this->router()->buildLink('support/tickets', $ticket), \XF::phrase('kieran_support_ticket_restore '));
	}

	public function actionHardDelete(Ticket $ticket, $reason)
	{

		$commenter = $this->service('Kieran\Support:Ticket\Commenter', $ticket);
		$commenter->setTicketState('deleted');
		$commenter->setMessage($reason);
		
		$comment = $commenter->save();

		$this->finalizeTicketComment($commenter);
		return $this->redirect($this->router()->buildLink('support/tickets/manage'), \XF::phrase('kieran_support_ticket_delete'));
	}
}