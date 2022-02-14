<?php declare(strict_types=1);

namespace JnlgDoiContactForm;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use ErrorException;
use JnlgDoiContactForm\Exception\Locale\LocaleException;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;

class JnlgDoiContactForm extends Plugin
{

    private LoggerInterface $logger;

    /**
     * @throws LocaleException
     * @throws ErrorException
     * @throws Exception
     */
    public function activate(ActivateContext $activateContext): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get('Doctrine\DBAL\Connection');

        /** @var EntityRepository $languageRepository */
        $languageRepository = $this->container->get('language.repository');

        (new Migration\Setup\DoiMailTemplateSetup($connection, $languageRepository))->createContactFormTemplate();
    }

    /**
     * @param LoggerInterface $logger
     *
     * @required
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}