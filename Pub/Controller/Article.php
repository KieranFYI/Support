<?php

namespace Kieran\Support\Pub\Controller;

use XF\Mvc\ParameterBag;
use Kieran\Support\Entity\TicketType;

class Article extends \XF\Pub\Controller\AbstractController
{

	protected function articleCreateEdit(\Kieran\Support\Entity\Article $article)
	{
		$viewParams = [
			'article' => $article,
			'topics' => $this->getTopicRepo()->findTopics(),
			'userGroups' => $this->em()->getRepository('XF:UserGroup')->getUserGroupTitlePairs(),
			'canManage' => $this->getTicketTypeRepo()->canManage(),
			'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
		];

		return $this->view('Kieran\Support:Types\Add', 'kieran_support_article_edit', $viewParams);
	}

	public function actionEdit(ParameterBag $params)
	{
		$article = $this->assertViewableArticle($params->article_id);
		return $this->articleCreateEdit($article);
	}

	public function actionCreate(ParameterBag $params)
	{	
		if (!\XF::visitor()->hasPermission('support', 'articles_can_manage')) {
			return $this->noPermission();
		}
		$article = $this->em()->create('Kieran\Support:Article');

		if ($params->article_id)
		{
			$parent = $this->assertViewableTopic($params->article_id);
			$article->topic_id = $parent->topic_id;
		}

		return $this->articleCreateEdit($article);
	}

	public function actionDelete(ParameterBag $params)
	{
		$article = $this->assertViewableArticle($params->article_id);
		$article->display_order = 0;
		$article->save();

		return $this->redirect($this->buildLink('support/' . $article->Topic->topic_id . '/manage'));
	}

	public function actionSave(ParameterBag $params)
	{	
		if (!\XF::visitor()->hasPermission('support', 'articles_can_manage')) {
			return $this->noPermission();
		}
		
		if ($params->article_id)
		{
			$article = $this->assertViewableArticle($params->article_id);
		}
		else
		{
			$article = $this->em()->create('Kieran\Support:Article');
		}

		$input = $this->filter([
			'title' => 'str',
			'topic_id' => 'uint',
			'description' => 'str',	
			'display_order' => 'uint',
			'groups' => 'array',
			'type' => 'str',
			'is_faq' => 'bool',
			
			'callback_class' => 'str',
			'callback_method' => 'str',
		]);

		if ($input['display_order'] < 1) {
			$input['display_order'] = 1;
		}

		$input['message'] = $this->plugin('XF:Editor')->fromInput('message', 0);

		$form = $this->formAction();
		$form->basicEntitySave($article, $input);
		$form->run();

		return $this->redirect($this->buildLink('support/' . $article->Topic->topic_id . '/manage'));
	}

	protected function assertViewableArticle($id, $with = null, $phraseKey = null) {
		if (strpos($id, '-')) {
			$id = substr($id, 0, strpos($id, '-'));
		}
		
		if (!\XF::visitor()->hasPermission('support', 'articles_can_manage')) {
			return $this->noPermission();
		}

		$article = $this->assertRecordExists('Kieran\Support:Article', $id, $with, $phraseKey);
		if ($article->display_order < 1) {
			throw $this->exception($this->notFound(\XF::phrase('requested_page_not_found')));
		}
		return $article;
	}

	protected function assertViewableTopic($id, $with = null, $phraseKey = null) {
		if (strpos($id, '-')) {
			$id = substr($id, 0, strpos($id, '-'));
		}
		
		if (!\XF::visitor()->hasPermission('support', 'articles_can_manage')) {
			return $this->noPermission();
		}

		$topic = $this->assertRecordExists('Kieran\Support:Topic', $id, $with, $phraseKey);
		if ($topic->display_order < 1) {
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
		return \XF::phrase('kieran_support_viewing_article');
	}
}