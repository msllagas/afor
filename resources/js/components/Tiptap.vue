<script lang="ts" setup>
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import Highlight from '@tiptap/extension-highlight';
import { TextStyle } from '@tiptap/extension-text-style';
import type { EditorView } from '@tiptap/pm/view';
import StarterKit from '@tiptap/starter-kit';
import { type Content, type Editor, EditorContent, Extension, useEditor } from '@tiptap/vue-3';
import {
    Bold,
    Check,
    ChevronDown,
    Code,
    Eraser,
    ExternalLink,
    Highlighter,
    Italic,
    Link2,
    Link2Off,
    List,
    ListOrdered,
    type LucideIcon,
    Minus,
    Redo2,
    SquareCode,
    Strikethrough,
    TextQuote,
    Underline,
    Undo2,
} from 'lucide-vue-next';
import { computed, inject, nextTick, ref, useTemplateRef, watch } from 'vue';

const model = defineModel<string>();
const props = defineProps<{
    /** Shows the description without letting it be edited, such as on an archived board. */
    readonly?: boolean;
}>();
const emit = defineEmits<{
    blur: [html: string];
}>();

const colors = inject<string[]>('colors', []);

const HEADING_LEVELS = [1, 2, 3, 4] as const;
type HeadingLevel = (typeof HEADING_LEVELS)[number];

const isApple = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.userAgent);
const modKey = isApple ? '⌘' : 'Ctrl+';
const shiftKey = isApple ? '⇧' : 'Shift+';

/** Highlights keep a list colour class (e.g. `list-blue`) so they follow light and dark mode. */
const MultiHighlight = Highlight.extend({
    // Typing at the edge of a highlight starts plain text again.
    inclusive: false,
    addAttributes() {
        return {
            ...this.parent?.(),
            class: {
                default: null,
                parseHTML: (element) => element.getAttribute('class'),
                renderHTML: (attributes) => (attributes.class ? { class: attributes.class } : {}),
            },
        };
    },
});

/**
 * Pressing Enter starts the next line as plain text. By default Tiptap carries marks such as bold or a
 * highlight over to the new line, so everything typed there keeps the formatting.
 */
const PlainTextAfterEnter = Extension.create({
    name: 'plainTextAfterEnter',
    onBeforeCreate() {
        this.editor.extensionManager.splittableMarks = [];
    },
});

const toolbar = useTemplateRef<HTMLElement>('toolbar');
const linkInput = useTemplateRef<HTMLInputElement>('link-input');
const isLinkOpen = ref(false);
const isHighlightOpen = ref(false);
const linkUrl = ref('');
const linkError = ref('');

/** Opens the link popover from the keyboard, prefilled with the link under the cursor. */
function openLinkPopover(editor: Editor) {
    linkUrl.value = (editor.getAttributes('link').href as string | undefined) ?? '';
    linkError.value = '';
    isLinkOpen.value = true;
}

const CODE_INDENT = '  ';

/**
 * Tab indents inside a code block instead of moving focus to the next field. Shift+Tab outdents, and
 * when there is nothing left to outdent it falls through, so keyboard users can still leave the editor.
 */
function indentCodeBlock(view: EditorView, outdent: boolean): boolean {
    const { state } = view;
    const { $from, $to, from, to } = state.selection;

    if (!$from.sameParent($to) || $from.parent.type.name !== 'codeBlock') {
        return false;
    }

    const blockStart = $from.start();
    const text = $from.parent.textContent;

    if (!outdent && from === to) {
        view.dispatch(state.tr.insertText(CODE_INDENT, from));

        return true;
    }

    const lastSelected = to > from ? to - blockStart - 1 : to - blockStart;
    const lineStarts = [0, ...[...text.matchAll(/\n/g)].map((match) => match.index + 1)];
    const selectedLineStarts = lineStarts.filter(
        (start, index) => start <= lastSelected && (lineStarts[index + 1] ?? text.length + 1) > from - blockStart,
    );

    const transaction = state.tr;

    // Walks backwards so earlier positions stay valid while the document changes.
    for (const lineStart of selectedLineStarts.reverse()) {
        const position = blockStart + lineStart;

        if (!outdent) {
            transaction.insertText(CODE_INDENT, position);

            continue;
        }

        const leading = /^( {1,2}|\t)/.exec(text.slice(lineStart))?.[0].length ?? 0;

        if (leading) {
            transaction.delete(position, position + leading);
        }
    }

    if (!transaction.docChanged) {
        return false;
    }

    view.dispatch(transaction);

    return true;
}

const editor = useEditor({
    content: (model.value ?? '') as Content,
    editable: !props.readonly,
    extensions: [
        StarterKit.configure({
            heading: { levels: [...HEADING_LEVELS] },
            dropcursor: { color: 'var(--primary)', width: 2 },
            link: {
                openOnClick: false,
                defaultProtocol: 'https',
                protocols: ['mailto'],
                HTMLAttributes: { rel: 'noopener noreferrer nofollow', target: '_blank' },
            },
        }),
        MultiHighlight.configure({ multicolor: true }),
        TextStyle,
        PlainTextAfterEnter,
    ],
    editorProps: {
        attributes: () => ({
            'aria-label': 'Description',
            'aria-multiline': 'true',
            'aria-readonly': props.readonly ? 'true' : 'false',
            role: 'textbox',
        }),
        handleKeyDown: (view, event) => {
            if (event.key === 'Tab' && !event.ctrlKey && !event.metaKey && !event.altKey) {
                const instance = editor.value!;
                let isHandled = indentCodeBlock(view, event.shiftKey);

                // In a list, Tab nests the item and Shift+Tab lifts it. Even when there is nowhere left to nest,
                // the key is swallowed so focus never jumps out of the editor.
                if (!isHandled && instance.isActive('listItem')) {
                    const chain = instance.chain();

                    (event.shiftKey ? chain.liftListItem('listItem') : chain.sinkListItem('listItem')).run();
                    isHandled = true;
                }

                if (isHandled) {
                    event.preventDefault();
                    // The dialog's focus trap wraps Tab from its last field back to the first, so it must not see this key.
                    event.stopPropagation();
                }

                return isHandled;
            }

            if (
                (event.metaKey || event.ctrlKey) &&
                !event.shiftKey &&
                !event.altKey &&
                event.key.toLowerCase() === 'k'
            ) {
                event.preventDefault();
                openLinkPopover(editor.value!);

                return true;
            }

            return false;
        },
        // Links are editable text, so a plain click places the cursor; Ctrl/⌘-click follows the link.
        // A read-only description has no cursor to place, so any click follows it.
        handleClick: (view, pos, event) => {
            if (!props.readonly && !(event.metaKey || event.ctrlKey)) {
                return false;
            }

            const href = (event.target as HTMLElement | null)?.closest('a')?.getAttribute('href');

            if (!href) {
                return false;
            }

            window.open(href, '_blank', 'noopener,noreferrer');

            return true;
        },
    },
    onUpdate: ({ editor }) => {
        model.value = editor.getHTML();
    },
    onBlur: ({ editor }) => {
        emit('blur', editor.getHTML());
    },
});

watch(
    () => props.readonly,
    (readonly) => editor.value?.setEditable(!readonly),
);

// Picks up description changes made elsewhere, without disturbing what the user is typing.
watch(
    () => model.value,
    (value) => {
        const instance = editor.value;

        if (!instance || instance.isFocused || (value ?? '') === instance.getHTML()) {
            return;
        }

        instance.commands.setContent((value ?? '') as Content, { emitUpdate: false });
    },
);

type ToolbarButton = {
    label: string;
    shortcut?: string;
    icon: LucideIcon;
    isActive?: (editor: Editor) => boolean;
    isDisabled?: (editor: Editor) => boolean;
    run: (editor: Editor) => void;
};

const historyButtons: ToolbarButton[] = [
    {
        label: 'Undo',
        shortcut: `${modKey}Z`,
        icon: Undo2,
        isDisabled: (editor) => !editor.can().undo(),
        run: (editor) => editor.chain().focus().undo().run(),
    },
    {
        label: 'Redo',
        shortcut: `${modKey}${shiftKey}Z`,
        icon: Redo2,
        isDisabled: (editor) => !editor.can().redo(),
        run: (editor) => editor.chain().focus().redo().run(),
    },
];

const markButtons: ToolbarButton[] = [
    {
        label: 'Bold',
        shortcut: `${modKey}B`,
        icon: Bold,
        isActive: (editor) => editor.isActive('bold'),
        run: (editor) => editor.chain().focus().toggleBold().run(),
    },
    {
        label: 'Italic',
        shortcut: `${modKey}I`,
        icon: Italic,
        isActive: (editor) => editor.isActive('italic'),
        run: (editor) => editor.chain().focus().toggleItalic().run(),
    },
    {
        label: 'Underline',
        shortcut: `${modKey}U`,
        icon: Underline,
        isActive: (editor) => editor.isActive('underline'),
        run: (editor) => editor.chain().focus().toggleUnderline().run(),
    },
    {
        label: 'Strikethrough',
        shortcut: `${modKey}${shiftKey}S`,
        icon: Strikethrough,
        isActive: (editor) => editor.isActive('strike'),
        run: (editor) => editor.chain().focus().toggleStrike().run(),
    },
    {
        label: 'Inline code',
        shortcut: `${modKey}E`,
        icon: Code,
        isActive: (editor) => editor.isActive('code'),
        run: (editor) => editor.chain().focus().toggleCode().run(),
    },
];

const blockButtons: ToolbarButton[] = [
    {
        label: 'Bulleted list',
        shortcut: `${modKey}${shiftKey}8`,
        icon: List,
        isActive: (editor) => editor.isActive('bulletList'),
        run: (editor) => editor.chain().focus().toggleBulletList().run(),
    },
    {
        label: 'Numbered list',
        shortcut: `${modKey}${shiftKey}7`,
        icon: ListOrdered,
        isActive: (editor) => editor.isActive('orderedList'),
        run: (editor) => editor.chain().focus().toggleOrderedList().run(),
    },
    {
        label: 'Quote',
        shortcut: `${modKey}${shiftKey}B`,
        icon: TextQuote,
        isActive: (editor) => editor.isActive('blockquote'),
        run: (editor) => editor.chain().focus().toggleBlockquote().run(),
    },
    {
        label: 'Code block',
        shortcut: `${modKey}Alt+C`,
        icon: SquareCode,
        isActive: (editor) => editor.isActive('codeBlock'),
        run: (editor) => editor.chain().focus().toggleCodeBlock().run(),
    },
    {
        label: 'Divider',
        icon: Minus,
        run: (editor) => editor.chain().focus().setHorizontalRule().run(),
    },
];

const buttonGroups: ToolbarButton[][] = [markButtons, blockButtons];

const textStyles: Array<{ label: string; level: HeadingLevel | null; class: string }> = [
    { label: 'Normal text', level: null, class: 'text-sm' },
    { label: 'Heading 1', level: 1, class: 'text-xl font-semibold' },
    { label: 'Heading 2', level: 2, class: 'text-lg font-semibold' },
    { label: 'Heading 3', level: 3, class: 'text-base font-semibold' },
    { label: 'Heading 4', level: 4, class: 'text-sm font-semibold' },
];

const highlightColors = computed(() =>
    colors.map((color) => ({
        label: color.charAt(0).toUpperCase() + color.slice(1),
        class: `list-${color.toLowerCase()}`,
    })),
);

function activeTextStyle(editor: Editor) {
    return textStyles.find(({ level }) => level !== null && editor.isActive('heading', { level })) ?? textStyles[0];
}

function setTextStyle(editor: Editor, level: HeadingLevel | null) {
    if (level === null) {
        editor.chain().focus().setParagraph().run();
    } else {
        editor.chain().focus().setHeading({ level }).run();
    }
}

function activeHighlightClass(editor: Editor): string | null {
    return editor.isActive('highlight') ? ((editor.getAttributes('highlight').class as string | null) ?? null) : null;
}

function toggleHighlight(editor: Editor, highlightClass: string) {
    if (editor.isActive('highlight', { class: highlightClass })) {
        editor.chain().focus().unsetMark('highlight').run();
    } else {
        editor.chain().focus().setMark('highlight', { class: highlightClass }).run();
    }

    isHighlightOpen.value = false;
}

function removeHighlight(editor: Editor) {
    editor.chain().focus().unsetMark('highlight').run();
    isHighlightOpen.value = false;
}

/** Adds `https://` (or `mailto:` for addresses) when the user typed a bare domain. */
function normalizeUrl(value: string): string {
    const url = value.trim();

    if (/^([a-z][a-z0-9+.-]*:|\/|#)/i.test(url)) {
        return url;
    }

    return /^[^\s@/]+@[^\s@/]+\.[^\s@/]+$/.test(url) ? `mailto:${url}` : `https://${url}`;
}

function applyLink(editor: Editor) {
    const url = linkUrl.value.trim();

    if (!url) {
        removeLink(editor);

        return;
    }

    const href = normalizeUrl(url);
    const { empty } = editor.state.selection;
    const chain = editor.chain().focus();

    // Without a selection there is nothing to attach the link to, so the address becomes the link text.
    const applied =
        empty && !editor.isActive('link')
            ? chain.insertContent({ type: 'text', text: href, marks: [{ type: 'link', attrs: { href } }] }).run()
            : chain.extendMarkRange('link').setLink({ href }).run();

    if (!applied) {
        linkError.value = 'Enter a valid web or email address.';
        nextTick(() => linkInput.value?.focus());

        return;
    }

    isLinkOpen.value = false;
}

function removeLink(editor: Editor) {
    editor.chain().focus().extendMarkRange('link').unsetLink().run();
    isLinkOpen.value = false;
}

function onLinkOpenChange(open: boolean, editor: Editor) {
    if (open) {
        openLinkPopover(editor);

        return;
    }

    isLinkOpen.value = false;
}

/** Arrow keys, Home and End move between toolbar buttons, as the ARIA toolbar pattern asks. */
function onToolbarKeydown(event: KeyboardEvent) {
    const keys = ['ArrowLeft', 'ArrowRight', 'Home', 'End'];

    if (!keys.includes(event.key) || !toolbar.value) {
        return;
    }

    const items = Array.from(toolbar.value.querySelectorAll<HTMLElement>('button:not(:disabled)'));
    const current = items.indexOf(event.target as HTMLElement);

    if (current === -1) {
        return;
    }

    const next = {
        ArrowLeft: (current - 1 + items.length) % items.length,
        ArrowRight: (current + 1) % items.length,
        Home: 0,
        End: items.length - 1,
    }[event.key as 'ArrowLeft' | 'ArrowRight' | 'Home' | 'End'];

    event.preventDefault();
    items[next]?.focus();
}

const toolButtonClass =
    'inline-flex size-8 cursor-pointer items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-background hover:text-foreground hover:shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-40 aria-pressed:bg-primary/15 aria-pressed:text-primary data-[state=open]:bg-background data-[state=open]:text-foreground data-[state=open]:shadow-xs dark:hover:bg-background/60 dark:data-[state=open]:bg-background/60 [&_svg]:size-4';

const popoverActionClass =
    'flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm text-muted-foreground transition-colors outline-none hover:bg-muted hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50';
</script>

<template>
    <div v-if="editor" class="flex flex-col">
        <TooltipProvider v-if="!readonly" :delay-duration="400">
            <!-- mousedown.prevent keeps the editor focused, so formatting applies to the current selection. -->
            <div
                ref="toolbar"
                aria-label="Text formatting"
                class="sticky top-0 z-10 flex flex-wrap items-center gap-0.5 rounded-t-[inherit] border-b bg-border/50 px-2 py-1.5 dark:bg-muted/40"
                role="toolbar"
                @keydown="onToolbarKeydown"
            >
                <Tooltip v-for="button in historyButtons" :key="button.label">
                    <TooltipTrigger as-child>
                        <button
                            :aria-keyshortcuts="button.shortcut"
                            :aria-label="button.label"
                            :class="toolButtonClass"
                            :disabled="button.isDisabled?.(editor)"
                            type="button"
                            @click="button.run(editor)"
                            @mousedown.prevent
                        >
                            <component :is="button.icon" aria-hidden="true" />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent>
                        {{ button.label }} <span class="opacity-70">{{ button.shortcut }}</span>
                    </TooltipContent>
                </Tooltip>

                <Separator class="mx-1 h-5!" orientation="vertical" />

                <DropdownMenu>
                    <DropdownMenuTrigger
                        :class="cn(toolButtonClass, 'w-auto gap-1 px-2 text-sm font-medium')"
                        aria-label="Text style"
                    >
                        <span class="hidden sm:inline">{{ activeTextStyle(editor).label }}</span>
                        <span class="sm:hidden">{{
                            activeTextStyle(editor).level ? `H${activeTextStyle(editor).level}` : 'Aa'
                        }}</span>
                        <ChevronDown aria-hidden="true" class="size-3.5!" />
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start" class="w-52" @close-auto-focus.prevent="editor.commands.focus()">
                        <DropdownMenuItem
                            v-for="style in textStyles"
                            :key="style.label"
                            class="cursor-pointer gap-2"
                            @select="setTextStyle(editor, style.level)"
                        >
                            <span :class="style.class" class="flex-1">{{ style.label }}</span>
                            <Check
                                v-if="activeTextStyle(editor).label === style.label"
                                aria-hidden="true"
                                class="size-4 text-primary"
                            />
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <template v-for="(group, index) in buttonGroups" :key="index">
                    <Separator class="mx-1 h-5!" orientation="vertical" />

                    <Tooltip v-for="button in group" :key="button.label">
                        <TooltipTrigger as-child>
                            <button
                                :aria-keyshortcuts="button.shortcut"
                                :aria-label="button.label"
                                :aria-pressed="button.isActive ? button.isActive(editor) : undefined"
                                :class="toolButtonClass"
                                type="button"
                                @click="button.run(editor)"
                                @mousedown.prevent
                            >
                                <component :is="button.icon" aria-hidden="true" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent>
                            {{ button.label }}
                            <span v-if="button.shortcut" class="opacity-70">{{ button.shortcut }}</span>
                        </TooltipContent>
                    </Tooltip>

                    <!-- The link button sits with the inline formatting, right after the marks. -->
                    <Popover v-if="index === 0" :open="isLinkOpen" @update:open="onLinkOpenChange($event, editor)">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <PopoverTrigger
                                    :aria-keyshortcuts="`${modKey}K`"
                                    :aria-pressed="editor.isActive('link')"
                                    :class="toolButtonClass"
                                    aria-label="Link"
                                >
                                    <Link2 aria-hidden="true" />
                                </PopoverTrigger>
                            </TooltipTrigger>
                            <TooltipContent
                                >Link <span class="opacity-70">{{ modKey }}K</span></TooltipContent
                            >
                        </Tooltip>
                        <PopoverContent
                            align="start"
                            class="w-[min(20rem,calc(100vw-2rem))] p-3"
                            @close-auto-focus.prevent="editor.commands.focus()"
                            @open-auto-focus.prevent="linkInput?.focus()"
                        >
                            <form class="space-y-2" @submit.prevent="applyLink(editor)">
                                <label class="text-xs font-medium text-muted-foreground" for="tiptap-link-url">
                                    Link address
                                </label>
                                <div class="flex gap-1.5">
                                    <input
                                        id="tiptap-link-url"
                                        ref="link-input"
                                        v-model="linkUrl"
                                        :aria-describedby="linkError ? 'tiptap-link-error' : undefined"
                                        :aria-invalid="linkError ? true : undefined"
                                        autocapitalize="off"
                                        autocomplete="off"
                                        class="h-8 min-w-0 flex-1 rounded-md border border-input bg-transparent px-2.5 text-base outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive sm:text-sm"
                                        inputmode="url"
                                        placeholder="example.com"
                                        spellcheck="false"
                                        type="text"
                                        @input="linkError = ''"
                                    />
                                    <button
                                        class="inline-flex h-8 cursor-pointer items-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground transition-opacity outline-none hover:opacity-90 focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        type="submit"
                                    >
                                        Apply
                                    </button>
                                </div>
                                <p
                                    v-if="linkError"
                                    id="tiptap-link-error"
                                    class="text-xs text-destructive"
                                    role="alert"
                                >
                                    {{ linkError }}
                                </p>
                            </form>
                            <div v-if="editor.isActive('link')" class="mt-2 border-t pt-2">
                                <a
                                    :class="popoverActionClass"
                                    :href="editor.getAttributes('link').href"
                                    rel="noopener noreferrer nofollow"
                                    target="_blank"
                                >
                                    <ExternalLink aria-hidden="true" class="size-4" />
                                    Open link
                                </a>
                                <button :class="popoverActionClass" type="button" @click="removeLink(editor)">
                                    <Link2Off aria-hidden="true" class="size-4" />
                                    Remove link
                                </button>
                            </div>
                        </PopoverContent>
                    </Popover>
                </template>

                <Separator class="mx-1 h-5!" orientation="vertical" />

                <Popover v-model:open="isHighlightOpen">
                    <PopoverTrigger
                        :class="cn(toolButtonClass, 'relative', editor.isActive('highlight') && 'text-foreground')"
                        aria-label="Highlight"
                    >
                        <Highlighter aria-hidden="true" />
                        <!-- Shows the colour under the cursor, like the ink in a highlighter pen. -->
                        <span
                            :class="activeHighlightClass(editor) ?? 'list-angel'"
                            aria-hidden="true"
                            class="absolute inset-x-2 bottom-1 h-1 rounded-full ring-1 ring-black/10 [background:var(--list-bg)] dark:ring-white/15"
                        />
                    </PopoverTrigger>
                    <PopoverContent
                        align="start"
                        class="w-auto p-3"
                        @close-auto-focus.prevent="editor.commands.focus()"
                    >
                        <p class="mb-2 text-xs font-medium text-muted-foreground">Highlight</p>
                        <div class="grid grid-cols-5 gap-1.5">
                            <button
                                v-for="color in highlightColors"
                                :key="color.class"
                                :aria-label="`${color.label} highlight`"
                                :aria-pressed="editor.isActive('highlight', { class: color.class })"
                                :class="color.class"
                                :title="color.label"
                                class="flex size-8 cursor-pointer items-center justify-center rounded-md text-sm font-semibold text-(--list-fg) ring-1 ring-black/10 transition-transform outline-none [background:var(--list-bg)] hover:scale-110 focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-pressed:ring-2 aria-pressed:ring-primary motion-reduce:hover:scale-100 dark:ring-white/15"
                                type="button"
                                @click="toggleHighlight(editor, color.class)"
                            >
                                A
                            </button>
                        </div>
                        <button
                            :class="cn(popoverActionClass, 'mt-2')"
                            :disabled="!editor.isActive('highlight')"
                            type="button"
                            @click="removeHighlight(editor)"
                        >
                            <Eraser aria-hidden="true" class="size-4" />
                            Remove highlight
                        </button>
                    </PopoverContent>
                </Popover>
            </div>
        </TooltipProvider>

        <!-- The text scrolls inside the editor, so the toolbar stays put and the dialog keeps room below. -->
        <div
            :class="{ 'cursor-text': !readonly }"
            class="relative max-h-[min(22rem,45dvh)] min-h-32 overflow-y-auto overscroll-contain bg-muted/50 px-4 py-3.5 dark:bg-card"
            @mousedown.self.prevent="!readonly && editor.commands.focus('end')"
        >
            <p
                v-if="editor.isEmpty"
                aria-hidden="true"
                class="pointer-events-none absolute top-3.5 left-4 text-base text-muted-foreground sm:text-sm"
            >
                {{ readonly ? 'No description.' : 'Add a more detailed description…' }}
            </p>
            <EditorContent :editor="editor" />
        </div>
    </div>
</template>
