<template>
    <div class="markdown-wiki">
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

            <div class="file-tree">
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
                >
                    <TreeNode
                        v-for="node in tree"
                        :key="node.path"
                        :node="node"
                        :selected-file="selectedFile"
                        :selected-resource="selectedResource"
                        :expanded-folders="expandedFolders"
                        @toggle-folder="toggleFolder"
                        @open-file="openFile"
                        @select-resource="selectResource"
                    />
                </ul>
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

                <div class="wiki-document-scroll">
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

                        <template v-else-if="editing">
                            <div
                                v-if="
                                    editorMode !==
                                    'preview'
                                "
                                class="markdown-editor-toolbar"
                                role="toolbar"
                                aria-label="Markdown formatting"
                            >
                                <div class="markdown-toolbar-group">
                                    <button
                                        type="button"
                                        class="markdown-toolbar-button markdown-toolbar-button-emphasis"
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
                                        class="markdown-toolbar-button markdown-toolbar-button-emphasis"
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
                                        class="markdown-toolbar-button markdown-toolbar-button-emphasis"
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

                                <div class="markdown-toolbar-separator" />

                                <div class="markdown-toolbar-group">
                                    <button
                                        type="button"
                                        class="markdown-toolbar-button markdown-toolbar-heading"
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
                                        class="markdown-toolbar-button markdown-toolbar-heading"
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
                                        class="markdown-toolbar-button markdown-toolbar-heading"
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

                                <div class="markdown-toolbar-separator" />

                                <div class="markdown-toolbar-group">
                                    <button
                                        type="button"
                                        class="markdown-toolbar-button"
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
                                        class="markdown-toolbar-button"
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
                                        class="markdown-toolbar-button"
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
                                        class="markdown-toolbar-button"
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

                                <div class="markdown-toolbar-separator" />

                                <div class="markdown-toolbar-group">
                                    <button
                                        type="button"
                                        class="markdown-toolbar-button"
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
                                        class="markdown-toolbar-button"
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

                                <div class="markdown-toolbar-separator" />

                                <div class="markdown-toolbar-group">
                                    <button
                                        type="button"
                                        class="markdown-toolbar-button markdown-toolbar-code"
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
                                        class="markdown-toolbar-code-language"
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
                                        class="markdown-toolbar-button markdown-toolbar-code"
                                        title="Code block"
                                        aria-label="Code block"
                                        @mousedown.prevent
                                        @click="
                                            applyCodeBlock()
                                        "
                                    >
                                        Code
                                    </button>

                                    <button
                                        type="button"
                                        class="markdown-toolbar-button"
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
                class="wiki-empty-state"
            >
                <div class="wiki-empty-state-content">
                    <h2>
                        {{ currentFolderName }}
                    </h2>

                    <p>
                        {{
                            treeMode === 'markdown'
                                ? 'Select a Markdown file from the sidebar to open it.'
                                : 'Select a Markdown file from the sidebar to open it, or another file to view its path.'
                        }}
                    </p>

                    <button
                        v-if="currentFolder && parentFolder"
                        type="button"
                        class="wiki-folder-button"
                        @click="openFolder(parentFolder)"
                    >
                        Go to parent folder
                    </button>
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
    </div>
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

    import {
        getFilePickerBuilder,
    } from '@nextcloud/dialogs'

    import '@nextcloud/dialogs/style.css'

    const wikiRoot = ref('')
    const tree = ref([])

    const selectedFile = ref('')
    const selectedResource = ref(null)

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

    const expandedFolders = ref(new Set())

    /*
     * Editor state.
     */
    const editing = ref(false)
    const editorMode = ref('split')
    const editorTextarea = ref(null)

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

    /*
     * Create menu.
     */
    function toggleCreateMenu() {
        createError.value = ''
        createMenuVisible.value =
            !createMenuVisible.value
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

        if (!confirmDiscardChanges()) {
            return
        }

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
                throw new Error(
                    data.message ||
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

            createDialogVisible.value = false
            createDialogName.value = ''
            createDialogError.value = ''
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

        clearSearch()
        await openFile(result.path)
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
     * document.
     */
    function confirmDiscardChanges() {
        if (!editing.value || !isDirty.value) {
            return true
        }

        return window.confirm(
            'You have unsaved changes. Discard them?',
        )
    }

    async function openFolder(path) {
        if (!confirmDiscardChanges()) {
            return
        }

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

    function selectResource(node) {
        if (!confirmDiscardChanges()) {
            return
        }

        stopEditing()

        selectedFile.value = ''
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
    }

    async function openFile(path) {
        if (
            normalizePath(path) ===
            normalizePath(selectedFile.value)
        ) {
            return
        }

        if (!confirmDiscardChanges()) {
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
            selectResource(node)
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
        } catch (err) {
            fileError.value =
                err.message
        } finally {
            loadingFile.value = false
        }
    }

    async function startEditing() {
        if (!selectedFile.value || loadingFile.value) {
            return
        }

        saveError.value = ''

        editedMarkdown.value =
            markdown.value

        selectedCodeLanguage.value = ''

        editorMode.value = 'split'
        editing.value = true

        await nextTick()

        if (editorTextarea.value) {
            editorTextarea.value.focus()
        }
    }

    function stopEditing() {
        editing.value = false
        editedMarkdown.value = markdown.value
        saveError.value = ''
        selectedCodeLanguage.value = ''
    }

    function cancelEditing() {
        if (
            isDirty.value &&
            !window.confirm(
                'Discard your unsaved changes?',
            )
        ) {
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

        if (
            href.startsWith('http://') ||
            href.startsWith('https://') ||
            href.startsWith('#') ||
            href.startsWith('mailto:')
        ) {
            return
        }

        if (
            !href.toLowerCase().endsWith('.md')
        ) {
            return
        }

        event.preventDefault()

        if (!selectedFile.value) {
            return
        }

        const resolvedPath =
            resolveRelativePath(
                href,
            )

        if (
            !resolvedPath ||
            !isInsideWikiRoot(
                resolvedPath,
            )
        ) {
            return
        }

        openFile(resolvedPath)
    }

    function renderMarkdown(content) {
        if (!content) {
            return ''
        }

        const renderer = new marked.Renderer()

        renderer.code = ({
            text,
            lang,
        }) => {
            const language =
                lang
                    ? lang
                        .trim()
                        .split(/\s+/)[0]
                        .toLowerCase()
                    : ''

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
        )
    }

    async function chooseWikiRoot() {
        if (!confirmDiscardChanges()) {
            return
        }

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
            createDialogVisible.value = false
            createDialogName.value = ''
            createDialogFolder.value = ''
            createDialogError.value = ''

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
        },

        emits: [
            'toggle-folder',
            'open-file',
            'select-resource',
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
                                            selectedFile,

                                        'tree-button-resource-active':
                                            selectedResource,

                                        'tree-button-folder-active':
                                            props.node.type ===
                                                'folder' &&
                                            expanded,
                                    },
                                ],

                                title:
                                    props.node.name,

                                'aria-label':
                                    props.node.name,

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

    onMounted(() => {
        loadSearchHistory()

        document.addEventListener(
            'click',
            handleDocumentClick,
        )

        loadWikiRoot()

        window.addEventListener(
            'beforeunload',
            handleBeforeUnload,
        )
    })

    onBeforeUnmount(() => {
        document.removeEventListener(
            'click',
            handleDocumentClick,
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