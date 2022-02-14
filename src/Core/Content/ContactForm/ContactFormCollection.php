<?php

namespace JnlgDoiContactForm\Core\Content\ContactForm;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                add(ContactFormEntity $entity)
 * @method void                set(string $key, ContactFormEntity $entity)
 * @method ContactFormEntity[]    getIterator()
 * @method ContactFormEntity[]    getElements()
 * @method ContactFormEntity|null get(string $key)
 * @method ContactFormEntity|null first()
 * @method ContactFormEntity|null last()
 */
class ContactFormCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'jnlg_contact_form';
    }

    protected function getExpectedClass(): string
    {
        return ContactFormEntity::class;
    }
}
