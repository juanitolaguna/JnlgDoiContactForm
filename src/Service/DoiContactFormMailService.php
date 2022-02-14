<?php

namespace JnlgDoiContactForm\Service;

use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Mime\Email;

final class DoiContactFormMailService
{
    private AbstractMailService $mailService;

    private EntityRepositoryInterface $mailTemplateRepository;


    public function __construct(
        AbstractMailService $mailService,
        EntityRepositoryInterface $mailTemplateRepository
    ) {
        $this->mailService = $mailService;
        $this->mailTemplateRepository = $mailTemplateRepository;
    }


    public function sendEmail(
        MailTemplateEntity $mailTemplate,
        SalesChannelContext $context,
        array $recipients,
        array $templateData
    ): ?Email {
        $data = new DataBag();
        $data->set('recipients', $recipients);
        $data->set('senderName', $mailTemplate->getTranslation('senderName'));
        $data->set('salesChannelId', ($context->getSalesChannelId() ?: null));
        $data->set('templateId', $mailTemplate->getId());
        $data->set('customFields', $mailTemplate->getCustomFields());
        $data->set('contentHtml', $mailTemplate->getTranslation('contentHtml'));
        $data->set('contentPlain', $mailTemplate->getTranslation('contentPlain'));
        $data->set('subject', $mailTemplate->getTranslation('subject'));
        $data->set('mediaIds', []);

        return $this->mailService->send(
            $data->all(),
            $context->getContext(),
            $templateData
        );
    }

    public function getMailTemplateEntity(
        string $technicalName,
        Context $context
    ): ?MailTemplateEntity {
        $criteria = new Criteria();

        $criteria->addFilter(new EqualsFilter('mailTemplateType.technicalName', $technicalName));
        $criteria->setLimit(1);
        return $this->mailTemplateRepository->search($criteria, $context)->first();
    }
}