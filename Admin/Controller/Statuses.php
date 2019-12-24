<?php

namespace Kieran\Support\Admin\Controller;

use XF\Mvc\ParameterBag;

class Statuses extends \XF\Admin\Controller\AbstractController
{

	public function actionIndex(ParameterBag $params)
	{
		return $this->view('Kieran\Support:Status', 'kieran_support_statuses', ['statuses' => $this->getStatusRepo()->getAll(false, false)]);
	}	

	protected function statusAddEdit(\Kieran\Support\Entity\Status $status)
	{
		$viewParams = [
			'status' => $status,
			'success' => $this->filter('success', 'bool'),
			'fields' => $this->getTicketFieldRepo()->findFieldsForList()->fetch(),
			'states' => ['visible', 'locked', 'hidden', 'awaiting', 'closed']
		];
		return $this->view('Kieran\Support:Statuses\Add', 'kieran_support_statuses_edit', $viewParams);
	}

	public function actionEdit(ParameterBag $params)
	{
		$status = $this->assertStatusExists($params->status_id);
		return $this->statusAddEdit($status);
	}

	public function actionDelete(ParameterBag $params)
	{
		$status = $this->assertStatusExists($params->status_id);

		if (!$status->canDelete())
		{
			return $this->error(\XF::phrase('status_cannot_be_deleted_associated_with_ticket_explain'));
		}

		$status->delete();

		return $this->redirect($this->buildLink('support/statuses'));
	}

	public function actionAdd(ParameterBag $params)
	{	
		$status = $this->getStatusRepo()->setupBaseStatus();

		return $this->statusAddEdit($status);
	}

	public function actionToggle()
	{
		/** @var \XF\ControllerPlugin\Toggle $plugin */
		$plugin = $this->plugin('XF:Toggle');
		return $plugin->actionToggle('Kieran\Support:Status', 'enabled');
	}

	public function actionSave(ParameterBag $params)
	{
		$this->assertPostOnly();

		if ($params->status_id)
		{
			$status = $this->assertStatusExists($params->status_id);
		}
		else
		{
			$status = $this->getStatusRepo()->setupBaseStatus();
		}

		$this->statusSaveProcess($status)->run();

		return $this->redirect($this->buildLink('support/statuses'));
	}

	protected function statusSaveProcess(\Kieran\Support\Entity\Status $status)
	{

		$form = $this->formAction();

		$input = $this->filter([
			'status' => [
				'enabled' => 'uint',
				'status_id' => 'str',
				'name' => 'str',
				'message' => 'str',
				'state' => 'str'
			],
			'fields' => 'array'
		]);

		if ($status->undeletable) {
			unset($input['status']['enabled']);
			unset($input['status']['status_id']);
		}

		if (!$status->isInsert()) {
			unset($input['status']['status_id']);
		}
		
		$form->basicEntitySave($status, $input['status']);
		$form->run();

		$form = $this->formAction();
		$fields = $this->getTicketFieldRepo()->findFieldsForList()->fetch();
		foreach ($fields as $value) {
			if (in_array($value->field_id, $input['fields']) && !$status->isFieldSelected($value->field_id)) {
				$field = $this->em()->create('Kieran\Support:Field');
				$form->basicEntitySave($field, [
					'field_id' => $value->field_id,
					'content_id' => $status->status_id,
					'content_type' => 'status'
				]);
			} else if (!in_array($value->field_id, $input['fields']) && $status->isFieldSelected($value->field_id)) {
				$field = $this->em()->find('Kieran\Support:Field', [
					'field_id' => $value->field_id,
					'content_id' => $status->status_id,
					'content_type' => 'status'
				]);
				$field->delete();
			}
		}

		$permCheck = $this->em()->find('XF:Permission', [
			'permission_group_id' => 'support_status',
			'permission_id' => $status->status_id
		]);

		if (!$permCheck) {
			$permission = $this->em()->create('XF:Permission');
			$input = [
				'permission_id' => $status->status_id,
				'permission_group_id' => 'support_status',
				'permission_type' => 'flag',
				'interface_group_id' => 'support_status',
				'display_order' => 1,
				'depend_permission_id' => '',
				'addon_id' => 'Kieran/Support'
			];

			$form->basicEntitySave($permission, $input);

			$form->apply(function() use ($status, $permission)
			{
				$title = $permission->getMasterPhrase();
				$title->phrase_text = 'Can set status to ' . $status->name;
				$title->save();
			});
		}
		
		return $form;
	}


	protected function assertStatusExists($id, $with = null, $phraseKey = null)
	{
		return $this->assertRecordExists('Kieran\Support:Status', $id, $with, $phraseKey);
	}
	
	protected function getStatusRepo()
	{
		return $this->repository('Kieran\Support:Status');
	}
	
	protected function getTicketFieldRepo()
	{
		return $this->repository('Kieran\Support:TicketField');
	}

}