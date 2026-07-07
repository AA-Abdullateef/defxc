{{--
    TipTap Rich Text Editor Component
    Usage: @include('components.tiptap-editor', ['editorId' => 'editor', 'inputName' => 'report', 'placeholder' => '...', 'value' => ''])
--}}

@props([
    'editorId'    => 'editor',
    'inputName'   => 'report',
    'placeholder' => 'Start typing...',
    'value'       => '',
    'minHeight'   => '200px',
])

<div class="tiptap-wrapper" data-editor-id="{{ $editorId }}" data-input-name="{{ $inputName }}">

    {{-- Toolbar --}}
    <div class="tiptap-toolbar" id="{{ $editorId }}-toolbar">

        {{-- History --}}
        <button type="button" data-action="undo" title="Undo (Ctrl+Z)"><i class="bi bi-arrow-counterclockwise"></i></button>
        <button type="button" data-action="redo" title="Redo (Ctrl+Y)"><i class="bi bi-arrow-clockwise"></i></button>

        <div class="toolbar-divider"></div>

        {{-- Text style --}}
        <button type="button" data-action="bold"          title="Bold (Ctrl+B)"><i class="bi bi-type-bold"></i></button>
        <button type="button" data-action="italic"        title="Italic (Ctrl+I)"><i class="bi bi-type-italic"></i></button>
        <button type="button" data-action="underline"     title="Underline (Ctrl+U)"><i class="bi bi-type-underline"></i></button>
        <button type="button" data-action="strike"        title="Strikethrough"><i class="bi bi-type-strikethrough"></i></button>
        <button type="button" data-action="highlight"     title="Highlight"><i class="bi bi-highlighter"></i></button>

        <div class="toolbar-divider"></div>

        {{-- Headings --}}
        <button type="button" data-action="h1"  title="Heading 1"><b>H1</b></button>
        <button type="button" data-action="h2"  title="Heading 2"><b>H2</b></button>
        <button type="button" data-action="h3"  title="Heading 3"><b>H3</b></button>

        <div class="toolbar-divider"></div>

        {{-- Lists --}}
        <button type="button" data-action="bulletList"   title="Bullet List"><i class="bi bi-list-ul"></i></button>
        <button type="button" data-action="orderedList"  title="Numbered List"><i class="bi bi-list-ol"></i></button>

        <div class="toolbar-divider"></div>

        {{-- Block --}}
        <button type="button" data-action="blockquote"  title="Blockquote"><i class="bi bi-blockquote-left"></i></button>
        <button type="button" data-action="codeBlock"   title="Code Block"><i class="bi bi-code-slash"></i></button>
        <button type="button" data-action="code"        title="Inline Code"><i class="bi bi-code"></i></button>
        <button type="button" data-action="horizontalRule" title="Horizontal Rule"><i class="bi bi-dash-lg"></i></button>

        <div class="toolbar-divider"></div>

        {{-- Alignment --}}
        <button type="button" data-action="alignLeft"   title="Align Left"><i class="bi bi-text-left"></i></button>
        <button type="button" data-action="alignCenter" title="Align Center"><i class="bi bi-text-center"></i></button>
        <button type="button" data-action="alignRight"  title="Align Right"><i class="bi bi-text-right"></i></button>

        <div class="toolbar-divider"></div>

        {{-- Link --}}
        <button type="button" data-action="link"        title="Insert Link"><i class="bi bi-link-45deg"></i></button>
        <button type="button" data-action="unlink"      title="Remove Link"><i class="bi bi-link"></i></button>

        <div class="toolbar-divider"></div>

        {{-- Sub/Super --}}
        <button type="button" data-action="subscript"   title="Subscript"><i class="bi bi-subscript"></i></button>
        <button type="button" data-action="superscript" title="Superscript"><i class="bi bi-superscript"></i></button>

        <div class="toolbar-divider"></div>

        {{-- Clear --}}
        <button type="button" data-action="clearFormatting" title="Clear Formatting"><i class="bi bi-eraser"></i></button>

    </div>

    {{-- Editor canvas --}}
    <div id="{{ $editorId }}"
         class="tiptap-editor-canvas"
         style="min-height: {{ $minHeight }};"
         data-placeholder="{{ $placeholder }}">
    </div>

    {{-- Hidden input that holds the HTML submitted with the form --}}
    <input type="hidden" name="{{ $inputName }}" id="{{ $editorId }}-input" value="{{ old($inputName, $value) }}" />

</div>
