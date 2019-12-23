<?php

namespace Kieran\Support\Admin\Controller;

use XF\Mvc\ParameterBag;

class Support extends \XF\Admin\Controller\AbstractController
{

	public function actionIndex(ParameterBag $params)
	{
		return $this->view('Kieran\Support:Types', 'kieran_support', []);
	}

	public function actionTypes(ParameterBag $params)
	{
		return $this->rerouteController('Kieran\Support:Types', 'index', $params);
	}

	public function actionStatuses(ParameterBag $params)
	{
		return $this->rerouteController('Kieran\Support:Statuses', 'index', $params);
	}

	public function actionFields(ParameterBag $params)
	{
		return $this->rerouteController('Kieran\Support:TicketField', 'index', $params);
	}

}