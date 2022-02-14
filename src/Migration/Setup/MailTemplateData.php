<?php

namespace JnlgDoiContactForm\Migration\Setup;

final class MailTemplateData
{
    public static function getMailTemplateData(): array
    {
        $templateData['en-GB']['senderName'] = '{{ salesChannel.name }}';
        $templateData['en-GB']['subject'] = 'Contact Form Mail Confirmation';
        $templateData['en-GB']['description'] = 'Contact Form Mail Confirmation';

        $templateData['en-GB']['contentHtml'] = self::getTemplateContent()['en-GB']['html'];
        $templateData['en-GB']['contentPlain'] = self::getTemplateContent()['en-GB']['plain'];

        $templateData['de-DE']['senderName'] = '{{ salesChannel.name }}';
        $templateData['de-DE']['subject'] = 'Kontakt Formular Email Bestätigung';
        $templateData['de-DE']['description'] = 'Kontakt Formular Email Bestätigung';

        $templateData['de-DE']['contentHtml'] = self::getTemplateContent()['de-DE']['html'];
        $templateData['de-DE']['contentPlain'] = self::getTemplateContent()['de-DE']['plain'];

        return $templateData;
    }

    private static function getTemplateContent(): array
    {
        $template['en-GB']['plain'] = 'In order send your contact request please confirm your email by clicking this Link: {{ contactFormData.confirmationLink }}';
        $template['de-DE']['plain'] = 'Bitte bestätige deine Email unter dem folgenden Link:{{ contactFormData.confirmationLink }}, damit deine Kontaktanfrage an uns versendet werden kann.';

        $template['en-GB']['html'] = <<<HTML
        <div style="font-family:arial; font-size:12px;">
            <p>
                In order send your contact request please confirm your email by clicking this Link: 
                <a href="{{ contactFormData.confirmationLink }}">
                {{ contactFormData.confirmationLink }}
                </a>
            </p>
        </div>
        HTML;

        $template['de-DE']['html'] = <<<HTML
        <div style="font-family:arial; font-size:12px;">
            <p>
                Bitte bestätige deine Email unter dem folgenden Link:
                 <a href="{{ contactFormData.confirmationLink }}">
                {{ contactFormData.confirmationLink }}
                </a>
                ,damit deine Kontaktanfrage an uns versendet werden kann.
            </p>
        </div>
        HTML;

        return $template;
    }

    public static function templateData(): string
    {
        return <<<'JSON'
            {
              "contactFormData": {
                "email": "test@example.com",
                "phone": "1234567890",
                "slotId": "ca8c3803264e415da27fa73b70e5cd8d",
                "comment": "comment",
                "privacy": "on",
                "subject": "subject",
                "lastName": "lastName",
                "firstName": "firstName",
                "entityName": "category",
                "confirmationId" : "confirmationId"
                "salutation": {
                  "id": "71a7ffd08df642a2a1a9c87c1de89262",
                  "createdAt": "2020-06-30T13:11:11.135+00:00",
                  "customers": null,
                  "updatedAt": "2020-12-20T18:51:17.729+00:00",
                  "versionId": null,
                  "extensions": {
                    "foreignKeys": {
                      "apiAlias": null,
                      "extensions": []
                    }
                  },
                  "letterName": "Sehr geehrter Herr/Frau",
                  "translated": {
                    "letterName": "Sehr geehrter Herr/Frau",
                    "displayName": "Herr",
                    "customFields": []
                  },
                  "displayName": "Herr",
                  "customFields": null,
                  "translations": null,
                  "salutationKey": "mr",
                  "orderAddresses": null,
                  "orderCustomers": null,
                  "_uniqueIdentifier": "71a7ffd08df642a2a1a9c87c1de89262",
                  "customerAddresses": null,
                  "newsletterRecipients": null
                },
                "_csrf_token": "4a31b1f24.iIaMVoLycsyme1AwrlfJcMgvhrRQ5V4F1XQ3z7Ei_Lc.w_7AIrKtJLzLLxNY9xibH7xq1Pk3jRVaihBAnPpDnd-4798cwIUCudMsHQ",
                "navigationId": "50fe15f911f74469b30624eb3b6677d5",
                "salutationId": "71a7ffd08df642a2a1a9c87c1de89262",
                "shopware_surname_confirm": ""
              }
            }
        JSON;
    }

}