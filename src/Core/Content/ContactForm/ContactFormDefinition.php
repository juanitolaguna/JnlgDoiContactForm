<?php

namespace JnlgDoiContactForm\Core\Content\ContactForm;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ContactFormDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'jnlg_contact_form';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return ContactFormCollection::class;
    }

    public function getEntityClass(): string
    {
        return ContactFormEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))
                ->addFlags(new Required(), new PrimaryKey(), new ApiAware()),

            (new JsonField('contact_form_data', 'contactFormData'))->addFlags(new ApiAware()),

            (new BoolField('dispatched', 'dispatched'))->addFlags(new ApiAware())
        ]);
    }
}