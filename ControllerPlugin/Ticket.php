<?php

namespace Kieran\Support\ControllerPlugin;

use XF\Mvc\Entity\Entity;
use Kieran\Support\Service\Ticket\Creator;

class Ticket extends \XF\ControllerPlugin\AbstractPlugin
{
	protected function setupTicketCreate($type)
	{
        $ticket_title = $this->request->filter('ticket_title', 'str');

		if ($type->hide_title) {
			$ticket_title = \XF::visitor()->username . ' ' . $type->name;
		} else if (!$ticket_title) {
            throw $this->exception($this->error(\XF::phrase('please_enter_reason_for_ticketing_this_message')));
        }

        $message = $this->plugin('XF:Editor')->fromInput('message');

		if ($type->hide_message) {
			$message = '';
		} else if (!$message) {
			throw $this->exception($this->error(\XF::phrase('please_enter_reason_for_ticketing_this_message')));
        }

		$creator = $this->service('Kieran\Support:Ticket\Creator', $type->type_id, $ticket_title);

        $creator->setMessage($message);
        $creator->setPriority($this->filter('priority', 'str'));

		$creator->setAttachmentHash($this->filter('attachment_hash', 'str'));

		return $creator;
	}

	protected function finalizeTicketCreate(Creator $creator)
	{

		$creator->getTicket()->draft_reply->delete();

		$fields = $this->filter('custom_fields', 'array');
		foreach ($fields as $key => $value) {
			if (is_array($value)) {
				$value = serialize($value);
			}
			
			$field = $this->em()->create('Kieran\Support:TicketFieldValue');
			$field->ticket_comment_id = $creator->getComment()->ticket_comment_id;
			$field->field_id = $key;
			$field->field_value = $value;
			$field->save();
		}
	}

	protected function validateFields($type) {
		$field_values = $this->filter('custom_fields', 'array');

		foreach ($type->fields as $field) {
			$def = new \XF\CustomField\Definition($field->toArray());
			$error = $this->isValid($def, isset($field_values[$field->field_id]) ? $field_values[$field->field_id] : '');
			if ($error !== true) {
				return $error;
			}
		}

		return true;
	}

	protected function isValid($field, $value) {

		if ($field->type_group == 'multiple')
		{
			// checkboxes or multi-select, value is an array.
			$value = [];
			if (is_string($value))
			{
				$value = [$value];
			}
			else if (is_array($value))
			{
				$value = $value;
			}
		}
        else
		{
			if (is_array($value))
			{
				$value = count($value) ? strval(reset($value)) : '';
			}
			else
			{
				$value = strval($value);
			}
		}

		// TODO: Considerations for import related value setting.

		$valid = $field->isValid($value, $error, $value);

		if (!$valid)
		{
			return $this->error($error);
		}

		if ($field->isRequired() 
		&& ((!is_array($value) && !strlen($value))
		|| (is_array($value) && !count($value))))
		{
			return $this->error(\XF::phraseDeferred('please_enter_value_for_all_required_fields'));
		}
		return true;
	}

	public function actionTicket($type, $options = [])
	{
		if ($this->filter('apply', 'bool'))
		{
			$creator = $this->setupTicketCreate($type);
			if (!$creator->validate($errors))
			{
				return $this->error($errors);
			}
			$error = $this->validateFields($type);

			if ($error !== true) {
				return $error;
			}
			$this->assertNotFlooding('ticket');
			$creator->save();
			$this->finalizeTicketCreate($creator);

			$creator->getCommentPreparer()->getComment()->save();

			return $this->redirect($this->buildLink('support/tickets', $creator->getTicket()));
		}
		else
		{
			return $this->view($options['view'], $options['template'], $options['extraViewParams']);
		}
	}
}