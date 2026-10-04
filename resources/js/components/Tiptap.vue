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
import StarterKit from '@tiptap/starter-kit';
import { type Content, type Editor, EditorContent, useEditor } from '@tiptap/vue-3';
import {
    Bold,
    Check,
    ChevronDown,
    Eraser,
    Highlighter,
    Italic,
    List,
    ListOrdered,
    type LucideIcon,
    Strikethrough,
    TextQuote,
    Underline,
} from 'lucide-vue-next';
import { computed, inject } from 'vue';

const model = defineModel<string>();
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

const editor = useEditor({
    content: (model.value ?? '') as Content,
    extensions: [
        StarterKit.configure({ heading: { levels: [...HEADING_LEVELS] }, link: { openOnClick: false } }),
        MultiHighlight.configure({ multicolor: true }),
        TextStyle,
    ],
    editorProps: {
        attributes: {
            'aria-label': 'Description',
            'aria-multiline': 'true',
            role: 'textbox',
        },
    },
    onUpdate: ({ editor }) => {
        model.value = editor.getHTML();
    },
    onBlur: ({ editor }) => {
        emit('blur', editor.getHTML());
    },
});

type ToolbarButton = {
    label: string;
    shortcut: string;
    icon: LucideIcon;
    isActive: (editor: Editor) => boolean;
    run: (editor: Editor) => void;
};

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
];

const textStyles: Array<{ label: string; level: HeadingLevel | null; class: string }> = [
    { label: 'Normal text', level: null, class: 'text-sm' },
    { label: 'Heading 1', level: 1, class: 'font-display text-xl font-semibold' },
    { label: 'Heading 2', level: 2, class: 'font-display text-lg font-semibold' },
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
}

function removeHighlight(editor: Editor) {
    editor.chain().focus().unsetMark('highlight').run();
}

const toolButtonClass =
    'inline-flex size-8 cursor-pointer items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-muted hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-pressed:bg-accent aria-pressed:text-accent-foreground data-[state=open]:bg-muted data-[state=open]:text-foreground [&_svg]:size-4';
</script>

<template>
    <div v-if="editor" class="flex flex-col">
        <TooltipProvider :delay-duration="400">
            <!-- mousedown.prevent keeps the editor focused, so formatting applies to the current selection. -->
            <div
                aria-label="Text formatting"
                class="sticky top-0 z-10 flex flex-wrap items-center gap-0.5 rounded-t-[inherit] border-b bg-background px-1.5 py-1"
                role="toolbar"
            >
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
                    <DropdownMenuContent align="start" class="w-52">
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

                <Separator class="mx-1 h-5!" orientation="vertical" />

                <Tooltip v-for="button in markButtons" :key="button.label">
                    <TooltipTrigger as-child>
                        <button
                            :aria-keyshortcuts="button.shortcut"
                            :aria-label="button.label"
                            :aria-pressed="button.isActive(editor)"
                            :class="toolButtonClass"
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

                <Tooltip v-for="button in blockButtons" :key="button.label">
                    <TooltipTrigger as-child>
                        <button
                            :aria-keyshortcuts="button.shortcut"
                            :aria-label="button.label"
                            :aria-pressed="button.isActive(editor)"
                            :class="toolButtonClass"
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

                <Popover>
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
                    <PopoverContent align="start" class="w-auto p-3">
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
                            :disabled="!editor.isActive('highlight')"
                            class="mt-2 flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm text-muted-foreground transition-colors outline-none hover:bg-muted hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50"
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
        <div class="relative max-h-[min(22rem,45dvh)] overflow-y-auto overscroll-contain px-3 py-2.5">
            <p
                v-if="editor.isEmpty"
                aria-hidden="true"
                class="pointer-events-none absolute top-2.5 left-3 text-sm text-muted-foreground"
            >
                Add a more detailed description…
            </p>
            <EditorContent :editor="editor" />
        </div>
    </div>
</template>
