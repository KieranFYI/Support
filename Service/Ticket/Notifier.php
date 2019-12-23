<?php

namespace Kieran\Support\Service\Ticket;

use Kieran\Support\Entity\Ticket;
use Kieran\Support\Entity\TicketComment;

class Notifier extends \XF\Service\AbstractService
{
	/**
	 * @var Ticket
	 */
	protected $ticket;

	/**
	 * @var TicketComment
	 */
	protected $comment;

	protected $notifyMentioned = [];

	protected $usersAlerted = [];

	public function __construct(\XF\App $app, Ticket $ticket, TicketComment $comment)
	{
		parent::__construct($app);
		$this->ticket = $ticket;
		$this->comment = $comment;
	}

	public function getTicket()
	{
		return $this->ticket;
	}

	public function getComment()
	{
		return $this->comment;
	}

	public function setNotifyMentioned(array $mentioned)
	{
		$this->notifyMentioned = array_unique($mentioned);
	}

	public function getNotifyMentioned()
	{
		return $this->notifyMentioned;
	}

	public function notify()
	{
		$notifiableUsers = $this->getUsersForNotification();

		$mentionUsers = $this->getNotifyMentioned();
		foreach ($mentionUsers AS $k => $userId)
		{
			if (isset($notifiableUsers[$userId]))
			{
				$user = $notifiableUsers[$userId];
				if (\XF::asVisitor($user, function() { return $this->ticket->canView(); }))
				{
					$this->sendMentionNotification($user);
				}
			}
			unset($mentionUsers[$k]);
		}
		$this->notifyMentioned = [];
	}

	protected function getUsersForNotification()
	{
		$userIds = $this->getNotifyMentioned();

		$users = $this->app->em()->findByIds('XF:User', $userIds, ['Profile', 'Option']);
		if (!$users->count())
		{
			return [];
		}

		$users = $users->toArray();
		foreach ($users AS $k => $user)
		{
			if (!\XF::asVisitor($user, function() { return $this->ticket->canView(); }))
			{
				unset($users[$k]);
			}
		}

		return $users;
	}

	protected function sendMentionNotification(\XF\Entity\User $user)
	{
		$comment = $this->comment;

		if (empty($this->usersAlerted[$user->user_id]) && ($user->user_id != $comment->user_id))
		{
			/** @var \XF\Repository\UserAlert $alertRepo */
			$alertRepo = $this->app->repository('XF:UserAlert');
			if ($alertRepo->alert($user, $comment->user_id, $comment->AssignedUser->username, 'ticket', $comment->ticket_id, 'mention', [
				'comment' => $comment->toArray()
			]))
			{
				$this->usersAlerted[$user->user_id] = true;
				return true;
			}
		}

		return false;
	}
}