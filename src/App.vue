<template>
    <div class="markdown-wiki">
        <aside class="wiki-sidebar">
            <div class="sidebar-header">
                <h1>Markdown Wiki</h1>

                <NcButton
                    type="tertiary"
                    :disabled="loadingRoot"
                    @click="selectWikiRoot"
                >
                    {{ loadingRoot ? 'Loading...' : 'Change Wiki Root' }}
                </NcButton>
            </div>

            <div v-if="selectedPath" class="wiki-root">
                <div class="wiki-root-title">
                    Wiki Root
                </div>

                <div class="wiki-root-path" :title="selectedPath">
                    {{ selectedPath }}
                </div>
            </div>

            <div v-if="errorMessage" class="error-message sidebar-error">
                {{ errorMessage }}
            </div>

            <div v-if="selectedPath" class="file-tree">
                <div class="tree-header">
                    <span>Explorer</span>
                </div>

                <p
                    v-if="treeLoading[selectedPath]"
                    class="tree-status"
                >
                    Loading...
                </p>

                <p
                    v-else-if="treeErrors[selectedPath]"
                    class="error-message tree-status"
                >
                    {{ treeErrors[selectedPath] }}
                </p>

                <p
                    v-else-if="visibleTreeItems.length === 0"
                    class="tree-status"
                >
                    No Markdown files or folders found.
                </p>

                <ul v-else class="tree-list">
                    <li
                        v-for="item in visibleTreeItems"
                        :key="item.type + ':' + item.path"
                        class="tree-item"
                    >
                        <button
                            v-if="item.type === 'folder'"
                            type="button"
                            class="tree-button"
                            :class="{
                                'tree-button-folder-active':
                                    currentPath === item.path &&
                                    !selectedFile,
                            }"
                            :style="{
                                paddingLeft: `${6 + item.depth * 18}px`,
                            }"
                            @click="toggleFolder(item.path)"
                        >
                            <span class="tree-chevron">
                                {{
                                    expandedFolders.has(item.path)
                                        ? '⌄'
                                        : '›'
                                }}
                            </span>

                            <span class="tree-icon tree-folder-icon">
                                <svg
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M3 6.5A2.5 2.5 0 0 1 5.5 4H10l2 2h6.5A2.5 2.5 0 0 1 21 8.5v9a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5z"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </span>

                            <span
                                class="tree-name"
                                :title="item.name"
                            >
                                {{ item.name }}
                            </span>
                        </button>

                        <button
                            v-else
                            type="button"
                            class="tree-button"
                            :class="{
                                'tree-button-active':
                                    selectedFile?.path === item.path,
                            }"
                            :style="{
                                paddingLeft:
                                    `${6 + item.depth * 18 + 18}px`,
                            }"
                            @click="openFile(item.path)"
                        >
                            <span class="tree-icon tree-file-icon">
                                <svg
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M6 3.5h8l4 4v13H6z"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M14 3.5v4h4"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M9 12h6M9 15.5h6"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </span>

                            <span
                                class="tree-name"
                                :title="item.name"
                            >
                                {{ item.name }}
                            </span>
                        </button>
                    </li>
                </ul>
            </div>
        </aside>

        <main class="wiki-main">
            <template v-if="selectedFile">
                <header class="document-header">
                    <div class="document-navigation">
                        <NcButton
                            type="tertiary"
                            :disabled="loadingFile"
                            @click="closeFile"
                        >
                            ← Back
                        </NcButton>
                    </div>

                    <div class="document-title">
                        <h2>{{ selectedFile.name }}</h2>

                        <div class="document-path">
                            {{ selectedFile.path }}
                        </div>
                    </div>
                </header>

                <div
                    v-if="loadingFile"
                    class="document-status"
                >
                    Loading...
                </div>

                <div
                    v-else-if="fileError"
                    class="error-message document-status"
                >
                    {{ fileError }}
                </div>

                <article
                    v-else
                    class="markdown-content"
                    v-html="renderedMarkdown"
                    @click="handleMarkdownClick"
                ></article>
            </template>

            <template v-else-if="selectedPath">
                <header class="folder-header">
                    <div>
                        <div class="folder-title">
                            {{ currentPath }}
                        </div>

                        <div class="folder-subtitle">
                            Select a Markdown file from the sidebar.
                        </div>
                    </div>
                </header>

                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                d="M6 3.5h8l4 4v13H6z"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M14 3.5v4h4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>

                    <h2>No document selected</h2>

                    <p>
                        Select a Markdown file from the sidebar to open it.
                    </p>
                </div>
            </template>

            <div v-else class="empty-state">
                <h2>Markdown Wiki</h2>

                <p>
                    Select a Wiki Root folder to get started.
                </p>

                <NcButton
                    type="primary"
                    :disabled="loadingRoot"
                    @click="selectWikiRoot"
                >
                    {{ loadingRoot ? 'Loading...' : 'Select Wiki Root' }}
                </NcButton>
            </div>
        </main>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { NcButton } from '@nextcloud/vue'
import { marked } from 'marked'
import DOMPurify from 'dompurify'

const selectedPath = ref('')
const currentPath = ref('')

const selectedFile = ref(null)
const fileContent = ref('')

const loadingRoot = ref(false)
const loadingFile = ref(false)

const errorMessage = ref('')
const fileError = ref('')

const treeChildren = reactive({})
const treeLoading = reactive({})
const treeErrors = reactive({})

const expandedFolders = ref(new Set())

const renderedMarkdown = computed(() => {
    const markdown = fileContent.value || ''
    const html = marked.parse(markdown)

    return DOMPurify.sanitize(html)
})

const visibleTreeItems = computed(() => {
    const result = []

    function addChildren(path, depth) {
        const children = treeChildren[path] || []

        for (const item of children) {
            result.push({
                ...item,
                depth,
            })

            if (
                item.type === 'folder' &&
                expandedFolders.value.has(item.path)
            ) {
                addChildren(item.path, depth + 1)
            }
        }
    }

    if (selectedPath.value) {
        addChildren(selectedPath.value, 0)
    }

    return result
})

function getRequestHeaders() {
    return {
        requesttoken: OC.requestToken,
    }
}

function normalizeNextcloudPath(path) {
    if (!path) {
        return ''
    }

    return '/' + path.replace(/^\/+|\/+$/g, '')
}

function getCurrentDirectory(filePath) {
    const normalized = normalizeNextcloudPath(filePath)
    const lastSlash = normalized.lastIndexOf('/')

    if (lastSlash <= 0) {
        return selectedPath.value
    }

    return normalized.substring(0, lastSlash)
}

function resolveMarkdownLink(href) {
    if (!href) {
        return null
    }

    if (
        href.startsWith('#') ||
        href.startsWith('http://') ||
        href.startsWith('https://') ||
        href.startsWith('mailto:')
    ) {
        return null
    }

    const cleanHref = href.split('#')[0].split('?')[0]

    if (!cleanHref.toLowerCase().endsWith('.md')) {
        return null
    }

    const normalizedRoot = normalizeNextcloudPath(selectedPath.value)
    const baseDirectory = getCurrentDirectory(selectedFile.value.path)

    let pathToResolve

    if (cleanHref.startsWith('/')) {
        pathToResolve = cleanHref
    } else {
        pathToResolve = `${baseDirectory}/${cleanHref}`
    }

    const segments = pathToResolve.split('/')
    const resolvedSegments = []

    for (const segment of segments) {
        if (!segment || segment === '.') {
            continue
        }

        if (segment === '..') {
            if (resolvedSegments.length === 0) {
                return null
            }

            resolvedSegments.pop()
            continue
        }

        resolvedSegments.push(segment)
    }

    const resolvedPath = '/' + resolvedSegments.join('/')

    if (
        resolvedPath !== normalizedRoot &&
        !resolvedPath.startsWith(normalizedRoot + '/')
    ) {
        return null
    }

    return resolvedPath
}

function handleMarkdownClick(event) {
    const target = event.target

    if (!(target instanceof HTMLAnchorElement)) {
        return
    }

    const href = target.getAttribute('href')
    const resolvedPath = resolveMarkdownLink(href)

    if (!resolvedPath) {
        return
    }

    event.preventDefault()

    openFile(resolvedPath)
}

async function loadWikiRoot() {
    loadingRoot.value = true
    errorMessage.value = ''

    try {
        const response = await fetch(
            '/index.php/apps/markdown_wiki/api/wiki-root',
            {
                headers: getRequestHeaders(),
            },
        )

        if (!response.ok) {
            throw new Error('Failed to load Wiki Root.')
        }

        const data = await response.json()

        selectedPath.value = normalizeNextcloudPath(data.wikiRoot)
        currentPath.value = selectedPath.value
    } catch (error) {
        console.error(error)

        errorMessage.value = 'Unable to load Wiki Root.'
    } finally {
        loadingRoot.value = false
    }
}

async function loadTreeFolder(path, force = false) {
    const normalizedPath = normalizeNextcloudPath(path)

    if (!normalizedPath) {
        return
    }

    if (
        !force &&
        Object.prototype.hasOwnProperty.call(
            treeChildren,
            normalizedPath,
        )
    ) {
        return
    }

    treeLoading[normalizedPath] = true
    delete treeErrors[normalizedPath]

    try {
        const params = new URLSearchParams({
            path: normalizedPath,
        })

        const response = await fetch(
            `/index.php/apps/markdown_wiki/api/files?${params.toString()}`,
            {
                headers: getRequestHeaders(),
            },
        )

        if (!response.ok) {
            const data = await response.json().catch(() => null)

            throw new Error(
                data?.message || 'Failed to load files.',
            )
        }

        const data = await response.json()

        treeChildren[normalizedPath] = data.items || []
        currentPath.value = normalizeNextcloudPath(data.path)
    } catch (error) {
        console.error(error)

        treeErrors[normalizedPath] =
            error.message || 'Unable to load files.'
    } finally {
        treeLoading[normalizedPath] = false
    }
}

async function toggleFolder(path) {
    const normalizedPath = normalizeNextcloudPath(path)

    if (expandedFolders.value.has(normalizedPath)) {
        const next = new Set(expandedFolders.value)
        next.delete(normalizedPath)
        expandedFolders.value = next

        currentPath.value = normalizedPath

        return
    }

    await loadTreeFolder(normalizedPath)

    if (treeErrors[normalizedPath]) {
        return
    }

    const next = new Set(expandedFolders.value)
    next.add(normalizedPath)
    expandedFolders.value = next

    currentPath.value = normalizedPath
}

async function openFile(path) {
    loadingFile.value = true
    fileError.value = ''

    selectedFile.value = {
        name: path.split('/').pop(),
        path,
    }

    fileContent.value = ''

    try {
        const params = new URLSearchParams({
            path,
        })

        const response = await fetch(
            `/index.php/apps/markdown_wiki/api/file?${params.toString()}`,
            {
                headers: getRequestHeaders(),
            },
        )

        if (!response.ok) {
            const data = await response.json().catch(() => null)

            throw new Error(
                data?.message || 'Failed to load Markdown file.',
            )
        }

        const data = await response.json()

        selectedFile.value = {
            name: data.name,
            path: data.path,
        }

        fileContent.value = data.content || ''
    } catch (error) {
        console.error(error)

        fileError.value =
            error.message || 'Unable to load Markdown file.'
    } finally {
        loadingFile.value = false
    }
}

function closeFile() {
    selectedFile.value = null
    fileContent.value = ''
    fileError.value = ''
}

function clearTree() {
    for (const key of Object.keys(treeChildren)) {
        delete treeChildren[key]
    }

    for (const key of Object.keys(treeLoading)) {
        delete treeLoading[key]
    }

    for (const key of Object.keys(treeErrors)) {
        delete treeErrors[key]
    }

    expandedFolders.value = new Set()
}

async function saveWikiRoot(path) {
    loadingRoot.value = true
    errorMessage.value = ''

    try {
        const response = await fetch(
            '/index.php/apps/markdown_wiki/api/wiki-root',
            {
                method: 'POST',
                headers: {
                    ...getRequestHeaders(),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    wikiRoot: path,
                }),
            },
        )

        if (!response.ok) {
            const data = await response.json().catch(() => null)

            throw new Error(
                data?.message || 'Failed to save Wiki Root.',
            )
        }

        const data = await response.json()

        selectedPath.value = normalizeNextcloudPath(data.wikiRoot)
        currentPath.value = selectedPath.value

        selectedFile.value = null
        fileContent.value = ''
        fileError.value = ''

        clearTree()

        await loadTreeFolder(selectedPath.value)
    } catch (error) {
        console.error(error)

        errorMessage.value =
            error.message || 'Unable to save Wiki Root.'
    } finally {
        loadingRoot.value = false
    }
}

async function selectWikiRoot() {
    errorMessage.value = ''

    try {
        const picker = getFilePickerBuilder('Select Wiki Root')
            .setMultiSelect(false)
            .setType(1)
            .allowDirectories(true)
            .build()

        const result = await picker.pick()

        if (!result) {
            return
        }

        const selected = Array.isArray(result)
            ? result[0]
            : result

        const path = selected.path || selected

        if (!path) {
            return
        }

        await saveWikiRoot(path)
    } catch (error) {
        console.error(error)

        errorMessage.value = 'Unable to select Wiki Root.'
    }
}

onMounted(async () => {
    await loadWikiRoot()

    if (selectedPath.value) {
        await loadTreeFolder(selectedPath.value)
    }
})
</script>

<style scoped>
.markdown-wiki {
    display: flex;
    width: 100%;
    height: 100%;
    min-height: 0;
    overflow: hidden;
}

.wiki-sidebar {
    display: flex;
    flex-direction: column;
    width: 280px;
    min-width: 220px;
    max-width: 360px;
    height: 100%;
    min-height: 0;
    border-right: 1px solid var(--color-border);
    background: var(--color-background-dark);
}

.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--color-border);
}

.sidebar-header h1 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.wiki-root {
    padding: 12px 16px;
    border-bottom: 1px solid var(--color-border);
}

.wiki-root-title {
    margin-bottom: 4px;
    color: var(--color-text-maxcontrast);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.wiki-root-path {
    overflow: hidden;
    color: var(--color-main-text);
    font-size: 12px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sidebar-error {
    margin: 8px 12px;
}

.file-tree {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 8px 6px;
}

.tree-header {
    display: flex;
    align-items: center;
    min-height: 30px;
    padding: 4px 8px;
    color: var(--color-text-maxcontrast);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.tree-status {
    padding: 8px;
    color: var(--color-text-maxcontrast);
    font-size: 13px;
}

.tree-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.tree-item {
    margin: 1px 0;
}

.tree-button {
    display: flex;
    align-items: center;
    width: 100%;
    min-height: 32px;
    padding-top: 4px;
    padding-right: 8px;
    padding-bottom: 4px;
    border: 0;
    border-radius: var(--border-radius-element);
    background: transparent;
    color: var(--color-main-text);
    cursor: pointer;
    font: inherit;
    text-align: left;
    transition:
        background-color 80ms ease,
        color 80ms ease;
}

.tree-button:hover {
    background: var(--color-background-hover);
}

.tree-button-active {
    background: var(--color-primary-element-light);
    color: var(--color-main-text);
    font-weight: 600;
}

.tree-button-folder-active {
    background: var(--color-background-hover);
    font-weight: 600;
}

.tree-chevron {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    width: 18px;
    height: 22px;
    color: var(--color-text-maxcontrast);
    font-size: 18px;
    line-height: 1;
}

.tree-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    width: 24px;
    height: 22px;
    margin-right: 2px;
}

.tree-icon svg {
    width: 17px;
    height: 17px;
}

.tree-folder-icon {
    color: var(--color-primary-element);
}

.tree-file-icon {
    color: var(--color-text-maxcontrast);
}

.tree-name {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.wiki-main {
    flex: 1;
    min-width: 0;
    height: 100%;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
}

.document-header,
.folder-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 24px;
    border-bottom: 1px solid var(--color-border);
}

.document-title {
    min-width: 0;
}

.document-title h2 {
    margin: 0;
    overflow: hidden;
    font-size: 20px;
    font-weight: 600;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.document-path {
    margin-top: 3px;
    overflow: hidden;
    color: var(--color-text-maxcontrast);
    font-size: 12px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.document-navigation {
    flex: 0 0 auto;
}

.document-status {
    padding: 24px;
}

.folder-header {
    justify-content: space-between;
}

.folder-title {
    font-size: 20px;
    font-weight: 600;
}

.folder-subtitle {
    margin-top: 4px;
    color: var(--color-text-maxcontrast);
    font-size: 13px;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    padding: 40px;
    text-align: center;
}

.empty-state h2 {
    margin: 12px 0 4px;
}

.empty-state p {
    margin: 0 0 20px;
    color: var(--color-text-maxcontrast);
}

.empty-state-icon {
    display: flex;
    align-items: center;
    justify-content: center;
}

.empty-state-icon svg {
    width: 42px;
    height: 42px;
    color: var(--color-text-maxcontrast);
}

.error-message {
    color: var(--color-error);
}

.markdown-content {
    max-width: 900px;
    margin: 0 auto;
    padding: 32px 40px 64px;
    overflow-wrap: anywhere;
    word-break: break-word;
    line-height: 1.6;
}

.markdown-content :deep(h1),
.markdown-content :deep(h2),
.markdown-content :deep(h3),
.markdown-content :deep(h4),
.markdown-content :deep(h5),
.markdown-content :deep(h6) {
    margin-top: 1.5em;
    margin-bottom: 0.6em;
    line-height: 1.3;
}

.markdown-content :deep(h1) {
    font-size: 2em;
}

.markdown-content :deep(h2) {
    font-size: 1.6em;
}

.markdown-content :deep(h3) {
    font-size: 1.35em;
}

.markdown-content :deep(h4) {
    font-size: 1.15em;
}

.markdown-content :deep(h5),
.markdown-content :deep(h6) {
    font-size: 1em;
}

.markdown-content :deep(strong) {
    font-weight: 700;
}

.markdown-content :deep(em) {
    font-style: italic;
}

.markdown-content :deep(del) {
    text-decoration: line-through;
}

.markdown-content :deep(p) {
    margin: 0.8em 0;
}

.markdown-content :deep(a) {
    text-decoration: underline;
}

.markdown-content :deep(ul),
.markdown-content :deep(ol) {
    margin: 0.8em 0;
    padding-left: 2em;
}

.markdown-content :deep(li) {
    margin: 0.25em 0;
}

.markdown-content :deep(blockquote) {
    margin: 1em 0;
    padding: 0.5em 1em;
    border-left: 4px solid var(--color-border-dark);
}

.markdown-content :deep(blockquote p) {
    margin: 0.4em 0;
}

.markdown-content :deep(table) {
    width: 100%;
    max-width: 100%;
    margin: 1em 0;
    border-collapse: collapse;
}

.markdown-content :deep(th),
.markdown-content :deep(td) {
    padding: 8px 12px;
    border: 1px solid var(--color-border);
    text-align: left;
    vertical-align: top;
}

.markdown-content :deep(th) {
    font-weight: 700;
}

.markdown-content :deep(hr) {
    margin: 1.5em 0;
    border: 0;
    border-top: 1px solid var(--color-border);
}

.markdown-content :deep(code) {
    padding: 0.15em 0.35em;
    border-radius: 4px;
    background: var(--color-background-dark);
    font-family: monospace;
    font-size: 0.9em;
}

.markdown-content :deep(pre) {
    max-width: 100%;
    margin: 1em 0;
    padding: 12px 16px;
    overflow-x: auto;
    border-radius: 6px;
    background: var(--color-background-dark);
}

.markdown-content :deep(pre code) {
    padding: 0;
    background: transparent;
    white-space: pre;
}

.markdown-content :deep(img) {
    display: block;
    max-width: 100%;
    height: auto;
    margin: 1em 0;
}

.markdown-content :deep(iframe),
.markdown-content :deep(video),
.markdown-content :deep(object),
.markdown-content :deep(embed) {
    max-width: 100%;
}
</style>
