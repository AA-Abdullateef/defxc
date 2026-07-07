/**
 * Initializes TipTap editors and falls back to editable HTML fields if the
 * TipTap CDN scripts are unavailable.
 */
document.addEventListener('DOMContentLoaded', () => {
    const wrappers = document.querySelectorAll('.tiptap-wrapper');

    const Editor = window.TiptapCore?.Editor;
    const StarterKit = window.TiptapStarterKit?.StarterKit;
    const Underline = window.TiptapUnderline?.Underline;
    const TextAlign = window.TiptapTextAlign?.TextAlign;
    const Highlight = window.TiptapHighlight?.Highlight;
    const Link = window.TiptapLink?.Link;
    const TextStyle = window.TiptapTextStyle?.TextStyle;
    const Color = window.TiptapColor?.Color;
    const Subscript = window.TiptapSubscript?.Subscript;
    const Superscript = window.TiptapSuperscript?.Superscript;

    const canUseTiptap = Editor && StarterKit && Underline && TextAlign && Highlight
        && Link && TextStyle && Color && Subscript && Superscript;

    if (!canUseTiptap) {
        enablePlainEditors(wrappers);
        return;
    }

    wrappers.forEach(wrapper => {
        const editorId = wrapper.dataset.editorId;
        const canvas = document.getElementById(editorId);
        const hiddenInput = document.getElementById(editorId + '-input');
        const toolbar = document.getElementById(editorId + '-toolbar');

        if (!canvas || !hiddenInput) return;

        let editor;

        try {
            editor = new Editor({
                element: canvas,
                extensions: [
                    StarterKit.configure({
                        codeBlock: { HTMLAttributes: { class: 'tiptap-code-block' } },
                    }),
                    Underline,
                    TextAlign.configure({ types: ['heading', 'paragraph'] }),
                    Highlight.configure({ multicolor: false }),
                    Link.configure({
                        openOnClick: false,
                        HTMLAttributes: { target: '_blank', rel: 'noopener noreferrer' },
                    }),
                    TextStyle,
                    Color,
                    Subscript,
                    Superscript,
                ],
                content: hiddenInput.value || '',
                editorProps: {
                    attributes: {
                        class: 'tiptap-prose',
                        'data-placeholder': canvas.dataset.placeholder || '',
                    },
                },
                onUpdate({ editor }) {
                    hiddenInput.value = editor.getHTML();
                    refreshToolbar(editor, toolbar);
                },
                onSelectionUpdate({ editor }) {
                    refreshToolbar(editor, toolbar);
                },
            });
        } catch (error) {
            enablePlainEditors([wrapper]);
            return;
        }

        canvas.addEventListener('click', () => editor.commands.focus());

        toolbar?.querySelectorAll('button[data-action]').forEach(btn => {
            btn.addEventListener('mousedown', e => {
                e.preventDefault();
                runAction(editor, btn.dataset.action);
            });
        });

        refreshToolbar(editor, toolbar);

        canvas.closest('form')?.addEventListener('submit', () => {
            hiddenInput.value = editor.getHTML();
        });
    });
});

function runAction(editor, action) {
    const chain = editor.chain().focus();

    const actions = {
        bold: () => chain.toggleBold().run(),
        italic: () => chain.toggleItalic().run(),
        underline: () => chain.toggleUnderline().run(),
        strike: () => chain.toggleStrike().run(),
        highlight: () => chain.toggleHighlight().run(),
        code: () => chain.toggleCode().run(),
        codeBlock: () => chain.toggleCodeBlock().run(),
        blockquote: () => chain.toggleBlockquote().run(),
        bulletList: () => chain.toggleBulletList().run(),
        orderedList: () => chain.toggleOrderedList().run(),
        horizontalRule: () => chain.setHorizontalRule().run(),
        h1: () => chain.toggleHeading({ level: 1 }).run(),
        h2: () => chain.toggleHeading({ level: 2 }).run(),
        h3: () => chain.toggleHeading({ level: 3 }).run(),
        alignLeft: () => chain.setTextAlign('left').run(),
        alignCenter: () => chain.setTextAlign('center').run(),
        alignRight: () => chain.setTextAlign('right').run(),
        subscript: () => chain.toggleSubscript().run(),
        superscript: () => chain.toggleSuperscript().run(),
        undo: () => chain.undo().run(),
        redo: () => chain.redo().run(),
        clearFormatting: () => chain.unsetAllMarks().clearNodes().run(),
        link: () => {
            const url = window.prompt('Enter URL:', 'https://');
            if (url) chain.setLink({ href: url }).run();
        },
        unlink: () => chain.unsetLink().run(),
    };

    actions[action]?.();
}

function refreshToolbar(editor, toolbar) {
    if (!toolbar) return;

    const stateMap = {
        bold: editor.isActive('bold'),
        italic: editor.isActive('italic'),
        underline: editor.isActive('underline'),
        strike: editor.isActive('strike'),
        highlight: editor.isActive('highlight'),
        code: editor.isActive('code'),
        codeBlock: editor.isActive('codeBlock'),
        blockquote: editor.isActive('blockquote'),
        bulletList: editor.isActive('bulletList'),
        orderedList: editor.isActive('orderedList'),
        h1: editor.isActive('heading', { level: 1 }),
        h2: editor.isActive('heading', { level: 2 }),
        h3: editor.isActive('heading', { level: 3 }),
        alignLeft: editor.isActive({ textAlign: 'left' }),
        alignCenter: editor.isActive({ textAlign: 'center' }),
        alignRight: editor.isActive({ textAlign: 'right' }),
        subscript: editor.isActive('subscript'),
        superscript: editor.isActive('superscript'),
        link: editor.isActive('link'),
    };

    toolbar.querySelectorAll('button[data-action]').forEach(btn => {
        const active = stateMap[btn.dataset.action] ?? false;
        btn.classList.toggle('is-active', active);
    });
}

function enablePlainEditors(wrappers) {
    wrappers.forEach(wrapper => {
        const editorId = wrapper.dataset.editorId;
        const canvas = document.getElementById(editorId);
        const hiddenInput = document.getElementById(editorId + '-input');
        const toolbar = document.getElementById(editorId + '-toolbar');

        if (!canvas || !hiddenInput) return;

        canvas.contentEditable = 'true';
        canvas.classList.add('tiptap-prose');
        canvas.innerHTML = hiddenInput.value || '';

        toolbar?.querySelectorAll('button[data-action]').forEach(btn => {
            btn.addEventListener('mousedown', e => {
                e.preventDefault();
                runPlainAction(btn.dataset.action);
                hiddenInput.value = canvas.innerHTML;
                canvas.focus();
            });
        });

        canvas.addEventListener('input', () => {
            hiddenInput.value = canvas.innerHTML;
        });

        canvas.closest('form')?.addEventListener('submit', () => {
            hiddenInput.value = canvas.innerHTML;
        });
    });
}

function runPlainAction(action) {
    const commandMap = {
        bold: 'bold',
        italic: 'italic',
        underline: 'underline',
        strike: 'strikeThrough',
        bulletList: 'insertUnorderedList',
        orderedList: 'insertOrderedList',
        undo: 'undo',
        redo: 'redo',
        alignLeft: 'justifyLeft',
        alignCenter: 'justifyCenter',
        alignRight: 'justifyRight',
        unlink: 'unlink',
    };

    if (action === 'link') {
        const url = window.prompt('Enter URL:', 'https://');
        if (url) document.execCommand('createLink', false, url);
        return;
    }

    if (action === 'h1' || action === 'h2' || action === 'h3') {
        document.execCommand('formatBlock', false, action.toUpperCase());
        return;
    }

    if (action === 'blockquote') {
        document.execCommand('formatBlock', false, 'BLOCKQUOTE');
        return;
    }

    if (action === 'clearFormatting') {
        document.execCommand('removeFormat', false);
        return;
    }

    if (commandMap[action]) {
        document.execCommand(commandMap[action], false);
    }
}
