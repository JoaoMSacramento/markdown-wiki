# Markdown Wiki

Markdown Wiki is a Nextcloud application that turns your Markdown files into an organized and easy-to-navigate wiki directly inside Nextcloud.

Your content remains stored as standard Markdown files, keeping it portable and accessible without locking it into a proprietary format.

## Features

- Folder-based navigation tree
- Full-text search across Markdown files
- Direct Markdown editing
- Create, rename, move, and delete Markdown files and folders
- Breadcrumb navigation
- Previous and next page navigation
- Automatic table of contents based on headings
- Mermaid diagram support
- Integration with Nextcloud Files
- Light and dark theme support
- Markdown checklist support

## Requirements

- Nextcloud 31–34

## Installation

### Nextcloud App Store

Once Markdown Wiki is available in the Nextcloud App Store, it can be installed directly from the Apps section of Nextcloud.

### Manual installation

Download a release of Markdown Wiki and extract it into the Nextcloud `apps` directory so that the application is located at:

`apps/markdown_wiki`

Then enable the application from the Nextcloud Apps page or with:

```bash
php occ app:enable markdown_wiki
```

## Development

Install the JavaScript dependencies:

```bash
npm install
```

Build the frontend:

```bash
npm run build
```

For development with Vite:

```bash
npm run dev
```

## License

Markdown Wiki is licensed under the GNU Affero General Public License v3.0 or later (`AGPL-3.0-or-later`).

See `LICENSES/AGPL-3.0-or-later.txt` for the full license text.
