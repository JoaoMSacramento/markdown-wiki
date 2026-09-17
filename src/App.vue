<template>
    <div class="markdown-wiki">
        <Teleport to="body">
            <Transition name="wiki-operation-message">
                <div
                    v-if="operationMessage"
                    class="wiki-operation-message"
                    :class="{
                        'wiki-operation-message-success':
                            operationMessageType === 'success',
                        'wiki-operation-message-error':
                            operationMessageType === 'error',
                    }"
                    role="status"
                    aria-live="polite"
                >
                    <span class="wiki-operation-message-text">
                        {{ operationMessage }}
                    </span>

                    <button
                        type="button"
                        class="wiki-operation-message-close"
                        aria-label="Dismiss message"
                        title="Dismiss"
                        @click="closeOperationMessage"
                    >
                        ×
                    </button>
                </div>
            </Transition>
        </Teleport>

        <aside class="wiki-sidebar">
            <div class="sidebar-header">
                <h1>Markdown Wiki</h1>
            </div>

            <div
                ref="searchContainer"
                class="wiki-search"
            >
                <div class="wiki-search-input-wrapper">
                    <svg
                        class="wiki-search-icon"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <circle
                            cx="11"
                            cy="11"
                            r="7"
                        />
                        <path
                            d="m20 20-4-4"
                        />
                    </svg>

                    <input
                        v-model="searchQuery"
                        type="search"
                        class="wiki-search-input"
                        placeholder="Search..."
                        aria-label="Search Wiki"
                        @focus="handleSearchFocus"
                        @keydown.escape="clearSearch"
                    />

                    <button
                        v-if="searchQuery"
                        type="button"
                        class="wiki-search-clear"
                        aria-label="Clear search"
                        title="Clear search"
                        @click="clearSearch"
                    >
                        ×
                    </button>
                </div>

                <div
                    v-if="
                        searchQuery ||
                        searchHistoryVisible
                    "
                    class="wiki-search-results"
                >
                    <template v-if="searchQuery">
                        <div
                            v-if="searchLoading"
                            class="wiki-search-status"
                        >
                            Searching...
                        </div>

                        <div
                            v-else-if="searchError"
                            class="wiki-search-status wiki-search-error"
                        >
                            {{ searchError }}
                        </div>

                        <div
                            v-else-if="searchResults.length === 0"
                            class="wiki-search-status"
                        >
                            No results found.
                        </div>

                        <button
                            v-for="result in searchResults"
                            v-else
                            :key="result.path"
                            type="button"
                            class="wiki-search-result"
                            :title="result.path"
                            @click="openSearchResult(result)"
                        >
                            <span class="wiki-search-result-name">
                                {{ result.name }}
                            </span>

                            <span class="wiki-search-result-path">
                                {{ getSearchResultDirectory(result.path) }}
                            </span>

                            <span
                                v-if="
                                    result.matchType === 'content' &&
                                    result.context
                                "
                                class="wiki-search-result-context"
                            >
                                {{ result.context }}
                            </span>
                        </button>
                    </template>

                    <template v-else-if="searchHistoryVisible">
                        <div class="wiki-search-history-header">
                            <span>
                                Recent searches
                            </span>

                            <button
                                v-if="searchHistory.length"
                                type="button"
                                class="wiki-search-history-clear"
                                @click="clearSearchHistory"
                            >
                                Clear all
                            </button>
                        </div>

                        <div
                            v-if="searchHistory.length === 0"
                            class="wiki-search-status"
                        >
                            No recent searches.
                        </div>

                        <div
                            v-for="item in searchHistory"
                            :key="item"
                            class="wiki-search-history-item"
                        >
                            <button
                                type="button"
                                class="wiki-search-history-select"
                                :title="item"
                                @click="selectSearchHistory(item)"
                            >
                                <svg
                                    class="wiki-search-history-icon"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <circle
                                        cx="12"
                                        cy="12"
                                        r="9"
                                    />
                                    <path
                                        d="M12 7v5l3 2"
                                    />
                                </svg>

                                <span class="wiki-search-history-text">
                                    {{ item }}
                                </span>
                            </button>

                            <button
                                type="button"
                                class="wiki-search-history-remove"
                                aria-label="Remove search from history"
                                title="Remove"
                                @click.stop="
                                    removeSearchHistoryItem(item)
                                "
                            >
                                ×
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="wiki-root">
                <div class="wiki-root-title">
                    Wiki Root
                </div>

                <div class="wiki-root-location">
                    <svg
                        class="wiki-root-folder-icon"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path
                            d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"
                        />
                    </svg>

                    <span class="wiki-root-name">
                        {{ wikiRootName }}
                    </span>
                </div>

                <div
                    v-if="wikiRoot"
                    class="wiki-root-path"
                    :title="wikiRoot"
                >
                    {{ wikiRoot }}
                </div>

                <button
                    type="button"
                    class="wiki-root-button"
                    @click="chooseWikiRoot"
                >
                    {{ wikiRoot ? 'Change Wiki Root' : 'Choose Wiki Root' }}
                </button>
            </div>

            <div
                v-if="error"
                class="sidebar-error"
            >
                <p class="error-message">
                    {{ error }}
                </p>
            </div>

            <div class="file-tree-section">
                <div class="tree-header-row">
                    <div class="tree-header">
                        <span>Files</span>

                        <div
                            v-if="wikiRoot"
                            class="tree-create-wrapper"
                        >
                            <button
                                type="button"
                                class="tree-create-button"
                                aria-label="Create"
                                title="Create"
                                @click.stop="toggleCreateMenu"
                            >
                                +
                            </button>

                            <div
                                v-if="createMenuVisible"
                                class="tree-create-menu"
                                @click.stop
                            >
                                <button
                                    type="button"
                                    class="tree-create-menu-item"
                                    @click="openCreateDialog('file')"
                                >
                                    New Markdown file
                                </button>

                                <button
                                    type="button"
                                    class="tree-create-menu-item"
                                    @click="openCreateDialog('folder')"
                                >
                                    New folder
                                </button>

                                <button
                                    type="button"
                                    class="tree-create-menu-item"
                                    @click="openUploadFilePicker"
                                >
                                    Upload file
                                </button>

                                <button
                                    type="button"
                                    class="tree-create-menu-item"
                                    @click="openUploadFolderPicker"
                                >
                                    Upload folder
                                </button>

                                <input
                                    ref="uploadFileInput"
                                    type="file"
                                    multiple
                                    hidden
                                    @change="handleUploadFileSelection"
                                />

                                <input
                                    ref="uploadFolderInput"
                                    type="file"
                                    webkitdirectory
                                    directory
                                    multiple
                                    hidden
                                    @change="handleUploadFolderSelection"
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        class="tree-view-toggle"
                        role="group"
                        aria-label="File view"
                    >
                        <button
                            type="button"
                            class="tree-view-button"
                            :class="{
                                'tree-view-button-active':
                                    treeMode === 'markdown',
                            }"
                            :aria-pressed="
                                treeMode === 'markdown'
                            "
                            @click="setTreeMode('markdown')"
                        >
                            Markdown
                        </button>

                        <button
                            type="button"
                            class="tree-view-button"
                            :class="{
                                'tree-view-button-active':
                                    treeMode === 'all',
                            }"
                            :aria-pressed="
                                treeMode === 'all'
                            "
                            @click="setTreeMode('all')"
                        >
                            All files
                        </button>
                    </div>
                </div>

                <div
                    v-if="createError"
                    class="tree-status tree-create-error"
                >
                    {{ createError }}
                </div>

                <div
                    ref="fileTreeElement"
                    class="file-tree"
                    @dragover="handleExternalRootDragOver"
                    @drop="handleExternalRootDrop"
                >
                    <div
                        v-if="loadingTree"
                        class="tree-status"
                    >
                    Loading...
                </div>

                <div
                    v-else-if="!wikiRoot"
                    class="tree-status"
                >
                    Wiki Root is not configured.
                </div>

                <div
                    v-else-if="tree.length === 0"
                    class="tree-status"
                >
                    {{
                        treeMode === 'markdown'
                            ? 'No Markdown files found.'
                            : 'No files found.'
                    }}
                </div>

                <ul
                    v-else
                    class="tree-list"
                    :class="{
                        'tree-list-drop-target-valid':
                            normalizePath(treeDropTargetPath) ===
                                normalizePath(wikiRoot) &&
                            treeDropTargetValid,
                        'tree-list-drop-target-invalid':
                            normalizePath(treeDropTargetPath) ===
                                normalizePath(wikiRoot) &&
                            !treeDropTargetValid,
                    }"
                    :style="
                        normalizePath(treeDropTargetPath) ===
                        normalizePath(wikiRoot)
                            ? {
                                borderRadius:
                                    'var(--border-radius-element)',
                                outline: treeDropTargetValid
                                    ? '2px solid var(--color-primary-element)'
                                    : '2px solid var(--color-error)',
                                outlineOffset: '-2px',
                                background: treeDropTargetValid
                                    ? 'var(--color-primary-element-light)'
                                    : 'transparent',
                            }
                            : null
                    "
                    @dragover="handleTreeDragOver(
                        $event,
                        getWikiRootDropTarget(),
                    )"
                    @dragleave="handleTreeExternalDragLeave"
                    @drop="handleTreeDrop(
                        $event,
                        getWikiRootDropTarget(),
                    )"
                >
                    <TreeNode
                        v-for="node in tree"
                        :key="node.path"
                        :node="node"
                        :selected-file="selectedFile"
                        :selected-resource="selectedResource"
                        :expanded-folders="expandedFolders"
                        :drag-source-path="treeDragSourcePath"
                        :drop-target-path="treeDropTargetPath"
                        :drop-target-valid="treeDropTargetValid"
                        @toggle-folder="toggleFolder"
                        @open-file="openFile"
                        @select-resource="selectResource"
                        @context-menu="openContextMenu"
                        @drag-start="handleTreeDragStart"
                        @drag-end="handleTreeDragEnd"
                        @drag-over="handleTreeDragOver"
                        @drop="handleTreeDrop"
                    />
                </ul>
                </div>
            </div>
        </aside>

        <main class="wiki-main">
            <div
                v-if="!wikiRoot"
                class="wiki-empty-state wiki-setup-state"
            >
                <div class="wiki-empty-state-content">
                    <h2>Configure your Wiki</h2>

                    <p>
                        Choose a folder from your Nextcloud Files
                        to use as the root of your Markdown Wiki.
                    </p>

                    <button
                        type="button"
                        class="wiki-root-button"
                        @click="chooseWikiRoot"
                    >
                        Choose Wiki Root
                    </button>

                    <p
                        v-if="error"
                        class="error-message"
                    >
                        {{ error }}
                    </p>
                </div>
            </div>

            <div
                v-else-if="selectedFile"
                class="wiki-document"
            >
                <div class="document-toolbar">
                    <div class="document-toolbar-inner">
                        <div class="document-breadcrumbs">
                            <button
                                type="button"
                                class="breadcrumb-button"
                                @click="openFolder(wikiRoot)"
                            >
                                {{ wikiRootName }}
                            </button>

                            <template
                                v-for="breadcrumb in breadcrumbs"
                                :key="breadcrumb.path"
                            >
                                <span class="breadcrumb-separator">
                                    /
                                </span>

                                <button
                                    type="button"
                                    class="breadcrumb-button"
                                    @click="
                                        openFolder(
                                            breadcrumb.path,
                                        )
                                    "
                                >
                                    {{ breadcrumb.name }}
                                </button>
                            </template>

                            <span class="breadcrumb-separator">
                                /
                            </span>

                            <span
                                class="breadcrumb-current"
                                :title="selectedFileName"
                            >
                                {{ selectedFileName }}
                            </span>
                        </div>

                        <div class="document-actions">
                            <button
                                v-if="tableOfContents.length > 0"
                                type="button"
                                class="document-action-button document-toc-toggle"
                                :class="{
                                    'document-toc-toggle-active':
                                        tableOfContentsVisible,
                                }"
                                :aria-pressed="tableOfContentsVisible"
                                @click="
                                    tableOfContentsVisible =
                                        !tableOfContentsVisible
                                "
                            >
                                {{
                                    tableOfContentsVisible
                                        ? 'Hide contents'
                                        : 'Show contents'
                                }}
                            </button>
                            <template v-if="!editing">
                                <button
                                    type="button"
                                    class="document-action-button document-action-primary"
                                    :disabled="loadingFile"
                                    @click="startEditing"
                                >
                                    Edit
                                </button>
                            </template>

                            <template v-else>
                                <div
                                    class="document-mode-toggle"
                                    role="group"
                                    aria-label="Editor mode"
                                >
                                    <button
                                        type="button"
                                        class="document-mode-button"
                                        :class="{
                                            'document-mode-button-active':
                                                editorMode ===
                                                'markdown',
                                        }"
                                        :aria-pressed="
                                            editorMode ===
                                            'markdown'
                                        "
                                        @click="
                                            editorMode =
                                                'markdown'
                                        "
                                    >
                                        Markdown
                                    </button>

                                    <button
                                        type="button"
                                        class="document-mode-button"
                                        :class="{
                                            'document-mode-button-active':
                                                editorMode ===
                                                'split',
                                        }"
                                        :aria-pressed="
                                            editorMode ===
                                            'split'
                                        "
                                        @click="
                                            editorMode =
                                                'split'
                                        "
                                    >
                                        Split
                                    </button>

                                    <button
                                        type="button"
                                        class="document-mode-button"
                                        :class="{
                                            'document-mode-button-active':
                                                editorMode ===
                                                'preview',
                                        }"
                                        :aria-pressed="
                                            editorMode ===
                                            'preview'
                                        "
                                        @click="
                                            editorMode =
                                                'preview'
                                        "
                                    >
                                        Preview
                                    </button>
                                </div>

                                <span
                                    v-if="saveStatus"
                                    class="document-save-status"
                                    :class="{
                                        'document-save-status-saving':
                                            savingFile,
                                        'document-save-status-error':
                                            saveError,
                                        'document-save-status-unsaved':
                                            isDirty,
                                        'document-save-status-saved':
                                            !savingFile &&
                                            !saveError &&
                                            !isDirty,
                                    }"
                                >
                                    {{ saveStatus }}
                                </span>

                                <button
                                    type="button"
                                    class="document-action-button"
                                    :disabled="savingFile"
                                    @click="cancelEditing"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="button"
                                    class="document-action-button document-action-primary"
                                    :disabled="
                                        savingFile ||
                                        !isDirty
                                    "
                                    @click="saveFile"
                                >
                                    {{
                                        savingFile
                                            ? 'Saving...'
                                            : 'Save'
                                    }}
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div
                    ref="documentScrollElement"
                    class="wiki-document-scroll"
                    @scroll="updateActiveHeading"
                >
                    <div class="wiki-document-inner">
                        <div class="document-header">
                            <h2>
                                {{ selectedFileDisplayName }}
                            </h2>
                        </div>

                        <div
                            v-if="loadingFile"
                            class="document-status"
                        >
                            Loading...
                        </div>

                        <div
                            v-else-if="fileError"
                            class="document-status document-error"
                        >
                            {{ fileError }}
                        </div>

                        <div
                            v-else
                            class="document-body-layout"
                            :class="{
                                'document-body-layout-with-toc':
                                    showTableOfContents,
                            }"
                        >
                            <div class="document-body-main">
                                <template v-if="editing">
                                    <div
                                        v-if="
                                            editorMode !==
                                            'preview'
                                        "
                                        class="markdown-editor-toolbar"
                                role="toolbar"
                                aria-label="Markdown formatting"
                            >
                                <div class="markdown-editor-toolbar-group">
                                    <button
                                        type="button"
                                        class="markdown-editor-toolbar-button markdown-toolbar-button-emphasis"
                                        title="Bold"
                                        aria-label="Bold"
                                        @mousedown.prevent
                                        @click="
                                            applyInlineFormatting(
                                                '**',
                                                '**',
                                            )
                                        "
                                    >
                                        B
                                    </button>

                                    <button
                                        type="button"
                                        class="markdown-editor-toolbar-button markdown-toolbar-button-emphasis"
                                        title="Italic"
                                        aria-label="Italic"
                                        @mousedown.prevent
                                        @click="
                                            applyInlineFormatting(
                                                '*',
                                                '*',
                                            )
                                        "
                                    >
                                        I
                                    </button>

                                    <button
                                        type="button"
                                        class="markdown-editor-toolbar-button markdown-toolbar-button-emphasis"
                                        title="Strikethrough"
                                        aria-label="Strikethrough"
                                        @mousedown.prevent
                                        @click="
                                            applyInlineFormatting(
                                                '~~',
                                                '~~',
                                            )
                                        "
                                    >
                                        S
                                    </button>
                                </div>

                                <div class="markdown-editor-toolbar-separator" />

                                <div class="markdown-editor-toolbar-group">
                                    <button
                                        type="button"
                                        class="markdown-editor-toolbar-button markdown-toolbar-heading"
                                        title="Heading 1"
                                        aria-label="Heading 1"
                                        @mousedown.prevent
                                        @click="
                                            applyLinePrefix(
                                                '# ',
                                            )
                                        "
                                    >
                                        H1
                                    </button>

                                    <button
                                        type="button"
                                        class="markdown-editor-toolbar-button markdown-toolbar-heading"
                                        title="Heading 2"
                                        aria-label="Heading 2"
                                        @mousedown.prevent
                                        @click="
                                            applyLinePrefix(
                                                '## ',
                                            )
                                        "
                                    >
                                        H2
                                    </button>

                                    <button
                                        type="button"
                                        class="markdown-editor-toolbar-button markdown-toolbar-heading"
                                        title="Heading 3"
                                        aria-label="Heading 3"
                                        @mousedown.prevent
                                        @click="
                                            applyLinePrefix(
                                                '### ',
                                            )
                                        "
                                    >
                                        H3
                                    </button>
                                </div>

                                <div class="markdown-editor-toolbar-separator" />

                                <div class="markdown-editor-toolbar-group">
                                    <button
                                        type="button"
                                        class="markdown-editor-toolbar-button"
                                        title="Bullet list"
                                        aria-label="Bullet list"
                                        @mousedown.prevent
                                        @click="
                                            applyBulletList()
                                        "
                                    >
                                        • List
                                    </button>

                                    <button
                                        type="button"
                                        class="markdown-editor-toolbar-button"
                                        title="Ordered list"
                                        aria-label="Ordered list"
                                        @mousedown.prevent
                                        @click="
                                            applyOrderedList()
                                        "
                                    >
                                        1. List
                                    </button>

                                    <button
                                        type="button"
                                        class="markdown-editor-toolbar-button"
                                        title="Checklist"
                                        aria-label="Checklist"
                                        @mousedown.prevent
                                        @click="
                                            applyChecklist()
                                        "
                                    >
                                        ☑ List
                                    </button>

                                    <button
                                        type="button"
                                        class="markdown-editor-toolbar-button"
                                        title="Blockquote"
                                        aria-label="Blockquote"
                                        @mousedown.prevent
                                        @click="
                                            applyBlockquote()
                                        "
                                    >
                                        Quote
                                    </button>
                                </div>

                                <div class="markdown-editor-toolbar-separator" />

                                <div class="markdown-editor-toolbar-group">
                                    <button
                                        type="button"
                                        class="markdown-editor-toolbar-button"
                                        title="Insert link"
                                        aria-label="Insert link"
                                        @mousedown.prevent
                                        @click="
                                            insertLink()
                                        "
                                    >
                                        Link
                                    </button>

                                    <button
                                        type="button"
                                        class="markdown-editor-toolbar-button"
                                        title="Insert image"
                                        aria-label="Insert image"
                                        @mousedown.prevent
                                        @click="
                                            insertImage()
                                        "
                                    >
                                        Image
                                    </button>
                                </div>

                                <div class="markdown-editor-toolbar-separator" />

                                <div class="markdown-editor-toolbar-group">
                                    <button
                                        type="button"
                                        class="markdown-editor-toolbar-button markdown-toolbar-code"
                                        title="Inline code"
                                        aria-label="Inline code"
                                        @mousedown.prevent
                                        @click="
                                            applyInlineFormatting(
                                                '`',
                                                '`',
                                            )
                                        "
                                    >
                                        &lt;/&gt;
                                    </button>

                                    <select
                                        v-model="selectedCodeLanguage"
                                        class="markdown-editor-toolbar-select"
                                        title="Code block language"
                                        aria-label="Code block language"
                                        @mousedown.stop
                                    >
                                        <option
                                            v-for="language in codeLanguages"
                                            :key="language.value"
                                            :value="language.value"
                                        >
                                            {{ language.label }}
                                        </option>
                                    </select>

                                    <button
                                        type="button"
                                        class="markdown-editor-toolbar-button markdown-toolbar-code"
                                        title="Code block"
                                        aria-label="Code block"
                                        @mousedown.prevent
                                        @click="
                                            applyCodeBlock()
                                        "
                                    >
                                        Code
                                    </button>

                                    <div
                                        class="markdown-toolbar-diagram-wrapper"
                                        @click.stop
                                    >
                                        <button
                                            type="button"
                                            class="markdown-editor-toolbar-button"
                                            title="Insert Mermaid diagram"
                                            aria-label="Insert Mermaid diagram"
                                            :aria-expanded="diagramMenuVisible"
                                            @mousedown.prevent
                                            @click="
                                                diagramMenuVisible =
                                                    !diagramMenuVisible
                                            "
                                        >
                                            Diagram ▾
                                        </button>
                                    
                                        <div
                                            v-if="diagramMenuVisible"
                                            class="markdown-toolbar-diagram-menu"
                                        >
                                            <button
                                                v-for="diagram in diagramTemplates"
                                                :key="diagram.type"
                                                type="button"
                                                class="markdown-toolbar-diagram-item"
                                                @mousedown.prevent
                                                @click="
                                                    insertMermaidDiagram(
                                                        diagram.type,
                                                    )
                                                "
                                            >
                                                {{ diagram.label }}
                                            </button>
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        class="markdown-editor-toolbar-button"
                                        title="Horizontal rule"
                                        aria-label="Horizontal rule"
                                        @mousedown.prevent
                                        @click="
                                            insertHorizontalRule()
                                        "
                                    >
                                        ―
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="
                                    editorMode ===
                                    'markdown'
                                "
                                class="editor-container"
                            >
                                <textarea
                                    ref="editorTextarea"
                                    v-model="editedMarkdown"
                                    class="markdown-editor"
                                    spellcheck="false"
                                    aria-label="Markdown editor"
                                    @keydown="
                                        handleEditorKeydown
                                    "
                                />
                            </div>

                            <div
                                v-else-if="
                                    editorMode ===
                                    'split'
                                "
                                class="editor-split"
                            >
                                <div
                                    class="editor-panel editor-source-panel"
                                >
                                    <div
                                        class="editor-panel-header"
                                    >
                                        <span>
                                            Markdown
                                        </span>
                                    </div>

                                    <textarea
                                        ref="editorTextarea"
                                        v-model="editedMarkdown"
                                        class="markdown-editor"
                                        spellcheck="false"
                                        aria-label="Markdown editor"
                                        @keydown="
                                            handleEditorKeydown
                                        "
                                    />
                                </div>

                                <div
                                    class="editor-panel editor-preview-panel"
                                >
                                    <div
                                        class="editor-panel-header"
                                    >
                                        <span>
                                            Preview
                                        </span>
                                    </div>

                                    <article
                                        class="markdown-content editor-preview-content"
                                        v-html="
                                            renderedEditorMarkdown
                                        "
                                    />
                                </div>
                            </div>

                                    <div
                                        v-else
                                        class="editor-preview-only"
                                    >
                                        <article
                                            class="markdown-content"
                                            @click="handleMarkdownClick"
                                            v-html="
                                                renderedEditorMarkdown
                                            "
                                        />
                                    </div>
                                </template>

                                <article
                                    v-else
                                    class="markdown-content"
                                    @click="handleMarkdownClick"
                                    v-html="renderedMarkdown"
                                />

                                <nav
                                    v-if="
                                        !editing &&
                                        (
                                            previousMarkdownPath ||
                                            nextMarkdownPath
                                        )
                                    "
                                    class="document-page-navigation"
                                    aria-label="Page navigation"
                                >
                                    <button
                                        v-if="previousMarkdownPath"
                                        type="button"
                                        class="document-page-navigation-button document-page-navigation-previous"
                                        :title="
                                            `Previous: ${previousMarkdownDisplayName}`
                                        "
                                        @click="
                                            navigateToAdjacentFile(
                                                previousMarkdownPath,
                                            )
                                        "
                                    >
                                        <span
                                            class="document-page-navigation-direction"
                                        >
                                            ← Previous
                                        </span>
                                        <span
                                            class="document-page-navigation-name"
                                        >
                                            {{ previousMarkdownDisplayName }}
                                        </span>
                                    </button>

                                    <span
                                        v-else
                                        class="document-page-navigation-spacer"
                                        aria-hidden="true"
                                    />

                                    <button
                                        v-if="nextMarkdownPath"
                                        type="button"
                                        class="document-page-navigation-button document-page-navigation-next"
                                        :title="
                                            `Next: ${nextMarkdownDisplayName}`
                                        "
                                        @click="
                                            navigateToAdjacentFile(
                                                nextMarkdownPath,
                                            )
                                        "
                                    >
                                        <span
                                            class="document-page-navigation-direction"
                                        >
                                            Next →
                                        </span>
                                        <span
                                            class="document-page-navigation-name"
                                        >
                                            {{ nextMarkdownDisplayName }}
                                        </span>
                                    </button>
                                </nav>
                            </div>

                            <aside
                                v-if="showTableOfContents"
                                class="wiki-toc"
                                aria-label="Table of contents"
                            >
                                <div class="wiki-toc-inner">
                                    <div class="wiki-toc-title">
                                        On this page
                                    </div>

                                    <nav class="wiki-toc-nav">
                                        <button
                                            v-for="heading in tableOfContents"
                                            :key="heading.id"
                                            type="button"
                                            class="wiki-toc-link"
                                            :class="[
                                                `wiki-toc-level-${heading.level}`,
                                                {
                                                    'wiki-toc-link-active':
                                                        activeHeadingId ===
                                                        heading.id,
                                                },
                                            ]"
                                            :title="heading.text"
                                            @click="
                                                scrollToHeading(
                                                    heading.id,
                                                )
                                            "
                                        >
                                            {{ heading.text }}
                                        </button>
                                    </nav>
                                </div>
                            </aside>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else-if="selectedResource"
                class="wiki-empty-state wiki-resource-state"
            >
                <div class="wiki-empty-state-content">
                    <div class="resource-icon">
                        <ResourceIcon
                            :file-type="
                                selectedResource.fileType
                            "
                        />
                    </div>

                    <h2>
                        {{ selectedResource.name }}
                    </h2>

                    <p class="resource-type">
                        {{
                            getFileTypeLabel(
                                selectedResource.fileType,
                            )
                        }}
                    </p>

                    <div class="resource-path">
                        <span class="resource-path-label">
                            Relative path
                        </span>

                        <code>
                            {{
                                getResourceRelativePath(
                                    selectedResource.path,
                                )
                            }}
                        </code>
                    </div>

                    <button
                        type="button"
                        class="wiki-folder-button"
                        @click="
                            copyResourceRelativePath(
                                selectedResource.path,
                            )
                        "
                    >
                        {{
                            copiedResourcePath
                                ? 'Copied'
                                : 'Copy relative path'
                        }}
                    </button>

                    <p
                        v-if="copyError"
                        class="error-message"
                    >
                        {{ copyError }}
                    </p>
                </div>
            </div>

            <div
                v-else
                class="wiki-folder-view"
                @dragover="handleExternalFolderViewDragOver"
                @drop="handleExternalFolderViewDrop"
            >
                <div class="folder-view-toolbar">
                    <div class="document-breadcrumbs">
                        <button
                            type="button"
                            class="breadcrumb-button"
                            @click="openFolder(wikiRoot)"
                        >
                            {{ wikiRootName }}
                        </button>

                        <template
                            v-for="breadcrumb in folderBreadcrumbs"
                            :key="breadcrumb.path"
                        >
                            <span class="breadcrumb-separator">
                                /
                            </span>

                            <button
                                type="button"
                                class="breadcrumb-button"
                                @click="openFolder(breadcrumb.path)"
                            >
                                {{ breadcrumb.name }}
                            </button>
                        </template>
                    </div>
                </div>

                <div class="folder-view-scroll">
                    <div class="folder-view-content">
                        <div class="folder-view-heading">
                            <div>
                                <h2>{{ currentFolderName }}</h2>
                                <p>
                                    {{ currentFolderItems.length }}
                                    {{
                                        currentFolderItems.length === 1
                                            ? 'item'
                                            : 'items'
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="currentFolderLoading"
                            class="folder-view-empty"
                        >
                            Loading folder…
                        </div>

                        <div
                            v-else-if="currentFolderItems.length === 0"
                            class="folder-view-empty"
                        >
                            {{
                                treeMode === 'markdown'
                                    ? 'No Markdown files or folders in this folder.'
                                    : 'This folder is empty.'
                            }}
                        </div>

                        <div
                            v-else
                            class="folder-view-list"
                        >
                            <button
                                v-for="item in currentFolderItems"
                                :key="item.path"
                                type="button"
                                class="folder-view-item"
                                @click="openFolderViewItem(item)"
                                @contextmenu="
                                    openContextMenu(
                                        $event,
                                        item,
                                    )
                                "
                            >
                                <span
                                    class="folder-view-item-icon"
                                    aria-hidden="true"
                                >
                                    <svg
                                        v-if="item.type === 'folder'"
                                        class="folder-view-folder-icon"
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

                                    <ResourceIcon
                                        v-else
                                        :file-type="item.fileType"
                                    />
                                </span>

                                <span class="folder-view-item-main">
                                    <span class="folder-view-item-name">
                                        {{ item.name }}
                                    </span>

                                    <span class="folder-view-item-type">
                                        {{
                                            item.type === 'folder'
                                                ? 'Folder'
                                                : item.fileType === 'markdown'
                                                    ? 'Markdown'
                                                    : 'File'
                                        }}
                                    </span>
                                </span>

                                <span
                                    class="folder-view-item-arrow"
                                    aria-hidden="true"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="m9 18 6-6-6-6" />
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!--
         * Create dialog.
         -->
        <div
            v-if="createDialogVisible"
            class="create-dialog-backdrop"
            @click.self="closeCreateDialog"
        >
            <div
                class="create-dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="create-dialog-title"
                tabindex="-1"
                @keydown.esc="closeCreateDialog"
            >
                <div class="create-dialog-header">
                    <h2 id="create-dialog-title">
                        {{ createDialogTitle }}
                    </h2>

                    <button
                        type="button"
                        class="create-dialog-close"
                        aria-label="Close"
                        title="Close"
                        :disabled="createDialogSubmitting"
                        @click="closeCreateDialog"
                    >
                        ×
                    </button>
                </div>

                <div class="create-dialog-body">
                    <label
                        for="create-dialog-name"
                        class="create-dialog-label"
                    >
                        {{ createDialogNameLabel }}
                    </label>

                    <input
                        id="create-dialog-name"
                        ref="createDialogNameInput"
                        v-model="createDialogName"
                        type="text"
                        class="create-dialog-input"
                        autocomplete="off"
                        :placeholder="
                            createDialogType === 'folder'
                                ? 'Folder name'
                                : 'File name'
                        "
                        @keydown.enter.prevent="submitCreateDialog"
                    />

                    <div class="create-dialog-label">
                        Create in
                    </div>

                    <div class="create-location-tree">
                        <div
                            class="create-location-row"
                            :class="{
                                'create-location-row-selected':
                                    normalizePath(
                                        createDialogFolder,
                                    ) ===
                                    normalizePath(
                                        wikiRoot,
                                    ),
                            }"
                        >
                            <span
                                class="create-location-toggle create-location-toggle-placeholder"
                            />

                            <button
                                type="button"
                                class="create-location-select"
                                @click="
                                    selectCreateDialogFolder(
                                        wikiRoot,
                                    )
                                "
                            >
                                <svg
                                    class="create-location-icon"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"
                                    />
                                </svg>

                                <span class="create-location-name">
                                    {{ wikiRootName }}
                                </span>
                            </button>
                        </div>

                        <ul
                            v-if="createDialogFolders.length > 0"
                            class="create-location-list"
                        >
                            <CreateLocationNode
                                v-for="node in createDialogFolders"
                                :key="node.path"
                                :node="node"
                                :selected-path="createDialogFolder"
                                :expanded-folders="
                                    createDialogExpandedFolders
                                "
                                @toggle-folder="
                                    toggleCreateDialogFolder
                                "
                                @select-folder="
                                    selectCreateDialogFolder
                                "
                            />
                        </ul>

                        <div
                            v-else
                            class="create-location-empty"
                        >
                            No subfolders.
                        </div>
                    </div>

                    <div
                        v-if="createDialogError"
                        class="create-dialog-error"
                    >
                        {{ createDialogError }}
                    </div>
                </div>

                <div class="create-dialog-footer">
                    <button
                        type="button"
                        class="create-dialog-button create-dialog-button-secondary"
                        :disabled="createDialogSubmitting"
                        @click="closeCreateDialog"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="create-dialog-button create-dialog-button-primary"
                        :disabled="
                            createDialogSubmitting ||
                            !createDialogName.trim()
                        "
                        @click="submitCreateDialog"
                    >
                        {{
                            createDialogSubmitting
                                ? 'Creating...'
                                : 'Create'
                        }}
                    </button>
                </div>
            </div>
        </div>

        <!--
         * Rename dialog.
         -->
        <Teleport to="body">
            <div
                v-if="renameDialogVisible"
                class="create-dialog-backdrop wiki-modal-backdrop"
                @click.self="closeRenameDialog"
            >
                <div
                    class="create-dialog"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="rename-dialog-title"
                    tabindex="-1"
                    @keydown.esc="closeRenameDialog"
                >
                    <div class="create-dialog-header">
                        <h2
                            id="rename-dialog-title"
                            class="create-dialog-title"
                        >
                            Rename
                        </h2>

                        <button
                            type="button"
                            class="create-dialog-close"
                            aria-label="Close"
                            title="Close"
                            :disabled="renameDialogSubmitting"
                            @click="closeRenameDialog"
                        >
                            ×
                        </button>
                    </div>

                    <div class="create-dialog-body">
                        <label
                            for="rename-dialog-name"
                            class="create-dialog-label"
                        >
                            New name
                        </label>

                        <input
                            id="rename-dialog-name"
                            ref="renameDialogInput"
                            v-model="renameDialogName"
                            type="text"
                            class="create-dialog-input"
                            autocomplete="off"
                            :disabled="renameDialogSubmitting"
                            @keydown.enter.prevent="submitRenameDialog"
                        />

                        <div
                            v-if="renameDialogError"
                            class="create-dialog-error"
                        >
                            {{ renameDialogError }}
                        </div>
                    </div>

                    <div class="create-dialog-footer">
                        <button
                            type="button"
                            class="create-dialog-button create-dialog-button-secondary"
                            :disabled="renameDialogSubmitting"
                            @click="closeRenameDialog"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="create-dialog-button create-dialog-button-primary"
                            :disabled="
                                renameDialogSubmitting ||
                                !renameDialogName.trim()
                            "
                            @click="submitRenameDialog"
                        >
                            {{
                                renameDialogSubmitting
                                    ? 'Renaming...'
                                    : 'Rename'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
        <div
            v-if="moveDialogVisible"
            class="create-dialog-backdrop wiki-modal-backdrop"
            @click.self="closeMoveDialog"
        >
            <div
                class="create-dialog" ref="moveDialogElement"
                role="dialog"
                aria-modal="true"
                aria-labelledby="move-dialog-title"
                tabindex="-1"
                @keydown.esc="closeMoveDialog"
            >
                <div class="create-dialog-header">
                    <h2 id="move-dialog-title">
                        Move
                    </h2>

                    <button
                        type="button"
                        class="create-dialog-close"
                        aria-label="Close"
                        title="Close"
                        :disabled="moveDialogSubmitting"
                        @click="closeMoveDialog"
                    >
                        ×
                    </button>
                </div>

                <div class="create-dialog-body">
                    <p class="create-dialog-label">{{ moveDialogSource?.name }}</p>
                    <div class="create-dialog-label">
                        Move to
                    </div>

                    <div class="create-location-tree" :inert="moveDialogSubmitting || moveDialogLoading">
                        <div
                            class="create-location-row"
                            :class="{
                                'create-location-row-selected':
                                    normalizePath(
                                        moveDialogFolder,
                                    ) ===
                                    normalizePath(
                                        wikiRoot,
                                    ),
                            }"
                        >
                            <span
                                class="create-location-toggle create-location-toggle-placeholder"
                            />

                            <button
                                type="button"
                                class="create-location-select"
                                @click="
                                    selectMoveDialogFolder(
                                        wikiRoot,
                                    )
                                "
                            >
                                <svg
                                    class="create-location-icon"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"
                                    />
                                </svg>

                                <span class="create-location-name">
                                    {{ wikiRootName }}
                                </span>
                            </button>
                        </div>

                        <ul
                            v-if="moveDialogFolders.length > 0"
                            class="create-location-list"
                        >
                            <CreateLocationNode
                                v-for="node in moveDialogFolders"
                                :key="node.path"
                                :node="node"
                                :selected-path="moveDialogFolder"
                                :expanded-folders="
                                    moveDialogExpandedFolders
                                "
                                @toggle-folder="
                                    toggleMoveDialogFolder
                                "
                                @select-folder="
                                    selectMoveDialogFolder
                                "
                            />
                        </ul>

                        <div
                            v-else
                            class="create-location-empty"
                        >
                            No subfolders.
                        </div>
                    </div>

                    <div
                        v-if="moveDialogError"
                        class="create-dialog-error"
                    >
                        {{ moveDialogError }}
                    </div>
                </div>

                <div class="create-dialog-footer">
                    <button
                        type="button"
                        class="create-dialog-button create-dialog-button-secondary"
                        :disabled="moveDialogSubmitting"
                        @click="closeMoveDialog"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="create-dialog-button create-dialog-button-primary"
                        :disabled="
                            moveDialogSubmitting ||
                            !moveDialogFolder || moveDialogLoading || !moveDialogSource
                        "
                        @click="submitMoveDialog"
                    >
                        {{
                            moveDialogSubmitting
                                ? 'Moving...'
                                : 'Move'
                        }}
                    </button>
                </div>
            </div>
        </div>

        </Teleport>

        <!--
         * Delete dialog.
         -->
        <Teleport to="body">
            <div
                v-if="deleteDialogVisible"
                class="create-dialog-backdrop wiki-modal-backdrop"
                @click.self="closeDeleteDialog"
            >
                <div
                    class="create-dialog delete-dialog"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="delete-dialog-title"
                    tabindex="-1"
                    @keydown.esc="closeDeleteDialog"
                >
                    <div class="create-dialog-header">
                        <h2
                            id="delete-dialog-title"
                            class="create-dialog-title"
                        >
                            Delete
                        </h2>

                        <button
                            type="button"
                            class="create-dialog-close"
                            aria-label="Close"
                            title="Close"
                            :disabled="deleteDialogSubmitting"
                            @click="closeDeleteDialog"
                        >
                            ×
                        </button>
                    </div>

                    <div class="create-dialog-body delete-dialog-body">
                        <p class="delete-dialog-description">
                            Are you sure you want to delete
                            <strong>
                                {{ deleteDialogSource?.name }}
                            </strong>?
                        </p>

                        <p class="delete-dialog-warning">
                            This action cannot be undone.
                        </p>

                        <div
                            v-if="deleteDialogError"
                            class="create-dialog-error"
                        >
                            {{ deleteDialogError }}
                        </div>
                    </div>

                    <div class="create-dialog-footer">
                        <button
                            type="button"
                            class="create-dialog-button create-dialog-button-secondary"
                            :disabled="deleteDialogSubmitting"
                            @click="closeDeleteDialog"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="create-dialog-button create-dialog-button-danger"
                            :disabled="
                                deleteDialogSubmitting ||
                                !deleteDialogSource
                            "
                            @click="submitDeleteDialog"
                        >
                            {{
                                deleteDialogSubmitting
                                    ? 'Deleting...'
                                    : 'Delete'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!--
         * Unsaved changes dialog.
         -->
        <Teleport to="body">
            <div
                v-if="unsavedChangesDialogVisible"
                class="create-dialog-backdrop wiki-modal-backdrop"
                @click.self="resolveUnsavedChanges(false)"
            >
                <div
                    ref="unsavedChangesDialogElement"
                    class="create-dialog delete-dialog"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="unsaved-changes-dialog-title"
                    tabindex="-1"
                    @keydown.esc="resolveUnsavedChanges(false)"
                >
                    <div class="create-dialog-header">
                        <h2
                            id="unsaved-changes-dialog-title"
                            class="create-dialog-title"
                        >
                            Unsaved changes
                        </h2>

                        <button
                            type="button"
                            class="create-dialog-close"
                            aria-label="Close"
                            title="Close"
                            @click="resolveUnsavedChanges(false)"
                        >
                            ×
                        </button>
                    </div>

                    <div class="create-dialog-body delete-dialog-body">
                        <p class="delete-dialog-description">
                            You have unsaved changes. Do you want to discard them?
                        </p>

                        <p class="delete-dialog-warning">
                            Your unsaved changes will be lost.
                        </p>
                    </div>

                    <div class="create-dialog-footer">
                        <button
                            type="button"
                            class="create-dialog-button create-dialog-button-secondary"
                            @click="resolveUnsavedChanges(false)"
                        >
                            Keep editing
                        </button>

                        <button
                            type="button"
                            class="create-dialog-button create-dialog-button-danger"
                            @click="resolveUnsavedChanges(true)"
                        >
                            Discard changes
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!--
        * File tree context menu.
        -->
        <div
            v-if="contextMenuVisible"
            ref="contextMenuElement"
            class="tree-context-menu"
            :style="contextMenuStyle"
            role="menu"
            @click.stop
            @contextmenu.prevent.stop
        >
            <!-- Folder -->
            <template v-if="contextMenuType === 'folder'">
                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('open')"
                >
                    Open
                </button>

                <div
                    class="tree-context-menu-separator"
                    role="separator"
                />

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('new-file')"
                >
                    New Markdown file
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('new-folder')"
                >
                    New folder
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('upload-file')"
                >
                    Upload file
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('upload-folder')"
                >
                    Upload folder
                </button>

                <div
                    class="tree-context-menu-separator"
                    role="separator"
                />

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('rename')"
                >
                    Rename
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('copy-path')"
                >
                    Copy path
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('move')"
                >
                    Move
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('duplicate')"
                >
                    Duplicate
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('download')"
                >
                    Download
                </button>

                <div
                    class="tree-context-menu-separator"
                    role="separator"
                />

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('properties')"
                >
                    Properties
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item tree-context-menu-item-danger"
                    role="menuitem"
                    @click="handleContextMenuAction('delete')"
                >
                    Delete
                </button>
            </template>

            <!-- Markdown file -->
            <template v-else-if="contextMenuType === 'markdown'">
                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('open')"
                >
                    Open
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('edit')"
                >
                    Open in editor
                </button>

                <div
                    class="tree-context-menu-separator"
                    role="separator"
                />

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('rename')"
                >
                    Rename
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('copy-path')"
                >
                    Copy path
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('copy-markdown-link')"
                >
                    Copy Markdown link
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('move')"
                >
                    Move
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('duplicate')"
                >
                    Duplicate
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('download')"
                >
                    Download
                </button>

                <div
                    class="tree-context-menu-separator"
                    role="separator"
                />

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('properties')"
                >
                    Properties
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item tree-context-menu-item-danger"
                    role="menuitem"
                    @click="handleContextMenuAction('delete')"
                >
                    Delete
                </button>
            </template>

            <!-- Other files -->
            <template v-else>
                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('open')"
                >
                    Open
                </button>

                <div
                    class="tree-context-menu-separator"
                    role="separator"
                />

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('rename')"
                >
                    Rename
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('copy-path')"
                >
                    Copy path
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('move')"
                >
                    Move
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('duplicate')"
                >
                    Duplicate
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('download')"
                >
                    Download
                </button>

                <div
                    class="tree-context-menu-separator"
                    role="separator"
                />

                <button
                    type="button"
                    class="tree-context-menu-item"
                    role="menuitem"
                    @click="handleContextMenuAction('properties')"
                >
                    Properties
                </button>

                <button
                    type="button"
                    class="tree-context-menu-item tree-context-menu-item-danger"
                    role="menuitem"
                    @click="handleContextMenuAction('delete')"
                >
                    Delete
                </button>
            </template>
        </div>
    </div>

    <!-- Render outside the app layout so overflow cannot clip the dialog. -->
    <Teleport to="body">
    <!-- Properties dialog -->
    <div
        v-if="propertiesDialogVisible"
        class="wiki-modal-backdrop"
        @click.self="closePropertiesDialog"
    >
        <div
            class="wiki-properties-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="properties-dialog-title"
        >
            <div class="wiki-properties-header">
                <h2 id="properties-dialog-title">
                    Properties
                </h2>

                <button
                    type="button"
                    class="wiki-properties-close"
                    aria-label="Close"
                    title="Close"
                    @click="closePropertiesDialog"
                >
                    ×
                </button>
            </div>

            <div class="wiki-properties-body">
                <div
                    v-if="propertiesDialogLoading"
                    class="wiki-properties-status"
                >
                    Loading properties...
                </div>

                <div
                    v-else-if="propertiesDialogError"
                    class="wiki-properties-status wiki-properties-error"
                >
                    {{ propertiesDialogError }}
                </div>

                <div
                    v-else-if="propertiesDialogData"
                    class="wiki-properties-list"
                >
                    <div class="wiki-properties-row">
                        <span class="wiki-properties-label">Name</span>
                        <span
                            class="wiki-properties-value"
                            :title="propertiesDialogData.name"
                        >
                            {{ propertiesDialogData.name }}
                        </span>
                    </div>

                    <div class="wiki-properties-row">
                        <span class="wiki-properties-label">Type</span>
                        <span class="wiki-properties-value">
                            {{ propertiesDialogData.type }}
                        </span>
                    </div>

                    <div
                        v-if="propertiesDialogData.extension"
                        class="wiki-properties-row"
                    >
                        <span class="wiki-properties-label">Extension</span>
                        <span class="wiki-properties-value">
                            {{ propertiesDialogData.extension }}
                        </span>
                    </div>

                    <div class="wiki-properties-row">
                        <span class="wiki-properties-label">Path</span>
                        <span
                            class="wiki-properties-value wiki-properties-path"
                            :title="propertiesDialogData.path"
                        >
                            {{ propertiesDialogData.path }}
                        </span>
                    </div>

                    <div
                        v-if="propertiesDialogData.type !== 'Folder'"
                        class="wiki-properties-row"
                    >
                        <span class="wiki-properties-label">Size</span>
                        <span class="wiki-properties-value">
                            {{
                                propertiesDialogData.size === null
                                    ? 'Unknown'
                                    : formatFileSize(
                                        propertiesDialogData.size,
                                    )
                            }}
                        </span>
                    </div>

                    <template
                        v-if="propertiesDialogData.type === 'Folder'"
                    >
                        <div class="wiki-properties-row">
                            <span class="wiki-properties-label">Items</span>
                            <span class="wiki-properties-value">
                                {{ propertiesDialogData.items }}
                            </span>
                        </div>

                        <div class="wiki-properties-row">
                            <span class="wiki-properties-label">Folders</span>
                            <span class="wiki-properties-value">
                                {{ propertiesDialogData.folders }}
                            </span>
                        </div>

                        <div class="wiki-properties-row">
                            <span class="wiki-properties-label">Files</span>
                            <span class="wiki-properties-value">
                                {{ propertiesDialogData.files }}
                            </span>
                        </div>

                        <div class="wiki-properties-row">
                            <span class="wiki-properties-label">Total size</span>
                            <span class="wiki-properties-value">
                                {{
                                    formatFileSize(
                                        propertiesDialogData.totalSize,
                                    )
                                }}
                            </span>
                        </div>
                    </template>

                    <div
                        v-if="propertiesDialogData.mimeType"
                        class="wiki-properties-row"
                    >
                        <span class="wiki-properties-label">MIME type</span>
                        <span class="wiki-properties-value">
                            {{ propertiesDialogData.mimeType }}
                        </span>
                    </div>

                    <div
                        v-if="
                            propertiesDialogData.modified ||
                            propertiesDialogData.latestModified
                        "
                        class="wiki-properties-row"
                    >
                        <span class="wiki-properties-label">
                            {{
                                propertiesDialogData.type === 'Folder'
                                    ? 'Latest modified'
                                    : 'Modified'
                            }}
                        </span>
                        <span class="wiki-properties-value">
                            {{
                                formatPropertiesDate(
                                    propertiesDialogData.modified ||
                                        propertiesDialogData.latestModified,
                                )
                            }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="wiki-properties-footer">
                <button
                    type="button"
                    class="wiki-properties-button"
                    @click="closePropertiesDialog"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
    </Teleport>
</template>

<script setup>
    import {
        computed,
        defineComponent,
        h,
        nextTick,
        onBeforeUnmount,
        onMounted,
        ref,
        watch,
    } from 'vue'

    import { marked } from 'marked'
    import DOMPurify from 'dompurify'
    import hljs from 'highlight.js/lib/common'
    import mermaid from 'mermaid'

    import {
        getFilePickerBuilder,
    } from '@nextcloud/dialogs'

    import '@nextcloud/dialogs/style.css'

    const wikiRoot = ref('')
    const tree = ref([])

    const selectedFile = ref('')
    const selectedResource = ref(null)

    const previousMarkdownPath = ref('')
    const nextMarkdownPath = ref('')

    const markdown = ref('')
    const editedMarkdown = ref('')

    const currentFolder = ref('')

    const loadingTree = ref(false)
    const loadingFile = ref(false)
    const savingFile = ref(false)

    const error = ref('')
    const fileError = ref('')
    const saveError = ref('')

    const createError = ref('')
    const createMenuVisible = ref(false)

    const uploadFileInput = ref(null)
    const uploadFolderInput = ref(null)
    const uploadingResources = ref(false)

    /*
     * Create dialog state.
     */
    const createDialogVisible = ref(false)
    const createDialogType = ref('file')
    const createDialogName = ref('')
    const createDialogFolder = ref('')
    const createDialogError = ref('')
    const createDialogSubmitting = ref(false)
    const createDialogExpandedFolders = ref(new Set())
    const createDialogNameInput = ref(null)

    const copyError = ref('')
    const copiedResourcePath = ref(false)

    /*
     * App-level operation message.
     */
    const operationMessage = ref('')
    const operationMessageType = ref('success')
    let operationMessageTimeout = null

    function showOperationMessage(
        message,
        type = 'success',
    ) {
        operationMessage.value = message
        operationMessageType.value = type

        if (operationMessageTimeout !== null) {
            window.clearTimeout(
                operationMessageTimeout,
            )
        }

        operationMessageTimeout =
            window.setTimeout(() => {
                operationMessage.value = ''
                operationMessageTimeout = null
            }, 4000)
    }

    function closeOperationMessage() {
        operationMessage.value = ''

        if (operationMessageTimeout !== null) {
            window.clearTimeout(
                operationMessageTimeout,
            )
            operationMessageTimeout = null
        }
    }

    const expandedFolders = ref(new Set())

    /*
     * Context menu state.
     */
    const contextMenuVisible = ref(false)
    const contextMenuType = ref('')
    const contextMenuPath = ref('')
    const contextMenuNode = ref(null)
    const contextMenuX = ref(0)
    const contextMenuY = ref(0)
    const contextMenuElement = ref(null)

    /*
     * Properties dialog state.
     */
    const propertiesDialogVisible = ref(false)
    const propertiesDialogLoading = ref(false)
    const propertiesDialogError = ref('')
    const propertiesDialogData = ref(null)

    /*
     * Editor state.
     */
    const editing = ref(false)
    const editorMode = ref('split')
    const editorTextarea = ref(null)
    const diagramMenuVisible = ref(false)

    const diagramTemplates = [
        {
            type: 'flowchart',
            label: 'Flowchart',
        },
        {
            type: 'sequence',
            label: 'Sequence diagram',
        },
        {
            type: 'class',
            label: 'Class diagram',
        },
        {
            type: 'state',
            label: 'State diagram',
        },
    ]

    /*
     * Table of contents state.
     */
    const documentScrollElement = ref(null)
    const activeHeadingId = ref('')
    const tableOfContentsVisible = ref(true)

    /*
     * Search state.
     */
    const searchQuery = ref('')
    const searchResults = ref([])
    const searchLoading = ref(false)
    const searchError = ref('')

    const searchHistory = ref([])
    const searchHistoryVisible = ref(false)
    const searchContainer = ref(null)
    const fileTreeElement = ref(null)

    const searchHistoryStorageKey =
        'markdown_wiki_search_history'

    const maxSearchHistory = 10

    let searchTimeout = null
    let searchRequestId = 0

    /*
     * Code block language.
     */
    const selectedCodeLanguage = ref('')

    const codeLanguages = [
        {
            value: '',
            label: 'Plain text',
        },
        {
            value: 'bash',
            label: 'Bash',
        },
        {
            value: 'c',
            label: 'C',
        },
        {
            value: 'cpp',
            label: 'C++',
        },
        {
            value: 'csharp',
            label: 'C#',
        },
        {
            value: 'css',
            label: 'CSS',
        },
        {
            value: 'go',
            label: 'Go',
        },
        {
            value: 'html',
            label: 'HTML',
        },
        {
            value: 'java',
            label: 'Java',
        },
        {
            value: 'javascript',
            label: 'JavaScript',
        },
        {
            value: 'json',
            label: 'JSON',
        },
        {
            value: 'markdown',
            label: 'Markdown',
        },
        {
            value: 'php',
            label: 'PHP',
        },
        {
            value: 'python',
            label: 'Python',
        },
        {
            value: 'rust',
            label: 'Rust',
        },
        {
            value: 'sql',
            label: 'SQL',
        },
        {
            value: 'typescript',
            label: 'TypeScript',
        },
    ]

    /*
     * File tree mode.
     */
    const treeMode = ref('markdown')

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

    function getDisplayFileName(path) {
        const name = getNameFromPath(path)

        if (name.toLowerCase().endsWith('.md')) {
            return name.slice(0, -3)
        }

        return name
    }

    function getFileTypeLabel(fileType) {
        const labels = {
            markdown: 'Markdown document',
            image: 'Image',
            pdf: 'PDF document',
            text: 'Text file',
            code: 'Code file',
            archive: 'Archive',
            file: 'File',
        }

        return labels[fileType] || labels.file
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
                fileType:
                    item.fileType ||
                    (
                        item.type === 'folder'
                            ? 'folder'
                            : 'file'
                    ),
                extension:
                    item.extension || '',
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

        if (treeMode.value === 'all') {
            url.searchParams.set(
                'showAll',
                'true',
            )
        }

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

    async function loadRootTree(
        resetState = true,
    ) {
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

            const root =
                normalizePath(
                    wikiRoot.value,
                )

            if (resetState) {
                expandedFolders.value =
                    new Set([root])

                currentFolder.value = root
            } else {
                const next =
                    new Set(
                        expandedFolders.value,
                    )

                next.add(root)

                expandedFolders.value = next
            }
        } catch (err) {
            error.value = err.message
            tree.value = []
        } finally {
            loadingTree.value = false
        }
    }

    async function loadFolderChildren(
        node,
        force = false,
    ) {
        if (
            node.loaded &&
            !force
        ) {
            return
        }

        const items = await fetchFolder(
            node.path,
        )

        node.children = buildTree(items)
        node.loaded = true
    }

    async function restoreExpandedFolders(
        paths,
    ) {
        if (!wikiRoot.value) {
            return
        }

        const root =
            normalizePath(
                wikiRoot.value,
            )

        const sortedPaths =
            [...paths]
                .filter(
                    (path) =>
                        normalizePath(path) !==
                        root,
                )
                .sort(
                    (a, b) =>
                        normalizePath(a)
                            .split('/')
                            .filter(Boolean)
                            .length -
                        normalizePath(b)
                            .split('/')
                            .filter(Boolean)
                            .length,
                )

        const next =
            new Set([root])

        for (const path of sortedPaths) {
            const normalizedPath =
                normalizePath(path)

            if (
                normalizedPath === root ||
                !normalizedPath.startsWith(
                    root + '/',
                )
            ) {
                continue
            }

            const node =
                findNode(
                    tree.value,
                    normalizedPath,
                )

            if (
                node &&
                node.type === 'folder'
            ) {
                await loadFolderChildren(node)
                next.add(normalizedPath)
            }
        }

        expandedFolders.value = next
    }

    async function setTreeMode(mode) {
        if (
            mode !== 'markdown' &&
            mode !== 'all'
        ) {
            return
        }

        if (treeMode.value === mode) {
            return
        }

        const previousExpanded =
            [...expandedFolders.value]

        const previousFolder =
            currentFolder.value

        /*
         * Changing Markdown / All files rebuilds the tree. Preserve the
         * user's exact vertical and horizontal position across that
         * rebuild instead of letting the new tree jump back to the top.
         */
        const previousTreeScroll =
            getFileTreeScrollPosition()

        treeMode.value = mode

        await loadRootTree(false)

        await restoreExpandedFolders(
            previousExpanded,
        )

        const normalizedFolder =
            normalizePath(
                previousFolder ||
                    wikiRoot.value,
            )

        if (
            normalizedFolder ===
            normalizePath(wikiRoot.value)
        ) {
            currentFolder.value =
                normalizePath(
                    wikiRoot.value,
                )

            await restoreFileTreeScrollPosition(
                previousTreeScroll,
            )

            return
        }

        const folderNode =
            findNode(
                tree.value,
                normalizedFolder,
            )

        if (
            folderNode &&
            folderNode.type === 'folder'
        ) {
            await loadFolderChildren(
                folderNode,
            )

            currentFolder.value =
                normalizedFolder
        } else {
            currentFolder.value =
                normalizePath(
                    wikiRoot.value,
                )
        }

        await restoreFileTreeScrollPosition(
            previousTreeScroll,
        )
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

        /*
         * When a deeply nested folder is opened, keep its row fully
         * visible inside the tree. This only moves the tree by the
         * minimum amount required and leaves it untouched when the
         * folder is already completely visible.
         */
        await scrollExpandedFolderIntoView(path)
    }

    /*
     * Context menu.
     */
    const contextMenuStyle = computed(() => ({
        left: `${contextMenuX.value}px`,
        top: `${contextMenuY.value}px`,
    }))

    function getContextMenuType(node) {
        if (node.type === 'folder') {
            return 'folder'
        }

        if (node.fileType === 'markdown') {
            return 'markdown'
        }

        return 'file'
    }

    async function openContextMenu(event, node) {
        event.preventDefault()
        event.stopPropagation()

        closeCreateMenu()

        contextMenuNode.value = node
        contextMenuPath.value =
            normalizePath(node.path)
        contextMenuType.value =
            getContextMenuType(node)

        contextMenuX.value = event.clientX
        contextMenuY.value = event.clientY
        contextMenuVisible.value = true

        await nextTick()

        positionContextMenu()
    }

    function positionContextMenu() {
        const menu =
            contextMenuElement.value

        if (!menu) {
            return
        }

        const padding = 8

        const menuWidth = menu.offsetWidth
        const menuHeight = menu.offsetHeight

        const viewportWidth =
            window.innerWidth

        const viewportHeight =
            window.innerHeight

        let x = contextMenuX.value
        let y = contextMenuY.value

        if (
            x + menuWidth >
            viewportWidth - padding
        ) {
            x =
                viewportWidth -
                menuWidth -
                padding
        }

        if (
            y + menuHeight >
            viewportHeight - padding
        ) {
            y =
                viewportHeight -
                menuHeight -
                padding
        }

        x = Math.max(
            padding,
            x,
        )

        y = Math.max(
            padding,
            y,
        )

        contextMenuX.value = x
        contextMenuY.value = y
    }

    function closeContextMenu() {
        contextMenuVisible.value = false
        contextMenuType.value = ''
        contextMenuPath.value = ''
        contextMenuNode.value = null
    }

    function clearContextSelection() {
        selectedFile.value = ''
        selectedResource.value = null
        previousMarkdownPath.value = ''
        nextMarkdownPath.value = ''
        markdown.value = ''
        editedMarkdown.value = ''
        fileError.value = ''
        saveError.value = ''
        copyError.value = ''
        copiedResourcePath.value = false
        editing.value = false
    }

    function findNodeByPath(nodes, path) {
        const normalizedPath =
            normalizePath(path)

        for (const node of nodes || []) {
            if (
                normalizePath(node.path) ===
                normalizedPath
            ) {
                return node
            }

            if (
                node.type === 'folder' &&
                Array.isArray(node.children) &&
                node.children.length
            ) {
                const found =
                    findNodeByPath(
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

    async function ensureTreePathExpanded(path) {
        const normalizedPath =
            normalizePath(path)

        const rootPath =
            normalizePath(wikiRoot.value)

        if (
            !normalizedPath ||
            !rootPath ||
            (
                normalizedPath !== rootPath &&
                !normalizedPath.startsWith(
                    rootPath + '/',
                )
            )
        ) {
            return
        }

        const relativePath =
            normalizedPath === rootPath
                ? ''
                : normalizedPath.slice(
                    rootPath.length + 1,
                )

        const parts =
            relativePath
                .split('/')
                .filter(Boolean)

        if (parts.length <= 1) {
            return
        }

        const next =
            new Set(expandedFolders.value)

        let currentPath =
            rootPath

        /*
         * Expand every ancestor, but not the created item itself.
         * Loading each ancestor ensures deeply nested targets exist
         * in the rendered tree before we try to scroll to them.
         */
        for (
            let index = 0;
            index < parts.length - 1;
            index++
        ) {
            currentPath =
                normalizePath(
                    `${currentPath}/${parts[index]}`,
                )

            next.add(currentPath)
            expandedFolders.value =
                new Set(next)

            const node =
                findNodeByPath(
                    tree.value,
                    currentPath,
                )

            if (
                node &&
                node.type === 'folder' &&
                !node.loaded
            ) {
                await loadFolderChildren(node)
            }
        }

        expandedFolders.value =
            new Set(next)

        await nextTick()
    }

    function getTreeElementByPath(path) {
        const container =
            fileTreeElement.value

        if (!container) {
            return null
        }

        const normalizedPath =
            normalizePath(path)

        const item = [
            ...container.querySelectorAll(
                '[data-tree-path]',
            ),
        ].find(
            (element) =>
                element.dataset.treePath ===
                normalizedPath,
        )

        if (!item) {
            return null
        }

        return (
            item.querySelector(
                ':scope > .tree-button',
            ) || item
        )
    }

    async function scrollExpandedFolderIntoView(path) {
        /*
         * Opening a folder can reveal descendants that are wider than
         * the folder row itself. Wait for the subtree to finish
         * rendering, then reveal the furthest-right visible name.
         */
        await nextTick()

        await new Promise((resolve) => {
            window.requestAnimationFrame(() => {
                window.requestAnimationFrame(resolve)
            })
        })

        const container =
            fileTreeElement.value

        const folderElement =
            getTreeElementByPath(path)

        if (!container || !folderElement) {
            return
        }

        const reveal = () => {
            const currentFolder =
                getTreeElementByPath(path)

            if (!currentFolder) {
                return
            }

            const containerRect =
                container.getBoundingClientRect()

            const folderRect =
                currentFolder.getBoundingClientRect()

            /*
             * Vertically keep the folder that was explicitly opened
             * visible, without jumping to one of its descendants.
             */
            if (
                folderRect.top <
                containerRect.top
            ) {
                container.scrollTop -=
                    containerRect.top -
                    folderRect.top
            } else if (
                folderRect.bottom >
                containerRect.bottom
            ) {
                container.scrollTop +=
                    folderRect.bottom -
                    containerRect.bottom
            }

            const names = [
                ...currentFolder.querySelectorAll(
                    '.tree-name',
                ),
            ]

            let rightmostRect = null

            for (const name of names) {
                const rect =
                    name.getBoundingClientRect()

                if (
                    !rightmostRect ||
                    rect.right >
                        rightmostRect.right
                ) {
                    rightmostRect = rect
                }
            }

            if (!rightmostRect) {
                const ownName =
                    currentFolder.querySelector(
                        ':scope > .tree-button .tree-name',
                    )

                rightmostRect =
                    ownName
                        ? ownName.getBoundingClientRect()
                        : folderRect
            }

            const horizontalMargin = 12

            const visibleRight =
                containerRect.right -
                horizontalMargin

            if (
                rightmostRect.right >
                visibleRight
            ) {
                container.scrollLeft +=
                    rightmostRect.right -
                    visibleRight
            }
        }

        reveal()

        /*
         * Recheck once the horizontal scrollbar has reacted to the
         * newly visible subtree width.
         */
        await new Promise((resolve) => {
            window.requestAnimationFrame(resolve)
        })

        reveal()
    }

    async function scrollTreePathIntoView(
        path,
        options = {},
    ) {
        const {
            centerVertically = false,
        } = options
        /*
         * Wait until folder expansion and intrinsic tree widths have
         * reached their final layout before measuring.
         */
        await nextTick()

        await new Promise((resolve) => {
            window.requestAnimationFrame(() => {
                window.requestAnimationFrame(resolve)
            })
        })

        const container =
            fileTreeElement.value

        if (!container) {
            return
        }

        const reveal = () => {
            const element =
                getTreeElementByPath(path)

            if (!element) {
                return
            }

            const containerRect =
                container.getBoundingClientRect()

            const elementRect =
                element.getBoundingClientRect()

            /*
             * Vertical:
             * - normal tree navigation only reveals the node;
             * - search results are placed around the centre of the
             *   visible tree, making the selected result much easier
             *   to locate in a large tree.
             */
            if (centerVertically) {
                const elementCenter =
                    elementRect.top +
                    elementRect.height / 2

                const containerCenter =
                    containerRect.top +
                    containerRect.height / 2

                container.scrollTop +=
                    elementCenter -
                    containerCenter
            } else if (
                elementRect.top <
                containerRect.top
            ) {
                container.scrollTop -=
                    containerRect.top -
                    elementRect.top
            } else if (
                elementRect.bottom >
                containerRect.bottom
            ) {
                container.scrollTop +=
                    elementRect.bottom -
                    containerRect.bottom
            }

            const row =
                element.querySelector(
                    ':scope > .tree-button',
                ) ||
                element.querySelector(
                    '.tree-button',
                ) ||
                element

            const name =
                row.querySelector(
                    '.tree-name',
                )

            const rowRect =
                row.getBoundingClientRect()

            const nameRect =
                name
                    ? name.getBoundingClientRect()
                    : rowRect

            const horizontalMargin = 12

            const visibleLeft =
                containerRect.left +
                horizontalMargin

            const visibleRight =
                containerRect.right -
                horizontalMargin

            /*
             * The right edge must be based on the real filename. This
             * guarantees that a long name is fully visible, not merely
             * the LI or button box.
             */
            if (
                nameRect.right >
                visibleRight
            ) {
                container.scrollLeft +=
                    nameRect.right -
                    visibleRight
            } else if (
                rowRect.left <
                visibleLeft
            ) {
                container.scrollLeft -=
                    visibleLeft -
                    rowRect.left
            }
        }

        reveal()

        /*
         * Re-measure after horizontal scrollbar/layout changes.
         */
        await new Promise((resolve) => {
            window.requestAnimationFrame(resolve)
        })

        reveal()
    }

    async function revealTreePath(path) {
        await ensureTreePathExpanded(path)
        await scrollTreePathIntoView(path)
    }

    function getFileTreeScrollPosition() {
        const element = fileTreeElement.value

        return {
            top: element
                ? element.scrollTop
                : 0,
            left: element
                ? element.scrollLeft
                : 0,
        }
    }

    async function restoreFileTreeScrollPosition(position) {
        await nextTick()

        const element = fileTreeElement.value

        if (!element) {
            return
        }

        element.scrollTop = position.top
        element.scrollLeft = position.left

        /*
         * Some tree updates finish rendering one frame after nextTick().
         * Restore once more on the next animation frame so a large tree
         * does not jump after a mutation.
         */
        await new Promise((resolve) => {
            window.requestAnimationFrame(resolve)
        })

        if (fileTreeElement.value) {
            fileTreeElement.value.scrollTop = position.top
            fileTreeElement.value.scrollLeft = position.left
        }
    }

    async function refreshAfterContextMutation(
        preserveTreeScroll = false,
    ) {
        const expanded =
            new Set(expandedFolders.value)

        const treeScrollPosition =
            preserveTreeScroll
                ? getFileTreeScrollPosition()
                : null

        await loadRootTree(false)
        await restoreExpandedFolders(expanded)

        if (treeScrollPosition !== null) {
            await restoreFileTreeScrollPosition(
                treeScrollPosition,
            )
        }
    }

    function buildWebDavUrl(path) {
        return buildDavUrl(path)
    }

    async function webDavRequest(
        method,
        path,
        options = {},
    ) {
        const davUrl =
            buildWebDavUrl(path)

        if (!davUrl) {
            throw new Error(
                'Invalid path.',
            )
        }

        const headers = {
            ...(options.headers || {}),
        }

        if (OC.requestToken) {
            headers.requesttoken =
                OC.requestToken
        }

        const response = await fetch(
            davUrl,
            {
                method,
                headers,
                body:
                    options.body ||
                    undefined,
            },
        )

        if (!response.ok) {
            let message =
                `${method} failed (${response.status}).`

            try {
                const text =
                    await response.text()

                const match =
                    text.match(
                        /<d:error[^>]*>[\s\S]*?<d:responsedescription>([\s\S]*?)<\/d:responsedescription>/i,
                    )

                if (match && match[1]) {
                    message =
                        match[1]
                            .replace(
                                /<[^>]+>/g,
                                '',
                            )
                            .trim()
                }
            } catch {
                // Keep the HTTP status message.
            }

            const error =
                new Error(message)

            error.status =
                response.status

            throw error
        }

        return response
    }

    function getContextTargetPath() {
        return normalizePath(
            contextMenuPath.value,
        )
    }

    function getContextTargetName() {
        return getNameFromPath(
            getContextTargetPath(),
        )
    }

    function getContextTargetParent() {
        return getParentPath(
            getContextTargetPath(),
        )
    }

    function getRelativeWikiPath(path) {
        return getResourceRelativePath(path)
    }

    function resolveContextFolderInput(
        value,
    ) {
        const root =
            normalizePath(
                wikiRoot.value,
            )

        const input =
            String(value || '')
                .trim()

        if (
            !input ||
            input === '.'
        ) {
            return root
        }

        const relative =
            input
                .replaceAll('\\', '/')
                .split('/')
                .filter(Boolean)

        const parts = []

        for (const part of relative) {
            if (
                part === '.' ||
                part === ''
            ) {
                continue
            }

            if (part === '..') {
                if (parts.length === 0) {
                    throw new Error(
                        'Destination is outside the Wiki Root.',
                    )
                }

                parts.pop()
                continue
            }

            parts.push(part)
        }

        const result =
            normalizePath(
                root +
                    '/' +
                    parts.join('/'),
            )

        if (!isInsideWikiRoot(result)) {
            throw new Error(
                'Destination is outside the Wiki Root.',
            )
        }

        return result
    }

    function validateContextName(name) {
        const value =
            String(name || '').trim()

        if (!value) {
            throw new Error(
                'Name cannot be empty.',
            )
        }

        if (
            value === '.' ||
            value === '..' ||
            value.includes('/') ||
            value.includes('\\')
        ) {
            throw new Error(
                'Name contains invalid characters.',
            )
        }

        return value
    }

    function preserveMarkdownExtension(
        name,
        originalName,
    ) {
        const value =
            validateContextName(name)

        if (
            originalName
                .toLowerCase()
                .endsWith('.md') &&
            !value
                .toLowerCase()
                .endsWith('.md')
        ) {
            return value + '.md'
        }

        return value
    }

    async function copyToClipboard(
        value,
        successMessage = 'Copied.',
    ) {
        try {
            if (
                navigator.clipboard &&
                navigator.clipboard.writeText
            ) {
                await navigator.clipboard.writeText(
                    value,
                )
            } else {
                const textarea =
                    document.createElement('textarea')

                textarea.value = value
                textarea.style.position = 'fixed'
                textarea.style.opacity = '0'

                document.body.appendChild(
                    textarea,
                )

                textarea.focus()
                textarea.select()

                const copied =
                    document.execCommand(
                        'copy',
                    )

                textarea.remove()

                if (!copied) {
                    throw new Error()
                }
            }

            showOperationMessage(
                successMessage,
                'success',
            )
        } catch {
            throw new Error(
                'Failed to copy to clipboard.',
            )
        }
    }

    function getMarkdownLinkForPath(
        path,
    ) {
        const targetPath =
            normalizePath(path)

        let baseDirectory =
            normalizePath(
                wikiRoot.value,
            )

        if (selectedFile.value) {
            baseDirectory =
                getParentPath(
                    selectedFile.value,
                )
        }

        const relative =
            getRelativePath(
                baseDirectory,
                targetPath,
            )

        const name =
            getNameFromPath(
                targetPath,
            )

        return `[${name}](${encodeURI(relative)})`
    }

    function contextTargetAffectsSelection(path) {
        const selectedFilePath =
            normalizePath(
                selectedFile.value,
            )

        const selectedResourcePath =
            selectedResource.value
                ? normalizePath(
                    selectedResource.value.path,
                )
                : ''

        const target =
            normalizePath(path)

        return (
            (
                selectedFilePath &&
                (
                    selectedFilePath === target ||
                    selectedFilePath.startsWith(
                        target + '/',
                    )
                )
            ) ||
            (
                selectedResourcePath &&
                (
                    selectedResourcePath === target ||
                    selectedResourcePath.startsWith(
                        target + '/',
                    )
                )
            )
        )
    }

    async function confirmContextMutation(path) {
        if (
            !contextTargetAffectsSelection(path)
        ) {
            return true
        }

        return await confirmDiscardChanges()
    }

    const renameDialogVisible = ref(false)
    const renameDialogSubmitting = ref(false)
    const renameDialogError = ref('')
    const renameDialogName = ref('')
    const renameDialogSource = ref(null)
    const renameDialogInput = ref(null)

    /*
     * Delete dialog state.
     */
    const deleteDialogVisible = ref(false)
    const deleteDialogSubmitting = ref(false)
    const deleteDialogError = ref('')
    const deleteDialogSource = ref(null)

    async function renameContextTarget() {
        const node = contextMenuNode.value
        if (!node || renameDialogVisible.value) return
        renameDialogSource.value = {
            path: getContextTargetPath(),
            parent: getContextTargetParent(),
            name: getContextTargetName(),
            type: node.type,
            fileType: node.fileType,
        }
        renameDialogName.value = renameDialogSource.value.name
        renameDialogError.value = ''
        renameDialogVisible.value = true
        closeContextMenu()
        await nextTick()
        const input = renameDialogInput.value
        if (input) {
            input.focus()
            const dot = renameDialogName.value.lastIndexOf('.')
            const end = node.type === 'file' && dot > 0
                ? dot : renameDialogName.value.length
            input.setSelectionRange(0, end)
        }
    }

    function closeRenameDialog() {
        if (renameDialogSubmitting.value) return
        renameDialogVisible.value = false
        renameDialogSource.value = null
        renameDialogError.value = ''
        renameDialogName.value = ''
    }

    async function submitRenameDialog() {
        if (renameDialogSubmitting.value || !renameDialogSource.value) return
        const source = renameDialogSource.value
        const { path, parent, name, type, fileType } = source
        renameDialogError.value = ''
        let newName
        try {
            newName = type === 'file' && fileType === 'markdown'
                ? preserveMarkdownExtension(renameDialogName.value, name)
                : validateContextName(renameDialogName.value)
        } catch (err) {
            renameDialogError.value = err.message || 'Invalid name.'
            return
        }
        if (newName === name) {
            closeRenameDialog()
            return
        }
        const destination = normalizePath(parent + '/' + newName)
        if (!isInsideWikiRoot(path) || !isInsideWikiRoot(destination)) {
            renameDialogError.value = 'The item must remain inside the Wiki Root.'
            return
        }
        if (!(await confirmContextMutation(path))) return
        renameDialogSubmitting.value = true
        let renamed = false
        try {
            const url = buildWebDavUrl(destination)
            if (!url) throw new Error('Unable to build destination URL.')
            const wasAffected = contextTargetAffectsSelection(path)
            const wasSelectedMarkdown = normalizePath(selectedFile.value) === path
                && type === 'file' && fileType === 'markdown'
            await webDavRequest('MOVE', path, { headers: {
                Destination: new URL(url, window.location.origin).href,
                Overwrite: 'F',
            } })
            renamed = true
            if (wasAffected) {
                clearContextSelection()
                currentFolder.value = parent
            }
            await refreshAfterContextMutation()
            if (wasSelectedMarkdown) await openFile(destination)
            renameDialogVisible.value = false
            renameDialogSource.value = null
            renameDialogName.value = ''
        } catch (err) {
            if (renamed) {
                renameDialogSource.value = null
                renameDialogError.value =
                    'The item was renamed, but the view could not be refreshed. Close this dialog and reload the page.'
            } else if (
                err?.status === 412 ||
                err?.status === 409
            ) {
                renameDialogError.value =
                    type === 'folder'
                        ? 'A folder with that name already exists.'
                        : 'A file with that name already exists.'
            } else {
                renameDialogError.value =
                    err.message ||
                    'Unable to rename the item.'
            }
        } finally {
            renameDialogSubmitting.value = false
        }
    }



    /*
     * Upload files and folders from the local computer.
     * The same helpers are used by the + menu and external drag & drop.
     */
    function getUploadDestinationFolder(preferredPath = '') {
        const root = normalizePath(wikiRoot.value)
        const preferred = normalizePath(preferredPath)
        const current = normalizePath(currentFolder.value)

        if (preferred && isInsideWikiRoot(preferred)) {
            return preferred
        }

        if (current && isInsideWikiRoot(current)) {
            return current
        }

        return root
    }

    function openUploadFilePicker() {
        closeCreateMenu()
        createError.value = ''
        if (!wikiRoot.value || uploadingResources.value) return
        uploadFileInput.value?.click()
    }

    function openUploadFolderPicker() {
        closeCreateMenu()
        createError.value = ''
        if (!wikiRoot.value || uploadingResources.value) return
        uploadFolderInput.value?.click()
    }

    function splitRelativeUploadPath(path) {
        return String(path || '')
            .replace(/\\/g, '/')
            .split('/')
            .map((part) => part.trim())
            .filter((part) => part && part !== '.' && part !== '..')
    }

    async function createUploadedDirectory(path) {
        try {
            await webDavRequest('MKCOL', path)
        } catch (err) {
            // 405 means the collection already exists. Existing folders are
            // safe to reuse while recreating a selected directory structure.
            if (err?.status !== 405) throw err
        }
    }

    async function ensureUploadDirectories(baseFolder, relativeParts) {
        let path = normalizePath(baseFolder)

        for (const part of relativeParts) {
            path = normalizePath(path + '/' + part)
            await createUploadedDirectory(path)
        }

        return path
    }

    async function uploadLocalFile(file, destinationPath) {
        try {
            await webDavRequest('PUT', destinationPath, {
                headers: {
                    'Content-Type': file.type || 'application/octet-stream',
                    'If-None-Match': '*',
                },
                body: file,
            })
        } catch (err) {
            if (err?.status === 412 || err?.status === 409) {
                throw new Error(`A file named "${getNameFromPath(destinationPath)}" already exists.`)
            }
            throw err
        }
    }

    async function uploadFileEntries(entries, destinationFolder) {
        if (!entries.length || uploadingResources.value) return

        uploadingResources.value = true
        let uploadedFiles = 0

        try {
            for (const entry of entries) {
                const parts = splitRelativeUploadPath(entry.relativePath || entry.file.name)
                if (!parts.length) continue

                const fileName = parts.pop()
                const parent = await ensureUploadDirectories(destinationFolder, parts)
                await uploadLocalFile(entry.file, normalizePath(parent + '/' + fileName))
                uploadedFiles++
            }

            currentFolder.value = normalizePath(destinationFolder)
            await refreshAfterContextMutation(true)

            const destinationNode = findNode(tree.value, normalizePath(destinationFolder))
            if (destinationNode?.type === 'folder') {
                await loadFolderChildren(destinationNode, true)
                const next = new Set(expandedFolders.value)
                next.add(normalizePath(destinationFolder))
                expandedFolders.value = next
            }

            showOperationMessage(
                uploadedFiles === 1
                    ? '1 file uploaded.'
                    : `${uploadedFiles} files uploaded.`,
                'success',
            )
        } catch (err) {
            showOperationMessage(err.message || 'Failed to upload files.', 'error')
        } finally {
            uploadingResources.value = false
        }
    }

    async function handleUploadFileSelection(event) {
        const files = Array.from(event.target?.files || [])
        if (event.target) event.target.value = ''
        const destination = getUploadDestinationFolder()
        if (!destination || !files.length) return

        await uploadFileEntries(
            files.map((file) => ({ file, relativePath: file.name })),
            destination,
        )
    }

    async function handleUploadFolderSelection(event) {
        const files = Array.from(event.target?.files || [])
        if (event.target) event.target.value = ''
        const destination = getUploadDestinationFolder()
        if (!destination || !files.length) return

        await uploadFileEntries(
            files.map((file) => ({
                file,
                relativePath: file.webkitRelativePath || file.name,
            })),
            destination,
        )
    }

    function isExternalFileDrag(event) {
        if (treeDragSource.value) return false
        const types = Array.from(event.dataTransfer?.types || [])
        return types.includes('Files')
    }

    function readFileSystemFile(entry) {
        return new Promise((resolve, reject) => entry.file(resolve, reject))
    }

    function readDirectoryEntries(reader) {
        return new Promise((resolve, reject) => {
            const all = []
            const readBatch = () => {
                reader.readEntries((entries) => {
                    if (!entries.length) {
                        resolve(all)
                        return
                    }
                    all.push(...entries)
                    readBatch()
                }, reject)
            }
            readBatch()
        })
    }

    async function collectDroppedEntry(entry, prefix = '') {
        if (!entry) return []
        const relativePath = prefix ? `${prefix}/${entry.name}` : entry.name

        if (entry.isFile) {
            const file = await readFileSystemFile(entry)
            return [{ file, relativePath }]
        }

        if (entry.isDirectory) {
            const children = await readDirectoryEntries(entry.createReader())
            const result = []
            for (const child of children) {
                result.push(...await collectDroppedEntry(child, relativePath))
            }
            return result
        }

        return []
    }

    async function getExternalDropEntries(dataTransfer) {
        const items = Array.from(dataTransfer?.items || [])
        const entries = []

        if (items.length && items.some((item) => typeof item.webkitGetAsEntry === 'function')) {
            for (const item of items) {
                const entry = item.webkitGetAsEntry?.()
                if (entry) entries.push(...await collectDroppedEntry(entry))
            }
            if (entries.length) return entries
        }

        return Array.from(dataTransfer?.files || []).map((file) => ({
            file,
            relativePath: file.webkitRelativePath || file.name,
        }))
    }

    async function handleExternalDrop(event, preferredDestination = '') {
        if (!isExternalFileDrag(event)) return false
        event.preventDefault()
        event.stopPropagation()

        const destination = getUploadDestinationFolder(preferredDestination)
        if (!destination) return true

        try {
            const entries = await getExternalDropEntries(event.dataTransfer)
            await uploadFileEntries(entries, destination)
        } catch (err) {
            showOperationMessage(err.message || 'Failed to read dropped files.', 'error')
        }

        return true
    }

    function handleExternalRootDragOver(event) {
        if (!isExternalFileDrag(event)) return
        event.preventDefault()
        if (event.dataTransfer) event.dataTransfer.dropEffect = 'copy'
    }

    async function handleExternalRootDrop(event) {
        await handleExternalDrop(event, wikiRoot.value)
    }

    function handleExternalFolderViewDragOver(event) {
        if (!isExternalFileDrag(event)) return
        event.preventDefault()
        if (event.dataTransfer) event.dataTransfer.dropEffect = 'copy'
    }

    async function handleExternalFolderViewDrop(event) {
        await handleExternalDrop(event, currentFolder.value || wikiRoot.value)
    }

    /*
     * Drag and drop in the main file tree.
     * Files and folders can be dragged, while only folders are
     * accepted as destinations. Closed folders auto-expand after
     * a short hover so nested destinations can be reached naturally.
     */
    const treeDragSource = ref(null)
    const treeDragSourcePath = ref('')
    const treeDropTargetPath = ref('')
    const treeDropTargetValid = ref(false)
    let treeDragExpandTimeout = null
    let treeDragExpandPath = ''

    function clearTreeDragExpandTimeout() {
        if (treeDragExpandTimeout !== null) {
            window.clearTimeout(treeDragExpandTimeout)
            treeDragExpandTimeout = null
        }

        treeDragExpandPath = ''
    }

    function resetTreeDragState() {
        clearTreeDragExpandTimeout()
        treeDragSource.value = null
        treeDragSourcePath.value = ''
        treeDropTargetPath.value = ''
        treeDropTargetValid.value = false
    }

    function getWikiRootDropTarget() {
        const rootPath =
            normalizePath(wikiRoot.value)

        if (!rootPath) {
            return null
        }

        return {
            type: 'folder',
            path: rootPath,
            name:
                getNameFromPath(rootPath) ||
                'Wiki Root',
            isWikiRootDropTarget: true,
        }
    }

    function getTreeDropDestinationTarget(target) {
        if (!target) {
            return null
        }

        if (target.type === 'folder') {
            return target
        }

        const parentPath =
            getParentPath(
                normalizePath(target.path),
            )

        if (!parentPath) {
            return null
        }

        return {
            type: 'folder',
            path: parentPath,
            name:
                normalizePath(parentPath) ===
                normalizePath(wikiRoot.value)
                    ? 'Wiki Root'
                    : getNameFromPath(parentPath),
            isLevelDropTarget: true,
        }
    }

    function canDropTreeNode(source, target) {
        if (!source || !target || target.type !== 'folder') {
            return false
        }

        const sourcePath = normalizePath(source.path)
        const targetPath = normalizePath(target.path)
        const sourceParent = getParentPath(sourcePath)

        if (
            !sourcePath ||
            !targetPath ||
            !isInsideWikiRoot(sourcePath) ||
            !isInsideWikiRoot(targetPath)
        ) {
            return false
        }

        if (targetPath === sourceParent) {
            return false
        }

        if (
            source.type === 'folder' &&
            (
                targetPath === sourcePath ||
                targetPath.startsWith(sourcePath + '/')
            )
        ) {
            return false
        }

        return true
    }

    function scheduleTreeDragExpand(target) {
        const path = normalizePath(target?.path)

        if (
            !path ||
            target?.type !== 'folder' ||
            expandedFolders.value.has(path) ||
            treeDragExpandPath === path
        ) {
            return
        }

        clearTreeDragExpandTimeout()
        treeDragExpandPath = path

        treeDragExpandTimeout = window.setTimeout(async () => {
            treeDragExpandTimeout = null

            if (
                treeDropTargetPath.value !== path ||
                !treeDropTargetValid.value
            ) {
                treeDragExpandPath = ''
                return
            }

            try {
                await loadFolderChildren(target)

                const next = new Set(expandedFolders.value)
                next.add(path)
                expandedFolders.value = next
            } catch (err) {
                showOperationMessage(
                    err.message || 'Failed to open the destination folder.',
                    'error',
                )
            } finally {
                treeDragExpandPath = ''
            }
        }, 700)
    }

    function handleTreeDragStart(event, node) {
        if (!node) return

        closeContextMenu()
        clearTreeDragExpandTimeout()

        treeDragSource.value = node
        treeDragSourcePath.value = normalizePath(node.path)
        treeDropTargetPath.value = ''
        treeDropTargetValid.value = false

        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move'
            event.dataTransfer.setData(
                'text/plain',
                treeDragSourcePath.value,
            )
        }
    }

    function handleTreeDragEnd() {
        resetTreeDragState()
    }

    function handleTreeDragOver(event, target) {
        if (isExternalFileDrag(event)) {
            const destinationTarget = getTreeDropDestinationTarget(target)
            if (!destinationTarget) return

            event.preventDefault()
            event.stopPropagation()

            const targetPath = normalizePath(destinationTarget.path)

            if (treeDropTargetPath.value !== targetPath) {
                clearTreeDragExpandTimeout()
            }

            // Reuse the same drop-target state and visual feedback used
            // by internal MOVE operations.
            treeDropTargetPath.value = targetPath
            treeDropTargetValid.value = true

            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'copy'
            }

            if (
                target?.type === 'folder' &&
                !destinationTarget.isWikiRootDropTarget
            ) {
                scheduleTreeDragExpand(destinationTarget)
            } else {
                clearTreeDragExpandTimeout()
            }

            return
        }

        const source =
            treeDragSource.value

        const destinationTarget =
            getTreeDropDestinationTarget(
                target,
            )

        if (
            !source ||
            !destinationTarget
        ) {
            return
        }

        event.preventDefault()
        event.stopPropagation()

        const targetPath =
            normalizePath(
                destinationTarget.path,
            )

        const valid =
            canDropTreeNode(
                source,
                destinationTarget,
            )

        if (
            treeDropTargetPath.value !==
            targetPath
        ) {
            clearTreeDragExpandTimeout()
        }

        treeDropTargetPath.value =
            targetPath

        treeDropTargetValid.value =
            valid

        if (event.dataTransfer) {
            event.dataTransfer.dropEffect =
                valid
                    ? 'move'
                    : 'none'
        }

        if (
            valid &&
            target?.type === 'folder' &&
            !destinationTarget
                .isWikiRootDropTarget
        ) {
            scheduleTreeDragExpand(
                destinationTarget,
            )
        } else {
            clearTreeDragExpandTimeout()
        }
    }

    function handleTreeExternalDragLeave(event) {
        if (!isExternalFileDrag(event)) return

        // dragleave also fires while moving between descendants of the tree.
        // Only clear the highlight when the pointer actually leaves the
        // complete tree area.
        const nextElement = event.relatedTarget
        if (
            nextElement instanceof Node &&
            event.currentTarget?.contains(nextElement)
        ) {
            return
        }

        resetTreeDragState()
    }

    function getMovedSelectionPath(selectedPath, sourcePath, destination) {
        const selected = normalizePath(selectedPath)
        const source = normalizePath(sourcePath)

        if (!selected || !source) return ''
        if (selected === source) return destination

        if (selected.startsWith(source + '/')) {
            return destination + selected.slice(source.length)
        }

        return ''
    }

    async function handleTreeDrop(event, target) {
        if (isExternalFileDrag(event)) {
            const destinationTarget = getTreeDropDestinationTarget(target)
            const destinationPath = destinationTarget?.path || wikiRoot.value

            // Remove the visual target immediately after the drop, while
            // keeping the resolved destination for the asynchronous upload.
            resetTreeDragState()
            await handleExternalDrop(event, destinationPath)
            return
        }

        const source =
            treeDragSource.value

        event.preventDefault()
        event.stopPropagation()
        clearTreeDragExpandTimeout()

        const destinationTarget =
            getTreeDropDestinationTarget(
                target,
            )

        if (
            !source ||
            !destinationTarget
        ) {
            resetTreeDragState()
            return
        }

        const sourcePath =
            normalizePath(source.path)

        const destinationFolder =
            normalizePath(
                destinationTarget.path,
            )

        const sourceParent =
            getParentPath(sourcePath)

        if (destinationFolder === sourceParent) {
            resetTreeDragState()
            return
        }

        if (!canDropTreeNode(source, destinationTarget)) {
            const invalidDescendant =
                source.type === 'folder' &&
                (
                    destinationFolder === sourcePath ||
                    destinationFolder.startsWith(sourcePath + '/')
                )

            resetTreeDragState()

            if (invalidDescendant) {
                showOperationMessage(
                    'A folder cannot be moved into itself or one of its subfolders.',
                    'error',
                )
            }

            return
        }

        const name = getNameFromPath(sourcePath)
        const destination = normalizePath(
            destinationFolder + '/' + name,
        )

        const selectedFileBefore = normalizePath(selectedFile.value)
        const selectedResourceBefore = selectedResource.value
            ? normalizePath(selectedResource.value.path)
            : ''

        const movedSelectedFile = getMovedSelectionPath(
            selectedFileBefore,
            sourcePath,
            destination,
        )

        const movedSelectedResource = getMovedSelectionPath(
            selectedResourceBefore,
            sourcePath,
            destination,
        )

        resetTreeDragState()

        if (!(await confirmContextMutation(sourcePath))) {
            return
        }

        let moved = false

        try {
            const url = buildWebDavUrl(destination)

            if (!url) {
                throw new Error('Unable to build destination URL.')
            }

            await webDavRequest('MOVE', sourcePath, {
                headers: {
                    Destination: new URL(
                        url,
                        window.location.origin,
                    ).href,
                    Overwrite: 'F',
                },
            })

            moved = true

            if (contextTargetAffectsSelection(sourcePath)) {
                clearContextSelection()
            }

            currentFolder.value = destinationFolder

            await refreshAfterContextMutation(true)

            if (movedSelectedFile) {
                await openFile(movedSelectedFile)
            } else if (movedSelectedResource) {
                const movedNode = findNode(
                    tree.value,
                    movedSelectedResource,
                )

                if (movedNode) {
                    selectResource(movedNode)
                }
            }

            showOperationMessage(
                `Moved "${name}" to "${getNameFromPath(destinationFolder)}".`,
                'success',
            )
        } catch (err) {
            if (moved) {
                showOperationMessage(
                    'The item was moved, but the file list could not be refreshed. Reload the page to update the tree.',
                    'error',
                )
            } else if (err?.status === 412 || err?.status === 409) {
                showOperationMessage(
                    source.type === 'folder'
                        ? 'A folder with that name already exists in the destination.'
                        : 'A file with that name already exists in the destination.',
                    'error',
                )
            } else {
                showOperationMessage(
                    err.message || 'Unable to move the item.',
                    'error',
                )
            }
        }
    }


    const moveDialogVisible = ref(false)
    const moveDialogSubmitting = ref(false)
    const moveDialogLoading = ref(false)
    const moveDialogError = ref('')
    const moveDialogFolder = ref('')
    const moveDialogSource = ref(null)
    const moveDialogElement = ref(null)
    const moveDialogExpandedFolders = ref(new Set())
    const moveDialogFolders = computed(() => tree.value.filter(node => node.type === 'folder'))
    let moveDialogRequest = 0

    async function moveContextTarget() {
        const node = contextMenuNode.value
        if (!node || moveDialogVisible.value) return
        const path = getContextTargetPath()
        const parent = getContextTargetParent()
        moveDialogSource.value = { path, parent, name: getNameFromPath(path), type: node.type }
        moveDialogError.value = ''
        moveDialogFolder.value = parent
        moveDialogExpandedFolders.value = new Set()
        moveDialogLoading.value = true
        moveDialogVisible.value = true
        const request = ++moveDialogRequest
        closeContextMenu()
        await nextTick()
        moveDialogElement.value?.focus()
        try {
            await prepareMoveDialogTree(parent)
        } catch (err) {
            if (request === moveDialogRequest) moveDialogError.value = err.message || 'Failed to load folders.'
        } finally {
            if (request === moveDialogRequest) moveDialogLoading.value = false
        }
    }

    function closeMoveDialog() {
        if (moveDialogSubmitting.value || moveDialogLoading.value) return
        moveDialogVisible.value = false
        moveDialogSource.value = null
        moveDialogError.value = ''
        moveDialogRequest++
    }

    async function submitMoveDialog() {
        if (moveDialogSubmitting.value || moveDialogLoading.value || !moveDialogSource.value) return
        const { path, parent, name, type } = moveDialogSource.value
        const destinationFolder = normalizePath(moveDialogFolder.value)
        moveDialogError.value = ''
        if (!isInsideWikiRoot(path) || !isInsideWikiRoot(destinationFolder)) {
            moveDialogError.value = 'The destination must be inside the Wiki Root.'
            return
        }
        if (destinationFolder === parent) {
            moveDialogError.value = 'The item is already in that folder.'
            return
        }
        if (type === 'folder' && (destinationFolder === path || destinationFolder.startsWith(path + '/'))) {
            moveDialogError.value = 'A folder cannot be moved into itself or one of its subfolders.'
            return
        }
        if (!(await confirmContextMutation(path))) return
        const destination = normalizePath(destinationFolder + '/' + name)
        moveDialogSubmitting.value = true
        let moved = false
        try {
            const url = buildWebDavUrl(destination)
            if (!url) throw new Error('Unable to build destination URL.')
            await webDavRequest('MOVE', path, { headers: {
                Destination: new URL(url, window.location.origin).href,
                Overwrite: 'F',
            } })
            moved = true
            if (contextTargetAffectsSelection(path)) {
                clearContextSelection()
                currentFolder.value = destinationFolder
            }
            await refreshAfterContextMutation()
            moveDialogVisible.value = false
            moveDialogSource.value = null
        } catch (err) {
            if (moved) {
                moveDialogSource.value = null
                moveDialogError.value = 'The item was moved, but the file list could not be refreshed. Close this dialog and reload the page.'
            } else {
                moveDialogError.value = err.message || 'Unable to move the item.'
            }
        } finally {
            moveDialogSubmitting.value = false
        }
    }

    async function prepareMoveDialogTree(
        selectedPath,
    ) {
        const root =
            normalizePath(
                wikiRoot.value,
            )

        const target =
            normalizePath(
                selectedPath || root,
            )

        moveDialogFolder.value = target

        const expanded =
            new Set([root])

        if (
            target !== root &&
            target.startsWith(root + '/')
        ) {
            const relativeParts =
                target
                    .slice(root.length + 1)
                    .split('/')
                    .filter(Boolean)

            let currentPath = root

            for (const part of relativeParts) {
                currentPath =
                    normalizePath(
                        currentPath +
                            '/' +
                            part,
                    )

                const node =
                    findNode(
                        tree.value,
                        currentPath,
                    )

                if (
                    !node ||
                    node.type !== 'folder'
                ) {
                    break
                }

                await loadFolderChildren(node)

                expanded.add(currentPath)
            }
        }

        moveDialogExpandedFolders.value =
            expanded
    }

    function selectMoveDialogFolder(
        path,
    ) {
        if (moveDialogSubmitting.value || moveDialogLoading.value) return
        const normalizedPath =
            normalizePath(path)

        if (
            !isInsideWikiRoot(
                normalizedPath,
            )
        ) {
            return
        }

        moveDialogFolder.value =
            normalizedPath
    }

    async function toggleMoveDialogFolder(node) {
        if (moveDialogSubmitting.value || moveDialogLoading.value) return
        const path = normalizePath(node.path)

        if (moveDialogExpandedFolders.value.has(path)) {
            const next = new Set(moveDialogExpandedFolders.value)
            next.delete(path)
            moveDialogExpandedFolders.value = next
            return
        }

        try {
            await loadFolderChildren(node, true)

            const next = new Set(moveDialogExpandedFolders.value)
            next.add(path)
            moveDialogExpandedFolders.value = next
        } catch (err) {
            moveDialogError.value =
                err.message || 'Failed to load folder contents.'
        }
    }

    function getDuplicateName(
        originalName,
        attempt,
    ) {
        const suffix =
            attempt === 0
                ? ' (copy)'
                : ` (copy ${attempt + 1})`

        const lastDot =
            originalName.lastIndexOf('.')

        if (
            lastDot > 0 &&
            lastDot < originalName.length - 1
        ) {
            return (
                originalName.slice(0, lastDot) +
                suffix +
                originalName.slice(lastDot)
            )
        }

        return originalName + suffix
    }

    async function duplicateContextTarget() {
        const path =
            getContextTargetPath()

        const node =
            contextMenuNode.value

        if (!node) {
            return
        }

        const parent =
            getContextTargetParent()

        let lastError = null

        for (
            let attempt = 0;
            attempt < 20;
            attempt++
        ) {
            const name =
                getDuplicateName(
                    getContextTargetName(),
                    attempt,
                )

            const destination =
                normalizePath(
                    parent + '/' + name,
                )

            try {
                await webDavRequest(
                    'COPY',
                    path,
                    {
                        headers: {
                            Destination:
                                new URL(
                                    buildWebDavUrl(
                                        destination,
                                    ),
                                    window.location.origin,
                                ).href,
                            Overwrite: 'F',
                        },
                    },
                )

                await refreshAfterContextMutation()
                currentFolder.value = parent
                return
            } catch (err) {
                lastError = err
            }
        }

        throw lastError ||
            new Error(
                'Failed to duplicate item.',
            )
    }

    async function deleteContextTarget() {
        const node =
            contextMenuNode.value

        if (
            !node ||
            deleteDialogVisible.value
        ) {
            return
        }

        const path =
            getContextTargetPath()

        deleteDialogSource.value = {
            path,
            parent:
                getContextTargetParent(),
            name:
                getContextTargetName(),
            type: node.type,
            fileType: node.fileType,
        }

        deleteDialogError.value = ''
        deleteDialogVisible.value = true

        closeContextMenu()
    }

    function closeDeleteDialog() {
        if (deleteDialogSubmitting.value) {
            return
        }

        deleteDialogVisible.value = false
        deleteDialogSource.value = null
        deleteDialogError.value = ''
    }

    async function submitDeleteDialog() {
        if (
            deleteDialogSubmitting.value ||
            !deleteDialogSource.value
        ) {
            return
        }

        const source =
            deleteDialogSource.value

        const {
            path,
            parent,
        } = source

        deleteDialogError.value = ''

        if (!isInsideWikiRoot(path)) {
            deleteDialogError.value =
                'The item must be inside the Wiki Root.'

            return
        }

        if (!(await confirmContextMutation(path))) {
            return
        }

        const wasAffected =
            contextTargetAffectsSelection(
                path,
            )

        deleteDialogSubmitting.value = true

        let deleted = false

        try {
            await webDavRequest(
                'DELETE',
                path,
            )

            deleted = true

            if (wasAffected) {
                clearContextSelection()
                currentFolder.value = parent
            }

            await refreshAfterContextMutation()

            deleteDialogVisible.value = false
            deleteDialogSource.value = null
            deleteDialogError.value = ''
        } catch (err) {
            if (deleted) {
                deleteDialogSource.value = null
                deleteDialogError.value =
                    'The item was deleted, but the view could not be refreshed. Close this dialog and reload the page.'
            } else {
                deleteDialogError.value =
                    err.message ||
                    'Unable to delete the item.'
            }
        } finally {
            deleteDialogSubmitting.value = false
        }
    }

    function downloadContextTarget() {
        const path =
            getContextTargetPath()

        const davUrl =
            buildWebDavUrl(path)

        if (!davUrl) {
            throw new Error(
                'Unable to build download URL.',
            )
        }

        const link =
            document.createElement('a')

        link.href = davUrl
        link.download =
            getContextTargetName()
        link.target = '_blank'
        link.rel = 'noopener'

        document.body.appendChild(link)
        link.click()
        link.remove()
    }

    function formatFileSize(bytes) {
        const size = Number(bytes)

        if (!Number.isFinite(size) || size < 0) {
            return 'Unknown'
        }

        if (size < 1024) {
            return `${size} B`
        }

        const units = [
            'KB',
            'MB',
            'GB',
            'TB',
        ]

        let value = size / 1024
        let unitIndex = 0

        while (
            value >= 1024 &&
            unitIndex < units.length - 1
        ) {
            value /= 1024
            unitIndex++
        }

        const decimals =
            value >= 100
                ? 0
                : value >= 10
                    ? 1
                    : 2

        return `${value.toFixed(decimals)} ${units[unitIndex]}`
    }

    function formatPropertiesDate(value) {
        if (!value) {
            return 'Unknown'
        }

        const date = new Date(value)

        if (Number.isNaN(date.getTime())) {
            return 'Unknown'
        }

        return new Intl.DateTimeFormat(
            'en-GB',
            {
                dateStyle: 'medium',
                timeStyle: 'short',
            },
        ).format(date)
    }

    function getDavResourceElements(xmlText) {
        const parser = new DOMParser()
        const xml = parser.parseFromString(
            xmlText,
            'application/xml',
        )

        if (
            xml.querySelector('parsererror')
        ) {
            throw new Error(
                'Unable to read file properties.',
            )
        }

        return [
            ...xml.getElementsByTagNameNS(
                'DAV:',
                'response',
            ),
        ]
    }

    function getDavChildText(element, localName) {
        const child =
            element.getElementsByTagNameNS(
                'DAV:',
                localName,
            )[0]

        return child?.textContent?.trim() || ''
    }

    function isDavCollection(element) {
        return Boolean(
            element.getElementsByTagNameNS(
                'DAV:',
                'collection',
            )[0],
        )
    }

    function getDavResponseHref(element) {
        return getDavChildText(
            element,
            'href',
        )
    }

    function getDavResponsePath(
        element,
        fallbackPath,
    ) {
        const href =
            getDavResponseHref(element)

        if (!href) {
            return fallbackPath
        }

        try {
            const url =
                new URL(
                    href,
                    window.location.origin,
                )

            const davBase =
                new URL(
                    buildWebDavUrl(
                        wikiRoot.value,
                    ),
                    window.location.origin,
                )

            const basePath =
                davBase.pathname
                    .replace(/\/+$/, '')

            const hrefPath =
                decodeURIComponent(
                    url.pathname,
                )

            if (
                hrefPath === basePath
            ) {
                return normalizePath(
                    wikiRoot.value,
                )
            }

            if (
                hrefPath.startsWith(
                    basePath + '/',
                )
            ) {
                return normalizePath(
                    wikiRoot.value +
                        '/' +
                        hrefPath
                            .slice(
                                basePath.length + 1,
                            )
                            .split('/')
                            .filter(Boolean)
                            .join('/'),
                )
            }
        } catch {
            // Fall back to the requested path.
        }

        return fallbackPath
    }

    async function webDavRequestUrl(
        method,
        url,
        options = {},
    ) {
        const headers = {
            ...(options.headers || {}),
        }

        if (OC.requestToken) {
            headers.requesttoken =
                OC.requestToken
        }

        const response = await fetch(
            url,
            {
                method,
                headers,
                body: options.body || undefined,
            },
        )

        if (!response.ok) {
            const error =
                new Error(
                    `${method} failed (${response.status}).`,
                )

            error.status =
                response.status

            throw error
        }

        return response
    }

    async function fetchPropertiesTree(
        folderPath,
    ) {
        const result = {
            files: 0,
            folders: 0,
            totalSize: 0,
            latestModified: '',
        }

        async function scanFolder(
            path,
        ) {
            const davUrl =
                buildWebDavUrl(path)

            if (!davUrl) {
                throw new Error(
                    'Unable to build properties URL.',
                )
            }

            const response =
                await webDavRequestUrl(
                    'PROPFIND',
                    davUrl,
                    {
                        headers: {
                            Depth: '1',
                            Accept:
                                'application/xml',
                        },
                    },
                )

            const xmlText =
                await response.text()

            const elements =
                getDavResourceElements(
                    xmlText,
                )

            for (const element of elements) {
                const isCollection =
                    isDavCollection(
                        element,
                    )

                const resourcePath =
                    getDavResponsePath(
                        element,
                        path,
                    )

                const normalizedResourcePath =
                    normalizePath(
                        resourcePath,
                    )

                if (
                    normalizedResourcePath ===
                    normalizePath(path)
                ) {
                    continue
                }

                const sizeText =
                    getDavChildText(
                        element,
                        'getcontentlength',
                    )

                const modified =
                    getDavChildText(
                        element,
                        'getlastmodified',
                    )

                if (modified) {
                    const modifiedTime =
                        new Date(
                            modified,
                        ).getTime()

                    const latestTime =
                        result.latestModified
                            ? new Date(
                                result.latestModified,
                            ).getTime()
                            : 0

                    if (
                        !Number.isNaN(
                            modifiedTime,
                        ) &&
                        modifiedTime >
                            latestTime
                    ) {
                        result.latestModified =
                            modified
                    }
                }

                if (isCollection) {
                    if (
                        normalizedResourcePath ===
                        normalizePath(path)
                    ) {
                        throw new Error(
                            'Unable to determine a folder path.',
                        )
                    }

                    result.folders++

                    await scanFolder(
                        normalizedResourcePath,
                    )

                    continue
                }

                result.files++

                const size =
                    Number(sizeText)

                if (
                    Number.isFinite(size) &&
                    size >= 0
                ) {
                    result.totalSize +=
                        size
                }
            }
        }

        await scanFolder(
            normalizePath(folderPath),
        )

        return result
    }

    async function loadFileProperties(
        path,
        node,
    ) {
        const davUrl =
            buildWebDavUrl(path)

        if (!davUrl) {
            throw new Error(
                'Unable to build properties URL.',
            )
        }

        const response =
            await webDavRequestUrl(
                'PROPFIND',
                davUrl,
                {
                    headers: {
                        Depth: '0',
                        Accept:
                            'application/xml',
                    },
                },
            )

        const xmlText =
            await response.text()

        const elements =
            getDavResourceElements(
                xmlText,
            )

        if (!elements.length) {
            throw new Error(
                'No properties were returned.',
            )
        }

        const element =
            elements[0]

        const sizeText =
            getDavChildText(
                element,
                'getcontentlength',
            )

        const contentType =
            getDavChildText(
                element,
                'getcontenttype',
            )

        const modified =
            getDavChildText(
                element,
                'getlastmodified',
            )

        const size =
            Number(sizeText)

        return {
            name: node.name,
            type: getFileTypeLabel(
                node.fileType,
            ),
            path,
            extension:
                node.extension || '',
            size:
                Number.isFinite(size) &&
                size >= 0
                    ? size
                    : null,
            modified,
            mimeType: contentType,
        }
    }

    async function showContextProperties() {
        const node =
            contextMenuNode.value

        if (!node) {
            return
        }

        const path =
            getContextTargetPath()

        propertiesDialogError.value = ''
        propertiesDialogData.value = null
        propertiesDialogLoading.value = true
        propertiesDialogVisible.value = true

        try {
            if (node.type === 'folder') {
                const stats =
                    await fetchPropertiesTree(
                        path,
                    )

                propertiesDialogData.value = {
                    name: node.name,
                    type: 'Folder',
                    path,
                    files: stats.files,
                    folders:
                        stats.folders,
                    items:
                        stats.files +
                        stats.folders,
                    totalSize:
                        stats.totalSize,
                    latestModified:
                        stats.latestModified,
                }
            } else {
                propertiesDialogData.value =
                    await loadFileProperties(
                        path,
                        node,
                    )
            }
        } catch (err) {
            propertiesDialogError.value =
                err.message ||
                'Unable to load properties.'
        } finally {
            propertiesDialogLoading.value = false
        }
    }

    function closePropertiesDialog() {
        propertiesDialogVisible.value = false
        propertiesDialogLoading.value = false
        propertiesDialogError.value = ''
        propertiesDialogData.value = null
    }

    async function uploadFilesToContextFolder(
        files,
        folder,
    ) {
        folder =
            normalizePath(folder)

        if (!isInsideWikiRoot(folder)) {
            throw new Error(
                'Invalid upload destination.',
            )
        }

        if (!files.length) {
            return
        }

        for (const file of files) {
            const relativePath =
                file.webkitRelativePath ||
                file.name

            const parts =
                relativePath
                    .replaceAll('\\', '/')
                    .split('/')
                    .filter(Boolean)

            if (!parts.length) {
                continue
            }

            const fileName =
                validateContextName(
                    parts.pop(),
                )

            let targetFolder = folder

            for (const part of parts) {
                const safePart =
                    validateContextName(part)

                targetFolder =
                    normalizePath(
                        targetFolder +
                            '/' +
                            safePart,
                    )

                try {
                    await webDavRequest(
                        'MKCOL',
                        targetFolder,
                    )
                } catch (err) {
                    if (err.status !== 405) {
                        throw err
                    }
                }
            }

            const targetPath =
                normalizePath(
                    targetFolder +
                        '/' +
                        fileName,
                )

            await webDavRequest(
                'PUT',
                targetPath,
                {
                    headers: {
                        'Content-Type':
                            file.type ||
                            'application/octet-stream',
                        'If-None-Match': '*',
                    },
                    body: file,
                },
            )
        }

        await refreshAfterContextMutation()
        currentFolder.value = folder
    }

    function chooseUpload(
        mode,
        targetFolder,
    ) {
        const input =
            document.createElement('input')

        input.type = 'file'
        input.multiple = true

        if (mode === 'folder') {
            input.webkitdirectory = true
            input.directory = true
        }

        input.addEventListener(
            'change',
            async (event) => {
                const files =
                    Array.from(
                        event.target.files ||
                            [],
                    )

                try {
                    await uploadFilesToContextFolder(
                        files,
                        targetFolder,
                    )
                } catch (err) {
                    showOperationMessage(
                        err.message ||
                            'Upload failed.',
                        'error',
                    )
                }
            },
            { once: true },
        )

        input.click()
    }

    async function handleContextMenuAction(
        action,
    ) {
        const node =
            contextMenuNode.value

        if (!node) {
            closeContextMenu()
            return
        }

        try {
            switch (action) {
                case 'open':
                    if (
                        node.type === 'folder'
                    ) {
                        await openFolder(
                            node.path,
                        )
                    } else if (
                        node.fileType ===
                        'markdown'
                    ) {
                        await openFile(
                            node.path,
                        )
                    } else {
                        await selectResource(node)
                    }
                    break

                case 'edit':
                    if (
                        node.type === 'file' &&
                        node.fileType ===
                            'markdown'
                    ) {
                        await openFile(
                            node.path,
                        )
                        await startEditing({
                            preserveScroll: false,
                        })
                        editorMode.value =
                            'split'
                    }
                    break

                case 'new-file':
                    currentFolder.value =
                        normalizePath(
                            node.path,
                        )
                    await openCreateDialog(
                        'file',
                    )
                    break

                case 'new-folder':
                    currentFolder.value =
                        normalizePath(
                            node.path,
                        )
                    await openCreateDialog(
                        'folder',
                    )
                    break

                case 'upload-file':
                    chooseUpload(
                        'file',
                        node.path,
                    )
                    break

                case 'upload-folder':
                    chooseUpload(
                        'folder',
                        node.path,
                    )
                    break

                case 'rename':
                    await renameContextTarget()
                    break

                case 'copy-path':
                    await copyToClipboard(
                        getRelativeWikiPath(
                            getContextTargetPath(),
                        ),
                        'Path copied.',
                    )
                    break

                case 'copy-markdown-link':
                    await copyToClipboard(
                        getMarkdownLinkForPath(
                            getContextTargetPath(),
                        ),
                        'Markdown link copied.',
                    )
                    break

                case 'move':
                    await moveContextTarget()
                    break

                case 'duplicate':
                    await duplicateContextTarget()
                    break

                case 'download':
                    downloadContextTarget()
                    break

                case 'properties':
                    await showContextProperties()
                    return

                case 'delete':
                    await deleteContextTarget()
                    break

                default:
                    break
            }
        } catch (err) {
            showOperationMessage(
                err.message ||
                    'The operation failed.',
                'error',
            )
        } finally {
            closeContextMenu()
        }
    }

    function closeCreateMenu() {
        createMenuVisible.value = false
    }

    /*
     * Create menu.
     */
    function toggleCreateMenu() {
        createError.value = ''
        createMenuVisible.value =
            !createMenuVisible.value

        if (createMenuVisible.value) {
            closeContextMenu()
        }
    }

    const createDialogTitle = computed(() =>
        createDialogType.value === 'folder'
            ? 'New folder'
            : 'New Markdown file',
    )

    const createDialogNameLabel = computed(() =>
        createDialogType.value === 'folder'
            ? 'Folder name'
            : 'File name',
    )

    const createDialogFolders = computed(() =>
        tree.value.filter(
            (node) => node.type === 'folder',
        ),
    )

    async function prepareCreateDialogTree(
        selectedPath,
    ) {
        const root =
            normalizePath(
                wikiRoot.value,
            )

        const target =
            normalizePath(
                selectedPath || root,
            )

        createDialogFolder.value = target

        const expanded =
            new Set([root])

        if (
            target !== root &&
            target.startsWith(root + '/')
        ) {
            const relativeParts =
                target
                    .slice(root.length + 1)
                    .split('/')
                    .filter(Boolean)

            let currentPath = root

            for (const part of relativeParts) {
                currentPath =
                    normalizePath(
                        currentPath +
                            '/' +
                            part,
                    )

                const node =
                    findNode(
                        tree.value,
                        currentPath,
                    )

                if (
                    !node ||
                    node.type !== 'folder'
                ) {
                    break
                }

                await loadFolderChildren(node)

                expanded.add(currentPath)
            }
        }

        createDialogExpandedFolders.value =
            expanded
    }

    async function openCreateDialog(type) {
        createMenuVisible.value = false
        createError.value = ''

        if (!wikiRoot.value) {
            createError.value =
                'Wiki Root is not configured.'

            return
        }

        if (!(await confirmDiscardChanges())) {
            return
        }

        closeContextMenu()

        createDialogType.value = type
        createDialogName.value = ''
        createDialogError.value = ''
        createDialogSubmitting.value = false

        const selectedFolder =
            normalizePath(
                currentFolder.value ||
                    wikiRoot.value,
            )

        await prepareCreateDialogTree(
            selectedFolder,
        )

        createDialogVisible.value = true

        await nextTick()

        if (createDialogNameInput.value) {
            createDialogNameInput.value.focus()
        }
    }

    function closeCreateDialog(force = false) {
        if (
            createDialogSubmitting.value &&
            !force
        ) {
            return
        }

        createDialogVisible.value = false
        createDialogName.value = ''
        createDialogError.value = ''
        createDialogSubmitting.value = false
    }

    function selectCreateDialogFolder(
        path,
    ) {
        const normalizedPath =
            normalizePath(path)

        if (
            !isInsideWikiRoot(
                normalizedPath,
            )
        ) {
            return
        }

        createDialogFolder.value =
            normalizedPath
    }

    async function toggleCreateDialogFolder(node) {
        const path = normalizePath(node.path)

        if (createDialogExpandedFolders.value.has(path)) {
            const next = new Set(createDialogExpandedFolders.value)
            next.delete(path)
            createDialogExpandedFolders.value = next
            return
        }

        try {
            await loadFolderChildren(node, true)

            const next = new Set(createDialogExpandedFolders.value)
            next.add(path)
            createDialogExpandedFolders.value = next
        } catch (err) {
            createDialogError.value =
                err.message || 'Failed to load folder contents.'
        }
    }

    async function refreshCreateParent(
        parentFolder,
    ) {
        if (
            parentFolder ===
            normalizePath(wikiRoot.value)
        ) {
            await loadRootTree(false)
            return
        }

        const parentNode =
            findNode(
                tree.value,
                parentFolder,
            )

        if (
            parentNode &&
            parentNode.type === 'folder'
        ) {
            await loadFolderChildren(
                parentNode,
                true,
            )

            return
        }

        await loadRootTree(false)

        await restoreExpandedFolders(
            expandedFolders.value,
        )
    }

    async function submitCreateDialog() {
        createDialogError.value = ''

        if (createDialogSubmitting.value) {
            return
        }

        if (!wikiRoot.value) {
            createDialogError.value =
                'Wiki Root is not configured.'

            return
        }

        let name =
            createDialogName.value.trim()

        if (!name) {
            createDialogError.value =
                createDialogType.value === 'folder'
                    ? 'Please enter a folder name.'
                    : 'Please enter a file name.'

            return
        }

        if (
            name.includes('/') ||
            name.includes('\\') ||
            name === '.' ||
            name === '..' ||
            name.includes('..')
        ) {
            createDialogError.value =
                'Invalid name.'

            return
        }

        const parentFolder =
            normalizePath(
                createDialogFolder.value ||
                    wikiRoot.value,
            )

        if (!isInsideWikiRoot(parentFolder)) {
            createDialogError.value =
                'The destination must be inside the Wiki Root.'

            return
        }

        const isFolder =
            createDialogType.value === 'folder'

        if (
            !isFolder &&
            !name
                .toLowerCase()
                .endsWith('.md')
        ) {
            name += '.md'
        }

        const path =
            normalizePath(
                parentFolder +
                    '/' +
                    name,
            )

        if (!isInsideWikiRoot(path)) {
            createDialogError.value =
                isFolder
                    ? 'The folder must be inside the Wiki Root.'
                    : 'The file must be inside the Wiki Root.'

            return
        }

        createDialogSubmitting.value = true

        try {
            const body =
                new URLSearchParams()

            body.set(
                'path',
                path,
            )

            const endpoint =
                isFolder
                    ? '/apps/markdown_wiki/api/create-folder'
                    : '/apps/markdown_wiki/api/create-file'

            const response = await fetch(
                OC.generateUrl(endpoint),
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type':
                            'application/x-www-form-urlencoded;charset=UTF-8',
                    },
                    body,
                },
            )

            const data =
                await response.json()

            if (!response.ok) {
                const serverMessage =
                    data.message || ''

                const alreadyExists =
                    response.status === 409 ||
                    /file or folder with that name already exists/i.test(
                        serverMessage,
                    )

                if (alreadyExists) {
                    throw new Error(
                        isFolder
                            ? 'A folder with that name already exists.'
                            : 'A file with that name already exists.',
                    )
                }

                throw new Error(
                    serverMessage ||
                        (
                            isFolder
                                ? 'Failed to create folder.'
                                : 'Failed to create Markdown file.'
                        ),
                )
            }

            const createdPath =
                normalizePath(
                    data.path || path,
                )

            await refreshCreateParent(
                parentFolder,
            )

            if (isFolder) {
                /*
                 * Open the newly created folder.
                 */
                const folderNode =
                    findNode(
                        tree.value,
                        createdPath,
                    )

                if (
                    folderNode &&
                    folderNode.type === 'folder'
                ) {
                    await loadFolderChildren(
                        folderNode,
                        true,
                    )

                    const next =
                        new Set(
                            expandedFolders.value,
                        )

                    next.add(createdPath)

                    expandedFolders.value =
                        next

                    currentFolder.value =
                        createdPath
                } else {
                    currentFolder.value =
                        createdPath
                }

                /*
                 * Clear the current selection because
                 * the user is now inside the new folder.
                 */
                selectedFile.value = ''
                selectedResource.value = null
                markdown.value = ''
                editedMarkdown.value = ''
                fileError.value = ''
                saveError.value = ''
                copyError.value = ''
                copiedResourcePath.value = false
            } else {
                /*
                 * Open the newly created Markdown file.
                 */
                selectedFile.value = ''
                selectedResource.value = null
                markdown.value = ''
                editedMarkdown.value = ''
                fileError.value = ''
                saveError.value = ''
                copyError.value = ''
                copiedResourcePath.value = false

                await openFile(createdPath)

                editing.value = true
                editedMarkdown.value =
                    markdown.value

                editorMode.value = 'split'
                selectedCodeLanguage.value = ''

                await nextTick()

                if (editorTextarea.value) {
                    editorTextarea.value.focus()
                }
            }

            await revealTreePath(
                createdPath,
            )

            closeCreateDialog(true)
        } catch (err) {
            createDialogError.value =
                err.message ||
                (
                    isFolder
                        ? 'Failed to create folder.'
                        : 'Failed to create Markdown file.'
                )
        } finally {
            createDialogSubmitting.value = false
        }
    }

    /*
     * Search history.
     */
    function loadSearchHistory() {
        try {
            const stored =
                window.localStorage.getItem(
                    searchHistoryStorageKey,
                )

            const parsed =
                stored
                    ? JSON.parse(stored)
                    : []

            searchHistory.value =
                Array.isArray(parsed)
                    ? parsed
                        .filter(
                            (item) =>
                                typeof item === 'string' &&
                                item.trim() !== '',
                        )
                        .slice(
                            0,
                            maxSearchHistory,
                        )
                    : []
        } catch (err) {
            searchHistory.value = []
        }
    }

    function saveSearchHistory() {
        try {
            window.localStorage.setItem(
                searchHistoryStorageKey,
                JSON.stringify(
                    searchHistory.value,
                ),
            )
        } catch (err) {
            // Ignore localStorage errors.
        }
    }

    function addSearchToHistory(query) {
        const normalizedQuery =
            query.trim()

        if (!normalizedQuery) {
            return
        }

        const next =
            searchHistory.value.filter(
                (item) =>
                    item.toLowerCase() !==
                    normalizedQuery.toLowerCase(),
            )

        next.unshift(normalizedQuery)

        searchHistory.value =
            next.slice(
                0,
                maxSearchHistory,
            )

        saveSearchHistory()
    }

    function removeSearchHistoryItem(
        query,
    ) {
        searchHistory.value =
            searchHistory.value.filter(
                (item) => item !== query,
            )

        saveSearchHistory()
    }

    function clearSearchHistory() {
        searchHistory.value = []

        saveSearchHistory()
    }

    function handleSearchFocus() {
        if (!searchQuery.value.trim()) {
            searchHistoryVisible.value = true
        }
    }

    function selectSearchHistory(query) {
        clearTreeDragExpandTimeout()

        if (searchTimeout !== null) {
            window.clearTimeout(searchTimeout)
            searchTimeout = null
        }

        searchHistoryVisible.value = false
        searchQuery.value = query
    }

    function handleDocumentClick(event) {
        const container =
            searchContainer.value

        if (
            !container ||
            container.contains(event.target)
        ) {
            return
        }

        searchHistoryVisible.value = false
        createMenuVisible.value = false
        diagramMenuVisible.value = false

        if (
            contextMenuVisible.value &&
            contextMenuElement.value &&
            !contextMenuElement.value.contains(
                event.target,
            )
        ) {
            closeContextMenu()
        }
    }

    function handleGlobalKeydown(event) {
        if (event.key === 'Escape') {
            if (diagramMenuVisible.value) {
                event.preventDefault()
                diagramMenuVisible.value = false
            }

            if (contextMenuVisible.value) {
                event.preventDefault()
                closeContextMenu()
            }
        }
    }

    function handleViewportChange() {
        if (!contextMenuVisible.value) {
            return
        }

        positionContextMenu()
    }

    /*
     * Search.
     */
    function scheduleSearch() {
        if (searchTimeout !== null) {
            window.clearTimeout(searchTimeout)
            searchTimeout = null
        }

        if (!searchQuery.value.trim()) {
            searchResults.value = []
            searchLoading.value = false
            searchError.value = ''
            return
        }

        searchHistoryVisible.value = false
        searchLoading.value = true

        searchTimeout = window.setTimeout(() => {
            searchTimeout = null
            searchWiki()
        }, 250)
    }

    async function searchWiki() {
        const query = searchQuery.value.trim()

        if (!query) {
            searchResults.value = []
            searchLoading.value = false
            searchError.value = ''
            return
        }

        if (!wikiRoot.value) {
            searchResults.value = []
            searchLoading.value = false
            searchError.value =
                'Wiki Root is not configured.'
            return
        }

        const requestId = ++searchRequestId

        searchLoading.value = true
        searchError.value = ''

        try {
            const url = new URL(
                OC.generateUrl(
                    '/apps/markdown_wiki/api/search',
                ),
                window.location.origin,
            )

            url.searchParams.set(
                'query',
                query,
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
                        'Failed to search Wiki.',
                )
            }

            if (requestId !== searchRequestId) {
                return
            }

            searchResults.value =
                Array.isArray(data.results)
                    ? data.results
                    : []

            addSearchToHistory(query)
        } catch (err) {
            if (requestId !== searchRequestId) {
                return
            }

            searchResults.value = []
            searchError.value =
                err.message ||
                'Failed to search Wiki.'
        } finally {
            if (requestId === searchRequestId) {
                searchLoading.value = false
            }
        }
    }

    function clearSearch() {
        if (searchTimeout !== null) {
            window.clearTimeout(searchTimeout)
            searchTimeout = null
        }

        if (operationMessageTimeout !== null) {
            window.clearTimeout(
                operationMessageTimeout,
            )
            operationMessageTimeout = null
        }

        searchRequestId++

        searchQuery.value = ''
        searchResults.value = []
        searchLoading.value = false
        searchError.value = ''
        searchHistoryVisible.value = false
    }

    async function openSearchResult(result) {
        if (!result || !result.path) {
            return
        }

        const resultPath =
            normalizePath(result.path)

        clearSearch()
        await openFile(resultPath)

        /*
         * openFile() already expands the full path and reveals the
         * selected file. Search navigation gets one extra pass so the
         * result is positioned clearly inside the visible tree rather
         * than merely touching its top/bottom edge.
         */
        await scrollTreePathIntoView(
            resultPath,
            {
                centerVertically: true,
            },
        )
    }

    function getSearchResultDirectory(path) {
        const normalizedPath =
            normalizePath(path)

        const root =
            normalizePath(
                wikiRoot.value,
            )

        const parent =
            getParentPath(
                normalizedPath,
            )

        if (parent === root) {
            return '.'
        }

        if (
            parent.startsWith(
                root + '/',
            )
        ) {
            return parent.slice(
                root.length + 1,
            )
        }

        return parent
    }

    /*
     * Ask whether it is safe to leave the current
     * document using an integrated application dialog.
     */
    const unsavedChangesDialogVisible = ref(false)
    const unsavedChangesDialogElement = ref(null)
    let unsavedChangesDialogResolve = null

    async function confirmDiscardChanges() {
        if (!editing.value || !isDirty.value) {
            return true
        }

        if (unsavedChangesDialogVisible.value) {
            return false
        }

        unsavedChangesDialogVisible.value = true

        const result = await new Promise((resolve) => {
            unsavedChangesDialogResolve = resolve

            nextTick(() => {
                unsavedChangesDialogElement.value?.focus()
            })
        })

        return result
    }

    function resolveUnsavedChanges(discard) {
        if (!unsavedChangesDialogVisible.value) {
            return
        }

        unsavedChangesDialogVisible.value = false

        const resolve = unsavedChangesDialogResolve
        unsavedChangesDialogResolve = null

        if (resolve) {
            resolve(Boolean(discard))
        }
    }

    async function openFolder(path) {
        if (!(await confirmDiscardChanges())) {
            return
        }

        closeContextMenu()

        const normalizedPath =
            normalizePath(path)

        stopEditing()

        selectedFile.value = ''
        selectedResource.value = null
        markdown.value = ''
        editedMarkdown.value = ''
        fileError.value = ''
        saveError.value = ''
        copyError.value = ''
        copiedResourcePath.value = false

        currentFolder.value = normalizedPath
        currentFolderLoading.value = true

        try {
            if (
                normalizedPath ===
                normalizePath(wikiRoot.value)
            ) {
                const next = new Set(
                    expandedFolders.value,
                )

                next.add(normalizedPath)

                expandedFolders.value = next

                /*
                 * The root tree is already the root folder listing.
                 * Refresh only when it has not been loaded yet.
                 */
                if (tree.value.length === 0) {
                    await loadRootTree(false)
                }

                return
            }

            let node = findNode(
                tree.value,
                normalizedPath,
            )

            /*
             * Breadcrumb navigation can target a folder whose node is
             * not currently loaded in the tree. Expand/load the path
             * first so the central Folder View always has real items.
             */
            if (
                !node ||
                node.type !== 'folder'
            ) {
                await ensureTreePathLoaded(
                    normalizedPath,
                )

                node = findNode(
                    tree.value,
                    normalizedPath,
                )
            }

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
        } catch (err) {
            showOperationMessage(
                err.message ||
                    'Failed to load folder contents.',
                'error',
            )
        } finally {
            currentFolderLoading.value = false
        }
    }

    async function openFolderViewItem(item) {
        if (!item) {
            return
        }

        if (item.type === 'folder') {
            await openFolder(item.path)
            return
        }

        if (item.fileType === 'markdown') {
            await openFile(item.path)
            return
        }

        await selectResource(item)
    }

    async function selectResource(node) {
        if (!(await confirmDiscardChanges())) {
            return
        }

        closeContextMenu()

        stopEditing()

        selectedFile.value = ''
        previousMarkdownPath.value = ''
        nextMarkdownPath.value = ''
        markdown.value = ''
        editedMarkdown.value = ''
        fileError.value = ''
        saveError.value = ''
        copyError.value = ''
        copiedResourcePath.value = false

        selectedResource.value = {
            name: node.name,
            type: node.type,
            fileType: node.fileType,
            extension: node.extension,
            path: node.path,
        }

        currentFolder.value =
            getParentPath(node.path)

        await scrollTreePathIntoView(
            node.path,
        )
    }

    function collectMarkdownFilesFromLoadedTree(nodes = tree.value) {
        const paths = []

        const visit = (items) => {
            for (const item of items || []) {
                if (item.type === 'folder') {
                    visit(item.children)
                    continue
                }

                if (
                    item.type === 'file' &&
                    (
                        item.fileType === 'markdown' ||
                        item.path
                            .toLowerCase()
                            .endsWith('.md')
                    )
                ) {
                    paths.push(
                        normalizePath(item.path),
                    )
                }
            }
        }

        visit(nodes)

        return paths
    }

    function updateMarkdownNavigation(
        currentPath = selectedFile.value,
    ) {
        previousMarkdownPath.value = ''
        nextMarkdownPath.value = ''

        const root =
            normalizePath(wikiRoot.value)

        const normalizedCurrentPath =
            normalizePath(currentPath)

        if (
            !root ||
            !normalizedCurrentPath
        ) {
            return
        }

        try {
            const files =
                collectMarkdownFilesFromLoadedTree()

            const currentIndex =
                files.findIndex(
                    (path) =>
                        normalizePath(path) ===
                        normalizedCurrentPath,
                )

            if (currentIndex === -1) {
                return
            }

            previousMarkdownPath.value =
                currentIndex > 0
                    ? files[currentIndex - 1]
                    : ''

            nextMarkdownPath.value =
                currentIndex <
                files.length - 1
                    ? files[currentIndex + 1]
                    : ''
        } catch (err) {
            console.warn(
                'Failed to build Markdown navigation.',
                err,
            )
        }
    }

    async function navigateToAdjacentFile(path) {
        const targetPath =
            normalizePath(path)

        if (!targetPath) {
            return
        }

        const previousSelection =
            normalizePath(selectedFile.value)

        await openFile(targetPath)

        if (
            normalizePath(selectedFile.value) !==
            previousSelection &&
            normalizePath(selectedFile.value) ===
            targetPath
        ) {
            await nextTick()

            if (documentScrollElement.value) {
                documentScrollElement.value.scrollTop = 0
                documentScrollElement.value.scrollLeft = 0
            }
        }
    }

    async function openFile(path) {
        closeContextMenu()

        if (
            normalizePath(path) ===
            normalizePath(selectedFile.value)
        ) {
            return
        }

        if (!(await confirmDiscardChanges())) {
            return
        }

        stopEditing()

        const filePath =
            normalizePath(path)

        const node =
            findNode(
                tree.value,
                filePath,
            )

        if (
            node &&
            node.type === 'file' &&
            node.fileType !== 'markdown'
        ) {
            await selectResource(node)
            return
        }

        if (
            !filePath
                .toLowerCase()
                .endsWith('.md')
        ) {
            return
        }

        loadingFile.value = true
        fileError.value = ''
        saveError.value = ''

        selectedFile.value = filePath
        selectedResource.value = null
        markdown.value = ''
        editedMarkdown.value = ''

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

            editedMarkdown.value =
                markdown.value

            const parent =
                getParentPath(filePath)

            currentFolder.value = parent

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

            await scrollTreePathIntoView(
                filePath,
            )

            updateMarkdownNavigation(
                filePath,
            )
        } catch (err) {
            fileError.value =
                err.message
        } finally {
            loadingFile.value = false
        }
    }

    async function startEditing({
        preserveScroll = true,
    } = {}) {
        if (!selectedFile.value || loadingFile.value) {
            return
        }

        const documentScroll =
            documentScrollElement.value

        const previousScrollTop =
            documentScroll
                ? documentScroll.scrollTop
                : 0

        const previousScrollLeft =
            documentScroll
                ? documentScroll.scrollLeft
                : 0

        saveError.value = ''

        editedMarkdown.value =
            markdown.value

        selectedCodeLanguage.value = ''

        editorMode.value = 'split'
        editing.value = true

        await nextTick()

        await new Promise((resolve) => {
            window.requestAnimationFrame(resolve)
        })

        if (documentScrollElement.value) {
            if (preserveScroll) {
                documentScrollElement.value.scrollTop =
                    previousScrollTop
                documentScrollElement.value.scrollLeft =
                    previousScrollLeft
            } else {
                documentScrollElement.value.scrollTop = 0
                documentScrollElement.value.scrollLeft = 0
            }
        }

        if (editorTextarea.value) {
            editorTextarea.value.focus({
                preventScroll: true,
            })
        }
    }

    function stopEditing() {
        editing.value = false
        editedMarkdown.value = markdown.value
        saveError.value = ''
        selectedCodeLanguage.value = ''
    }

    async function cancelEditing() {
        if (!(await confirmDiscardChanges())) {
            return
        }

        stopEditing()
    }

    function getEditorElement() {
        const element = editorTextarea.value

        if (
            element instanceof HTMLTextAreaElement
        ) {
            return element
        }

        return null
    }

    function replaceEditorText(
        start,
        end,
        replacement,
        selectionStart = null,
        selectionEnd = null,
    ) {
        const textarea = getEditorElement()

        if (!textarea) {
            return
        }

        const value = editedMarkdown.value

        editedMarkdown.value =
            value.slice(0, start) +
            replacement +
            value.slice(end)

        nextTick(() => {
            const element = getEditorElement()

            if (!element) {
                return
            }

            element.focus()

            const nextStart =
                selectionStart !== null
                    ? selectionStart
                    : start + replacement.length

            const nextEnd =
                selectionEnd !== null
                    ? selectionEnd
                    : nextStart

            element.setSelectionRange(
                nextStart,
                nextEnd,
            )
        })
    }

    function applyInlineFormatting(
        prefix,
        suffix,
    ) {
        const textarea = getEditorElement()

        if (!textarea) {
            return
        }

        const start = textarea.selectionStart
        const end = textarea.selectionEnd
        const value = editedMarkdown.value

        const selectedText =
            value.slice(start, end)

        if (
            selectedText.length >=
                prefix.length +
                    suffix.length &&
            selectedText.startsWith(prefix) &&
            selectedText.endsWith(suffix)
        ) {
            const unformatted =
                selectedText.slice(
                    prefix.length,
                    selectedText.length -
                        suffix.length,
                )

            replaceEditorText(
                start,
                end,
                unformatted,
                start,
                start + unformatted.length,
            )

            return
        }

        if (!selectedText) {
            const placeholder =
                prefix === '**'
                    ? 'bold text'
                    : prefix === '*'
                        ? 'italic text'
                        : prefix === '~~'
                            ? 'strikethrough text'
                            : 'code'

            const replacement =
                prefix +
                placeholder +
                suffix

            const placeholderStart =
                start + prefix.length

            const placeholderEnd =
                placeholderStart +
                placeholder.length

            replaceEditorText(
                start,
                end,
                replacement,
                placeholderStart,
                placeholderEnd,
            )

            return
        }

        const replacement =
            prefix +
            selectedText +
            suffix

        replaceEditorText(
            start,
            end,
            replacement,
            start,
            start + replacement.length,
        )
    }

    function getSelectedLineRange() {
        const textarea = getEditorElement()

        if (!textarea) {
            return null
        }

        const value = editedMarkdown.value
        const start = textarea.selectionStart
        const end = textarea.selectionEnd

        const lineStart =
            value.lastIndexOf(
                '\n',
                Math.max(0, start - 1),
            ) + 1

        let lineEnd =
            value.indexOf(
                '\n',
                end,
            )

        if (lineEnd === -1) {
            lineEnd = value.length
        }

        return {
            start,
            end,
            lineStart,
            lineEnd,
            text: value.slice(
                lineStart,
                lineEnd,
            ),
        }
    }

    function applyLinePrefix(prefix) {
        const range = getSelectedLineRange()

        if (!range) {
            return
        }

        const lines =
            range.text.split('\n')

        const replacement =
            lines
                .map((line) => {
                    const content =
                        line.replace(
                            /^#{1,6}\s+/,
                            '',
                        )

                    if (!content.trim()) {
                        return prefix.trimEnd()
                    }

                    return prefix + content
                })
                .join('\n')

        replaceEditorText(
            range.lineStart,
            range.lineEnd,
            replacement,
            range.lineStart,
            range.lineStart +
                replacement.length,
        )
    }

    function applyBulletList() {
        const range = getSelectedLineRange()

        if (!range) {
            return
        }

        const lines =
            range.text.split('\n')

        const replacement =
            lines
                .map((line) => {
                    const content =
                        line.replace(
                            /^\s*(?:[-*+]\s+(?:\[[ xX]\]\s*)?|\d+\.\s+|>\s+)/,
                            '',
                        )

                    return `- ${content}`
                })
                .join('\n')

        replaceEditorText(
            range.lineStart,
            range.lineEnd,
            replacement,
            range.lineStart,
            range.lineStart +
                replacement.length,
        )
    }

    function applyOrderedList() {
        const range = getSelectedLineRange()

        if (!range) {
            return
        }

        const lines =
            range.text.split('\n')

        const replacement =
            lines
                .map((line, index) => {
                    const content =
                        line.replace(
                            /^\s*(?:[-*+]\s+(?:\[[ xX]\]\s*)?|\d+\.\s+|>\s+)/,
                            '',
                        )

                    return `${index + 1}. ${content}`
                })
                .join('\n')

        replaceEditorText(
            range.lineStart,
            range.lineEnd,
            replacement,
            range.lineStart,
            range.lineStart +
                replacement.length,
        )
    }

    function applyChecklist() {
        const range = getSelectedLineRange()

        if (!range) {
            return
        }

        const lines =
            range.text.split('\n')

        const hasChecklist =
            lines.length > 0 &&
            lines.every((line) =>
                /^\s*[-*+]\s+\[[ xX]\]\s+/.test(
                    line,
                ),
            )

        const replacement =
            lines
                .map((line) => {
                    const checklistMatch =
                        line.match(
                            /^\s*[-*+]\s+\[([ xX])\]\s*(.*)$/,
                        )

                    if (checklistMatch) {
                        const checked =
                            checklistMatch[1]
                                .toLowerCase() ===
                            'x'

                        if (hasChecklist) {
                            return (
                                checked
                                    ? '- [ ] '
                                    : '- [x] '
                            ) +
                                checklistMatch[2]
                        }

                        return (
                            `- [${checked ? 'x' : ' '}] ` +
                            checklistMatch[2]
                        )
                    }

                    const content =
                        line.replace(
                            /^\s*(?:[-*+]\s+|\d+\.\s+|>\s+)/,
                            '',
                        )

                    return `- [ ] ${content}`
                })
                .join('\n')

        replaceEditorText(
            range.lineStart,
            range.lineEnd,
            replacement,
            range.lineStart,
            range.lineStart +
                replacement.length,
        )
    }

    function applyBlockquote() {
        const range = getSelectedLineRange()

        if (!range) {
            return
        }

        const lines =
            range.text.split('\n')

        const allQuoted =
            lines.every((line) =>
                /^\s*>\s?/.test(line),
            )

        const replacement =
            lines
                .map((line) => {
                    if (allQuoted) {
                        return line.replace(
                            /^\s*>\s?/,
                            '',
                        )
                    }

                    return `> ${line}`
                })
                .join('\n')

        replaceEditorText(
            range.lineStart,
            range.lineEnd,
            replacement,
            range.lineStart,
            range.lineStart +
                replacement.length,
        )
    }

    function insertMermaidDiagram(type) {
        const textarea = getEditorElement()

        if (!textarea) {
            diagramMenuVisible.value = false
            return
        }

        const start = textarea.selectionStart
        const end = textarea.selectionEnd
        const value = editedMarkdown.value

        const templates = {
            flowchart: {
                content:
                    '```mermaid\n' +
                    'flowchart TD\n' +
                    '    A[Start] --> B[Next step]\n' +
                    '```\n',
                selection: 'A[Start] --> B[Next step]',
            },
            sequence: {
                content:
                    '```mermaid\n' +
                    'sequenceDiagram\n' +
                    '    participant A as User\n' +
                    '    participant B as Service\n' +
                    '    A->>B: Request\n' +
                    '    B-->>A: Response\n' +
                    '```\n',
                selection: 'A->>B: Request',
            },
            class: {
                content:
                    '```mermaid\n' +
                    'classDiagram\n' +
                    '    class Example {\n' +
                    '        +String name\n' +
                    '        +run()\n' +
                    '    }\n' +
                    '```\n',
                selection: 'Example',
            },
            state: {
                content:
                    '```mermaid\n' +
                    'stateDiagram-v2\n' +
                    '    [*] --> Ready\n' +
                    '    Ready --> Running\n' +
                    '    Running --> [*]\n' +
                    '```\n',
                selection: 'Ready --> Running',
            },
        }

        const template =
            templates[type]

        if (!template) {
            diagramMenuVisible.value = false
            return
        }

        const needsLeadingNewline =
            start > 0 &&
            value[start - 1] !== '\n'

        const needsTrailingNewline =
            end < value.length &&
            value[end] !== '\n'

        const prefix =
            needsLeadingNewline
                ? '\n\n'
                : ''

        const suffix =
            needsTrailingNewline
                ? '\n'
                : ''

        const replacement =
            prefix +
            template.content +
            suffix

        const selectionOffset =
            replacement.indexOf(
                template.selection,
            )

        const selectionStart =
            start + selectionOffset

        const selectionEnd =
            selectionStart +
            template.selection.length

        diagramMenuVisible.value = false

        replaceEditorText(
            start,
            end,
            replacement,
            selectionStart,
            selectionEnd,
        )
    }

    function insertLink() {
        const textarea = getEditorElement()

        if (!textarea) {
            return
        }

        const start = textarea.selectionStart
        const end = textarea.selectionEnd

        const selectedText =
            editedMarkdown.value.slice(
                start,
                end,
            )

        const label =
            selectedText || 'link text'

        const replacement =
            `[${label}](https://)`

        const labelStart =
            start + 1

        const labelEnd =
            labelStart + label.length

        replaceEditorText(
            start,
            end,
            replacement,
            labelStart,
            labelEnd,
        )
    }

    function insertImage() {
        const textarea = getEditorElement()

        if (!textarea) {
            return
        }

        const start = textarea.selectionStart
        const end = textarea.selectionEnd

        const selectedText =
            editedMarkdown.value.slice(
                start,
                end,
            )

        const altText =
            selectedText || 'image'

        const replacement =
            `![${altText}](path/to/image.png)`

        const altStart =
            start + 2

        const altEnd =
            altStart + altText.length

        replaceEditorText(
            start,
            end,
            replacement,
            altStart,
            altEnd,
        )
    }

    function applyCodeBlock() {
        const textarea = getEditorElement()

        if (!textarea) {
            return
        }

        const start = textarea.selectionStart
        const end = textarea.selectionEnd

        const selectedText =
            editedMarkdown.value.slice(
                start,
                end,
            )

        const language =
            selectedCodeLanguage.value

        const openingFence =
            language
                ? `\`\`\`${language}`
                : '```'

        if (selectedText) {
            const replacement =
                `${openingFence}\n` +
                `${selectedText}\n` +
                '```'

            replaceEditorText(
                start,
                end,
                replacement,
                start,
                start + replacement.length,
            )

            return
        }

        const placeholder =
            'code'

        const replacement =
            `${openingFence}\n` +
            `${placeholder}\n` +
            '```'

        const placeholderStart =
            start +
            openingFence.length +
            1

        const placeholderEnd =
            placeholderStart +
            placeholder.length

        replaceEditorText(
            start,
            end,
            replacement,
            placeholderStart,
            placeholderEnd,
        )
    }

    function insertHorizontalRule() {
        const textarea = getEditorElement()

        if (!textarea) {
            return
        }

        const start = textarea.selectionStart
        const end = textarea.selectionEnd

        const before =
            editedMarkdown.value.slice(
                0,
                start,
            )

        const after =
            editedMarkdown.value.slice(
                end,
            )

        const prefix =
            before.length > 0 &&
            !before.endsWith('\n\n')
                ? '\n\n'
                : ''

        const suffix =
            after.length > 0 &&
            !after.startsWith('\n\n')
                ? '\n\n'
                : ''

        const replacement =
            prefix +
            '---' +
            suffix

        const cursor =
            start + replacement.length

        replaceEditorText(
            start,
            end,
            replacement,
            cursor,
            cursor,
        )
    }

    function handleEditorKeydown(event) {
        const modifier =
            event.ctrlKey ||
            event.metaKey

        if (
            modifier &&
            event.key.toLowerCase() === 's'
        ) {
            event.preventDefault()
            saveFile()

            return
        }

        if (
            modifier &&
            !event.shiftKey &&
            event.key.toLowerCase() === 'b'
        ) {
            event.preventDefault()

            applyInlineFormatting(
                '**',
                '**',
            )

            return
        }

        if (
            modifier &&
            !event.shiftKey &&
            event.key.toLowerCase() === 'i'
        ) {
            event.preventDefault()

            applyInlineFormatting(
                '*',
                '*',
            )

            return
        }

        if (
            modifier &&
            !event.shiftKey &&
            event.key.toLowerCase() === 'k'
        ) {
            event.preventDefault()

            insertLink()
        }
    }

    async function saveFile() {
        if (
            !selectedFile.value ||
            savingFile.value ||
            !isDirty.value
        ) {
            return
        }

        savingFile.value = true
        saveError.value = ''

        try {
            const body =
                new URLSearchParams()

            body.set(
                'path',
                selectedFile.value,
            )

            body.set(
                'content',
                editedMarkdown.value,
            )

            const response = await fetch(
                OC.generateUrl(
                    '/apps/markdown_wiki/api/save-file',
                ),
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type':
                            'application/x-www-form-urlencoded;charset=UTF-8',
                    },
                    body,
                },
            )

            const data =
                await response.json()

            if (!response.ok) {
                throw new Error(
                    data.message ||
                        'Failed to save Markdown file.',
                )
            }

            markdown.value =
                editedMarkdown.value

            editedMarkdown.value =
                markdown.value

            saveError.value = ''
            selectedCodeLanguage.value = ''
        } catch (err) {
            saveError.value =
                err.message ||
                'Failed to save Markdown file.'
        } finally {
            savingFile.value = false
        }
    }

    function handleBeforeUnload(event) {
        if (!isDirty.value) {
            return
        }

        event.preventDefault()
        event.returnValue = ''
    }

    function getResourceRelativePath(path) {
        const normalizedPath =
            normalizePath(path)

        const root =
            normalizePath(
                wikiRoot.value,
            )

        if (
            normalizedPath === root
        ) {
            return '.'
        }

        if (
            normalizedPath.startsWith(
                root + '/',
            )
        ) {
            return normalizedPath
                .slice(root.length + 1)
        }

        return normalizedPath
    }

    function getRelativePath(
        fromDirectory,
        targetPath,
    ) {
        const fromParts =
            normalizePath(
                fromDirectory,
            )
                .split('/')
                .filter(Boolean)

        const targetParts =
            normalizePath(
                targetPath,
            )
                .split('/')
                .filter(Boolean)

        let commonLength = 0

        while (
            commonLength <
                fromParts.length &&
            commonLength <
                targetParts.length &&
            fromParts[commonLength] ===
                targetParts[commonLength]
        ) {
            commonLength++
        }

        const upCount =
            fromParts.length -
            commonLength

        const relativeParts = []

        for (
            let index = 0;
            index < upCount;
            index++
        ) {
            relativeParts.push('..')
        }

        relativeParts.push(
            ...targetParts.slice(
                commonLength,
            ),
        )

        return (
            relativeParts.join('/') ||
            '.'
        )
    }

    function getResourceCopyPath(path) {
        if (selectedFile.value) {
            return getRelativePath(
                getParentPath(
                    selectedFile.value,
                ),
                path,
            )
        }

        return getResourceRelativePath(
            path,
        )
    }

    async function copyResourceRelativePath(
        path,
    ) {
        copyError.value = ''
        copiedResourcePath.value = false

        const relativePath =
            getResourceCopyPath(path)

        try {
            await navigator.clipboard.writeText(
                relativePath,
            )

            copiedResourcePath.value = true

            window.setTimeout(() => {
                copiedResourcePath.value = false
            }, 1800)
        } catch (err) {
            copyError.value =
                'Failed to copy path.'
        }
    }

    function resolveRelativePath(path) {
        if (!selectedFile.value) {
            return null
        }

        const currentDirectory =
            getParentPath(
                selectedFile.value,
            )

        const resolvedParts =
            currentDirectory
                .split('/')
                .filter(Boolean)

        const linkParts =
            path.split('/')

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

        return '/' +
            resolvedParts.join('/')
    }

    function isInsideWikiRoot(path) {
        const normalizedPath =
            normalizePath(path)

        const root =
            normalizePath(
                wikiRoot.value,
            )

        return (
            normalizedPath === root ||
            normalizedPath.startsWith(
                root + '/',
            )
        )
    }

    function buildDavUrl(path) {
        if (
            !path ||
            !OC.currentUser
        ) {
            return null
        }

        const normalizedPath =
            normalizePath(path)

        if (
            !isInsideWikiRoot(
                normalizedPath,
            )
        ) {
            return null
        }

        const encodedPath =
            normalizedPath
                .split('/')
                .filter(Boolean)
                .map((part) =>
                    encodeURIComponent(
                        part,
                    ),
                )
                .join('/')

        return (
            OC.generateUrl(
                '/remote.php/dav/files/' +
                    encodeURIComponent(
                        OC.currentUser,
                    ),
            ) +
            '/' +
            encodedPath
        )
    }

    function resolveMarkdownImages(html) {
        if (!html) {
            return html
        }

        const parser =
            new DOMParser()

        const document =
            parser.parseFromString(
                html,
                'text/html',
            )

        const images =
            document.querySelectorAll(
                'img',
            )

        images.forEach((image) => {
            const source =
                image.getAttribute(
                    'src',
                )

            if (!source) {
                return
            }

            if (
                source.startsWith(
                    'http://',
                ) ||
                source.startsWith(
                    'https://',
                ) ||
                source.startsWith(
                    'data:',
                ) ||
                source.startsWith(
                    'blob:',
                )
            ) {
                return
            }

            if (
                source.startsWith('//')
            ) {
                return
            }

            const match =
                source.match(
                    /^([^?#]*)([?#].*)?$/,
                )

            const sourcePath =
                match
                    ? match[1]
                    : source

            const suffix =
                match && match[2]
                    ? match[2]
                    : ''

            let resolvedPath

            if (
                sourcePath.startsWith('/')
            ) {
                const root =
                    normalizePath(
                        wikiRoot.value,
                    )

                resolvedPath =
                    normalizePath(
                        root +
                            '/' +
                            sourcePath
                                .split('/')
                                .filter(Boolean)
                                .join('/'),
                    )
            } else {
                resolvedPath =
                    resolveRelativePath(
                        sourcePath,
                    )
            }

            if (
                !resolvedPath ||
                !isInsideWikiRoot(
                    resolvedPath,
                )
            ) {
                return
            }

            const davUrl =
                buildDavUrl(
                    resolvedPath,
                )

            if (!davUrl) {
                return
            }

            image.setAttribute(
                'src',
                davUrl + suffix,
            )

            image.setAttribute(
                'loading',
                'lazy',
            )

            image.setAttribute(
                'decoding',
                'async',
            )
        })

        return document.body.innerHTML
    }

    async function handleMarkdownClick(event) {
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

        if (
            href.startsWith('http://') ||
            href.startsWith('https://') ||
            href.startsWith('mailto:') ||
            href.startsWith('tel:') ||
            href.startsWith('//')
        ) {
            return
        }

        const hashIndex =
            href.indexOf('#')

        const rawPath =
            hashIndex >= 0
                ? href.slice(0, hashIndex)
                : href

        const rawHash =
            hashIndex >= 0
                ? href.slice(hashIndex + 1)
                : ''

        let linkPath = rawPath
        let headingId = rawHash

        try {
            linkPath = decodeURIComponent(rawPath)
        } catch {
            linkPath = rawPath
        }

        try {
            headingId = decodeURIComponent(rawHash)
        } catch {
            headingId = rawHash
        }

        if (!linkPath) {
            if (!headingId) {
                return
            }

            event.preventDefault()

            await scrollToHeading(
                headingId,
            )

            return
        }

        if (
            !linkPath
                .toLowerCase()
                .endsWith('.md')
        ) {
            return
        }

        event.preventDefault()

        if (!selectedFile.value) {
            return
        }

        let resolvedPath = null

        if (linkPath.startsWith('/')) {
            const root =
                normalizePath(
                    wikiRoot.value,
                )

            const rootRelativePath =
                linkPath
                    .replace(/^\/+/, '')

            resolvedPath =
                normalizePath(
                    root + '/' +
                    rootRelativePath,
                )
        } else {
            resolvedPath =
                resolveRelativePath(
                    linkPath,
                )
        }

        if (
            !resolvedPath ||
            !isInsideWikiRoot(
                resolvedPath,
            )
        ) {
            return
        }

        const currentPath =
            normalizePath(
                selectedFile.value,
            )

        const navigatedToAnotherFile =
            normalizePath(resolvedPath) !==
            currentPath

        if (navigatedToAnotherFile) {
            await openFile(resolvedPath)

            /*
             * Mermaid diagrams can substantially change the document
             * height after the Markdown article first appears. If we
             * scroll to an anchor before Mermaid finishes rendering,
             * the target is pushed down afterwards and the page appears
             * not to have scrolled. Wait for the final diagram layout
             * before applying a cross-file anchor.
             */
            await nextTick()
            await renderMermaidDiagrams()

            await new Promise((resolve) => {
                window.requestAnimationFrame(() => {
                    window.requestAnimationFrame(
                        resolve,
                    )
                })
            })
        }

        if (headingId) {
            await scrollToHeading(
                headingId,
            )
        }
    }

    function createHeadingSlug(text) {
        return String(text || '')
            .trim()
            .toLowerCase()
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/<[^>]*>/g, '')
            .replace(/&[a-z0-9#]+;/gi, '')
            .replace(/[^\p{L}\p{N}\s-]/gu, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '') ||
            'section'
    }

    function extractTableOfContents(content) {
        if (!content) {
            return []
        }

        const headings = []
        const slugCounts = new Map()
        const lines = String(content).split(/\r?\n/)
        let fence = ''

        for (const line of lines) {
            const fenceMatch =
                line.match(/^\s*(`{3,}|~{3,})/)

            if (fenceMatch) {
                const marker =
                    fenceMatch[1][0]

                if (!fence) {
                    fence = marker
                } else if (
                    marker === fence
                ) {
                    fence = ''
                }

                continue
            }

            if (fence) {
                continue
            }

            const match =
                line.match(
                    /^\s{0,3}(#{1,3})\s+(.+?)\s*#*\s*$/,
                )

            if (!match) {
                continue
            }

            const level =
                match[1].length

            const text =
                match[2]
                    .replace(
                        /!\[([^\]]*)\]\([^)]*\)/g,
                        '$1',
                    )
                    .replace(
                        /\[([^\]]+)\]\([^)]*\)/g,
                        '$1',
                    )
                    .replace(
                        /[*_~`]/g,
                        '',
                    )
                    .trim()

            if (!text) {
                continue
            }

            const baseSlug =
                createHeadingSlug(text)

            const count =
                slugCounts.get(baseSlug) || 0

            slugCounts.set(
                baseSlug,
                count + 1,
            )

            headings.push({
                level,
                text,
                id:
                    count === 0
                        ? baseSlug
                        : `${baseSlug}-${count + 1}`,
            })
        }

        return headings
    }

    function getHeadingIdFactory() {
        const slugCounts = new Map()

        return (text) => {
            const baseSlug =
                createHeadingSlug(text)

            const count =
                slugCounts.get(baseSlug) || 0

            slugCounts.set(
                baseSlug,
                count + 1,
            )

            return count === 0
                ? baseSlug
                : `${baseSlug}-${count + 1}`
        }
    }

    function renderMarkdown(content) {
        if (!content) {
            return ''
        }

        const renderer = new marked.Renderer()
        const getHeadingId =
            getHeadingIdFactory()

        renderer.heading = ({
            tokens,
            depth,
        }) => {
            const headingText =
                renderer.parser.parseInline(
                    tokens,
                )

            const plainText =
                tokens
                    .map(token => token.text || '')
                    .join('')

            const id =
                getHeadingId(plainText)

            return (
                `<h${depth} id="${id}">` +
                headingText +
                `</h${depth}>`
            )
        }

        renderer.code = (token) => {
            const text =
                typeof token === 'string'
                    ? token
                    : token?.text || ''

            const lang =
                typeof token === 'object'
                    ? (
                        token?.lang ||
                        token?.language ||
                        ''
                    )
                    : ''

            const language =
                lang
                    ? String(lang)
                        .trim()
                        .split(/\s+/)[0]
                        .toLowerCase()
                    : ''

            if (language === 'mermaid') {
                return (
                    '<div class="mermaid-diagram" ' +
                    `data-mermaid-source="${encodeURIComponent(text)}">` +
                    '<div class="mermaid-diagram-loading">Rendering diagram…</div>' +
                    '</div>'
                )
            }

            let highlightedCode

            if (
                language &&
                hljs.getLanguage(language)
            ) {
                highlightedCode =
                    hljs.highlight(
                        text,
                        {
                            language,
                            ignoreIllegals: true,
                        },
                    ).value
            } else {
                highlightedCode =
                    hljs.highlightAuto(
                        text,
                    ).value
            }

            const languageClass =
                language
                    ? ` language-${language}`
                    : ''

            return (
                `<pre><code class="hljs${languageClass}">` +
                highlightedCode +
                '</code></pre>'
            )
        }

        const html =
            marked.parse(
                content,
                {
                    renderer,
                },
            )

        const htmlWithImages =
            resolveMarkdownImages(
                html,
            )

        return DOMPurify.sanitize(
            htmlWithImages,
            {
                ADD_ATTR: [
                    'data-mermaid-source',
                ],
            },
        )
    }

    function isDarkNextcloudTheme() {
        const target =
            document.querySelector(
                '#markdown-wiki-app .markdown-wiki',
            ) ||
            document.querySelector(
                '#markdown-wiki-app',
            ) ||
            document.body

        const background =
            window
                .getComputedStyle(target)
                .backgroundColor

        const channels =
            background
                .match(/[\d.]+/g)
                ?.slice(0, 3)
                .map(Number)

        if (!channels || channels.length < 3) {
            return window.matchMedia(
                '(prefers-color-scheme: dark)',
            ).matches
        }

        const [red, green, blue] =
            channels

        const luminance =
            (
                0.2126 * red +
                0.7152 * green +
                0.0722 * blue
            ) / 255

        return luminance < 0.5
    }

    let mermaidRenderGeneration = 0

    async function renderMermaidDiagrams() {
        await nextTick()

        await new Promise((resolve) => {
            window.requestAnimationFrame(() => {
                window.requestAnimationFrame(resolve)
            })
        })

        const generation =
            ++mermaidRenderGeneration

        const containers =
            document.querySelectorAll(
                '#markdown-wiki-app .mermaid-diagram[data-mermaid-source]',
            )

        if (!containers.length) {
            return
        }

        const darkTheme =
            isDarkNextcloudTheme()

        mermaid.initialize({
            startOnLoad: false,
            securityLevel: 'strict',
            theme: darkTheme
                ? 'dark'
                : 'default',
            suppressErrorRendering: true,
            themeVariables: darkTheme
                ? {
                    darkMode: true,
                    background: '#1e1e1e',
                    primaryColor: '#2c2c2c',
                    primaryTextColor: '#f2f2f2',
                    primaryBorderColor: '#8c7cff',
                    lineColor: '#c8c8c8',
                    secondaryColor: '#252525',
                    secondaryTextColor: '#f2f2f2',
                    tertiaryColor: '#303030',
                    tertiaryTextColor: '#f2f2f2',
                    textColor: '#f2f2f2',
                    actorBkg: '#2c2c2c',
                    actorBorder: '#8c7cff',
                    actorTextColor: '#f2f2f2',
                    actorLineColor: '#8c7cff',
                    signalColor: '#c8c8c8',
                    signalTextColor: '#f2f2f2',
                    labelBoxBkgColor: '#252525',
                    labelBoxBorderColor: '#666666',
                    labelTextColor: '#f2f2f2',
                    loopTextColor: '#f2f2f2',
                    noteBkgColor: '#333333',
                    noteBorderColor: '#777777',
                    noteTextColor: '#f2f2f2',
                }
                : {
                    darkMode: false,
                    background: '#ffffff',
                    primaryColor: '#f4f6f8',
                    primaryTextColor: '#1f1f1f',
                    primaryBorderColor: '#6b7280',
                    lineColor: '#4b5563',
                    secondaryColor: '#eef2f6',
                    secondaryTextColor: '#1f1f1f',
                    tertiaryColor: '#ffffff',
                    tertiaryTextColor: '#1f1f1f',
                    textColor: '#1f1f1f',
                    actorBkg: '#f4f6f8',
                    actorBorder: '#6b7280',
                    actorTextColor: '#1f1f1f',
                    actorLineColor: '#6b7280',
                    signalColor: '#4b5563',
                    signalTextColor: '#1f1f1f',
                    labelBoxBkgColor: '#ffffff',
                    labelBoxBorderColor: '#9ca3af',
                    labelTextColor: '#1f1f1f',
                    loopTextColor: '#1f1f1f',
                    noteBkgColor: '#fff8d8',
                    noteBorderColor: '#b8a45a',
                    noteTextColor: '#1f1f1f',
                },
        })

        let index = 0

        for (const container of containers) {
            if (
                generation !==
                mermaidRenderGeneration
            ) {
                return
            }

            const encodedSource =
                container.getAttribute(
                    'data-mermaid-source',
                )

            if (!encodedSource) {
                continue
            }

            let source = ''

            try {
                source =
                    decodeURIComponent(
                        encodedSource,
                    )
            } catch {
                source = encodedSource
            }

            try {
                const id =
                    `markdown-wiki-mermaid-${generation}-${index++}`

                const { svg } =
                    await mermaid.render(
                        id,
                        source,
                    )

                if (
                    generation !==
                    mermaidRenderGeneration
                ) {
                    return
                }

                container.innerHTML = svg
                container.classList.remove(
                    'mermaid-diagram-error',
                )
            } catch (err) {
                console.warn(
                    'Failed to render Mermaid diagram.',
                    err,
                )

                container.classList.add(
                    'mermaid-diagram-error',
                )

                container.textContent =
                    'Unable to render Mermaid diagram.'
            }
        }
    }

    function getVisibleMarkdownContent() {
        const container =
            documentScrollElement.value

        if (!container) {
            return null
        }

        const candidates =
            [...container.querySelectorAll(
                '.markdown-content',
            )]

        return candidates.find((element) => {
            const style =
                window.getComputedStyle(
                    element,
                )

            return (
                style.display !== 'none' &&
                style.visibility !== 'hidden'
            )
        }) || null
    }

    function updateActiveHeading() {
        if (!showTableOfContents.value) {
            activeHeadingId.value = ''
            return
        }

        const container =
            documentScrollElement.value

        const content =
            getVisibleMarkdownContent()

        if (
            !container ||
            !content
        ) {
            return
        }

        const headings =
            [...content.querySelectorAll(
                'h1[id], h2[id], h3[id]',
            )]

        if (!headings.length) {
            activeHeadingId.value = ''
            return
        }

        const containerTop =
            container.getBoundingClientRect().top

        const activationLine =
            containerTop + 48

        let active =
            headings[0]

        for (const heading of headings) {
            if (
                heading.getBoundingClientRect().top <=
                activationLine
            ) {
                active = heading
            } else {
                break
            }
        }

        activeHeadingId.value =
            active.id
    }

    async function scrollToHeading(id) {
        if (!id) {
            return false
        }

        let decodedId = id

        try {
            decodedId =
                decodeURIComponent(id)
        } catch {
            decodedId = id
        }

        const candidateIds =
            [
                decodedId,
                createHeadingSlug(decodedId),
            ].filter(
                (value, index, values) =>
                    value &&
                    values.indexOf(value) === index,
            )

        /*
         * Cross-file navigation can finish loading before Vue has
         * committed the new article and its final layout to the DOM.
         * Retry until the real target heading exists.
         */
        for (
            let attempt = 0;
            attempt < 30;
            attempt++
        ) {
            await nextTick()

            await new Promise((resolve) => {
                window.requestAnimationFrame(
                    resolve,
                )
            })

            const container =
                documentScrollElement.value

            const content =
                getVisibleMarkdownContent()

            if (
                !container ||
                !content
            ) {
                continue
            }

            const heading =
                [...content.querySelectorAll(
                    'h1[id], h2[id], h3[id]',
                )].find(
                    element =>
                        candidateIds.includes(
                            element.id,
                        ),
                )

            if (!heading) {
                continue
            }

            heading.scrollIntoView({
                behavior: 'auto',
                block: 'start',
                inline: 'nearest',
            })

            await new Promise((resolve) => {
                window.requestAnimationFrame(
                    resolve,
                )
            })

            container.scrollTop =
                Math.max(
                    0,
                    container.scrollTop - 24,
                )

            activeHeadingId.value =
                heading.id

            return true
        }

        return false
    }

    async function chooseWikiRoot() {
        if (!(await confirmDiscardChanges())) {
            return
        }

        closeContextMenu()

        error.value = ''

        try {
            const picker = getFilePickerBuilder(
                'Choose Wiki Root',
            )
                .setMultiSelect(false)
                .allowDirectories(true)
                .addButton({
                    label: 'Select folder',
                    variant: 'primary',
                    callback: (nodes) => {
                        return nodes
                    },
                })
                .build()

            const paths = await picker.pick()

            if (
                !paths ||
                (
                    Array.isArray(paths) &&
                    paths.length === 0
                )
            ) {
                return
            }

            const selectedPath =
                normalizePath(
                    Array.isArray(paths)
                        ? paths[0]
                        : paths,
                )

            const body = new URLSearchParams()

            body.set(
                'wikiRoot',
                selectedPath,
            )

            const response = await fetch(
                OC.generateUrl(
                    '/apps/markdown_wiki/api/wiki-root',
                ),
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                    },
                    body,
                },
            )

            const data =
                await response.json()

            if (!response.ok) {
                throw new Error(
                    data.message ||
                        'Failed to save Wiki Root.',
                )
            }

            stopEditing()

            wikiRoot.value =
                data.wikiRoot || ''

            selectedFile.value = ''
            selectedResource.value = null
            markdown.value = ''
            editedMarkdown.value = ''
            fileError.value = ''
            currentFolder.value = ''
            saveError.value = ''
            copyError.value = ''
            copiedResourcePath.value = false
            createError.value = ''
            createMenuVisible.value = false
            closeCreateDialog(true)
            closeContextMenu()

            clearSearch()

            expandedFolders.value = new Set()
            createDialogExpandedFolders.value =
                new Set()
            tree.value = []

            if (wikiRoot.value) {
                await loadRootTree()
            }
        } catch (err) {
            error.value =
                err.message ||
                'Failed to choose Wiki Root.'
        }
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

    const selectedFileDisplayName = computed(() => {
        return getDisplayFileName(
            selectedFile.value,
        )
    })

    const previousMarkdownDisplayName = computed(() => {
        return previousMarkdownPath.value
            ? getDisplayFileName(
                previousMarkdownPath.value,
            )
            : ''
    })

    const nextMarkdownDisplayName = computed(() => {
        return nextMarkdownPath.value
            ? getDisplayFileName(
                nextMarkdownPath.value,
            )
            : ''
    })

    const currentFolderName = computed(() => {
        if (!currentFolder.value) {
            return wikiRootName.value
        }

        return getNameFromPath(
            currentFolder.value,
        )
    })

    const currentFolderLoading = ref(false)

    const folderBreadcrumbs = computed(() => {
        if (
            !currentFolder.value ||
            !wikiRoot.value
        ) {
            return []
        }

        const root =
            normalizePath(wikiRoot.value)

        const folderPath =
            normalizePath(currentFolder.value)

        if (folderPath === root) {
            return []
        }

        const relative =
            folderPath
                .slice(root.length)
                .split('/')
                .filter(Boolean)

        let path = root

        return relative.map((name) => {
            path =
                normalizePath(
                    path + '/' + name,
                )

            return {
                name,
                path,
            }
        })
    })

    const currentFolderItems = computed(() => {
        const folderPath =
            normalizePath(
                currentFolder.value ||
                    wikiRoot.value,
            )

        if (!folderPath) {
            return []
        }

        let items = []

        if (
            folderPath ===
            normalizePath(wikiRoot.value)
        ) {
            items = tree.value || []
        } else {
            const node =
                findNode(
                    tree.value,
                    folderPath,
                )

            items =
                node?.type === 'folder'
                    ? node.children || []
                    : []
        }

        return [...items].sort((a, b) => {
            if (a.type !== b.type) {
                return a.type === 'folder'
                    ? -1
                    : 1
            }

            return String(a.name || '')
                .localeCompare(
                    String(b.name || ''),
                    undefined,
                    {
                        numeric: true,
                        sensitivity: 'base',
                    },
                )
        })
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

    const isDirty = computed(() => {
        return (
            editing.value &&
            editedMarkdown.value !==
                markdown.value
        )
    })

    const saveStatus = computed(() => {
        if (!editing.value) {
            return ''
        }

        if (savingFile.value) {
            return 'Saving...'
        }

        if (saveError.value) {
            return 'Save failed'
        }

        if (isDirty.value) {
            return 'Unsaved changes'
        }

        return 'Saved'
    })

    const tableOfContents = computed(() => {
        return extractTableOfContents(
            editing.value
                ? editedMarkdown.value
                : markdown.value,
        )
    })

    const showTableOfContents = computed(() => {
        return (
            tableOfContentsVisible.value &&
            tableOfContents.value.length > 0 &&
            (
                !editing.value ||
                editorMode.value !==
                    'markdown'
            )
        )
    })

    const renderedMarkdown = computed(() => {
        return renderMarkdown(
            markdown.value,
        )
    })

    const renderedEditorMarkdown = computed(() => {
        return renderMarkdown(
            editedMarkdown.value,
        )
    })

    watch(
        [
            tableOfContents,
            editorMode,
            selectedFile,
            renderedMarkdown,
            renderedEditorMarkdown,
            loadingFile,
            editing,
        ],
        async () => {
            await nextTick()

            window.requestAnimationFrame(
                updateActiveHeading,
            )

            await renderMermaidDiagrams()
        },
        {
            flush: 'post',
        },
    )

    const ResourceIcon = defineComponent({
        name: 'ResourceIcon',

        props: {
            fileType: {
                type: String,
                default: 'file',
            },
        },

        setup(props) {
            return () => {
                const common = {
                    viewBox: '0 0 24 24',
                    fill: 'none',
                    stroke: 'currentColor',
                    'stroke-width': '1.8',
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                    'aria-hidden': 'true',
                }

                if (
                    props.fileType === 'image'
                ) {
                    return h(
                        'svg',
                        common,
                        [
                            h('rect', {
                                x: '3',
                                y: '3',
                                width: '18',
                                height: '18',
                                rx: '2',
                            }),
                            h('circle', {
                                cx: '8.5',
                                cy: '8.5',
                                r: '1.5',
                            }),
                            h('path', {
                                d: 'm3 16 5-5 4 4 3-3 6 6',
                            }),
                        ],
                    )
                }

                if (
                    props.fileType === 'pdf'
                ) {
                    return h(
                        'svg',
                        common,
                        [
                            h('path', {
                                d: 'M6 2h9l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z',
                            }),
                            h('path', {
                                d: 'M14 2v5h5',
                            }),
                            h('path', {
                                d: 'M8 15h8M8 18h6',
                            }),
                        ],
                    )
                }

                if (
                    props.fileType === 'code'
                ) {
                    return h(
                        'svg',
                        common,
                        [
                            h('path', {
                                d: 'm8 8-4 4 4 4',
                            }),
                            h('path', {
                                d: 'm16 8 4 4-4 4',
                            }),
                            h('path', {
                                d: 'm14 4-4 16',
                            }),
                        ],
                    )
                }

                if (
                    props.fileType === 'archive'
                ) {
                    return h(
                        'svg',
                        common,
                        [
                            h('path', {
                                d: 'M6 3h12v18H6z',
                            }),
                            h('path', {
                                d: 'M9 3v4h6V3',
                            }),
                            h('path', {
                                d: 'M9 10h6M9 14h6M9 18h6',
                            }),
                        ],
                    )
                }

                if (
                    props.fileType === 'text'
                ) {
                    return h(
                        'svg',
                        common,
                        [
                            h('path', {
                                d: 'M6 2h9l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z',
                            }),
                            h('path', {
                                d: 'M14 2v5h5',
                            }),
                            h('path', {
                                d: 'M8 12h8M8 16h8M8 20h5',
                            }),
                        ],
                    )
                }

                if (
                    props.fileType === 'markdown'
                ) {
                    return h(
                        'svg',
                        common,
                        [
                            h('path', {
                                d: 'M5 3h9l5 5v13H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z',
                            }),
                            h('path', {
                                d: 'M14 3v6h6',
                            }),
                            h('path', {
                                d: 'M7 14h2l1 2 1-2h2v4',
                            }),
                        ],
                    )
                }

                return h(
                    'svg',
                    common,
                    [
                        h('path', {
                            d: 'M6 2h9l4 4v16H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z',
                        }),
                        h('path', {
                            d: 'M14 2v5h5',
                        }),
                    ],
                )
            }
        },
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

            selectedResource: {
                type: Object,
                default: null,
            },

            expandedFolders: {
                type: Object,
                required: true,
            },

            dragSourcePath: {
                type: String,
                default: '',
            },

            dropTargetPath: {
                type: String,
                default: '',
            },

            dropTargetValid: {
                type: Boolean,
                default: false,
            },
        },

        emits: [
            'toggle-folder',
            'open-file',
            'select-resource',
            'context-menu',
            'drag-start',
            'drag-end',
            'drag-over',
            'drop',
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

                const selectedFile =
                    props.node.type ===
                        'file' &&
                    normalizePath(
                        props.selectedFile,
                    ) === nodePath

                const selectedResource =
                    props.node.type ===
                        'file' &&
                    props.selectedResource &&
                    normalizePath(
                        props.selectedResource.path,
                    ) === nodePath

                const children =
                    props.node.children || []

                const icon =
                    props.node.type ===
                        'folder'
                        ? h(
                            'svg',
                            {
                                class:
                                    'tree-folder-icon',
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
                            ResourceIcon,
                            {
                                fileType:
                                    props.node
                                        .fileType,
                            },
                        )

                return h(
                    'li',
                    {
                        class: [
                            'tree-item',
                            {
                                'tree-item-drop-scope-valid':
                                    props.node.type ===
                                        'folder' &&
                                    normalizePath(
                                        props.dropTargetPath,
                                    ) === nodePath &&
                                    props.dropTargetValid,

                                'tree-item-drop-scope-invalid':
                                    props.node.type ===
                                        'folder' &&
                                    normalizePath(
                                        props.dropTargetPath,
                                    ) === nodePath &&
                                    !props.dropTargetValid,
                            },
                        ],

                        style:
                            props.node.type ===
                                'folder' &&
                            normalizePath(
                                props.dropTargetPath,
                            ) === nodePath
                                ? {
                                    borderRadius:
                                        'var(--border-radius-element)',
                                    outline:
                                        props.dropTargetValid
                                            ? '2px solid var(--color-primary-element)'
                                            : '2px solid var(--color-error)',
                                    outlineOffset:
                                        '-2px',
                                    background:
                                        props.dropTargetValid
                                            ? 'var(--color-primary-element-light)'
                                            : 'transparent',
                                }
                                : null,

                        'data-tree-path':
                            normalizePath(
                                props.node.path,
                            ),
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
                                            selectedFile,

                                        'tree-button-resource-active':
                                            selectedResource,

                                        'tree-button-folder-active':
                                            props.node.type ===
                                                'folder' &&
                                            expanded,

                                        'tree-button-dragging':
                                            normalizePath(
                                                props.dragSourcePath,
                                            ) === nodePath,

                                        'tree-button-drop-target-valid':
                                            props.node.type ===
                                                'folder' &&
                                            normalizePath(
                                                props.dropTargetPath,
                                            ) === nodePath &&
                                            props.dropTargetValid,

                                        'tree-button-drop-target-invalid':
                                            props.node.type ===
                                                'folder' &&
                                            normalizePath(
                                                props.dropTargetPath,
                                            ) === nodePath &&
                                            !props.dropTargetValid,
                                    },
                                ],

                                title:
                                    props.node.name,

                                'aria-label':
                                    props.node.name,

                                draggable: true,

                                onDragstart: (event) => {
                                    emit(
                                        'drag-start',
                                        event,
                                        props.node,
                                    )
                                },

                                onDragend: (event) => {
                                    emit(
                                        'drag-end',
                                        event,
                                        props.node,
                                    )
                                },

                                onDragover: (event) => {
                                    emit(
                                        'drag-over',
                                        event,
                                        props.node,
                                    )
                                },

                                onDrop: (event) => {
                                    emit(
                                        'drop',
                                        event,
                                        props.node,
                                    )
                                },

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

                                        return
                                    }

                                    if (
                                        props.node
                                            .fileType ===
                                        'markdown'
                                    ) {
                                        emit(
                                            'open-file',
                                            props.node.path,
                                        )

                                        return
                                    }

                                    emit(
                                        'select-resource',
                                        props.node,
                                    )
                                },

                                onContextmenu: (event) => {
                                    event.preventDefault()
                                    event.stopPropagation()

                                    emit(
                                        'context-menu',
                                        event,
                                        props.node,
                                    )
                                },
                            },
                            [
                                h(
                                    'span',
                                    {
                                        class:
                                            'tree-chevron',
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
                                        class:
                                            'tree-icon',
                                    },
                                    [icon],
                                ),

                                h(
                                    'span',
                                    {
                                        class:
                                            'tree-name',
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
                                    class:
                                        'tree-list',
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

                                                selectedResource:
                                                    props.selectedResource,

                                                expandedFolders:
                                                    props.expandedFolders,

                                                dragSourcePath:
                                                    props.dragSourcePath,

                                                dropTargetPath:
                                                    props.dropTargetPath,

                                                dropTargetValid:
                                                    props.dropTargetValid,

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

                                                onSelectResource:
                                                    (
                                                        resourceNode,
                                                    ) =>
                                                        emit(
                                                            'select-resource',
                                                            resourceNode,
                                                        ),

                                                onContextMenu:
                                                    (
                                                        event,
                                                        resourceNode,
                                                    ) =>
                                                        emit(
                                                            'context-menu',
                                                            event,
                                                            resourceNode,
                                                        ),

                                                onDragStart:
                                                    (
                                                        event,
                                                        resourceNode,
                                                    ) =>
                                                        emit(
                                                            'drag-start',
                                                            event,
                                                            resourceNode,
                                                        ),

                                                onDragEnd:
                                                    (
                                                        event,
                                                        resourceNode,
                                                    ) =>
                                                        emit(
                                                            'drag-end',
                                                            event,
                                                            resourceNode,
                                                        ),

                                                onDragOver:
                                                    (
                                                        event,
                                                        resourceNode,
                                                    ) =>
                                                        emit(
                                                            'drag-over',
                                                            event,
                                                            resourceNode,
                                                        ),

                                                onDrop:
                                                    (
                                                        event,
                                                        resourceNode,
                                                    ) =>
                                                        emit(
                                                            'drop',
                                                            event,
                                                            resourceNode,
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

    const CreateLocationNode = defineComponent({
        name: 'CreateLocationNode',

        props: {
            node: {
                type: Object,
                required: true,
            },

            selectedPath: {
                type: String,
                required: true,
            },

            expandedFolders: {
                type: Object,
                required: true,
            },
        },

        emits: [
            'toggle-folder',
            'select-folder',
        ],

        setup(props, { emit }) {
            const folderChildren = computed(() =>
                (props.node.children || []).filter(
                    child => child.type === 'folder',
                ),
            )

            const nodePath = computed(() =>
                normalizePath(props.node.path),
            )

            const expanded = computed(() =>
                props.expandedFolders.has(
                    nodePath.value,
                ),
            )

            /*
             * A folder can have children that have not
             * been loaded yet. Therefore, show the
             * chevron until we know that the folder is
             * actually empty.
             */
            const hasExpandableChildren = computed(() =>
                !props.node.loaded ||
                folderChildren.value.length > 0,
            )

            return () =>
                h(
                    'li',
                    {
                        class: 'create-location-item',
                    },
                    [
                        h(
                            'div',
                            {
                                class: [
                                    'create-location-row',
                                    props.selectedPath ===
                                        nodePath.value
                                        ? 'create-location-row-selected'
                                        : '',
                                ],
                            },
                            [
                                h(
                                    'button',
                                    {
                                        type: 'button',
                                        class: 'create-location-toggle',

                                        'aria-label':
                                            expanded.value
                                                ? 'Collapse folder'
                                                : 'Expand folder',

                                        onClick: (event) => {
                                            event.preventDefault()
                                            event.stopPropagation()

                                            emit(
                                                'toggle-folder',
                                                props.node,
                                            )
                                        },
                                    },
                                    hasExpandableChildren.value
                                        ? expanded.value
                                            ? '⌄'
                                            : '›'
                                        : '',
                                ),

                                h(
                                    'button',
                                    {
                                        type: 'button',
                                        class: 'create-location-select',

                                        onClick: (event) => {
                                            event.preventDefault()
                                            event.stopPropagation()

                                            emit(
                                                'select-folder',
                                                props.node.path,
                                            )
                                        },
                                    },
                                    [
                                        h(
                                            'span',
                                            {
                                                class:
                                                    'create-location-icon',
                                            },
                                            [
                                                h(
                                                    'svg',
                                                    {
                                                        viewBox:
                                                            '0 0 24 24',
                                                        fill: 'none',
                                                        stroke:
                                                            'currentColor',
                                                        'stroke-width':
                                                            '2',
                                                        'stroke-linecap':
                                                            'round',
                                                        'stroke-linejoin':
                                                            'round',
                                                        'aria-hidden':
                                                            'true',
                                                    },
                                                    [
                                                        h('path', {
                                                            d:
                                                                'M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z',
                                                        }),
                                                    ],
                                                ),
                                            ],
                                        ),

                                        h(
                                            'span',
                                            {
                                                class:
                                                    'create-location-name',
                                            },
                                            props.node.name,
                                        ),
                                    ],
                                ),
                            ],
                        ),

                        expanded.value &&
                        folderChildren.value.length > 0
                            ? h(
                                'ul',
                                {
                                    class:
                                        'create-location-children',
                                },
                                folderChildren.value.map(
                                    child =>
                                        h(
                                            CreateLocationNode,
                                            {
                                                key:
                                                    child.path,

                                                node: child,

                                                selectedPath:
                                                    props.selectedPath,

                                                expandedFolders:
                                                    props.expandedFolders,

                                                onToggleFolder:
                                                    childNode =>
                                                        emit(
                                                            'toggle-folder',
                                                            childNode,
                                                        ),

                                                onSelectFolder:
                                                    path =>
                                                        emit(
                                                            'select-folder',
                                                            path,
                                                        ),
                                            },
                                        ),
                                ),
                            )
                            : null,
                    ],
                )
        },
    })

    watch(searchQuery, () => {
        scheduleSearch()
    })

    let mermaidThemeObserver = null

    onMounted(() => {
        loadSearchHistory()

        mermaidThemeObserver =
            new MutationObserver(() => {
                renderMermaidDiagrams()
            })

        mermaidThemeObserver.observe(
            document.documentElement,
            {
                attributes: true,
                attributeFilter: [
                    'class',
                    'data-theme',
                ],
            },
        )

        mermaidThemeObserver.observe(
            document.body,
            {
                attributes: true,
                attributeFilter: [
                    'class',
                    'data-theme',
                    'style',
                ],
            },
        )

        document.addEventListener(
            'click',
            handleDocumentClick,
        )

        document.addEventListener(
            'keydown',
            handleGlobalKeydown,
        )

        window.addEventListener(
            'resize',
            handleViewportChange,
        )

        window.addEventListener(
            'scroll',
            handleViewportChange,
            true,
        )

        loadWikiRoot()

        window.addEventListener(
            'beforeunload',
            handleBeforeUnload,
        )
    })

    onBeforeUnmount(() => {
        mermaidRenderGeneration++

        if (mermaidThemeObserver) {
            mermaidThemeObserver.disconnect()
            mermaidThemeObserver = null
        }

        document.removeEventListener(
            'click',
            handleDocumentClick,
        )

        document.removeEventListener(
            'keydown',
            handleGlobalKeydown,
        )

        window.removeEventListener(
            'resize',
            handleViewportChange,
        )

        window.removeEventListener(
            'scroll',
            handleViewportChange,
            true,
        )

        window.removeEventListener(
            'beforeunload',
            handleBeforeUnload,
        )

        if (searchTimeout !== null) {
            window.clearTimeout(searchTimeout)
            searchTimeout = null
        }

        searchRequestId++
    })
</script>