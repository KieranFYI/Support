<?php

namespace Kieran\Support\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class Status extends Repository
{

	public function getAll($enabled = true, $undeletable=true) {
		$finder = $this->finder('Kieran\Support:Status');
		
		if ($enabled) {
			$finder->where('enabled', 1);
		}

		if ($undeletable) {
			$finder->where('undeletable', 0);
		}

		return $finder->fetch();
	}

	public function setupBaseStatus()
	{
		return $this->em->create('Kieran\Support:Status');
	}


}