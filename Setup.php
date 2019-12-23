<?php

namespace Kieran\Support;

use XF\Db\Schema\Create;

class Setup extends \XF\AddOn\AbstractSetup
{
	use \XF\AddOn\StepRunnerInstallTrait;
	use \XF\AddOn\StepRunnerUpgradeTrait;
	use \XF\AddOn\StepRunnerUninstallTrait;

	// php cmd.php xf-addon:install-step Kieran/Support 2
	// php cmd.php xf-addon:build-release Kieran/Support

	public function installStep1(array $stepParams = [])
	{
		
		$this->schemaManager()->createTable('xf_kieran_support_topic', function(Create $table)
		{
			$table->addColumn('topic_id', 'int')->autoIncrement();
			$table->addColumn('parent_topic_id', 'int');
			$table->addColumn('title', 'varchar', 50);
			$table->addColumn('description', 'text');
			$table->addColumn('display_order', 'int')->setDefault(1);
			$table->addColumn('view_power_required', 'int')->setDefault(0);
			$table->addColumn('icon', 'varchar', 25)->comment('fontawesome icon class.');
			
			$table->addPrimaryKey('topic_id');
		});

		$this->schemaManager()->createTable('xf_kieran_support_article', function(Create $table)
		{
			$table->addColumn('article_id', 'int')->autoIncrement();
			$table->addColumn('topic_id', 'int');
			$table->addColumn('title', 'varchar', 50);
			$table->addColumn('description', 'text');
			$table->addColumn('display_order', 'int')->setDefault(1);
			$table->addColumn('view_power_required', 'int')->setDefault(0);

			$table->addColumn('type', 'enum', ['bbcode', 'link'])->setDefault('bbcode');
			$table->addColumn('message', ' mediumtext');

			$table->addColumn('is_faq', 'tinyint');
			$table->addColumn('callback_class', 'varchar', 255);
			$table->addColumn('callback_method', 'varchar', 255);

			$table->addPrimaryKey('article_id');
			$table->addKey(['topic_id'], 'topic_id');
		});
		
	}
	
	public function installStep2(array $stepParams = [])
	{
		// TODO: Send email on ticket notifications (ticket created & ticket updated) for author only
		// TODO: The type of page can be text, link or template (enter template name and return that)
		$this->schemaManager()->createTable('xf_kieran_support_ticket', function(Create $table)
		{
			$table->addColumn('ticket_id', 'int')->autoIncrement();
			$table->addColumn('user_id', 'int');
			$table->addColumn('type_id', 'int', 11);
			$table->addColumn('ticket_title', 'varchar', 150);
			$table->addColumn('status_id', 'varchar', 100);
			$table->addColumn('state', 'enum', ['visible', 'locked', 'hidden', 'awaiting', 'closed', 'deleted'])->setDefault('visible');
			$table->addColumn('assigned_user_id', 'int')->setDefault(0);
			$table->addColumn('comment_count', 'int')->setDefault(0);
			$table->addColumn('last_modified_date', 'int');
			$table->addColumn('last_modified_user_id', 'int')->setDefault(0);
			$table->addColumn('ticket_date', 'int');
			$table->addPrimaryKey('ticket_id');
			$table->addKey(['user_id'], 'user_id');
		});
		
		$this->schemaManager()->createTable('xf_kieran_support_ticket_type', function(Create $table)
		{
			$table->addColumn('type_id', 'int')->autoIncrement();
			$table->addColumn('name', 'varchar', 150);
			$table->addColumn('description', 'text');
			$table->addColumn('enabled', 'int', 1)->setDefault(0);

			$table->addColumn('hide_title', 'int', 1)->setDefault(0);
			$table->addColumn('hide_message', 'int', 1)->setDefault(0);

			$table->addColumn('permission_create', 'varchar', 150);
			$table->addColumn('permission_process', 'varchar', 150);
			$table->addColumn('permission_view', 'varchar', 150);
			$table->addColumn('permission_soft_delete', 'varchar', 150);
			$table->addColumn('permission_hard_delete', 'varchar', 150);
			
			$table->addPrimaryKey('type_id');
			$table->addKey(['type_id'], 'type_id');
		});
		
		$this->schemaManager()->createTable('xf_kieran_support_ticket_comment', function(Create $table)
		{
			$table->addColumn('ticket_comment_id', 'int')->autoIncrement();
			$table->addColumn('ticket_id', 'int');
			$table->addColumn('user_id', 'int');
			$table->addColumn('message', ' mediumtext');
			$table->addColumn('message_state', 'enum', ['visible', 'hidden', 'deleted'])->setDefault('visible');
			$table->addColumn('ip_id', 'int')->setDefault(0);
			$table->addColumn('comment_date', 'int');
			$table->addColumn('status_change', 'varchar', 100);
			$table->addColumn('assigned_user_id', 'int')->setDefault(0);
			$table->addColumn('is_ticket', 'tinyint', 3);
			$table->addColumn('attach_count', 'smallint', 5)->setDefault(0);
			$table->addPrimaryKey('ticket_comment_id');
			$table->addKey(['ticket_id', 'user_id'], 'ticket_id_user_id');
		});
		
		$this->schemaManager()->createTable('xf_kieran_support_ticket_watcher', function(Create $table)
		{
			$table->addColumn('ticket_id', 'int');
			$table->addColumn('user_id', 'int');
			$table->addPrimaryKey(['ticket_id', 'user_id']);
			$table->addKey(['ticket_id', 'user_id'], 'ticket_id_user_id');
		});
		
		$this->schemaManager()->createTable('xf_kieran_support_ticket_type_status', function(Create $table)
		{
			$table->addColumn('status_id', 'varbinary', 25);
			$table->addColumn('type_id', 'int');
			$table->addPrimaryKey(['status_id', 'type_id']);
			$table->addKey(['status_id', 'type_id'], 'status_id_type_id');
		});
		
		$this->schemaManager()->createTable('xf_kieran_support_ticket_status', function(Create $table)
		{
			$table->addColumn('status_id', 'varbinary', 25);
			$table->addColumn('name', 'varchar', 100);
			$table->addColumn('message', 'varchar', 200);
			$table->addColumn('state', 'enum', ['visible', 'locked', 'hidden', 'awaiting', 'closed', 'deleted'])->setDefault('visible');
			$table->addColumn('enabled', 'int', 1)->setDefault(0);
			$table->addColumn('undeletable', 'int', 1)->setDefault(0);
			$table->addPrimaryKey('status_id');
			$table->addKey(['status_id'], 'status_id');
		});

		$status = $this->app->em()->create('Kieran\Support:Status');
		$status->status_id = 'new';
		$status->name = 'New';
		$status->message = 'Your ticket is in a queue and will be assigned to a processor soon.';
		$status->state = 'visible';
		$status->enabled = 1;
		$status->undeletable = 1;
		$status->save();

		$status = $this->app->em()->create('Kieran\Support:Status');
		$status->status_id = 'open';
		$status->name = 'Open';
		$status->state = 'visible';
		$status->enabled = 1;
		$status->undeletable = 1;
		$status->save();

		$status = $this->app->em()->create('Kieran\Support:Status');
		$status->status_id = 'locked';
		$status->name = 'Locked';
		$status->state = 'locked';
		$status->enabled = 1;
		$status->undeletable = 1;
		$status->save();

		$status = $this->app->em()->create('Kieran\Support:Status');
		$status->status_id = 'hidden';
		$status->name = 'Hidden';
		$status->state = 'hidden';
		$status->enabled = 1;
		$status->undeletable = 1;
		$status->save();

		$status = $this->app->em()->create('Kieran\Support:Status');
		$status->status_id = 'awaiting';
		$status->name = 'Awaiting response';
		$status->message = 'Your ticket requires your response before we can proceed, if we do not hear from you then the ticket will automatically expire.';
		$status->state = 'awaiting';
		$status->enabled = 1;
		$status->undeletable = 1;
		$status->save();

		$status = $this->app->em()->create('Kieran\Support:Status');
		$status->status_id = 'closed';
		$status->name = 'Closed';
		$status->message = 'Your ticket has been closed, if you have any further questions or concerns you can create a new ticket.';
		$status->state = 'closed';
		$status->enabled = 1;
		$status->undeletable = 1;
		$status->save();
		
		$this->schemaManager()->createTable('xf_kieran_support_field', function(Create $table)
		{
			$table->addColumn('content_type', 'varbinary', 25);
			$table->addColumn('content_id', 'varbinary', 25);
			$table->addColumn('field_id', 'varbinary', 25);
			$table->addPrimaryKey(['content_type', 'content_id', 'field_id']);
			$table->addKey(['content_type', 'content_id', 'field_id'], 'content_type_content_id_field_id');
		});

		$this->schemaManager()->createTable('xf_kieran_support_ticket_field', function(Create $table)
		{
			$table->addColumn('field_id', 'varbinary', 25);
			$table->addColumn('display_group', 'enum')->values(['ticket'])->setDefault('ticket');
			$table->addColumn('display_order', 'int')->setDefault(1);
			$table->addColumn('field_type', 'varbinary', 25)->setDefault('textbox');
			$table->addColumn('field_choices', 'blob');
			$table->addColumn('match_type', 'varbinary', 25)->setDefault('none');
			$table->addColumn('match_params', 'blob');
			$table->addColumn('max_length', 'int')->setDefault(0);
			$table->addColumn('required', 'tinyint', 3)->setDefault(0);
			$table->addColumn('display_template', 'text');
			$table->addPrimaryKey('field_id');
			$table->addKey(['display_group', 'display_order'], 'display_group_order');
		});

		$this->schemaManager()->createTable('xf_kieran_support_ticket_field_value', function(Create $table)
		{
			$table->addColumn('ticket_comment_id', 'int');
			$table->addColumn('field_id', 'varbinary', 25);
			$table->addColumn('field_value', 'mediumtext');
			$table->addPrimaryKey(['ticket_comment_id', 'field_id']);
			$table->addKey('field_id');
		});
	}
	
	public function upgrade(array $stepParams = [])
	{
	}
	
	public function uninstallStep1(array $stepParams = [])
	{
		
		$this->schemaManager()->dropTable('xf_kieran_support_topic');
		$this->schemaManager()->dropTable('xf_kieran_support_article');
		
	}

	public function uninstallStep2(array $stepParams = [])
	{
		$this->schemaManager()->dropTable('xf_kieran_support_ticket');
		$this->schemaManager()->dropTable('xf_kieran_support_ticket_type');
		$this->schemaManager()->dropTable('xf_kieran_support_ticket_type_status');
		$this->schemaManager()->dropTable('xf_kieran_support_ticket_comment');
		$this->schemaManager()->dropTable('xf_kieran_support_ticket_watcher');
		$this->schemaManager()->dropTable('xf_kieran_support_ticket_status');
		$this->schemaManager()->dropTable('xf_kieran_support_field');
		$this->schemaManager()->dropTable('xf_kieran_support_ticket_field');
		$this->schemaManager()->dropTable('xf_kieran_support_ticket_field_value');
	}
}