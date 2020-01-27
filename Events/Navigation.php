<?php

namespace Kieran\Support\Events;

use XF;
use XF\Entity\User;
use XF\Mvc\Entity\Entity;

class Navigation {

	public static function assignedTickets($temp, $selectedNav, $params) {
		if (!self::getTicketTypeRepo()->canManage()) {
			return [];
		}

		return [
            'title' => 'Assigned Tickets',
            'href' => XF::app()->router()->buildLink('support/tickets/assigned'),
        ];
	}

	public static function allTickets($temp, $selectedNav, $params) {
		if (!self::getTicketTypeRepo()->canManage()) {
			return [];
		}
		
		return [
            'title' => 'All Tickets',
            'href' => XF::app()->router()->buildLink('support/tickets/manage'),
        ];
	}

	private static function getTicketTypeRepo()
	{
		return XF::repository('Kieran\Support:TicketType');
	}

}