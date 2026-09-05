import { Controller } from '@hotwired/stimulus';

// An `inline` format wraps the selection with its marker. A `line` format
// prefixes each selected line, with numbers instead of the prefix when it is
// `numbered`. Both are toggled when applied twice: a line is considered
// prefixed if it matches the `pattern` (or starts with the prefix by default).
const FORMATS = {
    bold: { type: 'inline', marker: '**', placeholder: 'bold' },
    italic: { type: 'inline', marker: '_', placeholder: 'italic' },
    link: { type: 'link' },
    heading: { type: 'line', prefix: '## ', pattern: /^#+ / },
    list: { type: 'line', prefix: '- ' },
    orderedList: { type: 'line', prefix: '1. ', numbered: true, pattern: /^\d+\. / },
    quote: { type: 'line', prefix: '> ' },
};

// The line prefixes carried over to the next line on Enter. A line reduced to
// its prefix ends the block instead.
const CONTINUED_PREFIXES = [/^[-*+] /, /^> /, /^\d+\. /];

const SHORTCUTS = {
    b: 'bold',
    i: 'italic',
    k: 'link',
};

export default class extends Controller {
    static targets = ['textarea', 'toolbar'];

    static values = {
        boldPlaceholder: String,
        italicPlaceholder: String,
        linkPlaceholder: String,
    };

    connect () {
        const textarea = this.textareaTarget;
        textarea.style.overflowY = 'hidden';
        textarea.style.resize = 'none';
        this.refresh();

        if (this.hasToolbarTarget) {
            this.buttons.forEach((button, index) => {
                button.tabIndex = index === 0 ? 0 : -1;
            });
        }
    }

    // Resize the textarea to fit its content.
    refresh () {
        const textarea = this.textareaTarget;

        // The textarea is collapsed to measure its content, which shortens
        // the page for the time of the measure: the browser would clamp the
        // scroll position and the page would jump. The element keeps its
        // height in the meantime to prevent that.
        this.element.style.minHeight = `${this.element.offsetHeight}px`;

        textarea.style.height = 'auto';
        textarea.style.height = `${textarea.scrollHeight}px`;

        this.element.style.minHeight = '';
    }

    // Apply the format given as the `format` param of the action.
    apply (event) {
        const params = event.params;
        this.format(params.format);
    }

    // Apply the format bound to a keyboard shortcut, if any.
    shortcut (event) {
        if (!(event.ctrlKey || event.metaKey) || event.altKey || event.shiftKey) {
            return;
        }

        const format = SHORTCUTS[event.key.toLowerCase()];
        if (!format) {
            return;
        }

        event.preventDefault();
        this.format(format);
    }

    // Move the focus between the buttons of the toolbar with the arrow keys.
    navigate (event) {
        const buttons = this.buttons;
        const index = buttons.indexOf(document.activeElement);
        if (index === -1) {
            return;
        }

        let nextIndex;
        switch (event.key) {
            case 'ArrowRight':
            case 'ArrowDown':
                nextIndex = (index + 1) % buttons.length;
                break;
            case 'ArrowLeft':
            case 'ArrowUp':
                nextIndex = (index - 1 + buttons.length) % buttons.length;
                break;
            case 'Home':
                nextIndex = 0;
                break;
            case 'End':
                nextIndex = buttons.length - 1;
                break;
            default:
                return;
        }

        event.preventDefault();

        buttons[index].tabIndex = -1;
        buttons[nextIndex].tabIndex = 0;
        buttons[nextIndex].focus();
    }

    get buttons () {
        return Array.from(this.toolbarTarget.querySelectorAll('button'));
    }

    format (name) {
        const format = FORMATS[name];
        if (!format) {
            return;
        }

        if (format.type === 'inline') {
            this.wrapSelection(format);
        } else if (format.type === 'line') {
            this.prefixLines(format);
        } else if (format.type === 'link') {
            this.insertLink();
        }

        this.textareaTarget.focus();
    }

    // Wrap the selection with the marker, or unwrap it if it is already
    // wrapped. Without a selection, a placeholder is inserted and selected so
    // it is replaced by typing.
    wrapSelection (format) {
        const marker = format.marker;
        const placeholder = this[`${format.placeholder}PlaceholderValue`];

        const textarea = this.textareaTarget;
        const value = textarea.value;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selection = value.slice(start, end);

        const before = value.slice(Math.max(0, start - marker.length), start);
        const after = value.slice(end, end + marker.length);

        // Without a selection, a caret right before the marker leaves the
        // formatted text instead of nesting a placeholder in it.
        if (!selection && after === marker) {
            textarea.setSelectionRange(end + marker.length, end + marker.length);
            return;
        }

        // Unwrap the text if the chars before and after the selection
        // correspond to the marker.
        if (selection && before === marker && after === marker) {
            this.replaceRange(selection, start - marker.length, end + marker.length);
            textarea.setSelectionRange(start - marker.length, end - marker.length);
            return;
        }

        // Unwrap the text if the first and last char(s) of the selection
        // correspond to the marker.
        if (
            selection.length > marker.length * 2 &&
            selection.startsWith(marker) &&
            selection.endsWith(marker)
        ) {
            const unwrapped = selection.slice(marker.length, -marker.length);
            this.replaceRange(unwrapped, start, end);
            textarea.setSelectionRange(start, start + unwrapped.length);
            return;
        }

        // Wrap the selection in the marker.
        const text = selection || placeholder;
        this.replaceRange(marker + text + marker, start, end);
        textarea.setSelectionRange(start + marker.length, start + marker.length + text.length);
    }

    // Prefix each line of the selection, or remove the prefix if all the lines
    // already have it. A numbered format counts the lines from 1, and removes
    // any number.
    prefixLines (format) {
        const textarea = this.textareaTarget;
        const value = textarea.value;
        const start = textarea.selectionStart;
        let end = textarea.selectionEnd;

        // A selection ending right after a line break does not include the
        // next line.
        if (end > start && value[end - 1] === '\n') {
            end -= 1;
        }

        const linesStart = value.lastIndexOf('\n', start - 1) + 1;
        let linesEnd = value.indexOf('\n', end);
        if (linesEnd === -1) {
            linesEnd = value.length;
        }

        const lines = value.slice(linesStart, linesEnd).split('\n');

        // The prefix at the start of the line, or null if it has none.
        const prefixOf = (line) => {
            if (format.pattern) {
                return line.match(format.pattern)?.[0] ?? null;
            } else {
                return line.startsWith(format.prefix) ? format.prefix : null;
            }
        };
        const prefixFor = (index) => {
            return format.numbered ? `${index + 1}. ` : format.prefix;
        };

        const allPrefixed = lines.every((line) => prefixOf(line) !== null);

        // Add or remove the prefix depending on the fact that all the
        // selected lines start with the prefix or not.
        const newLines = lines.map((line, index) => {
            const unprefixed = line.slice(prefixOf(line)?.length ?? 0);
            return allPrefixed ? unprefixed : prefixFor(index) + unprefixed;
        });
        const replacement = newLines.join('\n');

        this.replaceRange(replacement, linesStart, linesEnd);

        if (start === end) {
            // Keep the caret where it was in the line.
            const shift = allPrefixed ? -prefixOf(lines[0]).length : prefixFor(0).length;
            const caret = Math.max(linesStart, start + shift);
            textarea.setSelectionRange(caret, caret);
        } else {
            // Select all the lines if text was already selected.
            textarea.setSelectionRange(linesStart, linesStart + replacement.length);
        }
    }

    // Insert a link. The selection becomes either the URL or the text of the
    // link, and the other part is selected so it is replaced by typing.
    insertLink () {
        const textarea = this.textareaTarget;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selection = textarea.value.slice(start, end);

        let text;
        let url;
        let selectText;
        if (/^https?:\/\/\S+$/.test(selection)) {
            text = this.linkPlaceholderValue;
            url = selection;
            selectText = true;
        } else {
            text = selection || this.linkPlaceholderValue;
            url = 'https://';
            selectText = !selection;
        }

        this.replaceRange(`[${text}](${url})`, start, end);

        if (selectText) {
            textarea.setSelectionRange(start + 1, start + 1 + text.length);
        } else {
            const urlStart = start + text.length + 3;
            textarea.setSelectionRange(urlStart, urlStart + url.length);
        }
    }

    // On Enter, carry the prefix of the current line (list, quote) over to the
    // new line. If the line contains nothing but its prefix, remove the prefix
    // instead: the block ends.
    continueLine (event) {
        if (
            event.key !== 'Enter' ||
            event.shiftKey || event.ctrlKey || event.metaKey || event.altKey ||
            event.isComposing
        ) {
            return;
        }

        const textarea = this.textareaTarget;
        const value = textarea.value;
        const start = textarea.selectionStart;
        if (start !== textarea.selectionEnd) {
            return;
        }

        const lineStart = value.lastIndexOf('\n', start - 1) + 1;
        let lineEnd = value.indexOf('\n', start);
        if (lineEnd === -1) {
            lineEnd = value.length;
        }

        const line = value.slice(lineStart, lineEnd);
        const match = CONTINUED_PREFIXES
            .map((regex) => line.match(regex))
            .find((match) => match);
        if (!match) {
            return;
        }

        event.preventDefault();

        const prefix = match[0];
        if (line === prefix) {
            this.replaceRange('', lineStart, lineEnd);
            return;
        }

        const nextPrefix = this.nextPrefix(prefix);
        // A caret inside the prefix splits the line after the prefix.
        const splitAt = Math.max(start, lineStart + prefix.length);
        const caret = splitAt + 1 + nextPrefix.length;

        if (nextPrefix === prefix) {
            this.replaceRange('\n' + nextPrefix, splitAt, splitAt);
            return;
        }

        // The new line takes a number: the numbered lines following it are
        // renumbered.
        let number = parseInt(nextPrefix, 10);
        let blockEnd = lineEnd;
        let nextLines = '';
        for (const followingLine of value.slice(lineEnd + 1).split('\n')) {
            if (!/^\d+\. /.test(followingLine)) {
                break;
            }

            number += 1;
            nextLines += '\n' + followingLine.replace(/^\d+/, number);
            blockEnd += 1 + followingLine.length;
        }

        const currentLine = value.slice(splitAt, lineEnd);
        this.replaceRange('\n' + nextPrefix + currentLine + nextLines, splitAt, blockEnd);
        textarea.setSelectionRange(caret, caret);
    }

    // The prefix of the line following a line with the given prefix: the same
    // one, except for numbered lists which are incremented.
    nextPrefix (prefix) {
        const number = parseInt(prefix, 10);
        if (Number.isNaN(number)) {
            return prefix;
        }

        return `${number + 1}. `;
    }

    // Replace a range of the textarea with the text, and leave the caret after
    // it.
    //
    // The native editing commands are used so the change can be undone with
    // Ctrl+Z, and they notify the change themselves.
    //
    // Note that `execCommand` is deprecated but has no replacement for this: it
    // falls back to `setRangeText` where it fails, at the cost of the undo
    // history.
    //
    // @see https://developer.mozilla.org/en-US/docs/Web/API/Document/execCommand
    replaceRange (text, start, end) {
        if (start === end && text === '') {
            return;
        }

        const textarea = this.textareaTarget;
        textarea.focus();
        textarea.setSelectionRange(start, end);

        let done;
        try {
            if (text === '') {
                done = document.execCommand('delete');
            } else {
                done = document.execCommand('insertText', false, text);
            }
        } catch {
            done = false;
        }

        if (!done) {
            textarea.setRangeText(text, start, end, 'end');
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
};
