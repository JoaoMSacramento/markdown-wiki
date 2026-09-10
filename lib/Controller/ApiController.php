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
                'message' => 'Not authenticated.',
            ], 401);
        }

        $wikiRoot = '/' . trim($wikiRoot, '/');

        if ($wikiRoot === '/') {
            return new DataResponse([
                'success' => false,
                'message' => 'Wiki Root cannot be the root of your Files.',
            ], 400);
        }

        /*
         * Prevent path traversal and invalid relative paths.
         */
        if (
            str_contains($wikiRoot, '/../') ||
            str_ends_with($wikiRoot, '/..') ||
            str_starts_with($wikiRoot, '../') ||
            str_contains($wikiRoot, '/./') ||
            str_ends_with($wikiRoot, '/.')
        ) {
            return new DataResponse([
                'success' => false,
                'message' => 'Invalid Wiki Root path.',
            ], 400);
        }

        $userFolder = $this->rootFolder->getUserFolder(
            $user->getUID(),
        );

        $relativePath = ltrim($wikiRoot, '/');

        try {
            $folder = $userFolder->get($relativePath);
        } catch (\OCP\Files\NotFoundException) {
            return new DataResponse([
                'success' => false,
                'message' => 'The selected folder was not found.',
            ], 404);
        }

        if (!$folder instanceof Folder) {
            return new DataResponse([
                'success' => false,
                'message' => 'The selected Wiki Root is not a folder.',
            ], 400);
        }

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
    public function listFiles(
        string $path = '',
        bool $showAll = false,
    ): DataResponse {
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

        /*
         * Prevent access outside the configured Wiki Root.
         */
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

            if (!$node instanceof File) {
                continue;
            }

            $extension = strtolower(
                $node->getExtension(),
            );

            /*
             * In the default Markdown mode we only expose
             * Markdown files.
             */
            if (
                !$showAll &&
                $extension !== 'md'
            ) {
                continue;
            }

            $fileType = match ($extension) {
                'md', 'markdown' => 'markdown',

                'png',
                'jpg',
                'jpeg',
                'gif',
                'webp',
                'svg',
                'bmp',
                'ico',
                'avif' => 'image',

                'pdf' => 'pdf',

                'txt',
                'log',
                'csv' => 'text',

                'json',
                'xml',
                'yaml',
                'yml',
                'toml',
                'ini',
                'conf' => 'code',

                'js',
                'jsx',
                'ts',
                'tsx',
                'vue',
                'css',
                'scss',
                'html',
                'php',
                'py',
                'java',
                'c',
                'cpp',
                'h',
                'hpp',
                'sh',
                'bash',
                'zsh' => 'code',

                'zip',
                'tar',
                'gz',
                '7z',
                'rar' => 'archive',

                default => 'file',
            };

            $items[] = [
                'name' => $node->getName(),
                'type' => 'file',
                'fileType' => $fileType,
                'extension' => $extension,
                'path' => $relativeNodePath,
            ];
        }

        /*
         * Folders always come first.
         * Files are sorted alphabetically.
         */
        usort(
            $items,
            static function (array $a, array $b): int {
                if ($a['type'] !== $b['type']) {
                    return $a['type'] === 'folder' ? -1 : 1;
                }

                return strcasecmp(
                    $a['name'],
                    $b['name'],
                );
            },
        );

        return new DataResponse([
            'success' => true,
            'path' => $normalizedPath,
            'showAll' => $showAll,
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
                pathinfo(
                    $normalizedPath,
                    PATHINFO_EXTENSION,
                ),
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

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function saveFile(
        string $path,
        string $content = '',
    ): DataResponse {
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

        /*
         * Reject invalid paths before accessing the filesystem.
         */
        if (
            $normalizedPath === '/'
            || str_contains($normalizedPath, '/../')
            || str_ends_with($normalizedPath, '/..')
            || str_starts_with($normalizedPath, '../')
            || str_contains($normalizedPath, '/./')
            || str_ends_with($normalizedPath, '/.')
        ) {
            return new DataResponse([
                'success' => false,
                'message' => 'Invalid file path.',
            ], 400);
        }

        /*
         * The file must remain inside the configured Wiki Root.
         */
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

        /*
         * Only Markdown files can currently be edited.
         */
        if (
            strtolower(
                pathinfo(
                    $normalizedPath,
                    PATHINFO_EXTENSION,
                ),
            ) !== 'md'
        ) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only Markdown files can be edited.',
            ], 400);
        }

        $userFolder = $this->rootFolder->getUserFolder(
            $user->getUID(),
        );

        $relativePath = ltrim(
            $normalizedPath,
            '/',
        );

        try {
            $file = $userFolder->get(
                $relativePath,
            );
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

        try {
            $file->putContent($content);
        } catch (\Throwable $e) {
            return new DataResponse([
                'success' => false,
                'message' => 'Failed to save the Markdown file.',
            ], 500);
        }

        return new DataResponse([
            'success' => true,
            'name' => $file->getName(),
            'path' => $normalizedPath,
        ]);
    }

    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function search(): DataResponse
    {
        $query = trim(
            $this->request->getParam('query', ''),
        );

        $user = $this->userSession->getUser();

        if ($user === null) {
            return new DataResponse([
                'success' => false,
                'message' => 'Not authenticated.',
                'results' => [],
            ], 401);
        }

        if ($query === '') {
            return new DataResponse([
                'success' => true,
                'results' => [],
            ]);
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
                'results' => [],
            ], 400);
        }

        $normalizedRoot = '/' . trim($wikiRoot, '/');

        $userFolder = $this->rootFolder->getUserFolder(
            $user->getUID(),
        );

        $relativeRoot = ltrim($normalizedRoot, '/');

        try {
            $rootFolder = $userFolder->get($relativeRoot);
        } catch (\OCP\Files\NotFoundException) {
            return new DataResponse([
                'success' => false,
                'message' => 'Wiki Root was not found.',
                'results' => [],
            ], 404);
        }

        if (!$rootFolder instanceof Folder) {
            return new DataResponse([
                'success' => false,
                'message' => 'Wiki Root is not a folder.',
                'results' => [],
            ], 400);
        }

        $queryLower = mb_strtolower($query);
        $results = [];

        $this->searchFolder(
            $rootFolder,
            $userFolder,
            $queryLower,
            $results,
        );

        /*
         * Keep the response bounded so a broad query cannot
         * generate an unnecessarily large response.
         */
        $results = array_slice($results, 0, 100);

        return new DataResponse([
            'success' => true,
            'query' => $query,
            'results' => $results,
        ]);
    }

    private function searchFolder(
        Folder $folder,
        Folder $userFolder,
        string $query,
        array &$results,
    ): void {
        foreach ($folder->getDirectoryListing() as $node) {
            if (count($results) >= 100) {
                return;
            }

            if ($node instanceof Folder) {
                $this->searchFolder(
                    $node,
                    $userFolder,
                    $query,
                    $results,
                );

                continue;
            }

            if (!$node instanceof File) {
                continue;
            }

            if (
                strtolower(
                    $node->getExtension(),
                ) !== 'md'
            ) {
                continue;
            }

            $nodeName = $node->getName();
            $nameMatch = mb_stripos(
                $nodeName,
                $query,
            ) !== false;

            $content = '';

            if (!$nameMatch) {
                $content = $node->getContent();

                if (
                    mb_stripos(
                        $content,
                        $query,
                    ) === false
                ) {
                    continue;
                }
            }

            $nodePath = $node->getPath();
            $userFolderPath = $userFolder->getPath();

            $relativeNodePath = '/' . ltrim(
                substr(
                    $nodePath,
                    strlen($userFolderPath),
                ),
                '/',
            );

            $matchType = $nameMatch
                ? 'name'
                : 'content';

            $context = '';

            if (!$nameMatch) {
                $position = mb_stripos(
                    $content,
                    $query,
                );

                if ($position !== false) {
                    $start = max(
                        0,
                        $position - 80,
                    );

                    $context = mb_substr(
                        $content,
                        $start,
                        180,
                    );

                    if ($start > 0) {
                        $context = '…' . $context;
                    }

                    if (
                        $start + 180 < mb_strlen($content)
                    ) {
                        $context .= '…';
                    }
                }
            }

            $results[] = [
                'name' => $nodeName,
                'path' => $relativeNodePath,
                'matchType' => $matchType,
                'context' => $context,
            ];
        }
    }
}