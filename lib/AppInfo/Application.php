<?php

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
