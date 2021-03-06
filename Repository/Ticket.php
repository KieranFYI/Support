<?php

namespace Kieran\Support\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class Ticket extends Repository
{

	public static $Priority = ['Low', 'Medium', 'Normal', 'High', 'Urgent'];

	public static function getAvailableSorts()
	{
		return [
			'status' => 'status_id',
			'state' => 'state',
			'created_by' => 'user_id',
			'title' => 'ticket_title',
			'created' => 'ticket_date',
			'last_updated' => 'last_modified_date',
			'priority' => 'priority',
		];
	}

	public function findTicketById($ticket_id)
	{
		return $this->finder('Kieran\Support:Ticket')
					->where('ticket_id', $ticket_id)
					->fetchOne();
	}
	
	public function findTicketsByUserId($user_id)
	{
		return $this->finder('Kieran\Support:Ticket')
					->where('user_id', $user_id)
					->where('state', ['locked', 'visible', 'awaiting', 'closed'])
					->order('last_modified_date', 'desc')
					->fetch();
	}
	
	public function findTickets($typeLimit, $filters, $page = 1, $perPage = 25)
	{
		$finder = $this->buildQuery($typeLimit, $filters);
		$finder->limitByPage($page, $perPage);
		return $finder;
	}

	public function findByState($state)
	{
		if (!is_array($state)) {
			$state = [$state];
		}

		$finder = $this->buildQuery([], ['state' => $state]);
		
		return $finder->fetch();
	}

	private function buildQuery($typeLimit, $filters)
	{
		$finder = $this->finder('Kieran\Support:Ticket');

		$finder->where('state', '!=', 'deleted');

		if (isset($filters['starter_id']) && $filters['starter_id'] > 0) {
			$finder->where('user_id', $filters['starter_id']);
		}

		if (isset($filters['date'])) {
			$start = 0;
			$end = time();

			if (isset($filters['date']['start'])) {
				$start = strtotime($filters['date']['start']);
			}

			if (isset($filters['date']['end'])) {
				$end = strtotime($filters['date']['end']);
			}

			if ($end < $start) {
				$t = $start;
				$start = $end;
				$end = $t;
			}

			$finder->where(['ticket_date', '>=', $start]);
			$finder->where(['ticket_date', '<=', $end]);
		}

		if (isset($filters['status']) && count($filters['status']) > 0)
		{
			$finder->where('status_id', $filters['status']);
		}

		if (isset($filters['priority']) && count($filters['priority']) > 0)
		{
			$finder->where('priority', $filters['priority']);
		}

		if (isset($filters['state']) && count($filters['state']) > 0) {
			$finder->where('state', $filters['state']);
		} else {
//			$finder->where('state', ['locked', 'visible', 'hidden', 'awaiting']);
			$finder->whereSql('state IN ("locked", "visible", "hidden", "awaiting") OR (state="closed" AND last_modified_date > (UNIX_TIMESTAMP() - 86400))');
		}

		if (isset($filters['type']) && count($filters['type']) > 0)
		{
			$finder->where('type_id', $filters['type']);
		}
		$finder->where('type_id', $typeLimit);
		
		$finder->order($finder->expression('state="closed"'));

		if (isset($filters['order']) && isset($filters['direction']))
		{
			$finder->order(self::getAvailableSorts()[$filters['order']], $filters['direction']);
		}
		else
		{
			$finder->order('ticket_date', 'asc');
		}

		if (isset($filters['assigned_user_id']) && $filters['assigned_user_id'] > 0) {
			$finder->where('assigned_user_id', $filters['assigned_user_id']);
		}

		return $finder;
	}
}