<?php

namespace Kieran\Support\Pub\Controller;

use XF\Mvc\ParameterBag;
use Kieran\Support\Entity\TicketType;
use Kieran\Support\Repository\Ticket as TicketRepo;

class Ticket extends \XF\Pub\Controller\AbstractController
{
	public function actionIndex(ParameterBag $params)
	{
		if ($params->ticket_id)
		{
			return $this->rerouteController(__CLASS__, 'view', $params);
		}

		$tickets = $this->getTicketRepo()->findTicketsByUserId(\XF::visitor()->user_id);

		$viewParams = [
			'topics' => $this->getTopicRepo()->findTopics(),
			'tickets' => $tickets,
			'canManage' => $this->getTicketTypeRepo()->canManage(),
			'canCreate' => $this->getTicketTypeRepo()->canCreate(),
			'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
		];

		return $this->view('Kieran\Support:Ticket\List', 'kieran_support_tickets', $viewParams);
	}

	
	public function actionManage(ParameterBag $params)
	{
		if ($params->ticket_id)
		{
			return $this->rerouteController(__CLASS__, 'view', $params);
		}

		if (!$this->getTicketTypeRepo()->canManage())
		{
			return $this->redirect($this->router()->buildLink('support'));
		}

		$topics = $this->getTopicRepo()->findTopics();

		$filters = $this->getFilterInput();

		$page = $this->filterPage();
		$perPage = 25;
		$finder = $this->getTicketRepo()->findTickets(array_column($this->getTicketTypeRepo()->getAllCreatable(), 'type_id'), $filters, $page, $perPage);
		$total = $finder->total();

		$this->assertValidPage($page, $perPage, $total, 'support/tickets/manage');

		$tickets = $finder->fetch();

		$viewParams = [
			'topics' => $topics,
			'tickets' => $tickets,
			'canManage' => $this->getTicketTypeRepo()->canManage(),
			'canCreate' => $this->getTicketTypeRepo()->canCreate(),
			'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
			'filters' => $filters,
			'perPage' => $perPage,
			'total' => $total,
			'page' => $page,
		];

		return $this->view('Kieran\Support:Ticket\List', 'kieran_support_tickets_manage', $viewParams);
	}
	
	public function actionAssigned(ParameterBag $params)
	{
		if ($params->ticket_id)
		{
			return $this->rerouteController(__CLASS__, 'view', $params);
		}

		if (!$this->getTicketTypeRepo()->canManage())
		{
			return $this->redirect($this->router()->buildLink('support'));
		}

		$topics = $this->getTopicRepo()->findTopics();

		$filters = $this->getFilterInput();
		$filters['assigned_user_id'] = \XF::visitor()->user_id;
		$page = $this->filterPage();
		$perPage = 25;
		$finder = $this->getTicketRepo()->findTickets(array_column($this->getTicketTypeRepo()->getAllCreatable(), 'type_id'), $filters, $page, $perPage);
		$total = $finder->total();

		$this->assertValidPage($page, $perPage, $total, 'support/tickets/manage');


		$tickets = $finder->fetch();

		$viewParams = [
			'topics' => $topics,
			'tickets' => $tickets,
			'canManage' => $this->getTicketTypeRepo()->canManage(),
			'canCreate' => $this->getTicketTypeRepo()->canCreate(),
			'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
			'filters' => $filters,
			'perPage' => $perPage,
			'total' => $total,
			'page' => $page,
			'assigned' => true,
		];

		return $this->view('Kieran\Support:Ticket\List', 'kieran_support_tickets_manage', $viewParams);
	}

	public function actionView(ParameterBag $params)
	{
		$topics = $this->getTopicRepo()->findTopics();

		$ticket = $this->assertViewableTicket($params->ticket_id);
		$messages = $ticket->getRelationOrDefault('Comments', false);
		$attachmentRepo = $this->repository('XF:Attachment');
		$attachmentRepo->addAttachmentsToContent($messages, 'ticket_message');

		if (!$ticket->canProcess()) {
			foreach ($messages as $key => $value) {
				if ($value->message_state == 'hidden') {
					unset($messages[$key]);
				}
			}
		}

		$viewParams = [
			'topics' => $topics,
			'ticket' => $ticket,
			'canManage' => $this->getTicketTypeRepo()->canManage(),
			'canCreate' => $this->getTicketTypeRepo()->canCreate(),
			'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
			'attachmentData' => $this->getReplyAttachmentData($ticket),
			'messages' => $messages,
		];
		
		return $this->view('Kieran\Support:Ticket\View', 'kieran_support_view_ticket', $viewParams);
	}

	public function actionReply(ParameterBag $params)
	{
		$this->assertPostOnly();

		$ticket = $this->assertViewableTicket($params->ticket_id);

		if ($ticket->status->state == 'locked' && !$ticket->canProcess())
		{
			return $this->noPermission();
		}

		if ($ticket->isClosed())
		{
			return $this->noPermission();
		}
		$this->assertNotFlooding('ticket_reply');

		$commentPlugin = $this->plugin('Kieran\Support:TicketComment');
		return $commentPlugin->actionTicketComment($ticket);
	}

	// save draft comment
	public function actionWatch(ParameterBag $params)
	{
		$ticket = $this->assertViewableTicket($params->ticket_id);

		$visitor = \XF::visitor();

		if (!$visitor->user_id)
		{
			return $this->noPermission();
		}

		$ticket->addWatcher($visitor->user_id);
		
		return $this->redirect($this->router()->buildLink('support/tickets/', $ticket));
	}

	// save draft comment
	public function actionUnwatch(ParameterBag $params)
	{
		$ticket = $this->assertViewableTicket($params->ticket_id);

		$visitor = \XF::visitor();

		if (!$visitor->user_id)
		{
			return $this->noPermission();
		}

		$ticket->removeWatcher($visitor->user_id);
		
		return $this->redirect($this->router()->buildLink('support/tickets/', $ticket));
	}

	// save draft comment
	public function actionDraft(ParameterBag $params)
	{
		$ticket = $this->assertViewableTicket($params->ticket_id);

		$draftPlugin = $this->plugin('XF:Draft');
		return $draftPlugin->actionDraftMessage($ticket->draft_reply);
	}

	public function actionCreate(ParameterBag $params)
	{
		$visitor = \XF::visitor();

		if (!$visitor->user_id)
		{
			return $this->noPermission();
		}

		$id = $this->request->filter('type', 'str');

		if ($params->ticket_id) {
			$id = $params->ticket_id;
		}

		$type = $this->getTicketTypeRepo()->get($id);

		if (!$type) {

			$types = $this->getTicketTypeRepo()->getAllCreatable();
			if (!count($types)) {
				throw $this->exception($this->notFound(\XF::phrase('kieran_support_tickets_no_types')));
			} else {
				return $this->view('Kieran\Support:Ticket\Ticket', 'kieran_support_ticket_create_start', 
					[
						'canManage' => $this->getTicketTypeRepo()->canManage(),
						'types' => $types,
					]);
			}
		} else {

			if (!$type->canCreate() || !$type->enabled)
			{
				return $this->noPermission();
			}

			$ticketPlugin = $this->plugin('Kieran\Support:Ticket');
			return $ticketPlugin->actionTicket(
				$type,
				[
					'view' => 'Kieran\Support:Ticket\Ticket',
					'template' => 'kieran_support_ticket_create',
					'extraViewParams' => [
                        'type' => $type,
                        'priorities' => TicketRepo::$Priority,
						'canManage' => $this->getTicketTypeRepo()->canManage(),
						'canCreate' => $this->getTicketTypeRepo()->canCreate(),
						'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
						'attachmentData' => $this->getReplyAttachmentData($type),
					]
				]
			);
		}
	}

	public function actionAssign(ParameterBag $params)
	{
		$ticket = $this->assertViewableTicket($params->ticket_id);

		if (!$ticket->canProcess())
		{
			throw $this->exception($this->noPermission());
		}
		
		if ($ticket->isClosed())
		{
			throw $this->exception($this->noPermission());
		}

		if ($this->request->isPost())
		{
			$status = null;
			$assigned_user_id = $this->filter('user_id', 'uint');
			$awaiting = $this->filter('is_awaiting', 'bool');

			if ($assigned_user_id)
			{
				// assigned_user_id is already assigned this ticket
				if ($assigned_user_id == $ticket->assigned_user_id) {
					$assigned_user_id = 0;
				} else {
					// assigned_user_id does not have permission to process ticket
					$moderators = $ticket->getUsersWhoCanHandleTicket($ticket->TicketType);
					$bool = false;
					foreach ($moderators AS $moderator)
					{
						if($moderator['user_id'] == $assigned_user_id)
						{
							$bool = true;
							break;
						}
					}
				
					if(!$bool)
					{
						return $this->error(\XF::phrase('you_cannot_reassign_this_report_to_this_user'));
					}

					if ($awaiting) {
						$status = 'awaiting';
					}
				}
			}

			$commentPlugin = $this->plugin('Kieran\Support:TicketComment');
			return $commentPlugin->actionTicketComment($ticket, $status, null, $assigned_user_id);
		}
		else
		{
			$moderators = $ticket->getUsersWhoCanHandleTicket();

			$viewParams = [
				'ticket' => $ticket,
				'moderators' => $moderators,
				'canManage' => $this->getTicketTypeRepo()->canManage(),
				'canCreate' => $this->getTicketTypeRepo()->canCreate(),
				'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
			];

			return $this->view('Kieran\Support:Ticket\Assign', 'kieran_support_ticket_assign', $viewParams);
		}
	}

	public function actionPriority(ParameterBag $params)
	{
		$ticket = $this->assertViewableTicket($params->ticket_id);

		if (!$ticket->canProcess())
		{
			throw $this->exception($this->noPermission());
		}
		
		$status = $this->em()->findOne('Kieran\Support:Status', ['status_id' => $this->request->filter('status', 'str')]);

		if ($this->isPost())
		{
			$commentPlugin = $this->plugin('Kieran\Support:TicketComment');
			return $commentPlugin->actionTicketComment($ticket, null, $this->filter('priority', 'str'));
		}
		else
		{
			$viewParams = [
				'ticket' => $ticket,
				'canManage' => $this->getTicketTypeRepo()->canManage(),
				'canCreate' => $this->getTicketTypeRepo()->canCreate(),
				'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
				'allPriorities' => TicketRepo::$Priority,
			];

			return $this->view('Kieran\Support:Ticket\Priority', 'kieran_support_ticket_priority', $viewParams);
		}
	}

	public function actionStatus(ParameterBag $params)
	{
		$ticket = $this->assertViewableTicket($params->ticket_id);

		if (!$ticket->canProcess())
		{
			throw $this->exception($this->noPermission());
		}
		
		$status = $this->em()->findOne('Kieran\Support:Status', ['status_id' => $this->request->filter('status', 'str')]);

		if (!$status || !$status->canUse()) {
			if ($this->request->isXhr()) {
				$viewParams = [
					'ticket' => $ticket,
					'canManage' => $this->getTicketTypeRepo()->canManage(),
					'canCreate' => $this->getTicketTypeRepo()->canCreate(),
					'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
				];

				return $this->view('Kieran\Support:Ticket\Status', 'kieran_support_ticket_status_start', $viewParams);
			} else {
				return $this->redirect($this->buildLink('support/tickets', $ticket));
			}
		} else {

			if ($this->filter('apply', 'bool'))
			{
				$commentPlugin = $this->plugin('Kieran\Support:TicketComment');
				return $commentPlugin->actionTicketComment($ticket, $this->filter('status', 'str'));
			}
			else
			{
				$viewParams = [
					'ticket' => $ticket,
					'canManage' => $this->getTicketTypeRepo()->canManage(),
					'canCreate' => $this->getTicketTypeRepo()->canCreate(),
					'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
					'status' => $status
				];

				return $this->view('Kieran\Support:Ticket\Status', 'kieran_support_ticket_status', $viewParams);
			}
		}
	}

	public function actionDelete(ParameterBag $params)
	{
		$ticket = $this->assertViewableTicket($params->ticket_id);

		if (!$ticket->canDelete('Soft'))
		{
			throw $this->exception($this->noPermission());
		}

		if ($this->isPost())
		{
			$type = $this->filter('hard_delete', 'bool') ? 'hard' : 'soft';
			$reason = $this->filter('reason', 'str');

			if ($type == 'soft' && $ticket->status->state == 'hidden') {
				$type = 'hard';
			}

			if (!$ticket->canDelete($type))
			{
				return $this->noPermission();
			}

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
		}
		else
		{
			$viewParams = [
				'ticket' => $ticket,
				'canManage' => $this->getTicketTypeRepo()->canManage(),
				'canCreate' => $this->getTicketTypeRepo()->canCreate(),
				'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
			];

			return $this->view('Kieran\Support:Ticket\Delete', 'kieran_support_ticket_delete', $viewParams);
		}
	}

	public function actionRestore(ParameterBag $params)
	{
		$ticket = $this->assertViewableTicket($params->ticket_id);

		if (!$ticket->canDelete('Soft'))
		{
			throw $this->exception($this->noPermission());
		}

		$commentPlugin = $this->plugin('Kieran\Support:TicketComment');
		return $commentPlugin->actionRestore($ticket);
	}

	public function actionFilters(ParameterBag $params)
	{
		$filters = $this->getFilterInput();

		if ($this->filter('apply', 'bool'))
		{
			return $this->redirect($this->buildLink('support/tickets/manage', null, $filters));
		}
		else if ($this->request->isXhr())
		{
			if (!empty($filters['starter_id']))
			{
				$starterFilter = $this->em()->find('XF:User', $filters['starter_id']);
			}
			else
			{
				$starterFilter = null;
			}

			$viewParams = [
				'canManage' => $this->getTicketTypeRepo()->canManage(),
				'canCreate' => $this->getTicketTypeRepo()->canCreate(),
				'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
				'filters' => $filters,
				'starterFilter' => $starterFilter,
				'allStatus' => $this->getStatusRepo()->getAll(true, false),
                'allPriorities' => TicketRepo::$Priority,
				'types' => $this->getTicketTypeRepo()->getAllCreatable(),
			];

			return $this->view('Kieran\Support:Ticket\Filters', 'kieran_support_filters', $viewParams);
		}
		else
		{
			return $this->redirect($this->router()->buildLink('support/tickets/manage'));
		}
	}

	protected function getFilterInput()
	{
		$filters = [];

		$input = $this->filter([
			'starter' => 'str',
			'starter_id' => 'uint',
			'last_days' => 'int',
			'order' => 'str',
			'direction' => 'str',
			'date' => 'array',
			'status' => 'array',
			'priority' => 'array',
			'type' => 'array',
		]);

		if ($input['starter_id'])
		{
			$filters['starter_id'] = $input['starter_id'];
		}
		else if ($input['starter'])
		{
			$user = $this->em()->findOne('XF:User', ['username' => $input['starter']]);
			if ($user)
			{
				$filters['starter_id'] = $user->user_id;
			}
		}

		if (in_array($input['last_days'], $this->getAvailableDateLimits()))
		{
			$filters['last_days'] = $input['last_days'];
		}

		$sorts = TicketRepo::getAvailableSorts();

		if ($input['order'] && isset($sorts[$input['order']]))
		{
			if (!in_array($input['direction'], ['asc', 'desc']))
			{
				$input['direction'] = 'desc';
			}

			$filters['order'] = $input['order'];
			$filters['direction'] = $input['direction'];
		}

		if (isset($input['date']) && count($input['date'])) {
			$filters['date'] = $input['date'];
		}

		if (isset($input['status']) && count($input['status'])) {
			$valid = [];
			$allStatus = $this->getStatusRepo()->getAll(true, false);
			foreach ($allStatus as $key => $value) {
				$valid[] = $value->status_id;
			}
			$filters['status'] = array_intersect($valid, $input['status']);
		}

		if (isset($input['priority']) && count($input['priority'])) {
			$filters['priority'] = array_intersect(TicketRepo::$Priority, $input['priority']);
		}

		if (isset($input['type']) && count($input['type'])) {
			$valid = [];
			$types = $this->getTicketTypeRepo()->getAll();
			foreach ($types as $key => $value) {
				$valid[] = $value->type_id;
			}
			$filters['type'] = array_intersect($valid, $input['type']);
		}

		if (count($filters)) {
			$filters['state'] = ['locked', 'visible', 'hidden', 'awaiting', 'closed'];
        }
        
		return $filters;
	}

	protected function getAvailableDateLimits()
	{
		return [-1, 7, 14, 30, 60, 90, 182, 365];
	}

	protected function assertViewableTicket($ticket_id)
	{
		$ticket = $this->getTicketRepo()->findTicketById($ticket_id);

		if (!$ticket)
		{
			throw $this->exception($this->notFound(\XF::phrase('requested_page_not_found')));
		}

		if (!$ticket->canView())
		{
			throw $this->exception($this->noPermission());
		}
		
		return $ticket;
	}

	protected function getReplyAttachmentData($ticket)
	{
		/** @var \XF\Entity\Forum $forum */
		$attachmentHash = $ticket->draft_reply->attachment_hash;

		/** @var \XF\Repository\Attachment $attachmentRepo */
		$attachmentRepo = $this->repository('XF:Attachment');
		return $attachmentRepo->getEditorData('ticket_message', $ticket, $attachmentHash);
	}

	protected function getTopicRepo()
	{
		return $this->repository('Kieran\Support:Topic');
	}
	
	protected function getTicketRepo()
	{
		return $this->repository('Kieran\Support:Ticket');
	}
	
	protected function getStatusRepo()
	{
		return $this->repository('Kieran\Support:Status');
	}
	
	protected function getTicketTypeRepo()
	{
		return $this->repository('Kieran\Support:TicketType');
	}

	public static function getActivityDetails(array $activities)
	{
		return \XF::phrase('kieran_support_viewing_ticket');
	}
}