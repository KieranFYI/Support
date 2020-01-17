<?php

namespace Kieran\Support\Pub\Controller;

use XF\Mvc\ParameterBag;
use Kieran\Support\Entity\TicketType;

class Support extends \XF\Pub\Controller\AbstractController
{

	public function actionIndex(ParameterBag $params)
	{
		if ($params->article_slug)
		{
			return $this->rerouteController(__CLASS__, 'Article', $params);
		}

		if ($params->topic_slug)
		{
			return $this->rerouteController(__CLASS__, 'Topic', $params);
		}

		$faq = $this->getArticleRepo()->findFaq();

		$viewParams = [
			'topics' => $this->getTopicRepo()->findTopics(),
			'faq' => $faq,
			'canManage' => $this->getTicketTypeRepo()->canManage(),
			'canCreate' => $this->getTicketTypeRepo()->canCreate(),
			'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
		];

		return $this->view('Kieran\Support:Support\View', 'kieran_support', $viewParams);
	}

	public function actionTickets(ParameterBag $params)
	{
		return $this->rerouteController('Kieran\Support:Ticket', 'index', $params);
	}

	public function actionTopic(ParameterBag $params)
	{
		$topic = $this->assertViewableTopic($params->topic_slug);

		$articles = $this->getArticleRepo()->findArticles($topic->topic_id);

		if ($topic->slug != $params->topic_slug) {
			return $this->redirect($this->router()->buildLink('support', ['topic_slug' => $topic->slug]));
		}

		$topics = $this->getTopicRepo()->findTopics();

		$viewParams = [
			'topics' => $this->getTopicRepo()->findTopics(),
			'topic' => $topic,
			'articles' => $articles,
			'canManage' => $this->getTicketTypeRepo()->canManage(),
			'canCreate' => $this->getTicketTypeRepo()->canCreate(),
			'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
		];

		return $this->view('Kieran\Support:Support\Topic', 'kieran_support_topic_view', $viewParams);
	}

	public function actionArticle(ParameterBag $params)
	{
		$topic = $this->assertViewableTopic($params->topic_slug);

		$article = $this->assertViewableArticle($params->article_slug);

		if ($topic->topic_id != $article->topic_id) {
			return $this->noPermission();
		}

		if ($article->type == 'link')
		{
			return $this->redirectPermanently($article->message);
		}

		if ($article->slug != $params->article_slug || $topic->slug != $params->topic_slug) {
			return $this->redirect($this->router()->buildLink('support', ['topic_slug' => $topic->slug, 'article_slug' => $article->slug]));
		}

		$arguments = [];
		if (!empty($article->callback_class) && !empty($article->callback_method)) {
			$arguments = call_user_func_array(['\\' . $article->callback_class, $article->callback_method], [$article]);
			if (!is_array($arguments)) {
				$arguments = [];
			}
		}

		$content = $this->replaceVariablesInTemplate($article->message, $arguments);
		$content = $this->app->bbCode()->render($content, 'html', 'article', null, $arguments);

		$viewParams = [
			'topics' => $this->getTopicRepo()->findTopics(),
			'article' => $article,
			'canManage' => $this->getTicketTypeRepo()->canManage(),
			'canCreate' => $this->getTicketTypeRepo()->canCreate(),
			'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
			'content' => $content,
			'arguments' => $arguments,
		];

		return $this->view('Kieran\Support:Support\Article', 'kieran_support_article_view', $viewParams);
	}

	private function replaceVariablesInTemplate($template, array $variables){

		return preg_replace_callback('#{(.*?)}#',
			function($match) use ($variables){
				$match = trim(trim($match[1]), '$');
				if (strstr($match, '.')) {
					$ex = explode('.', $match);
					if (!isset($variables[$ex[0]])) {
						return '';
					}
					$val = $variables[$ex[0]];
					unset($ex[0]);
					foreach ($ex as $key) {
						if (!isset($val[$key])) {
							return '';
						}
						$val = $val[$key];
					}

					return $val;
				} else {
					if (!isset($variables[$match])) {
						return '';
					}
					return $variables[$match];
				}
			}, ' ' . $template . ' ');

	}

	public function actionManage(ParameterBag $params)
	{

		if (!\XF::visitor()->hasPermission('support', 'articles_can_manage')) {
			return $this->noPermission();
		}

		$topic = null;

		if ($params->topic_slug) {
			$id = $params->topic_slug;
			if (strpos($id, '-')) {
				$id = substr($id, 0, strpos($id, '-'));
			}
	
			$topic = $this->assertRecordExists('Kieran\Support:Topic', $id);
		}
		
		$viewParams = [
			'topics' => $this->getTopicRepo()->findTopics(),
			'canManage' => $this->getTicketTypeRepo()->canManage(),
			'canCreate' => $this->getTicketTypeRepo()->canCreate(),
			'canManageArticles' => \XF::visitor()->hasPermission('support', 'articles_can_manage'),
			'currentTopics' => $this->getTopicRepo()->findTopics($params->topic_slug ? $params->topic_slug : 0, true),
			'currentArticles' => $this->getArticleRepo()->findArticles($params->topic_slug ? $params->topic_slug : 0, true),
			'topic' => $topic,
		];

		return $this->view('Kieran\Support:Support\Manage', 'kieran_support_articles_manage', $viewParams);
	}

	protected function assertViewableTopic($id, $with = null, $phraseKey = null) {
		if (strpos($id, '-')) {
			$id = substr($id, 0, strpos($id, '-'));
		}

		$topic = $this->assertRecordExists('Kieran\Support:Topic', $id, $with, $phraseKey);
		if (!$topic->canView()) {
			throw $this->exception($this->notFound(\XF::phrase('requested_page_not_found')));
		}
		return $topic;
	}

	protected function assertViewableArticle($id, $with = null, $phraseKey = null) {
		if (strpos($id, '-')) {
			$id = substr($id, 0, strpos($id, '-'));
		}
		$article = $this->assertRecordExists('Kieran\Support:Article', $id, $with, $phraseKey);
		if (!$article->canView()) {
			throw $this->exception($this->notFound(\XF::phrase('requested_page_not_found')));
		}
		return $article;
	}

	protected function getTopicRepo()
	{
		return $this->repository('Kieran\Support:Topic');
	}

	protected function getArticleRepo()
	{
		return $this->repository('Kieran\Support:Article');
	}
	
	protected function getTicketTypeRepo()
	{
		return $this->repository('Kieran\Support:TicketType');
	}

	public static function getActivityDetails(array $activities)
	{
		return \XF::phrase('kieran_support_viewing');
	}

	public static function getExampleVariables($article) {
		return [
			'variable1' => 'variable1value',
			'variable2' => [
				'value1' => 'variable2value1',
				'value2' => 'variable2value2',
			]
		];
	}
}