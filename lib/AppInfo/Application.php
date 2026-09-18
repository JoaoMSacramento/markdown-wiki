<?php

/**
 * SPDX-FileCopyrightText: 2026 João Sacramento <jlmsacramento@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\MarkdownWiki\AppInfo;

use OCP\AppFramework\App;

class Application extends App
{
    public const APP_ID = 'markdown_wiki';

    public function __construct()
    {
        parent::__construct(self::APP_ID);
    }
}
