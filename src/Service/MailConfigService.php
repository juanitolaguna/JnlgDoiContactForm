<?php

namespace JnlgDoiContactForm\Service;

use Shopware\Core\Content\LandingPage\LandingPageDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;


final class MailConfigService
{
    private SystemConfigService $systemConfigService;

    private EntityRepositoryInterface $cmsSlotRepository;

    private EntityRepositoryInterface $productRepository;

    private EntityRepositoryInterface $landingPageRepository;

    private EntityRepositoryInterface $categoryRepository;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $cmsSlotRepository,
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $landingPageRepository,
        EntityRepositoryInterface $categoryRepository
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->cmsSlotRepository = $cmsSlotRepository;
        $this->productRepository = $productRepository;
        $this->landingPageRepository = $landingPageRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function getRecipient(SalesChannelContext $context, ?string $slotId = null, ?string $navigationId = null, ?string $entityName = null): array {

        $mailConfigs = $this->getMailConfigs($context, $slotId, $navigationId, $entityName);
        if (empty($mailConfigs['receivers'])) {
            $mailConfigs['receivers'][] = $this->systemConfigService->get('core.basicInformation.email');
        }

        $recipientStructs = [];
        foreach ($mailConfigs['receivers'] as $mail) {
            $recipientStructs[$mail] = $mail;
        }

        return $recipientStructs;
    }

    private function getMailConfigs(SalesChannelContext $context, ?string $slotId = null, ?string $navigationId = null, ?string $entityName = null): array
    {
        $mailConfigs['receivers'] = [];

        if (!$slotId) {
            return $mailConfigs;
        }

        if ($navigationId) {
            $mailConfigs = $this->getSlotConfig($slotId, $navigationId, $context, $entityName);
            if (!empty($mailConfigs['receivers'])) {
                return $mailConfigs;
            }
        }

        $criteria = new Criteria([$slotId]);
        $slot = $this->cmsSlotRepository->search($criteria, $context->getContext());
        $mailConfigs['receivers'] = $slot->getEntities()->first()->getTranslated()['config']['mailReceiver']['value'];
        return $mailConfigs;
    }


    private function getSlotConfig(string $slotId, string $navigationId, SalesChannelContext $context, ?string $entityName = null): array
    {
        $mailConfigs['receivers'] = [];

        $criteria = new Criteria([$navigationId]);

        switch ($entityName) {
            case ProductDefinition::ENTITY_NAME:
                $entity = $this->productRepository->search($criteria, $context->getContext())->first();

                break;
            case LandingPageDefinition::ENTITY_NAME:
                $entity = $this->landingPageRepository->search($criteria, $context->getContext())->first();

                break;
            default:
                $entity = $this->categoryRepository->search($criteria, $context->getContext())->first();
        }

        if (!$entity) {
            return $mailConfigs;
        }

        if (empty($entity->getSlotConfig()[$slotId])) {
            return $mailConfigs;
        }

        $mailConfigs['receivers'] = $entity->getSlotConfig()[$slotId]['mailReceiver']['value'];

        return $mailConfigs;
    }
}