<?php

declare(strict_types=1);

namespace OCA\MarkdownWiki\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;

class ApiController extends Controller
{
    public function __construct(
        string $appName,
        IRequest $request,
        private IConfig $config,
        private IUserSession $userSession,
        private IRootFolder $rootFolder,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function getWikiRoot(): DataResponse
    {
        $user = $this->userSession->getUser();

        if ($user === null) {
            return new DataResponse([
                'wikiRoot' => '',
            ], 401);
        }

        return new DataResponse([
            'wikiRoot' => $this->config->getUserValue(
                $user->getUID(),
                $this->appName,
                'wiki_root',
                '',
            ),
        ]);
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function setWikiRoot(string $wikiRoot): DataResponse
    {
        $user = $this->userSession->getUser();

        if ($user === null) {
            return new DataResponse([
                'success' => false,
            ], 401);
        }

        $wikiRoot = '/' . trim($wikiRoot, '/');

        $this->config->setUserValue(
            $user->getUID(),
            $this->appName,
            'wiki_root',
            $wikiRoot,
        );

        return new DataResponse([
            'success' => true,
            'wikiRoot' => $wikiRoot,
        ]);
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function listFiles(string $path = ''): DataResponse
    {
        $user = $this->userSession->getUser();

        if ($user === null) {
            return new DataResponse([
                'success' => false,
                'message' => 'Not authenticated.',
                'items' => [],
            ], 401);
        }

        $wikiRoot = $this->config->getUserValue(
            $user->getUID(),
            $this->appName,
            'wiki_root',
            '',
        );

        if ($wikiRoot === '') {
            return new DataResponse([
                'success' => false,
                'message' => 'Wiki Root is not configured.',
                'items' => [],
            ], 400);
        }

        $normalizedRoot = '/' . trim($wikiRoot, '/');

        $requestedPath = $path !== ''
            ? $path
            : $normalizedRoot;

        $normalizedPath = '/' . trim($requestedPath, '/');

        if (
            $normalizedPath !== $normalizedRoot
            && !str_starts_with(
                $normalizedPath . '/',
                $normalizedRoot . '/',
            )
        ) {
            return new DataResponse([
                'success' => false,
                'message' => 'Path is outside the Wiki Root.',
                'items' => [],
            ], 403);
        }

        $userFolder = $this->rootFolder->getUserFolder(
            $user->getUID(),
        );

        $relativePath = ltrim($normalizedPath, '/');

        try {
            $folder = $userFolder->get($relativePath);
        } catch (\OCP\Files\NotFoundException) {
            return new DataResponse([
                'success' => false,
                'message' => 'Folder was not found.',
                'items' => [],
            ], 404);
        }

        if (!$folder instanceof Folder) {
            return new DataResponse([
                'success' => false,
                'message' => 'Path is not a folder.',
                'items' => [],
            ], 400);
        }

        $items = [];

        foreach ($folder->getDirectoryListing() as $node) {
            /*
             * Convert Nextcloud's internal user path:
             *
             * /admin/files/MDs_Test/Test.md
             *
             * into the user-relative path used by the application:
             *
             * /MDs_Test/Test.md
             */
            $nodePath = $node->getPath();
            $userFolderPath = $userFolder->getPath();

            $relativeNodePath = '/' . ltrim(
                substr(
                    $nodePath,
                    strlen($userFolderPath),
                ),
                '/',
            );

            if ($node instanceof Folder) {
                $items[] = [
                    'name' => $node->getName(),
                    'type' => 'folder',
                    'path' => $relativeNodePath,
                ];

                continue;
            }

            if ($node instanceof File) {
                if (strtolower($node->getExtension()) !== 'md') {
                    continue;
                }

                $items[] = [
                    'name' => $node->getName(),
                    'type' => 'file',
                    'path' => $relativeNodePath,
                ];
            }
        }

        usort(
            $items,
            static function (array $a, array $b): int {
                if ($a['type'] !== $b['type']) {
                    return $a['type'] === 'folder' ? -1 : 1;
                }

                return strcasecmp($a['name'], $b['name']);
            }
        );

        return new DataResponse([
            'success' => true,
            'path' => $normalizedPath,
            'items' => $items,
        ]);
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function getFile(string $path): DataResponse
    {
        $user = $this->userSession->getUser();

        if ($user === null) {
            return new DataResponse([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);
        }

        $wikiRoot = $this->config->getUserValue(
            $user->getUID(),
            $this->appName,
            'wiki_root',
            '',
        );

        if ($wikiRoot === '') {
            return new DataResponse([
                'success' => false,
                'message' => 'Wiki Root is not configured.',
            ], 400);
        }

        $normalizedRoot = '/' . trim($wikiRoot, '/');
        $normalizedPath = '/' . trim($path, '/');

        if (
            $normalizedPath !== $normalizedRoot
            && !str_starts_with(
                $normalizedPath,
                $normalizedRoot . '/',
            )
        ) {
            return new DataResponse([
                'success' => false,
                'message' => 'File is outside Wiki Root.',
            ], 403);
        }

        if (
            strtolower(
                pathinfo($normalizedPath, PATHINFO_EXTENSION)
            ) !== 'md'
        ) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only Markdown files are allowed.',
            ], 400);
        }

        $userFolder = $this->rootFolder->getUserFolder(
            $user->getUID(),
        );

        $relativePath = ltrim($normalizedPath, '/');

        try {
            $file = $userFolder->get($relativePath);
        } catch (\OCP\Files\NotFoundException) {
            return new DataResponse([
                'success' => false,
                'message' => 'File was not found.',
            ], 404);
        }

        if (!$file instanceof File) {
            return new DataResponse([
                'success' => false,
                'message' => 'Path is not a file.',
            ], 400);
        }

        return new DataResponse([
            'success' => true,
            'name' => $file->getName(),
            'path' => $normalizedPath,
            'content' => $file->getContent(),
        ]);
    }
}