<?php

namespace JnlgDoiContactForm\Core\Content\ContactForm;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ContactFormEntity extends Entity
{
    use EntityIdTrait;

    protected array $contactFormData;

    protected bool $dispatched;

    public function getContactFormData(): array
    {
        return $this->contactFormData;
    }

    public function setContactFormData(array $contactFormData): void
    {
        $this->contactFormData = $contactFormData;
    }


    public function isDispatched(): bool
    {
        return $this->dispatched;
    }


    public function setDispatched(bool $dispatched): void
    {
        $this->dispatched = $dispatched;
    }

}
