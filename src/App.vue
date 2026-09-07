<template>
    <div class="markdown-wiki">
        <aside class="wiki-sidebar">
            <div class="sidebar-header">
                <h1>Markdown Wiki</h1>

```
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
                Files
            </div>

            <p v-if="loadingFiles" class="tree-status">
                Loading...
            </p>

            <p v-else-if="filesError" class="error-message tree-status">
                {{ filesError }}
            </p>

            <p v-else-if="items.length === 0" class="tree-status">
                No Markdown files or folders found.
            </p>

            <ul v-else class="tree-list">
                <li
                    v-for="item in items"
                    :key="item.type + ':' + item.path"
                    class="tree-item"
                >
                    <button
                        v-if="item.type === 'folder'"
                        type="button"
                        class="tree-button"
                        @click="openFolder(item.path)"
                    >
                        <span class="tree-icon">📁</span>
                        <span class="tree-name">{{ item.name }}</span>
                    </button>

                    <button
                        v-else
                        type="button"
                        class="tree-button"
                        :class="{
                            'tree-button-active':
                                selectedFile?.path === item.path,
                        }"
                        @click="openFile(item.path)"
                    >
                        <span class="tree-icon">📄</span>
                        <span class="tree-name">{{ item.name }}</span>
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

            <div v-if="loadingFile" class="document-status">
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

                <NcButton
                    v-if="currentPath !== selectedPath"
                    type="tertiary"
                    :disabled="loadingFiles"
                    @click="goBack"
                >
                    ← Parent folder
                </NcButton>
            </header>

            <div class="empty-state">
                <div class="empty-state-icon">📄</div>

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
```

</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { NcButton } from '@nextcloud/vue'
import { marked } from 'marked'
import DOMPurify from 'dompurify'

const selectedPath = ref('')
const currentPath = ref('')
const items = ref([])

const selectedFile = ref(null)
const fileContent = ref('')

const loadingRoot = ref(false)
const loadingFiles = ref(false)
const loadingFile = ref(false)

const errorMessage = ref('')
const filesError = ref('')
const fileError = ref('')

const renderedMarkdown = computed(() => {
    const markdown = fileContent.value || ''
    const html = marked.parse(markdown)

    return DOMPurify.sanitize(html)
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

function normalizeRelativePath(path) {
    return path
        .replace(/^\/+/, '')
        .replace(/\/+$/, '')
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

    const baseDirectory = getCurrentDirectory(selectedFile.value.path)

    let resolvedPath

    if (cleanHref.startsWith('/')) {
        resolvedPath = normalizeNextcloudPath(cleanHref)
    } else {
        resolvedPath = normalizeNextcloudPath(
            `${baseDirectory}/${cleanHref}`,
        )
    }

    const normalizedRoot = normalizeNextcloudPath(selectedPath.value)

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

async function loadFiles(path = selectedPath.value) {
    if (!selectedPath.value) {
        return
    }

    loadingFiles.value = true
    filesError.value = ''

    try {
        const params = new URLSearchParams({
            path,
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

        items.value = data.items || []
        currentPath.value = normalizeNextcloudPath(data.path)
    } catch (error) {
        console.error(error)

        filesError.value = error.message || 'Unable to load files.'
        items.value = []
    } finally {
        loadingFiles.value = false
    }
}

async function openFolder(path) {
    selectedFile.value = null
    fileContent.value = ''
    fileError.value = ''

    await loadFiles(path)
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

async function goBack() {
    if (currentPath.value === selectedPath.value) {
        return
    }

    const normalized = normalizeNextcloudPath(currentPath.value)
    const lastSlash = normalized.lastIndexOf('/')

    const parentPath =
        lastSlash > 0
            ? normalized.substring(0, lastSlash)
            : selectedPath.value

    await loadFiles(parentPath)
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

        await loadFiles(selectedPath.value)
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
        await loadFiles(selectedPath.value)
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
    font-size: 13px;
    font-weight: 600;
}

.wiki-root-path {
    overflow: hidden;
    color: var(--color-text-maxcontrast);
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
    padding: 8px;
}

.tree-header {
    padding: 4px 8px 8px;
    color: var(--color-text-maxcontrast);
    font-size: 12px;
    font-weight: 600;
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
    min-height: 36px;
    padding: 6px 8px;
    border: 0;
    border-radius: var(--border-radius-element);
    background: transparent;
    color: var(--color-main-text);
    cursor: pointer;
    font: inherit;
    text-align: left;
}

.tree-button:hover {
    background: var(--color-background-hover);
}

.tree-button-active {
    background: var(--color-primary-element-light);
    font-weight: 600;
}

.tree-icon {
    flex: 0 0 auto;
    width: 24px;
}

.tree-name {
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
    font-size: 40px;
}

.error-message {
    color: var(--color-error);
}

/*
 * Markdown HTML is injected with v-html.
 * :deep() is required because these elements are
 * dynamically inserted into the DOM.
 */

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
