<template>
    <AppLayout>
        
        <!-- ===== CARD HEADER ===== -->
        <div class="surface-card p-4 mb-4 border-round-lg shadow-1">
            <div class="flex justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="text-2xl font-bold text-900 m-0">Plotting Guru</h2>
                    <span class="text-600 text-sm" v-if="activeYear">
                        Tahun Ajaran: <span class="font-bold text-primary">{{ activeYear.name }} - {{ activeYear.semester }}</span>
                    </span>
                </div>
                <IconField iconPosition="left">
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="search" placeholder="Cari Kelas..." @input="handleSearch" class="w-full md:w-20rem" />
                </IconField>
            </div>
        </div>

        <div v-if="!activeYear" class="p-3 border-round bg-red-100 text-red-700 mb-3">
            <i class="pi pi-exclamation-triangle mr-2"></i> Belum ada Tahun Ajaran Aktif!
        </div>

        <!-- ===== CARD TABLE ===== -->
        <div class="surface-card p-4 border-round-lg shadow-1">
            <DataTable :value="classrooms.data" stripedRows showGridlines tableStyle="min-width: 50rem">
                <template #empty>Tidak ada data kelas.</template>

                <Column field="name" header="Nama Kelas" sortable>
                    <template #body="{ data }">
                        <span class="font-bold text-lg">{{ data.name }}</span>
                    </template>
                </Column>
               <Column field="name" header="Walikelas" sortable>
                    <template #body="{ data }">
                        <div v-if="data.teacher">
                            <Tag :value="data.teacher.full_name" severity="success" />
                        </div>
                        <div v-else>
                            <Tag value="Belum Ditentukan" severity="warning" />
                        </div>
                    </template>
                </Column>

                <Column field="level" header="Tingkat" sortable style="width: 15%">
                    <template #body="{ data }">
                        <Tag :value="data.level" severity="info" />
                    </template>
                </Column>

                <Column field="major" header="Jurusan" style="width: 20%">
                    <template #body="{ data }">
                        {{ data.major || 'Umum' }}
                    </template>
                </Column>
                
                <Column header="Aksi" style="width: 15%">
                    <template #body="{ data }">
                        <Link :href="route('admin.schedules.manage', data.id)">
                            <Button label="Atur Guru" icon="pi pi-users" size="small" outlined />
                        </Link>
                    </template>
                </Column>
            </DataTable>

            <div class="mt-4 flex justify-content-center">
                 <template v-for="(link, k) in classrooms.links" :key="k">
                    <Link v-if="link.url" :href="link.url" class="p-button p-component p-button-sm mx-1" :class="{'p-button-outlined': !link.active}" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue'; // Import watch & onMounted
import { router, Link } from '@inertiajs/vue3'; // Import usePage
import debounce from 'lodash/debounce';

import AppLayout from '@/Layouts/AppLayout.vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const toast = useToast();
const page = usePage();
onMounted(() => {
    if (page.props.flash?.success) {
        toast.add({
            severity: 'success',
            summary: 'Berhasil',
            detail: page.props.flash.success,
            life: 3000
        })
    }

    if (page.props.flash?.error) {
        toast.add({
            severity: 'error',
            summary: 'Gagal',
            detail: page.props.flash.error,
            life: 3000
        })
    }
})
const props = defineProps({
    classrooms: Object,
    activeYear: Object,
    filters: Object
});


const search = ref(props.filters.search || '');

const handleSearch = debounce(() => {
    router.get(route('admin.schedules.index'), { search: search.value }, { preserveState: true, replace: true });
}, 300);
</script>