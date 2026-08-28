<template>
    <div class="rich-editor-wrapper border border-300 border-round-lg overflow-hidden" :class="{ 'border-red-500': hasError }">
        <!-- TOOLBAR -->
        <div v-if="editor" class="editor-toolbar flex flex-wrap gap-1 p-2 surface-100 border-bottom-1 border-300">
            <!-- Text Formatting -->
            <button type="button" @click="editor.chain().focus().toggleBold().run()" :class="{ 'active': editor.isActive('bold') }" class="toolbar-btn" title="Bold">
                <b>B</b>
            </button>
            <button type="button" @click="editor.chain().focus().toggleItalic().run()" :class="{ 'active': editor.isActive('italic') }" class="toolbar-btn" title="Italic">
                <i>I</i>
            </button>
            <button type="button" @click="editor.chain().focus().toggleUnderline().run()" :class="{ 'active': editor.isActive('underline') }" class="toolbar-btn" title="Underline">
                <u>U</u>
            </button>
            <button type="button" @click="editor.chain().focus().toggleStrike().run()" :class="{ 'active': editor.isActive('strike') }" class="toolbar-btn" title="Strikethrough">
                <s>S</s>
            </button>

            <div class="toolbar-divider"></div>

            <!-- Headings -->
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" :class="{ 'active': editor.isActive('heading', { level: 1 }) }" class="toolbar-btn text-xs font-bold" title="Heading 1">H1</button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :class="{ 'active': editor.isActive('heading', { level: 2 }) }" class="toolbar-btn text-xs font-bold" title="Heading 2">H2</button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" :class="{ 'active': editor.isActive('heading', { level: 3 }) }" class="toolbar-btn text-xs font-bold" title="Heading 3">H3</button>

            <div class="toolbar-divider"></div>

            <!-- Lists -->
            <button type="button" @click="editor.chain().focus().toggleBulletList().run()" :class="{ 'active': editor.isActive('bulletList') }" class="toolbar-btn" title="Bullet List">
                <i class="pi pi-list text-xs"></i>
            </button>
            <button type="button" @click="editor.chain().focus().toggleOrderedList().run()" :class="{ 'active': editor.isActive('orderedList') }" class="toolbar-btn" title="Numbered List">
                <span class="text-xs font-bold">1.</span>
            </button>

            <div class="toolbar-divider"></div>

            <!-- Text Alignment -->
            <button type="button" @click="editor.chain().focus().setTextAlign('left').run()" :class="{ 'active': editor.isActive({ textAlign: 'left' }) }" class="toolbar-btn" title="Align Left">
                <i class="pi pi-align-left text-xs"></i>
            </button>
            <button type="button" @click="editor.chain().focus().setTextAlign('center').run()" :class="{ 'active': editor.isActive({ textAlign: 'center' }) }" class="toolbar-btn" title="Align Center">
                <i class="pi pi-align-center text-xs"></i>
            </button>
            <button type="button" @click="editor.chain().focus().setTextAlign('right').run()" :class="{ 'active': editor.isActive({ textAlign: 'right' }) }" class="toolbar-btn" title="Align Right">
                <i class="pi pi-align-right text-xs"></i>
            </button>

            <div class="toolbar-divider"></div>

            <!-- Color -->
            <label class="toolbar-btn relative p-0 overflow-hidden" title="Text Color">
                <span class="flex align-items-center justify-content-center w-full h-full gap-1 px-2">
                    <span class="font-bold text-xs" :style="{ color: currentColor }">A</span>
                    <span class="block w-full h-1 border-round" :style="{ backgroundColor: currentColor, width: '12px' }"></span>
                </span>
                <input type="color" class="absolute opacity-0 inset-0 w-full h-full cursor-pointer" :value="currentColor" @input="(e) => editor.chain().focus().setColor(e.target.value).run()" />
            </label>

            <div class="toolbar-divider"></div>

            <!-- Code & Blockquote -->
            <button type="button" @click="editor.chain().focus().toggleCode().run()" :class="{ 'active': editor.isActive('code') }" class="toolbar-btn" title="Inline Code">
                <span class="text-xs font-mono">&lt;/&gt;</span>
            </button>
            <button type="button" @click="editor.chain().focus().toggleBlockquote().run()" :class="{ 'active': editor.isActive('blockquote') }" class="toolbar-btn" title="Blockquote">
                <span class="text-xs">"</span>
            </button>
            <button type="button" @click="editor.chain().focus().setHorizontalRule().run()" class="toolbar-btn" title="Horizontal Rule">
                <span class="text-xs">—</span>
            </button>

            <div class="toolbar-divider"></div>

            <!-- Link -->
            <button type="button" @click="addLink" :class="{ 'active': editor.isActive('link') }" class="toolbar-btn" title="Insert Link">
                <i class="pi pi-link text-xs"></i>
            </button>

            <!-- Image Upload -->
            <label 
                class="toolbar-btn cursor-pointer" 
                :class="{ 'opacity-50 pointer-events-none': isUploading }" 
                :title="isUploading ? 'Mengupload...' : 'Upload Gambar ke Server'"
            >
                <span v-if="isUploading" class="text-xs" style="animation: spin 1s linear infinite; display:inline-block;">⏳</span>
                <i v-else class="pi pi-image text-xs"></i>
                <input type="file" class="hidden" accept="image/*" @change="handleImageUpload" :disabled="isUploading" />
            </label>

            <!-- Image URL -->
            <button type="button" @click="addImageUrl" class="toolbar-btn" title="Insert Image from URL">
                <i class="pi pi-globe text-xs"></i>
            </button>

            <div class="toolbar-divider"></div>

            <!-- Undo/Redo -->
            <button type="button" @click="editor.chain().focus().undo().run()" :disabled="!editor.can().undo()" class="toolbar-btn" title="Undo">
                <i class="pi pi-undo text-xs"></i>
            </button>
            <button type="button" @click="editor.chain().focus().redo().run()" :disabled="!editor.can().redo()" class="toolbar-btn" title="Redo">
                <i class="pi pi-refresh text-xs"></i>
            </button>
        </div>

        <!-- EDITOR CONTENT -->
        <EditorContent :editor="editor" class="editor-content" />

        <!-- Image URL Dialog -->
        <div v-if="showImageUrlDialog" class="fixed inset-0 z-50 flex align-items-center justify-content-center" style="background:rgba(0,0,0,0.5);">
            <div class="surface-card border-round-xl p-4 shadow-4" style="min-width:340px">
                <h4 class="font-bold text-900 mb-3 m-0">Insert Image from URL</h4>
                <input v-model="imageUrlInput" type="url" placeholder="https://..." class="p-inputtext w-full mb-3" @keydown.enter="insertImageFromUrl" />
                <div class="flex gap-2 justify-content-end">
                    <button type="button" @click="showImageUrlDialog=false" class="p-button p-button-secondary p-button-outlined p-button-sm">Batal</button>
                    <button type="button" @click="insertImageFromUrl" class="p-button p-button-primary p-button-sm">Insert</button>
                </div>
            </div>
        </div>

        <!-- Link Dialog -->
        <div v-if="showLinkDialog" class="fixed inset-0 z-50 flex align-items-center justify-content-center" style="background:rgba(0,0,0,0.5);">
            <div class="surface-card border-round-xl p-4 shadow-4" style="min-width:340px">
                <h4 class="font-bold text-900 mb-3 m-0">Insert Link</h4>
                <input v-model="linkUrlInput" type="url" placeholder="https://..." class="p-inputtext w-full mb-3" @keydown.enter="insertLink" />
                <div class="flex gap-2 justify-content-end">
                    <button type="button" @click="showLinkDialog=false" class="p-button p-button-secondary p-button-outlined p-button-sm">Batal</button>
                    <button type="button" @click="insertLink" class="p-button p-button-primary p-button-sm">Insert</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onBeforeUnmount, computed } from 'vue';
import axios from 'axios';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';
import { TextStyle } from '@tiptap/extension-text-style';
import { Color } from '@tiptap/extension-color';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Tuliskan teks soal di sini...' },
    hasError: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const showImageUrlDialog = ref(false);
const showLinkDialog = ref(false);
const imageUrlInput = ref('');
const linkUrlInput = ref('');
const isUploading = ref(false);

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [
        StarterKit.configure({
            link: false,
            underline: false,
        }),
        Underline,
        TextStyle,
        Color,
        Image.configure({ inline: false, allowBase64: true }),
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
        Link.configure({ openOnClick: false, HTMLAttributes: { rel: 'noopener noreferrer', target: '_blank' } }),
        Placeholder.configure({ placeholder: props.placeholder }),
    ],
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
    editorProps: {
        attributes: {
            class: 'rich-editor-body focus:outline-none',
        },
    },
});

// Sync incoming prop changes (e.g. when editing existing question)
watch(() => props.modelValue, (newVal) => {
    if (editor.value && newVal !== editor.value.getHTML()) {
        editor.value.commands.setContent(newVal || '', false);
    }
});

const currentColor = computed(() => editor.value?.getAttributes('textStyle')?.color || '#374151');

const handleImageUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    isUploading.value = true;
    try {
        const formData = new FormData();
        formData.append('image', file);

        const response = await axios.post(route('guru.assignments.upload_image'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'Accept': 'application/json'
            }
        });

        const data = response.data;
        editor.value.chain().focus().setImage({ src: data.url, alt: file.name }).run();
    } catch (err) {
        let errorMessage = 'Gagal mengupload gambar. ';
        if (err.response && err.response.status === 422) {
            const errors = err.response.data.errors;
            if (errors && errors.image) {
                errorMessage += errors.image[0];
            } else {
                errorMessage += err.response.data.message || 'File tidak valid atau terlalu besar.';
            }
        } else if (err.response && err.response.status === 413) {
            errorMessage += 'File gambar terlalu besar melebihi batas server.';
        } else {
            errorMessage += err.message || 'Unknown error';
        }
        alert(errorMessage);
        console.error('Image upload error:', err);
    } finally {
        isUploading.value = false;
        event.target.value = '';
    }
};

const addImageUrl = () => {
    imageUrlInput.value = '';
    showImageUrlDialog.value = true;
};

const insertImageFromUrl = () => {
    if (imageUrlInput.value) {
        editor.value.chain().focus().setImage({ src: imageUrlInput.value }).run();
    }
    showImageUrlDialog.value = false;
};

const addLink = () => {
    const previousUrl = editor.value.getAttributes('link').href;
    linkUrlInput.value = previousUrl || '';
    showLinkDialog.value = true;
};

const insertLink = () => {
    if (linkUrlInput.value === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
    } else {
        editor.value.chain().focus().extendMarkRange('link').setLink({ href: linkUrlInput.value }).run();
    }
    showLinkDialog.value = false;
};

onBeforeUnmount(() => {
    editor.value?.destroy();
});
</script>

<style>
/* Toolbar Buttons */
.toolbar-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 28px;
    padding: 0 6px;
    border-radius: 6px;
    border: 1px solid transparent;
    background: transparent;
    cursor: pointer;
    font-size: 13px;
    color: #374151;
    transition: all 0.15s;
    line-height: 1;
}
.toolbar-btn:hover {
    background: #e5e7eb;
    border-color: #d1d5db;
}
.toolbar-btn.active {
    background: #dbeafe;
    border-color: #93c5fd;
    color: #1d4ed8;
    font-weight: 700;
}
.toolbar-btn:disabled {
    opacity: 0.35;
    cursor: not-allowed;
}
.toolbar-divider {
    width: 1px;
    height: 24px;
    background: #e5e7eb;
    margin: 0 2px;
    align-self: center;
}

/* Tiptap Editor Body */
.ProseMirror {
    padding: 12px 16px;
    min-height: 140px;
    outline: none;
    font-size: 15px;
    color: #111827;
    line-height: 1.7;
}
.ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    color: #9ca3af;
    pointer-events: none;
    float: left;
    height: 0;
}
.ProseMirror h1 { font-size: 1.6em; font-weight: 700; margin: 8px 0 4px; }
.ProseMirror h2 { font-size: 1.3em; font-weight: 700; margin: 8px 0 4px; }
.ProseMirror h3 { font-size: 1.1em; font-weight: 700; margin: 8px 0 4px; }
.ProseMirror ul { list-style: disc; padding-left: 20px; }
.ProseMirror ol { list-style: decimal; padding-left: 20px; }
.ProseMirror blockquote { border-left: 4px solid #60a5fa; margin: 8px 0; padding: 4px 12px; color: #374151; background: #f0f9ff; border-radius: 4px; }
.ProseMirror code { background: #f3f4f6; border-radius: 4px; padding: 2px 6px; font-family: monospace; font-size: 0.9em; color: #be123c; }
.ProseMirror pre { background: #1f2937; color: #f9fafb; border-radius: 8px; padding: 12px 16px; overflow-x: auto; }
.ProseMirror img { max-width: 100%; height: auto; border-radius: 8px; display: block; margin: 8px auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; }
.ProseMirror img.ProseMirror-selectednode { outline: 3px solid #60a5fa; }
.ProseMirror hr { border: none; border-top: 2px solid #e5e7eb; margin: 12px 0; }
.ProseMirror a { color: #2563eb; text-decoration: underline; }
</style>
