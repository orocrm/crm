<?php

namespace OroCRM\Bundle\SalesBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;



use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;
use OroCRM\Bundle\SalesBundle\Migrations\Schema\v1_5\OroCRMSalesBundle as SalesNoteMigration;
use OroCRM\Bundle\SalesBundle\Migrations\Schema\v1_6\OroCRMSalesBundle as SalesActivityMigration;
use OroCRM\Bundle\SalesBundle\Migrations\Schema\v1_7\OpportunityAttachment;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroCRMSalesBundleInstaller implements
    Installation,
    ExtendExtensionAwareInterface,
    NoteExtensionAwareInterface,
    ActivityExtensionAwareInterface,
    AttachmentExtensionAwareInterface
{
    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * @var NoteExtension
     */
    protected $noteExtension;

    /**
     * @var ActivityExtension
     */
    protected $activityExtension;

    /**
     * @var AttachmentExtension
     */
    protected $attachmentExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_8';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrocrmSalesOpportunityTable($schema);
        $this->createOrocrmSalesLeadStatusTable($schema);
        $this->createOrocrmSalesFunnelTable($schema);
        $this->createOrocrmSalesOpportStatusTable($schema);
        $this->createOrocrmSalesOpportCloseRsnTable($schema);
        $this->createOrocrmSalesLeadTable($schema);

        /** Foreign keys generation **/
        $this->addOrocrmSalesOpportunityForeignKeys($schema);
        $this->addOrocrmSalesFunnelForeignKeys($schema);
        $this->addOrocrmSalesLeadForeignKeys($schema);

        /** Apply extensions */
        SalesNoteMigration::addNoteAssociations($schema, $this->noteExtension);
        SalesActivityMigration::addActivityAssociations($schema, $this->activityExtension);
        OpportunityAttachment::addOpportunityAttachment($schema, $this->attachmentExtension);
    }

    /**
     * Create orocrm_sales_opportunity table
     *
     * @param Schema $schema
     */
    protected function createOrocrmSalesOpportunityTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_sales_opportunity');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('contact_id', 'integer', ['notnull' => false]);
        $table->addColumn('close_reason_name', 'string', ['notnull' => false, 'length' => 32]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('status_name', 'string', ['notnull' => false, 'length' => 32]);
        $table->addColumn('lead_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('close_date', 'date', ['notnull' => false]);
        $table->addColumn(
            'probability',
            'percent',
            ['notnull' => false, 'precision' => 0, 'comment' => '(DC2Type:percent)']
        );
        $table->addColumn(
            'budget_amount',
            'money',
            ['notnull' => false, 'precision' => 0, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn(
            'close_revenue',
            'money',
            ['notnull' => false, 'precision' => 0, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('customer_need', 'text', ['notnull' => false]);
        $table->addColumn('proposed_solution', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->addIndex(['contact_id'], 'idx_c0fe4aace7a1254a', []);
        $table->addIndex(['created_at'], 'opportunity_created_idx', []);
        $table->addUniqueIndex(['workflow_item_id'], 'uniq_c0fe4aac1023c4ee');
        $table->addIndex(['user_owner_id'], 'idx_c0fe4aac9eb185f9', []);
        $table->addIndex(['lead_id'], 'idx_c0fe4aac55458d', []);
        $table->addIndex(['account_id'], 'idx_c0fe4aac9b6b5fba', []);
        $table->addIndex(['close_reason_name'], 'idx_c0fe4aacd81b931c', []);
        $table->addIndex(['status_name'], 'idx_c0fe4aac6625d392', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['workflow_step_id'], 'idx_c0fe4aac71fe882c', []);
    }

    /**
     * Create orocrm_sales_lead_status table
     *
     * @param Schema $schema
     */
    protected function createOrocrmSalesLeadStatusTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_sales_lead_status');
        $table->addColumn('name', 'string', ['length' => 32]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addUniqueIndex(['label'], 'uniq_4516951bea750e8');
        $table->setPrimaryKey(['name']);
    }

    /**
     * Create orocrm_sales_funnel table
     *
     * @param Schema $schema
     */
    protected function createOrocrmSalesFunnelTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_sales_funnel');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('opportunity_id', 'integer', ['notnull' => false]);
        $table->addColumn('lead_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('startdate', 'date', []);
        $table->addColumn('createdat', 'datetime', []);
        $table->addColumn('updatedat', 'datetime', ['notnull' => false]);
        $table->addIndex(['opportunity_id'], 'idx_e20c73449a34590f', []);
        $table->addIndex(['workflow_step_id'], 'idx_e20c734471fe882c', []);
        $table->addIndex(['lead_id'], 'idx_e20c734455458d', []);
        $table->addIndex(['startdate'], 'sales_start_idx', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_owner_id'], 'idx_e20c73449eb185f9', []);
        $table->addUniqueIndex(['workflow_item_id'], 'uniq_e20c73441023c4ee');
    }

    /**
     * Create orocrm_sales_opport_status table
     *
     * @param Schema $schema
     */
    protected function createOrocrmSalesOpportStatusTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_sales_opport_status');
        $table->addColumn('name', 'string', ['length' => 32]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addUniqueIndex(['label'], 'uniq_2db212b5ea750e8');
        $table->setPrimaryKey(['name']);
    }

    /**
     * Create orocrm_sales_opport_close_rsn table
     *
     * @param Schema $schema
     */
    protected function createOrocrmSalesOpportCloseRsnTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_sales_opport_close_rsn');
        $table->addColumn('name', 'string', ['length' => 32]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addUniqueIndex(['label'], 'uniq_fa526a41ea750e8');
        $table->setPrimaryKey(['name']);
    }

    /**
     * Create orocrm_sales_lead table
     *
     * @param Schema $schema
     */
    protected function createOrocrmSalesLeadTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_sales_lead');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('address_id', 'integer', ['notnull' => false]);
        $table->addColumn('contact_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('status_name', 'string', ['notnull' => false, 'length' => 32]);
        $table->addColumn('workflow_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('name_prefix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('first_name', 'string', ['length' => 255]);
        $table->addColumn('middle_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('last_name', 'string', ['length' => 255]);
        $table->addColumn('name_suffix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('job_title', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('phone_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('email', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('company_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('website', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('number_of_employees', 'integer', ['notnull' => false]);
        $table->addColumn('industry', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('createdat', 'datetime', []);
        $table->addColumn('updatedat', 'datetime', ['notnull' => false]);
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $this->extendExtension->addOptionSet(
            $schema,
            $table,
            'extend_source',
            [
                'extend' => ['is_extend' => true, 'set_expanded' => false]
            ]
        );
        $this->extendExtension->addManyToOneRelation(
            $schema,
            $table,
            'campaign',
            'orocrm_campaign',
            'combined_name',
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM, 'is_extend' => true]]
        );
        $table->addIndex(['status_name'], 'idx_73db46336625d392', []);
        $table->addIndex(['user_owner_id'], 'idx_73db46339eb185f9', []);
        $table->addIndex(['account_id'], 'idx_73db46339b6b5fba', []);
        $table->addIndex(['createdat'], 'lead_created_idx', []);
        $table->addIndex(['contact_id'], 'idx_73db4633e7a1254a', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['workflow_step_id'], 'idx_73db463371fe882c', []);
        $table->addIndex(['address_id'], 'idx_73db4633f5b7af75', []);
        $table->addUniqueIndex(['workflow_item_id'], 'uniq_73db46331023c4ee');
    }

    /**
     * Add orocrm_sales_opportunity foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrocrmSalesOpportunityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_sales_opportunity');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_contact'),
            ['contact_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_sales_opport_close_rsn'),
            ['close_reason_name'],
            ['name'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_account'),
            ['account_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_sales_opport_status'),
            ['status_name'],
            ['name'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_sales_lead'),
            ['lead_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_item'),
            ['workflow_item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_step'),
            ['workflow_step_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add orocrm_sales_funnel foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrocrmSalesFunnelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_sales_funnel');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_sales_opportunity'),
            ['opportunity_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_sales_lead'),
            ['lead_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_item'),
            ['workflow_item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_step'),
            ['workflow_step_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add orocrm_sales_lead foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrocrmSalesLeadForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_sales_lead');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_campaign'),
            ['campaign_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_address'),
            ['address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_contact'),
            ['contact_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_account'),
            ['account_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_sales_lead_status'),
            ['status_name'],
            ['name'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_item'),
            ['workflow_item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_step'),
            ['workflow_step_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
