<?php
declare(strict_types=1);

namespace JnlgDoiContactForm\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1644537970ContactForm extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1644537970;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            "CREATE TABLE `jnlg_contact_form` (
                    `id` BINARY(16) NOT NULL,
                    `contact_form_data` JSON NULL,
                    `dispatched` TINYINT(1) NULL DEFAULT '0',
                    `created_at` DATETIME(3) NOT NULL,
                    `updated_at` DATETIME(3) NULL,
                    PRIMARY KEY (`id`),
                    CONSTRAINT `json.jnlg_contact_form.contact_form_data` CHECK (JSON_VALID(`contact_form_data`))
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
