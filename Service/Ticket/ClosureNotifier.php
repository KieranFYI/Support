<?php

namespace Kieran\Support\Service\Ticket;

use Kieran\Support\Entity\Ticket;
use Kieran\Support\Entity\TicketComment;

class ClosureNotifier extends \XF\Service\AbstractService
{
	/**
	 * @var Ticket
	 */
	protected $ticket;

	protected $notifyUserIds = [];

	protected $alertComment = '';
	protected $alertType = null;

	public function __construct(\XF\App $app, Ticket $ticket, array $notifyUserIds = null)
	{
		parent::__construct($app);
		$this->ticket = $ticket;
		$this->alertType = $this->ticket->status;
		$this->setNotifyUserIds($notifyUserIds);
	}

	public function getTicket()
	{
		return $this->ticket;
	}

	public function setNotifyUserIds(array $notifyUserIds = null)
	{
		if ($notifyUserIds === null)
		{
			$notifyUserIds = $this->determineNotifiableUserIds();
		}

		$this->notifyUserIds = $notifyUserIds;
	}

	public function getNotifyUserIds()
	{
		return $this->notifyUserIds;
	}

	public function setAlertComment($comment)
	{
		$this->alertComment = $comment;
	}

	public function getAlertComment()
	{
		return $this->alertComment;
	}

	public function setAlertType($type)
	{
		$this->alertType = $type;
	}

	public function getAlertType()
	{
		return $this->alertType;
	}

	public function notify()
	{
		$users = $this->app->em()->findByIds('XF:User', $this->notifyUserIds, ['Profile', 'Option']);
		foreach ($users AS $user)
		{
			$this->sendClosureNotification($user);
		}
		$this->notifyUserIds = [];
	}

	protected function sendClosureNotification(\XF\Entity\User $user)
	{
		$ticket = $this->ticket;

		$title = $ticket->title;
		if ($title instanceof \XF\Phrase)
		{
			$title = $title->render('raw');
		}
		$link = $ticket->link;

		/** @var \XF\Repository\UserAlert $alertRepo */
		$alertRepo = $this->repository('XF:UserAlert');
		$alertSent = $alertRepo->alertFromUser(
			$user, null,
			'user', $user->user_id,
			"ticket_{$this->alertType}",
			[
				'comment' => $this->alertComment,
				'title' => $title,
				'link' => $link
			]
		);

		return $alertSent;
	}

	public function determineNotifiableUserIds()
	{
		$ticketId = $this->ticket->ticket_id;
		$db = $this->db();

		$lastOpenDate = $db->fetchOne("
			SELECT comment_date
			FROM xf_ticket_comment
			WHERE ticket_id = ?
				AND status_change = 'open'
			ORDER BY comment_date DESC
			LIMIT 1
		", $ticketId);
		$userIds = $db->fetchAllColumn("
			SELECT user_id
			FROM xf_ticket_comment
			WHERE ticket_id = ?
				AND comment_date >= ?
				AND is_ticket = 1
		", [$ticketId, $lastOpenDate ?: 0]);

		return array_unique($userIds);
	}
}