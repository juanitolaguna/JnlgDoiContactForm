<?php

namespace JnlgDoiContactForm\Core\Content\ContactForm\SalesChannel;

use JnlgDoiContactForm\Migration\Setup\DoiMailTemplateSetup;
use JnlgDoiContactForm\Service\DoiContactFormMailService;
use Shopware\Core\Content\ContactForm\SalesChannel\AbstractContactFormRoute;
use Shopware\Core\Content\ContactForm\SalesChannel\ContactFormRouteResponse;
use Shopware\Core\Content\ContactForm\SalesChannel\ContactFormRouteResponseStruct;
use Shopware\Core\Content\LandingPage\LandingPageDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\RateLimiter\RateLimiter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationFactoryInterface;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @RouteScope(scopes={"store-api"})
 */
class ContactFormRouteDecorator extends AbstractContactFormRoute
{
    private DataValidationFactoryInterface $contactFormValidationFactory;

    private DataValidator $validator;

    private EventDispatcherInterface $eventDispatcher;

    private SystemConfigService $systemConfigService;

    private EntityRepositoryInterface $cmsSlotRepository;

    private EntityRepositoryInterface $salutationRepository;

    private EntityRepositoryInterface $categoryRepository;

    private EntityRepositoryInterface $landingPageRepository;

    private EntityRepositoryInterface $productRepository;

    private RequestStack $requestStack;

    private RateLimiter $rateLimiter;

    private EntityRepositoryInterface $contactFormRepository;

    private AbstractContactFormRoute $decorated;

    private DoiContactFormMailService $mailService;

    private UrlGeneratorInterface $router;

    /**
     * @param DataValidationFactoryInterface $contactFormValidationFactory
     * @param DataValidator $validator
     * @param EventDispatcherInterface $eventDispatcher
     * @param SystemConfigService $systemConfigService
     * @param EntityRepositoryInterface $cmsSlotRepository
     * @param EntityRepositoryInterface $salutationRepository
     * @param EntityRepositoryInterface $categoryRepository
     * @param EntityRepositoryInterface $landingPageRepository
     * @param EntityRepositoryInterface $productRepository
     * @param RequestStack $requestStack
     * @param RateLimiter $rateLimiter
     * @param EntityRepositoryInterface $contactFormRepository
     * @param AbstractContactFormRoute $decorated
     * @param DoiContactFormMailService $mailService
     * @param UrlGeneratorInterface $router
     */
    public function __construct(
        DataValidationFactoryInterface $contactFormValidationFactory,
        DataValidator $validator,
        EventDispatcherInterface $eventDispatcher,
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $cmsSlotRepository,
        EntityRepositoryInterface $salutationRepository,
        EntityRepositoryInterface $categoryRepository,
        EntityRepositoryInterface $landingPageRepository,
        EntityRepositoryInterface $productRepository,
        RequestStack $requestStack,
        RateLimiter $rateLimiter,
        EntityRepositoryInterface $contactFormRepository,
        AbstractContactFormRoute $decorated,
        DoiContactFormMailService $mailService,
        UrlGeneratorInterface $router
    ) {
        $this->contactFormValidationFactory = $contactFormValidationFactory;
        $this->validator = $validator;
        $this->eventDispatcher = $eventDispatcher;
        $this->systemConfigService = $systemConfigService;
        $this->cmsSlotRepository = $cmsSlotRepository;
        $this->salutationRepository = $salutationRepository;
        $this->categoryRepository = $categoryRepository;
        $this->landingPageRepository = $landingPageRepository;
        $this->productRepository = $productRepository;
        $this->requestStack = $requestStack;
        $this->rateLimiter = $rateLimiter;
        $this->contactFormRepository = $contactFormRepository;
        $this->decorated = $decorated;
        $this->mailService = $mailService;
        $this->router = $router;
    }


    public function getDecorated(): AbstractContactFormRoute
    {
        return $this->decorated;
    }

    /**
     * @Route("/store-api/contact-form", name="store-api.contact.form", methods={"POST"})
     */
    public function load(RequestDataBag $data, SalesChannelContext $context): ContactFormRouteResponse
    {
        $this->validateContactForm($data, $context);

        if (Feature::isActive('FEATURE_NEXT_13795') && ($request = $this->requestStack->getMainRequest(
            )) !== null && $request->getClientIp() !== null) {
            $this->rateLimiter->ensureAccepted(RateLimiter::CONTACT_FORM, $request->getClientIp());
        }

        $mailConfigs = $this->getMailConfigs($context, $data->get('slotId'), $data->get('navigationId'), $data->get('entityName'));
        $this->setSalutation($data, $context);

        $uuid = Uuid::randomHex();

        $this->setConfirmationLink($uuid, $data);

        $this->contactFormRepository->create([
            [
                'id' => $uuid,
                'contactFormData' => $data->all(),
                'dispatched' => false,
            ]
        ], $context->getContext());

        $this->sendConfirmationMail($data, $context);

        $result = new ContactFormRouteResponseStruct();
        $result->assign([
            'individualSuccessMessage' => $mailConfigs['message'] ?? '',
        ]);

        return new ContactFormRouteResponse($result);
    }

    private function validateContactForm(DataBag $data, SalesChannelContext $context): void
    {
        $definition = $this->contactFormValidationFactory->create($context);
        $violations = $this->validator->getViolations($data->all(), $definition);

        if ($violations->count() > 0) {
            throw new ConstraintViolationException($violations, $data->all());
        }
    }

    private function getSlotConfig(string $slotId, string $navigationId, SalesChannelContext $context, ?string $entityName = null): array
    {
        $mailConfigs['receivers'] = [];
        $mailConfigs['message'] = '';

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
        $mailConfigs['message'] = $entity->getSlotConfig()[$slotId]['confirmationText']['value'];

        return $mailConfigs;
    }

    private function getMailConfigs(SalesChannelContext $context, ?string $slotId = null, ?string $navigationId = null, ?string $entityName = null): array
    {
        $mailConfigs['receivers'] = [];
        $mailConfigs['message'] = '';

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
        $mailConfigs['message'] = $slot->getEntities()->first()->getTranslated()['config']['confirmationText']['value'];

        return $mailConfigs;
    }

    private function sendConfirmationMail(RequestDataBag $data, SalesChannelContext $context): void
    {
        $recipientName = sprintf('%s %s', $data->all()['firstName'], $data->all()['lastName']);
        $recipientMail = $data->all()['email'];

        $mailTemplate = $this->mailService->getMailTemplateEntity(
            DoiMailTemplateSetup::TEMPLATE_TYPE,
            $context->getContext()
        );

        $this->mailService->sendEmail($mailTemplate, $context, [$recipientMail => $recipientName], ['contactFormData' => $data->all()]);
    }


    private function setSalutation(RequestDataBag $data, SalesChannelContext $context): void
    {
        $salutationCriteria = new Criteria([$data->get('salutationId')]);
        $salutationSearchResult = $this->salutationRepository->search($salutationCriteria, $context->getContext());

        if ($salutationSearchResult->count() !== 0) {
            $data->set('salutation', $salutationSearchResult->first());
        }
    }

    private function setConfirmationLink(string $uuid, RequestDataBag $data): void
    {
        $confirmationLink = $this->router->generate('jnlg.mail.confirmation', [
            'confirmationId' => $uuid
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $data->set('confirmationId', $uuid);
        $data->set('confirmationLink', $confirmationLink);
    }
}