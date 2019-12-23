<?php

namespace Kieran\Support\Pub\Controller;

use XF\Mvc\ParameterBag;
use Kieran\Support\Entity\TicketType;

class Topic extends \XF\Pub\Controller\AbstractController
{

	protected function typeCreateEdit(\Kieran\Support\Entity\Topic $topic)
	{
		
		$viewParams = [
			'topic' => $topic,
			'topics' => $this->getTopicRepo()->findTopics(),
			'canManage' => $this->getTicketTypeRepo()->canManage(),
			'canCreate' => $this->getTicketTypeRepo()->canCreate(),
			'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
		];

		return $this->view('Kieran\Support:Types\Add', 'kieran_support_topic_edit', $viewParams);
	}

	public function actionEdit(ParameterBag $params)
	{
		if (!\XF::visitor()->hasPermission('support', 'articles_can_manage')) {
			return $this->noPermission();
		}
		
		$topic = $this->assertViewableTopic($params->topic_id);
		return $this->typeCreateEdit($topic);
	}

	public function actionDelete(ParameterBag $params)
	{
		if (!\XF::visitor()->hasPermission('support', 'articles_can_manage')) {
			return $this->noPermission();
		}
		
		$topic = $this->assertViewableTopic($params->topic_id);
		$topic->display_order = 0;
		$topic->save();
		return $this->redirect($this->buildLink('support/' . ($topic->Parent ? $topic->parent_topic_id . '/' : '') . 'manage'));;
	}

	public function actionCreate(ParameterBag $params)
	{	
		if (!\XF::visitor()->hasPermission('support', 'articles_can_manage')) {
			return $this->noPermission();
		}
		$topic = $this->em()->create('Kieran\Support:Topic');

		if ($params->topic_id)
		{
			$parent = $this->assertViewableTopic($params->topic_id);
			$topic->parent_topic_id = $parent->topic_id;
		}

		return $this->typeCreateEdit($topic);
	}

	public function actionSave(ParameterBag $params)
	{
		if (!\XF::visitor()->hasPermission('support', 'articles_can_manage')) {
			return $this->noPermission();
		}
		
		if ($params->topic_id)
		{
			$topic = $this->assertViewableTopic($params->topic_id);
		}
		else
		{
			$topic = $this->em()->create('Kieran\Support:Topic');
		}

		$input = $this->filter([
			'title' => 'str',
			'parent_topic_id' => 'uint',
			'description' => 'str',	
			'icon' => 'str',
			'display_order' => 'uint',
			'view_power_required' => 'uint'
		]);
		if ($input['display_order'] < 1) {
			$input['display_order'] = 1;
		}

		$form = $this->formAction();
		$form->basicEntitySave($topic, $input);
		$form->run();

		return $this->redirect($this->buildLink('support/' . ($topic->Parent ? $topic->parent_topic_id . '/' : '') . 'manage'));
	}

	protected function assertViewableTopic($id, $with = null, $phraseKey = null) {
		if (strpos($id, '-')) {
			$id = substr($id, 0, strpos($id, '-'));
		}

		$topic = $this->assertRecordExists('Kieran\Support:Topic', $id, $with, $phraseKey);
		if (\XF::visitor()->hasPermission('support', 'view_power') < $topic->view_power_required || $topic->display_order < 1) {
			throw $this->exception($this->notFound(\XF::phrase('requested_page_not_found')));
		}
		return $topic;
	}

	protected function getTopicRepo()
	{
		return $this->repository('Kieran\Support:Topic');
	}

	protected function getTicketTypeRepo()
	{
		return $this->repository('Kieran\Support:TicketType');
	}

	public static function getActivityDetails(array $activities)
	{
		return \XF::phrase('kieran_support_viewing');
	}
}