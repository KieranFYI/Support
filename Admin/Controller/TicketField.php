<?php

namespace Kieran\Support\Admin\Controller;

use XF\Mvc\ParameterBag;
use XF\Admin\Controller\AbstractField;

class TicketField extends AbstractField
{

	protected function getClassIdentifier()
	{
		return 'Kieran\Support:TicketField';
	}

	protected function getLinkPrefix()
	{
		return 'support/fields';
	}

	protected function getTemplatePrefix()
	{
		return 'ticket_field';
	}
}