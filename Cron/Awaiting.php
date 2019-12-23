<?php

namespace Kieran\Support\Cron;

class Awaiting
{
	public static function cleanUp()
	{

		$tickets = self::getTicketRepo()->findByState('awaiting');

		$time = strtotime('-3 days');

		foreach ($tickets as $ticket) {
			$lastComment = $ticket->findLastCommentBy($ticket->user_id);
			if ($lastComment->comment_date < $time) {
				$ticket->state = 'closed';
				$ticket->status_id = 'closed';
				$ticket->save();
			}
		}
	}

	public static function notify()
	{

		$tickets = self::getTicketRepo()->findByState('awaiting');
		$emails = [];
		foreach ($tickets as $ticket) {
			$user = $ticket->getRelationOrDefault('User');
			if (isset($emails[$user->user_id])) {
				$emails[$user->user_id]['tickets'][] = $ticket;
			} else {
				$emails[$user->user_id] = [
					'user' => $user,
					'tickets' => [$ticket]
				];
			}
		}

		foreach ($emails as $key => $value) {
			\XF::app()->mailer()->newMail()
				->setTemplate('kieran_support_awaiting', ['user' => $value['user'], 'tickets' => $value['tickets']])
				->setToUser($value['user'])
				->queue();
		}
	}

	/**
	 * @return \Kieran\Support\Repository\Ticket
	 */
	private static function getTicketRepo()
	{
		return \XF::repository('Kieran\Support:Ticket');
	}
}