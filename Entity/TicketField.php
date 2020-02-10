<?php

namespace Kieran\Support\Entity;

use XF\Mvc\Entity\Structure;
use XF\Entity\AbstractField;

class TicketField extends AbstractField
{
	protected function getClassIdentifier()
	{
		return 'Kieran\Support:TicketField';
	}

	public function isRequired()
	{
		return $this->required;
	}

	protected static function getPhrasePrefix()
	{
		return 'ticket_field';
	}

	public static function getStructure(Structure $structure)
	{
		self::setupDefaultStructure(
			$structure,
			'xf_kieran_support_ticket_field',
			'Kieran\Support:TicketField',
			['groups' => ['ticket']]
		);

		return $structure;
	}

	public function getFormattedValue($value)
	{

		if ($value === '' || $value === null)
		{
			return '';
		}

		switch ($this->getFieldRepo()->getFieldTypes()[$this->field_type]['type'])
		{
			case 'single':
				$value = isset($this->field_choices[$value]) ? $this->field_choices[$value] : '';
				break;

			case 'multiple':
				$value = unserialize($value);
				foreach ($value AS $key => &$phrase)
				{
					$phrase = $this->field_choices[$phrase];
				}

				break;

			case 'rich_text':
				$value = \XF::app()->bbCode()->render($value, 'html', 'custom_field:' . $this->field_id, null);
				break;

			case 'text':
			default:
				$value = nl2br(htmlspecialchars(\XF::app()->stringFormatter()->censorText($value)));
				break;
		}

		if ($this->display_template)
		{
			if (is_array($value))
			{
				foreach ($value AS $choice => &$thisValue)
				{
					$thisValue = $this->translateValue($thisValue, $choice);
				}
			}
			else
			{
				$value = $this->translateValue($value);
			}
		}

		if (is_array($value))
		{
			$value = implode(', ', $value);
		}

		return $value;
	}

	protected function rebuildFieldCache()
	{
	}
}