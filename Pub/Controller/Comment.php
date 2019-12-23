<?php

namespace Kieran\Support\Pub\Controller;

use XF\Mvc\ParameterBag;

class Comment extends \XF\Pub\Controller\AbstractController
{

	public function actionIndex(ParameterBag $params)
	{
		return $this->redirect($this->router()->buildLink('support/tickets/manage'));
	}

	public function actionIp(ParameterBag $params)
	{
		$comment = $this->assertViewableComment($params->ticket_comment_id);

		/** @var \XF\ControllerPlugin\Ip $ipPlugin */
		$ipPlugin = $this->plugin('XF:Ip');
		return $ipPlugin->actionIp($comment);
	}

	public function actionDelete(ParameterBag $params)
	{

		$comment = $this->assertViewableComment($params->ticket_comment_id);
		$ticket = $comment->getRelationOrDefault('Ticket');

		if (!$ticket->canDelete('Soft'))
		{
			throw $this->exception($this->noPermission());
		}

		if (!$this->request->isXhr()) {

			return $this->redirect($this->router()->buildLink('support/tickets', $ticket));

		} else if ($this->isPost()) {
			$type = $this->filter('hard_delete', 'bool') ? 'hard' : 'soft';
			$reason = $this->filter('reason', 'str');

			if ($type == 'soft' && $comment->message_state == 'hidden') {
				$type = 'hard';
			}

			if (!$ticket->canDelete($type))
			{
				return $this->noPermission();
			}
			
			if ($comment->is_ticket) {
				$deletionLog = $ticket->getRelationOrDefault('DeletionLog');
				$deletionLog->setFromUser(\XF::visitor());
				$deletionLog->delete_reason = $reason;
				$deletionLog->save();

				$commentPlugin = $this->plugin('Kieran\Support:TicketComment');
				if ($type == 'hard') {
					return $commentPlugin->actionHardDelete($ticket, $reason);
				} else {
					return $commentPlugin->actionSoftDelete($ticket, $reason);
				}
			} else {

				$deletionLog = $comment->getRelationOrDefault('DeletionLog');
				$deletionLog->setFromUser(\XF::visitor());
				$deletionLog->delete_reason = $reason;
				$deletionLog->save();

				if ($type == 'hard') {
					$comment->fastUpdate('message_state', 'deleted');
				} else {
					$comment->fastUpdate('message_state', 'hidden');
				}
			}

			return $this->redirect($this->router()->buildLink('support/tickets', $ticket));
		}
		else
		{
			$viewParams = [
				'ticket' => $ticket,
				'comment' => $comment,
			];

			return $this->view('Kieran\Support:Ticket\Delete', 'kieran_support_comment_delete', $viewParams);
		}
	}

	public function actionRestore(ParameterBag $params)
	{
		$comment = $this->assertViewableComment($params->ticket_comment_id);
		$ticket = $comment->getRelationOrDefault('Ticket');

		if (!$ticket->canDelete('Soft'))
		{
			throw $this->exception($this->noPermission());
		}

		$comment->fastUpdate('message_state', 'visible');
		return $this->redirect($this->router()->buildLink('support/tickets', $ticket));
	}

	protected function assertViewableComment($comment_id)
	{
		$comment = $this->getCommentRepo()->findCommentById($comment_id);

		if (!$comment)
		{
			throw $this->exception($this->notFound(\XF::phrase('requested_page_not_found')));
		}
		
		$ticket = $comment->getRelationOrDefault('Ticket');


		if (!$ticket->canView())
		{
			throw $this->exception($this->noPermission());
		}
		
		return $comment;
	}

	protected function getCommentRepo()
	{
		return $this->repository('Kieran\Support:TicketComment');
	}

}