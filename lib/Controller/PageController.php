<?php

declare(strict_types=1);

namespace OCA\MarkdownWiki\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class PageController extends Controller
{
    public function __construct(
        string $appName,
        IRequest $request,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function index(): TemplateResponse
    {
        Util::addScript($this->appName, 'markdown_wiki');

        return new TemplateResponse(
            $this->appName,
            'main',
        );
    }
}
