<?php

namespace Kieran\Support\Attachment;

use XF\Attachment\AbstractHandler;
use XF\Entity\Attachment;
use XF\Mvc\Entity\Entity;

class Ticket extends AbstractHandler
{
	public function canView(Attachment $attachment, Entity $container, &$error = null)
	{
		return $container->canView();
	}

	public function canManageAttachments(array $context, &$error = null)
	{
		return true;
	}

	public function onAttachmentDelete(Attachment $attachment, Entity $container = null)
	{
		if (!$container)
		{
			return;
		}

		$container->attach_count--;
		$container->save();
	}

	public function getConstraints(array $context)
	{
		return \XF::repository('XF:Attachment')->getDefaultAttachmentConstraints();
	}

	public function getContainerIdFromContext(array $context)
	{
		return isset($context['ticket_id']) ? intval($context['ticket_id']) : null;
	}

	public function getContainerLink(Entity $container, array $extraParams = [])
	{
		return \XF::app()->router('public')->buildLink('support/tickets/', $container, $extraParams);
	}

	public function getContext(Entity $entity = null, array $extraContext = [])
	{

		if ($entity instanceof \Kieran\Support\Entity\Ticket)
		{
			$extraContext['ticket_id'] = $entity->ticket_id;
		}
		else if ($entity instanceof \Kieran\Support\Entity\TicketType)
		{
			$extraContext['type_id'] = $entity->type_id;
		}
		else if (!$entity)
		{
			// need nothing
		}
		else
		{
			throw new \InvalidArgumentException("Entity must be ticket");
		}

		return $extraContext;
	}
}