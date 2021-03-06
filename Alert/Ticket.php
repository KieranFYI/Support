<?php

namespace Kieran\Support\Alert;

use XF\Mvc\Entity\Entity;
use XF\Alert\AbstractHandler;

class Ticket extends AbstractHandler
{
	
	public function canViewContent(Entity $entity, &$error = null)
	{
		return true;
	}

	public function getOptOutActions()
	{
		return [
			'updated',
			'watched'
		];
	}

	public function getOptOutDisplayOrder()
	{
		return 30005;
	}
}