<?php

namespace JnlgDoiContactForm\Controller;

use JnlgDoiContactForm\Core\Content\ContactForm\ContactFormEntity;
use JnlgDoiContactForm\Service\MailConfigService;
use Shopware\Core\Content\ContactForm\Event\ContactFormEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Cache\Annotation\HttpCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @RouteScope(scopes={"storefront"})
 */
class MailConfirmationController extends StorefrontController
{
    private EntityRepositoryInterface $contactFormRepository;

    private MailConfigService $mailConfigService;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityRepositoryInterface $contactFormRepository,
        MailConfigService $mailConfigService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->contactFormRepository = $contactFormRepository;
        $this->mailConfigService = $mailConfigService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @HttpCache()
     * @Route("/doi/confirmation/{confirmationId}", name="jnlg.mail.confirmation", methods={"GET"})
     */
    public function confirm(Request $request, SalesChannelContext $context): ?Response
    {
        $confirmationId = $request->get('confirmationId');

        $criteria = new Criteria([$confirmationId]);
        /** @var ContactFormEntity $result */
        $result = $this->contactFormRepository->search($criteria, $context->getContext())->first();

        $data =  new DataBag($result->getContactFormData());
        $recipients = $this->mailConfigService->getRecipient($context, $data->get('slotId'), $data->get('navigationId'), $data->get('entityName'));


        if ($result != null) {
            $event = new ContactFormEvent(
                $context->getContext(),
                $context->getSalesChannel()->getId(),
                new MailRecipientStruct($recipients),
                $data
            );

            $this->eventDispatcher->dispatch(
                $event,
                ContactFormEvent::EVENT_NAME
            );

            dd('send');
        } else {
            dd('nada');
        }

        return null;
    }
}