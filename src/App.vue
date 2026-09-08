<template>
    <div class="markdown-wiki">
        <aside class="wiki-sidebar">
            <div class="sidebar-header">
                <h1>Markdown Wiki</h1>
            </div>

            <div class="wiki-root">
                <div class="wiki-root-title">
                    Wiki Root
                </div>

                <div class="wiki-root-path">
                    {{ wikiRoot || 'Not configured' }}
                </div>
            </div>

            <div v-if="error" class="sidebar-error">
                <p class="error-message">
                    {{ error }}
                </p>
            </div>

            <div class="file-tree">
                <div class="tree-header">
                    Files
                </div>

                <div v-if="loadingTree" class="tree-status">
                    Loading...
                </div>

                <div v-else-if="!wikiRoot" class="tree-status">
                    Wiki Root is not configured.
                </div>

                <div v-else-if="tree.length === 0" class="tree-status">
                    No Markdown files found.
                </div>

                <ul v-else class="tree-list">
                    <TreeNode
                        v-for="node in tree"
                        :key="node.path"
                        :node="node"
                        :selected-file="selectedFile"
                        :expanded-folders="expandedFolders"
                        @toggle-folder="toggleFolder"
                        @open-file="openFile"
                    />
                </ul>
            </div>
        </aside>

        <main class="wiki-main">
            <div v-if="loadingFile" class="document-status">
                Loading document...
            </div>

            <div v-else-if="fileError" class="document-status">
                <p class="error-message">
                    {{ fileError }}
                </p>
            </div>

            <template v-else-if="selectedFile">
                <header class="document-header">
                    <div class="document-top">
                        <div class="document-navigation">
                            <button
                                v-if="parentFolder"
                                type="button"
                                class="button-vue"
                                @click="openFolder(parentFolder)"
                            >
                                Back
                            </button>
                        </div>

                        <div class="document-title">
                            <h2>{{ selectedFileName }}</h2>
                        </div>
                    </div>

                    <div class="breadcrumbs">
                        <button
                            type="button"
                            class="breadcrumb-button"
                            @click="openFolder(wikiRoot)"
                        >
                            {{ wikiRootName }}
                        </button>

                        <template
                            v-for="(crumb, index) in breadcrumbs"
                            :key="crumb.path"
                        >
                            <span class="breadcrumb-separator">
                                /
                            </span>

                            <button
                                type="button"
                                class="breadcrumb-button"
                                :class="{
                                    'breadcrumb-current':
                                        index === breadcrumbs.length - 1,
                                }"
                                @click="openFolder(crumb.path)"
                            >
                                {{ crumb.name }}
                            </button>
                        </template>
                    </div>

                    <div class="document-path">
                        {{ selectedFile }}
                    </div>
                </header>

                <article
                    class="wiki-markdown-content"
                    v-html="renderedMarkdown"
                    @click="handleMarkdownClick"
                ></article>
            </template>

            <template v-else-if="currentFolder">
                <header class="folder-header">
                    <div>
                        <div class="folder-title">
                            {{ currentFolderName }}
                        </div>

                        <div class="folder-subtitle">
                            {{ currentFolder }}
                        </div>
                    </div>

                    <button
                        v-if="parentFolder"
                        type="button"
                        class="button-vue"
                        @click="openFolder(parentFolder)"
                    >
                        Back
                    </button>
                </header>

                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"
                            />
                        </svg>
                    </div>

                    <h2>{{ currentFolderName }}</h2>

                    <p>
                        Select a Markdown file from the sidebar.
                    </p>
                </div>
            </template>

            <template v-else>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
                            />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="8" y1="13" x2="16" y2="13" />
                            <line x1="8" y1="17" x2="14" y2="17" />
                        </svg>
                    </div>

                    <h2>Select a Markdown file</h2>

                    <p>
                        Choose a file from the sidebar to view its contents.
                    </p>
                </div>
            </template>
        </main>
    </div>
</template>

<script setup>
import {
    computed,
    defineComponent,
    h,
    onMounted,
    ref,
} from 'vue'

import { marked } from 'marked'
import DOMPurify from 'dompurify'

const wikiRoot = ref('')
const tree = ref([])

const selectedFile = ref('')
const markdown = ref('')

const currentFolder = ref('')

const loadingTree = ref(false)
const loadingFile = ref(false)

const error = ref('')
const fileError = ref('')

const expandedFolders = ref(new Set())

function normalizePath(path) {
    if (!path) {
        return '/'
    }

    return '/' + path.split('/').filter(Boolean).join('/')
}

function getParentPath(path) {
    const normalized = normalizePath(path)

    const parts = normalized
        .split('/')
        .filter(Boolean)

    if (parts.length <= 1) {
        return '/'
    }

    parts.pop()

    return '/' + parts.join('/')
}

function getNameFromPath(path) {
    const parts = path
        .split('/')
        .filter(Boolean)

    return parts[parts.length - 1] || ''
}

function isExpanded(path) {
    return expandedFolders.value.has(
        normalizePath(path),
    )
}

function findNode(nodes, path) {
    const normalizedPath = normalizePath(path)

    for (const node of nodes) {
        if (
            normalizePath(node.path) ===
            normalizedPath
        ) {
            return node
        }

        if (
            node.type === 'folder' &&
            node.children.length > 0
        ) {
            const found = findNode(
                node.children,
                normalizedPath,
            )

            if (found) {
                return found
            }
        }
    }

    return null
}

function buildTree(items) {
    const folders = []
    const files = []

    for (const item of items) {
        const node = {
            name: item.name,
            type: item.type,
            path: normalizePath(item.path),
            children: [],
            loaded: false,
        }

        if (item.type === 'folder') {
            folders.push(node)
        } else {
            files.push(node)
        }
    }

    folders.sort((a, b) =>
        a.name.localeCompare(
            b.name,
            undefined,
            { sensitivity: 'base' },
        ),
    )

    files.sort((a, b) =>
        a.name.localeCompare(
            b.name,
            undefined,
            { sensitivity: 'base' },
        ),
    )

    return [
        ...folders,
        ...files,
    ]
}

async function fetchFolder(path) {
    const url = new URL(
        OC.generateUrl(
            '/apps/markdown_wiki/api/files',
        ),
        window.location.origin,
    )

    url.searchParams.set(
        'path',
        normalizePath(path),
    )

    const response = await fetch(url, {
        method: 'GET',
        headers: {
            Accept: 'application/json',
        },
    })

    const data = await response.json()

    if (!response.ok) {
        throw new Error(
            data.message ||
                'Failed to load folder.',
        )
    }

    return data.items || []
}

async function loadRootTree() {
    if (!wikiRoot.value) {
        return
    }

    loadingTree.value = true
    error.value = ''

    try {
        const items = await fetchFolder(
            wikiRoot.value,
        )

        tree.value = buildTree(items)

        expandedFolders.value = new Set([
            normalizePath(wikiRoot.value),
        ])

        currentFolder.value =
            normalizePath(wikiRoot.value)
    } catch (err) {
        error.value = err.message
        tree.value = []
    } finally {
        loadingTree.value = false
    }
}

async function loadFolderChildren(node) {
    if (node.loaded) {
        return
    }

    try {
        const items = await fetchFolder(
            node.path,
        )

        node.children = buildTree(items)
        node.loaded = true
    } catch (err) {
        error.value = err.message
    }
}

async function toggleFolder(node) {
    const path = normalizePath(node.path)

    if (isExpanded(path)) {
        const next = new Set(
            expandedFolders.value,
        )

        next.delete(path)

        expandedFolders.value = next

        currentFolder.value = path

        return
    }

    await loadFolderChildren(node)

    const next = new Set(
        expandedFolders.value,
    )

    next.add(path)

    expandedFolders.value = next

    currentFolder.value = path
}

async function openFolder(path) {
    const normalizedPath =
        normalizePath(path)

    selectedFile.value = ''
    markdown.value = ''
    fileError.value = ''

    currentFolder.value = normalizedPath

    if (
        normalizedPath ===
        normalizePath(wikiRoot.value)
    ) {
        const next = new Set(
            expandedFolders.value,
        )

        next.add(normalizedPath)

        expandedFolders.value = next

        return
    }

    const node = findNode(
        tree.value,
        normalizedPath,
    )

    if (
        node &&
        node.type === 'folder'
    ) {
        await loadFolderChildren(node)

        const next = new Set(
            expandedFolders.value,
        )

        next.add(normalizedPath)

        expandedFolders.value = next
    }
}

async function openFile(path) {
    const filePath =
        normalizePath(path)

    loadingFile.value = true
    fileError.value = ''

    selectedFile.value = filePath
    markdown.value = ''

    try {
        const url = new URL(
            OC.generateUrl(
                '/apps/markdown_wiki/api/file',
            ),
            window.location.origin,
        )

        url.searchParams.set(
            'path',
            filePath,
        )

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
            },
        })

        const data = await response.json()

        if (!response.ok) {
            throw new Error(
                data.message ||
                    'Failed to load Markdown file.',
            )
        }

        markdown.value =
            data.content || ''

        const parent =
            getParentPath(filePath)

        currentFolder.value = parent

        /*
         * Make sure every parent folder of the
         * selected file is expanded.
         */
        const root =
            normalizePath(
                wikiRoot.value,
            )

        const relative =
            filePath.slice(root.length)

        const parts =
            relative
                .split('/')
                .filter(Boolean)

        parts.pop()

        let currentPath = root

        const foldersToExpand = []

        for (const part of parts) {
            currentPath +=
                '/' + part

            foldersToExpand.push(
                currentPath,
            )
        }

        for (
            const folderPath
            of foldersToExpand
        ) {
            const folderNode =
                findNode(
                    tree.value,
                    folderPath,
                )

            if (
                folderNode &&
                folderNode.type ===
                    'folder'
            ) {
                await loadFolderChildren(
                    folderNode,
                )
            }
        }

        const next = new Set(
            expandedFolders.value,
        )

        next.add(root)

        for (
            const folderPath
            of foldersToExpand
        ) {
            next.add(folderPath)
        }

        expandedFolders.value = next
    } catch (err) {
        fileError.value =
            err.message
    } finally {
        loadingFile.value = false
    }
}

/*
 * Resolve relative Markdown links inside the Wiki.
 *
 * Example:
 *
 * Current file:
 * /MDs_Test/Test_Subfolder/Nested_Test.md
 *
 * Link:
 * ../Getting_Started.md
 *
 * Result:
 * /MDs_Test/Getting_Started.md
 *
 * Markdown links are opened through openFile()
 * instead of allowing the browser to navigate
 * away from the Wiki application.
 */
function handleMarkdownClick(event) {
    const link =
        event.target.closest('a')

    if (!link) {
        return
    }

    const href =
        link.getAttribute('href')

    if (!href) {
        return
    }

    /*
     * Leave external links, anchors and mail
     * links to the browser.
     */
    if (
        href.startsWith('http://') ||
        href.startsWith('https://') ||
        href.startsWith('#') ||
        href.startsWith('mailto:')
    ) {
        return
    }

    /*
     * Only intercept relative Markdown files.
     */
    if (
        !href.toLowerCase().endsWith('.md')
    ) {
        return
    }

    event.preventDefault()

    if (!selectedFile.value) {
        return
    }

    const currentDirectory =
        getParentPath(
            selectedFile.value,
        )

    /*
     * Start with the directory containing
     * the currently opened Markdown file.
     */
    const resolvedParts =
        currentDirectory
            .split('/')
            .filter(Boolean)

    /*
     * Resolve ./, ../ and normal path components.
     */
    const linkParts =
        href.split('/')

    for (const part of linkParts) {
        if (
            !part ||
            part === '.'
        ) {
            continue
        }

        if (part === '..') {
            if (resolvedParts.length > 0) {
                resolvedParts.pop()
            }

            continue
        }

        resolvedParts.push(part)
    }

    const resolvedPath =
        '/' +
        resolvedParts.join('/')

    const root =
        normalizePath(
            wikiRoot.value,
        )

    /*
     * Never allow a Markdown link to escape
     * the configured Wiki Root.
     */
    if (
        resolvedPath !== root &&
        !resolvedPath.startsWith(
            root + '/',
        )
    ) {
        return
    }

    openFile(resolvedPath)
}

async function loadWikiRoot() {
    error.value = ''

    try {
        const response = await fetch(
            OC.generateUrl(
                '/apps/markdown_wiki/api/wiki-root',
            ),
            {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                },
            },
        )

        const data =
            await response.json()

        if (!response.ok) {
            throw new Error(
                data.message ||
                    'Failed to load Wiki Root.',
            )
        }

        wikiRoot.value =
            data.wikiRoot || ''

        if (wikiRoot.value) {
            await loadRootTree()
        }
    } catch (err) {
        error.value =
            err.message
    }
}

const wikiRootName = computed(() => {
    if (!wikiRoot.value) {
        return 'Wiki Root'
    }

    return getNameFromPath(
        wikiRoot.value,
    )
})

const selectedFileName = computed(() => {
    return getNameFromPath(
        selectedFile.value,
    )
})

const currentFolderName = computed(() => {
    if (!currentFolder.value) {
        return wikiRootName.value
    }

    return getNameFromPath(
        currentFolder.value,
    )
})

const parentFolder = computed(() => {
    const path =
        selectedFile.value ||
        currentFolder.value

    if (!path) {
        return ''
    }

    const parent =
        getParentPath(path)

    const root =
        normalizePath(
            wikiRoot.value,
        )

    if (
        parent !== root &&
        !parent.startsWith(
            root + '/',
        )
    ) {
        return root
    }

    return parent
})

const breadcrumbs = computed(() => {
    if (
        !selectedFile.value ||
        !wikiRoot.value
    ) {
        return []
    }

    const root =
        normalizePath(
            wikiRoot.value,
        )

    const filePath =
        normalizePath(
            selectedFile.value,
        )

    const relative =
        filePath.slice(root.length)

    const parts =
        relative
            .split('/')
            .filter(Boolean)

    parts.pop()

    let path = root

    return parts.map((name) => {
        path += '/' + name

        return {
            name,
            path,
        }
    })
})

const renderedMarkdown = computed(() => {
    if (!markdown.value) {
        return ''
    }

    const html =
        marked.parse(
            markdown.value,
        )

    return DOMPurify.sanitize(
        html,
    )
})

const TreeNode = defineComponent({
    name: 'TreeNode',

    props: {
        node: {
            type: Object,
            required: true,
        },

        selectedFile: {
            type: String,
            default: '',
        },

        expandedFolders: {
            type: Object,
            required: true,
        },
    },

    emits: [
        'toggle-folder',
        'open-file',
    ],

    setup(props, { emit }) {
        return () => {
            const nodePath =
                normalizePath(
                    props.node.path,
                )

            const expanded =
                props.expandedFolders.has(
                    nodePath,
                )

            const selected =
                props.node.type ===
                    'file' &&
                normalizePath(
                    props.selectedFile,
                ) === nodePath

            const children =
                props.node.children || []

            return h(
                'li',
                {
                    class: 'tree-item',
                },
                [
                    h(
                        'button',
                        {
                            type: 'button',

                            class: [
                                'tree-button',
                                {
                                    'tree-button-active':
                                        selected,

                                    'tree-button-folder-active':
                                        props.node.type ===
                                            'folder' &&
                                        expanded,
                                },
                            ],

                            onClick: () => {
                                if (
                                    props.node
                                        .type ===
                                    'folder'
                                ) {
                                    emit(
                                        'toggle-folder',
                                        props.node,
                                    )
                                } else {
                                    emit(
                                        'open-file',
                                        props.node.path,
                                    )
                                }
                            },
                        },
                        [
                            h(
                                'span',
                                {
                                    class: 'tree-chevron',
                                },
                                props.node
                                    .type ===
                                    'folder'
                                    ? expanded
                                        ? '⌄'
                                        : '›'
                                    : '',
                            ),

                            h(
                                'span',
                                {
                                    class: 'tree-icon',
                                },
                                [
                                    props.node
                                        .type ===
                                        'folder'
                                        ? h(
                                              'svg',
                                              {
                                                  class: 'tree-folder-icon',
                                                  viewBox:
                                                      '0 0 24 24',
                                                  fill:
                                                      'none',
                                                  stroke:
                                                      'currentColor',
                                                  'stroke-width':
                                                      '2',
                                                  'stroke-linecap':
                                                      'round',
                                                  'stroke-linejoin':
                                                      'round',
                                              },
                                              [
                                                  h(
                                                      'path',
                                                      {
                                                          d: 'M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z',
                                                      },
                                                  ),
                                              ],
                                          )
                                        : h(
                                              'svg',
                                              {
                                                  class: 'tree-file-icon',
                                                  viewBox:
                                                      '0 0 24 24',
                                                  fill:
                                                      'none',
                                                  stroke:
                                                      'currentColor',
                                                  'stroke-width':
                                                      '2',
                                                  'stroke-linecap':
                                                      'round',
                                                  'stroke-linejoin':
                                                      'round',
                                              },
                                              [
                                                  h(
                                                      'path',
                                                      {
                                                          d: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z',
                                                      },
                                                  ),
                                                  h(
                                                      'polyline',
                                                      {
                                                          points:
                                                              '14 2 14 8 20 8',
                                                      },
                                                  ),
                                                  h(
                                                      'line',
                                                      {
                                                          x1: '8',
                                                          y1: '13',
                                                          x2: '16',
                                                          y2: '13',
                                                      },
                                                  ),
                                                  h(
                                                      'line',
                                                      {
                                                          x1: '8',
                                                          y1: '17',
                                                          x2: '14',
                                                          y2: '17',
                                                      },
                                                  ),
                                              ],
                                          ),
                                ],
                            ),

                            h(
                                'span',
                                {
                                    class: 'tree-name',
                                },
                                props.node.name,
                            ),
                        ],
                    ),

                    props.node.type ===
                        'folder' &&
                    expanded &&
                    children.length > 0
                        ? h(
                              'ul',
                              {
                                  class: 'tree-list',
                                  style: {
                                      paddingLeft:
                                          '18px',
                                  },
                              },
                              children.map(
                                  (child) =>
                                      h(
                                          TreeNode,
                                          {
                                              key: child.path,
                                              node: child,
                                              selectedFile:
                                                  props.selectedFile,
                                              expandedFolders:
                                                  props.expandedFolders,

                                              onToggleFolder:
                                                  (
                                                      childNode,
                                                  ) =>
                                                      emit(
                                                          'toggle-folder',
                                                          childNode,
                                                      ),

                                              onOpenFile:
                                                  (
                                                      filePath,
                                                  ) =>
                                                      emit(
                                                          'open-file',
                                                          filePath,
                                                      ),
                                          },
                                      ),
                              ),
                          )
                        : null,
                ],
            )
        }
    },
})

onMounted(() => {
    loadWikiRoot()
})
</script>