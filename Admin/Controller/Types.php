<?php

namespace Kieran\Support\Admin\Controller;

use XF\Mvc\ParameterBag;
use Kieran\Support\Entity\TicketType;

class Types extends \XF\Admin\Controller\AbstractController
{

	public function actionIndex(ParameterBag $params)
	{
		return $this->view('Kieran\Support:Types', 'kieran_support_types', ['types' => $this->getTicketTypeRepo()->getAll(false)]);
	}

	protected function typeAddEdit(TicketType $type)
	{
		$viewParams = [
			'type' => $type,
			'success' => $this->filter('success', 'bool'),
			'fields' => $this->getTicketFieldRepo()->findFieldsForList()->fetch(),
			'allStatus' => $this->getStatusRepo()->getAll(),
		];
		return $this->view('Kieran\Support:Types\Add', 'kieran_support_types_edit', $viewParams);
	}

	public function actionEdit(ParameterBag $params)
	{
		$type = $this->assertTypeExists($params['type_id']);
		return $this->typeAddEdit($type);
	}

	public function actionAdd(ParameterBag $params)
	{	
		$type = $this->getTicketTypeRepo()->setupBaseType();

		return $this->typeAddEdit($type);
	}

	public function actionToggle()
	{
		/** @var \XF\ControllerPlugin\Toggle $plugin */
		$plugin = $this->plugin('XF:Toggle');
		return $plugin->actionToggle('Kieran\Support:TicketType', 'enabled');
	}

	public function actionSave(ParameterBag $params)
	{
		$this->assertPostOnly();

		if ($params->type_id)
		{
			$type = $this->assertTypeExists($params->type_id);
		}
		else
		{
			$type = $this->getTicketTypeRepo()->setupBaseType();
		}

		$this->typeSaveProcess($type)->run();

		return $this->redirect($this->buildLink('support/types'));
	}

	public function actionDelete(ParameterBag $params)
	{
		$type = $this->assertTypeExists($params->type_id);

		if (!$type->canDelete())
		{
			return $this->error(\XF::phrase('type_cannot_be_deleted_associated_with_ticket_explain'));
		}

		$type->delete();

		return $this->redirect($this->buildLink('support/types'));
	}

	protected function typeSaveProcess(TicketType $type)
	{

		$form = $this->formAction();

		$input = $this->filter([
			'type' => [
				'enabled' => 'uint',
				'name' => 'str',
				'description' => 'str',	
			],
			'fields' => 'array',
			'status' => 'array'
		]);
		
		$form->basicEntitySave($type, $input['type']);
		$form->run();
		
		$type->checkAndCreatePermissions($type);


		$form = $this->formAction();
		$fields = $this->getTicketFieldRepo()->findFieldsForList()->fetch();
		foreach ($fields as $value) {
			if (in_array($value->field_id, $input['fields']) && !$type->isFieldSelected($value->field_id)) {
				$field = $this->em()->create('Kieran\Support:Field');
				$form->basicEntitySave($field, [
					'field_id' => $value->field_id,
					'content_id' => $type->type_id,
					'content_type' => 'type'
				]);
			} else if (!in_array($value->field_id, $input['fields']) && $type->isFieldSelected($value->field_id)) {
				$field = $this->em()->find('Kieran\Support:Field', [
					'field_id' => $value->field_id,
					'content_id' => $type->type_id,
					'content_type' => 'type'
				]);
				$field->delete();
			}
		}

		$statuses = $this->getStatusRepo()->getAll(true, false);
		$input['status'][] = 'open';
		$input['status'][] = 'locked';
		$input['status'][] = 'awaiting';
		foreach ($statuses as $value) {
			if (in_array($value->status_id, $input['status']) && !$type->isStatusSelected($value->status_id)) {
				$field = $this->em()->create('Kieran\Support:TicketTypeStatus');
				$form->basicEntitySave($field, [
					'status_id' => $value->status_id,
					'type_id' => $type->type_id
				]);
			} else if (!in_array($value->status_id, $input['status']) && $type->isStatusSelected($value->status_id)) {
				$field = $this->em()->find('Kieran\Support:TicketTypeStatus', [
					'status_id' => $value->status_id,
					'type_id' => $type->type_id
				]);
				$field->delete();
			}
		}

		return $form;
	}

	protected function assertTypeExists($id, $with = null, $phraseKey = null)
	{
		return $this->assertRecordExists('Kieran\Support:TicketType', $id, $with, $phraseKey);
	}
	
	protected function getTicketTypeRepo()
	{
		return $this->repository('Kieran\Support:TicketType');
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