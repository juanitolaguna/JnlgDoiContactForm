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
use Shopware\Storefront\Page\GenericPageLoader;
use Shopware\Storefront\Page\Page;
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

    private GenericPageLoader $genericLoader;

    public function __construct(
        EntityRepositoryInterface $contactFormRepository,
        MailConfigService $mailConfigService,
        EventDispatcherInterface $eventDispatcher,
        GenericPageLoader $genericLoader
    ) {
        $this->contactFormRepository = $contactFormRepository;
        $this->mailConfigService = $mailConfigService;
        $this->eventDispatcher = $eventDispatcher;
        $this->genericLoader = $genericLoader;
    }

    /**
     * @HttpCache()
     * @Route("/doi/confirmation/{confirmationId}", name="jnlg.mail.confirmation", methods={"GET"})
     */
    public function confirm(Request $request, SalesChannelContext $context): ?Response
    {

        $page = $this->genericLoader->load($request, $context);


        $confirmationId = $request->get('confirmationId');


        try {
            $criteria = new Criteria([$confirmationId]);
            /** @var ContactFormEntity $result */
            $result = $this->contactFormRepository->search($criteria, $context->getContext())->first();
        } catch (\Exception $e) {
            $result = null;
        }
        $data =  new DataBag($result ? $result->getContactFormData(): []);

        $recipients = $this->mailConfigService->getRecipient($context, $data->get('slotId'), $data->get('navigationId'), $data->get('entityName'));

        if ($result != null && !$result->isDispatched()) {
            return $this->dispatchAndConfirm($context, $recipients, $data, $result, $page);
        }

        if ($result != null && $result->isDispatched()) {
            return $this->mailAllreadyConfirmed($data, $page);
        }

        $data->set('state', 'doesNotExist');
        return $this->renderStorefront('@JnlgDoiContactForm/storefront/page/mail-confirmation.html.twig', [
            'page' => $page,
            'data' => $data
        ]);
    }


    private function markAsDispatched(ContactFormEntity $result, SalesChannelContext $context): void
    {
        $this->contactFormRepository->update([
            [
                'id' => $result->getId(),
                'dispatched' => true

            ]
        ], $context->getContext());
    }

    private function dispatchAndConfirm(
        SalesChannelContext $context,
        array $recipients,
        DataBag $data,
        ContactFormEntity $result,
        Page $page
    ): Response {
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

        $this->markAsDispatched($result, $context);
        $data->set('state', 'confirm');

        return $this->renderStorefront('@JnlgDoiContactForm/storefront/page/mail-confirmation.html.twig', [
            'page' => $page,
            'data' => $data,
        ]);
    }


    private function mailAllreadyConfirmed(DataBag $data, Page $page): Response
    {
        $data->set('state', 'confirmed');
        return $this->renderStorefront('@JnlgDoiContactForm/storefront/page/mail-confirmation.html.twig', [
            'page' => $page,
            'data' => $data,
        ]);
    }
}