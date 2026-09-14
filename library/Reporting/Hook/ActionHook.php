<?php

// SPDX-FileCopyrightText: 2019 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Reporting\Hook;

use Icinga\Application\Hook;
use Icinga\Module\Reporting\Report;
use ipl\Html\Form;

abstract class ActionHook
{
    /**
     * @return  string
     */
    abstract public function getName();

    /**
     * Execute this action
     *
     * If asynchronous work needs to be awaited (e.g. exporting a PDF), return the
     * corresponding {@see \React\Promise\PromiseInterface} instead of blocking, since this
     * may be called from within an already running event loop (e.g. the scheduler daemon).
     *
     * @param Report $report
     * @param array  $config
     *
     * @return \React\Promise\PromiseInterface|null
     */
    abstract public function execute(Report $report, array $config);

    /**
     * @param Form   $form
     * @param Report $report
     */
    public function initConfigForm(Form $form, Report $report)
    {
    }

    /**
     * @return  ActionHook[]
     */
    final public static function getActions()
    {
        return Hook::all('reporting/Action');
    }
}
