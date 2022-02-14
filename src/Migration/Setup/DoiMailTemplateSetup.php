<?php

namespace JnlgDoiContactForm\Migration\Setup;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use ErrorException;
use JnlgDoiContactForm\Exception\Locale\LocaleException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageEntity;

final class DoiMailTemplateSetup
{
    public const TEMPLATE_TYPE = 'jnlg_doi_contact_form';

    private Connection $connection;

    private EntityRepository $languageRepository;

    /**
     * @param Connection $connection
     * @param EntityRepository $languageRepository
     */
    public function __construct(Connection $connection, EntityRepository $languageRepository)
    {
        $this->connection = $connection;
        $this->languageRepository = $languageRepository;
    }


    /**
     * @throws LocaleException
     * @throws ErrorException
     * @throws Exception
     */
    public function createContactFormTemplate(): void
    {
        $templateTypeId = $this->createMailTemplateType($this->connection);
        $this->createMailTemplate($templateTypeId, $this->connection);
    }

    /**
     * @throws ErrorException
     * @throws Exception
     */
    private function createMailTemplateType(Connection $connection): string
    {
        $mailTemplateTypeId = Uuid::randomHex();

        $connection->insert('mail_template_type', [
            'id' => Uuid::fromHexToBytes($mailTemplateTypeId),
            'technical_name' => self::TEMPLATE_TYPE,
            'available_entities' => json_encode(['salesChannel' => 'sales_channel']),
            'template_data' => MailTemplateData::templateData(),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        ]);


        $this->createMailTemplateTypeTranslations($mailTemplateTypeId, $connection);
        return $mailTemplateTypeId;
    }


    /**
     * @throws LocaleException
     * @throws Exception
     */
    private function createMailTemplateTypeTranslations(string $mailTemplateTypeId, Connection $connection)
    {
        $data['en-GB']['name'] = 'Contact Form DOI Mail';
        $data['de-DE']['name'] = 'Kontakt Formular DOI Mail';

        foreach ($this->getLocale() as $locale) {
            $connection->insert('mail_template_type_translation', [
                'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'name' => $data[$locale]['name'],
                'language_id' => Uuid::fromHexToBytes($this->getLanguageIdByLocale($locale)),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
            ]);
        }
    }

    /**
     * @throws ErrorException
     * @throws Exception
     * @throws LocaleException
     */
    private function createMailTemplate(string $mailTemplateTypeId, Connection $connection)
    {
        $mailTemplateId = Uuid::randomHex();

        $connection->insert('mail_template', [
            'id' => Uuid::fromHexToBytes($mailTemplateId),
            'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId),
            'system_default' => true,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        ]);

        $this->createMailTemplateTranslations($connection, $mailTemplateId, MailTemplateData::getMailTemplateData());
        $this->addMailTemplateToSalesChannels($connection, $mailTemplateTypeId, $mailTemplateId);
    }

    /**
     * @throws LocaleException
     * @throws Exception
     */
    private function createMailTemplateTranslations(Connection $connection, string $mailTemplateId, array $data): void
    {
        foreach ($this->getLocale() as $locale) {
            $connection->insert('mail_template_translation', [
                'mail_template_id' => Uuid::fromHexToBytes($mailTemplateId),
                'language_id' => Uuid::fromHexToBytes($this->getLanguageIdByLocale($locale)),
                'sender_name' => $data[$locale]['senderName'],
                'subject' => $data[$locale]['subject'],
                'description' => $data[$locale]['description'],
                'content_html' => $data[$locale]['contentHtml'],
                'content_plain' => $data[$locale]['contentPlain'],
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
            ]);
        }
    }

    /**
     * @throws Exception
     */
    private function addMailTemplateToSalesChannels(
        Connection $connection,
        string $mailTemplateTypeId,
        string $mailTemplateId
    ): void {
        $salesChannels = $connection->fetchAllAssociative('SELECT `id` FROM `sales_channel`');

        foreach ($salesChannels as $salesChannel) {
            $mailTemplateSalesChannel = [
                'id' => Uuid::randomBytes(),
                'mail_template_id' => Uuid::fromHexToBytes($mailTemplateId),
                'mail_template_type_id' => Uuid::fromHexToBytes($mailTemplateTypeId),
                'sales_channel_id' => $salesChannel['id'],
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ];
            $connection->insert('mail_template_sales_channel', $mailTemplateSalesChannel);
        }
    }


    private function getLocale()
    {
        return ['en-GB', 'de-DE'];
    }

    /**
     * @throws LocaleException
     */
    private function getLanguageIdByLocale(string $locale): string
    {
        $criteria = new Criteria();
        $criteria->addAssociation('locale');
        $criteria->addFilter(new EqualsFilter('locale.code', $locale));

        /** @var LanguageEntity $languageEntity */
        $languageEntity = $this->languageRepository->search($criteria, new Context(new SystemSource()))->first();

        if ($languageEntity === null) {
            throw new LocaleException($locale);
        }

        return $languageEntity->getId();
    }


}