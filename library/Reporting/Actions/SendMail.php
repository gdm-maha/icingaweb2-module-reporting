<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Reporting\Actions;

use Icinga\Application\Config;
use Icinga\Application\Hook\PdfexportHook;
use Icinga\Module\Pdfexport\ProvidedHook\Pdfexport;
use Icinga\Module\Reporting\Hook\ActionHook;
use Icinga\Module\Reporting\Mail;
use Icinga\Module\Reporting\Report;
use ipl\Html\Form;
use ipl\Stdlib\Str;
use ipl\Validator\CallbackValidator;
use ipl\Validator\EmailAddressValidator;

class SendMail extends ActionHook
{
    public function getName()
    {
        return 'Send Mail';
    }

    public function execute(Report $report, array $config)
    {
        $name = sprintf(
            '%s - Availability %s',
            substr(date('Y'), 2, 2) . date('m'),
            $report->getName(),
        );

        $mail = new Mail();

        $mail->setFrom(
            Config::module('reporting', 'config', true)->get('mail', 'from', 'reporting@icinga')
        );

        if (isset($config['subject'])) {
            $mail->setSubject($config['subject']);
        }

        /** @var array<int, string> $recipients */
        $recipients = preg_split('/[\s,]+/', $config['recipients']);
        $recipients = array_filter($recipients);

        switch ($config['type']) {
            case 'pdf':
                // TODO: Remove this once the dependency on the Pdfexport module is removed
                /** @var PdfexportHook $exporter */
                $exporter = method_exists(PdfexportHook::class, 'first')
                    ? PdfexportHook::first()
                    : Pdfexport::first();

                if (! method_exists($exporter, 'asyncHtmlToPdf')) {
                    // Fallback for exporters that don't support the async API. This blocks on
                    // Loop::run() internally and must not be used while a scheduler loop is
                    // already running, otherwise the nested/reentrant run() call will hang.
                    $mail->attachPdf($exporter->htmlToPdf($report->toPdf()), $name);
                    $mail->send(null, $recipients);

                    return null;
                }

                // Use the async export API so this doesn't block/nest the already-running
                // scheduler event loop (Loop::run() must never be called reentrantly).
                return $exporter->asyncHtmlToPdf($report->toPdf())->then(
                    function ($pdf) use ($mail, $name, $recipients) {
                        $mail->attachPdf($pdf, $name);
                        $mail->send(null, $recipients);
                    }
                );
            case 'csv':
                $mail->attachCsv($report->toCsv(), $name);

                break;
            case 'json':
                $mail->attachJson($report->toJson(), $name);

                break;
            default:
                throw new \InvalidArgumentException();
        }

        $mail->send(null, $recipients);

        return null;
    }

    public function initConfigForm(Form $form, Report $report)
    {
        $types = ['pdf' => 'PDF'];

        if ($report->providesData()) {
            $types['csv'] = 'CSV';
            $types['json'] = 'JSON';
        }

        $form->addElement('select', 'type', [
            'required' => true,
            'label'    => t('Type'),
            'options'  => $types
        ]);

        $form->addElement('text', 'subject', [
            'label'       => t('Subject'),
            'placeholder' => Mail::DEFAULT_SUBJECT
        ]);

        $form->addElement('textarea', 'recipients', [
            'required' => true,
            'label'    => t('Recipients'),
            'validators' => [
                new CallbackValidator(function ($value, CallbackValidator $validator): bool {
                    $mailValidator = new EmailAddressValidator();
                    $mails = Str::trimSplit($value);
                    foreach ($mails as $mail) {
                        if (! $mailValidator->isValid($mail)) {
                            $validator->addMessage(...$mailValidator->getMessages());

                            return false;
                        }
                    }

                    return true;
                })
            ]
        ]);
    }
}
