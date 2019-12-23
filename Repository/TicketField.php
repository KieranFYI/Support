<?php

namespace Kieran\Support\Repository;

use XF\Repository\AbstractField;

class TicketField extends AbstractField
{
	protected function getRegistryKey()
	{
		return 'userFieldsInfo';
	}

	protected function getClassIdentifier()
	{
		return 'Kieran\Support:TicketField';
	}

	public function getDisplayGroups()
	{
		return ['ticket' => \XF::phrase('ticket')];
	}

	public function getTicketFieldValues($userId)
	{
		$fields = $this->db()->fetchAll('
			SELECT field_value.*, field.field_type
			FROM xf_kieran_support_ticket_field_value AS field_value
			INNER JOIN xf_kieran_support_ticket_field AS field ON (field.field_id = field_value.field_id)
			WHERE field_value.user_id = ?
		', $userId);

		$values = [];
		foreach ($fields AS $field)
		{
			if ($field['field_type'] == 'checkbox' || $field['field_type'] == 'multiselect')
			{
				$values[$field['field_id']] = \XF\Util\Php::safeUnserialize($field['field_value']);
			}
			else
			{
				$values[$field['field_id']] = $field['field_value'];
			}
		}
		return $values;
	}
}