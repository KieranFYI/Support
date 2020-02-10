<?php

namespace Kieran\Support\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class TicketType extends Repository
{

	public function canManage() {
		$visitor = \XF::visitor();

		if (!$visitor->user_id)
		{
			return false;
		}

		$types = $this->getAll();
		foreach ($types as $type) {
			if ($type->canView()) {
				return true;
			}
		}

		return false;
	}


	public function canCreate() {
		$visitor = \XF::visitor();

		if (!$visitor->user_id)
		{
			return false;
		}

		$types = $this->getAll();
		foreach ($types as $type) {
			if ($type->canCreate()) {
				return true;
			}
		}

		return false;
	}

	public function get($id) {
		return $this->finder('Kieran\Support:TicketType')->where('type_id', $id)->fetchOne();
	}

	private $cache = [];

	public function getAll($enabled = true) {
		if (isset($this->cache[$enabled])) {
			return $this->cache[$enabled];
		}

		$finder = $this->finder('Kieran\Support:TicketType');
		
		if ($enabled) {
			$finder->where('enabled', 1);
		}
		$this->cache[$enabled] = $finder->fetch();

		return $this->cache[$enabled];
	}

	public function getAllCreatable() {
		$all = $this->getAll();
		$creatable = [];
		foreach ($all as $type) {
			if ($type->canCreate()) {
				$creatable[] = $type;
			}
		}

		return $creatable;
	}

	public function setupBaseType()
	{
		return $this->em->create('Kieran\Support:TicketType');
	}

}