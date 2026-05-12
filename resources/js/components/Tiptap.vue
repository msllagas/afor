<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import Highlight from '@tiptap/extension-highlight';
import { TextStyle } from '@tiptap/extension-text-style';
import StarterKit from '@tiptap/starter-kit';
import { Content, EditorContent, useEditor } from '@tiptap/vue-3';
import {
    Bold,
    Heading1,
    Heading2,
    Heading3,
    Heading4,
    HighlighterIcon,
    Italic,
    List,
    Underline,
} from 'lucide-vue-next';
import { computed, inject } from 'vue';

const model = defineModel();
const emit = defineEmits(['update:modelValue', 'blur']);

const colors = inject('colors') as string[];

const headings = [1, 2, 3, 4] as const;
const headingIcons = {
    1: Heading1,
    2: Heading2,
    3: Heading3,
    4: Heading4,
} as const;
const headingClasses = {
    1: 'text-3xl font-bold',
    2: 'text-2xl font-semibold',
    3: 'text-xl font-semibold',
    4: 'text-lg font-medium',
} as const;

StarterKit.configure({
    heading: {
        levels: [...headings],
    },
});
const MultiHighlight = Highlight.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            class: {
                default: null,
                parseHTML: (el) => el.getAttribute('class'),
                renderHTML: (attrs) => (attrs.class ? { class: attrs.class } : {}),
            },
        };
    },
});
const editor = useEditor({
    content: model.value as Content,
    extensions: [StarterKit, MultiHighlight.configure({ multicolor: true }), TextStyle],
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
    onBlur: ({ editor }) => {
        emit('blur', editor.getHTML());
    },
});

const activeHeading = computed(() => {
    for (const level of headings) {
        if (editor && editor.value?.isActive('heading', { level })) return level;
    }
    return null;
});

const highlightColors = colors.map((color) => ({
    label: color,
    class: `list-${color.toLowerCase()}`,
}));

function toggleHighlight(cls: string) {
    (editor.value?.chain().focus().toggleHighlight as any)({ class: cls }).run();
}
</script>
<template>
    <div v-if="editor">
        <div class="flex h-12 items-center gap-2 p-2">
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button class="rounded-2xl" size="icon" variant="ghost">
                        <component
                            :is="headingIcons[activeHeading ?? 1]"
                            :class="{ 'tiptap-active': activeHeading !== null }"
                        />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" class="w-56" side="bottom">
                    <DropdownMenuGroup>
                        <DropdownMenuItem
                            v-for="level in headings"
                            :key="level"
                            :class="{ 'bg-[#ffffff33]': editor.isActive('heading', { level }) }"
                            @click="editor.chain().focus().toggleHeading({ level }).run()"
                        >
                            <span
                                :class="[
                                    headingClasses[level],
                                    { 'tiptap-active': editor.isActive('heading', { level }) },
                                ]"
                            >
                                Heading {{ level }}
                            </span>
                        </DropdownMenuItem>
                    </DropdownMenuGroup>
                </DropdownMenuContent>
            </DropdownMenu>
            <Button
                class="rounded-2xl"
                size="icon"
                variant="ghost"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <List :class="{ 'tiptap-active': editor.isActive('bulletList') }" />
            </Button>
            <Separator orientation="vertical" />
            <div>
                <Button
                    class="rounded-2xl"
                    size="icon"
                    variant="ghost"
                    @click="editor.chain().focus().toggleBold().run()"
                >
                    <Bold :class="{ 'tiptap-active': editor.isActive('bold') }" />
                </Button>
                <Button
                    class="rounded-2xl"
                    size="icon"
                    variant="ghost"
                    @click="editor.chain().focus().toggleItalic().run()"
                >
                    <Italic :class="{ 'tiptap-active': editor.isActive('italic') }" />
                </Button>
                <Button
                    class="rounded-2xl text-sm"
                    size="icon"
                    variant="ghost"
                    @click="editor.chain().focus().toggleUnderline().run()"
                >
                    <Underline :class="{ 'tiptap-active': editor.isActive('underline') }" />
                </Button>
            </div>
            <Separator orientation="vertical" />
            <Popover>
                <PopoverTrigger>
                    <Button class="rounded-2xl" size="icon" variant="ghost">
                        <HighlighterIcon :class="{ 'tiptap-active': editor.isActive('highlight') }" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent class="space-y-2 space-x-2">
                    <Button
                        v-for="{ class: cls } in highlightColors"
                        :key="cls"
                        :class="[
                            cls,
                            'cursor-pointer',
                            { 'tiptap-active': editor.isActive('highlight', { class: cls }) },
                        ]"
                        :style="{ backgroundColor: 'var(--list-fg-muted)', color: 'var(--list-bg)' }"
                        @click="toggleHighlight(cls)"
                    >
                        A
                    </Button>
                </PopoverContent>
            </Popover>
        </div>
        <div class="p-4">
            <editor-content :editor="editor" />
        </div>
    </div>
</template>

<style scoped></style>
