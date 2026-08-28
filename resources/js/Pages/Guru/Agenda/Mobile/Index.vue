<template>
    <MobileLayout title="Agenda Guru">
        <div class="p-3">
            <TeacherTabMenu />
            <div class="flex flex-column gap-3 mb-4">
                <Message v-if="$page.props.flash.success" severity="success">
                    {{ $page.props.flash.success }}
                </Message>
                <div>
                    <h2 class="text-xl font-bold m-0 text-900">Agenda Mengajar</h2>
                    <small class="text-500 font-medium">Tahun Ajaran {{ academicYear.name }}</small>
                </div>
                <div class="flex align-items-center gap-2 surface-card p-2 border-round shadow-1">
                    <span class="text-sm font-bold text-600 px-2">Buka Tanggal:</span>
                    <DatePicker 
                        v-model="jumpDate" 
                        dateFormat="dd/mm/yy" 
                        showIcon 
                        iconDisplay="input"
                        placeholder="Pilih Tanggal"
                        @date-select="handleJumpDate"
                        class="w-full md:w-12rem"
                    />
                </div>

                <div class="flex gap-2">
                    <Select 
                        v-model="selectedFilter" 
                        :options="filterOptions" 
                        optionLabel="label" 
                        optionValue="value" 
                        class="flex-1"
                        @change="handleFilter"
                    />
                    <Button 
                        icon="pi pi-plus" 
                        severity="primary" 
                        @click="router.get(route('guru.agenda.create'))"
                        v-tooltip.left="'Tambah Agenda'"
                    />
                </div>
            </div>

            <div v-if="agendas.data.length === 0" class="text-center py-8">
                <i class="pi pi-calendar-times text-4xl text-300 mb-3"></i>
                <p class="text-500">Tidak ada agenda ditemukan.</p>
            </div>
            <div class="flex flex-column gap-2">
                <div 
                    v-for="agenda in agendas.data" 
                    :key="agenda.id" 
                    class="flex align-items-center gap-3 p-2 surface-card border-round shadow-1 active:surface-50 cursor-pointer border-left-3 border-primary"
                    @click="router.get(route('guru.agenda.show', agenda.id))"
                >
                    <div class="bg-blue-50 p-2 border-round flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="pi pi-book text-blue-500"></i>
                    </div>

                    <div class="flex-1 overflow-hidden">
                        <div class="flex justify-content-between align-items-center">
                            <span class="text-sm font-bold text-900">{{ agenda.classroom?.name }}</span>
                            <small class="text-500">{{ formatDate(agenda.date) }}</small>
                        </div>

                        <div class="text-xs text-700 mt-1 white-space-nowrap overflow-hidden text-overflow-ellipsis">
                            <span class="font-bold text-primary">{{ agenda.tp?.kode_tp }}</span> · {{ agenda.materi_pembelajaran }}
                        </div>

                        <div class="flex gap-3 mt-1 align-items-center">
                            <div class="flex align-items-center gap-1">
                                <i class="pi pi-users text-xs text-400"></i>
                                <span class="text-xs font-medium text-600">{{ agenda.attendances_count }} Siswa</span>
                            </div>
                            <div class="flex align-items-center gap-1">
                                <i class="pi pi-check-circle text-xs text-green-500"></i>
                                <span class="text-xs font-bold text-green-600">{{ agenda.hadir_count }}</span>
                            </div>
                            <div class="flex align-items-center gap-1">
                                <i class="pi pi-times-circle text-xs text-red-500"></i>
                                <span class="text-xs font-bold text-red-600">{{ agenda.tidak_hadir_count }}</span>
                            </div>
                        </div>
                    </div>

                    <i class="pi pi-chevron-right text-300 ml-1"></i>
                </div>
                </div>


            <div class="flex justify-content-center mt-5 mb-8" v-if="agendas.total > 20">
                <Button 
                    v-if="agendas.prev_page_url" 
                    icon="pi pi-chevron-left" 
                    text 
                    @click="router.get(agendas.prev_page_url)" 
                />
                <span class="align-self-center px-4 font-bold text-600">
                    Hal {{ agendas.current_page }} / {{ agendas.last_page }}
                </span>
                <Button 
                    v-if="agendas.next_page_url" 
                    icon="pi pi-chevron-right" 
                    text 
                    @click="router.get(agendas.next_page_url)" 
                />
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import MobileLayout from '@/Layouts/MobileLayout.vue';
import TeacherTabMenu from '@/Components/TeacherTabMenu.vue';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Message from 'primevue/message';
import DatePicker from 'primevue/datepicker';

const props = defineProps({
    agendas: Object,
    academicYear: Object,
    filters: Object
});

const selectedFilter = ref(props.filters?.filter || 'semua');

const filterOptions = [
    { label: 'Semua Agenda', value: 'semua' },
    { label: 'Hari Ini', value: 'hari_ini' },
    { label: 'Minggu Ini', value: 'minggu_ini' },
    { label: 'Bulan Ini', value: 'bulan_ini' },
];

const handleFilter = () => {
    router.get(route('guru.agenda.index'), 
        { filter: selectedFilter.value }, 
        { preserveState: true }
    );
};

const formatDateShort = (dateStr) => {
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
};
const jumpDate = ref(null);

const handleJumpDate = (date) => {
    // Format tanggal ke YYYY-MM-DD agar aman di URL
    const formattedDate = date.toLocaleDateString('en-CA');
    
    // Lompat ke Index dengan filter tanggal
    router.get(route('guru.agenda.index'), { 
        filter: 'custom', 
        date: formattedDate 
    });
};
const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('id-ID', { 
        day: 'numeric', 
        month: 'short' 
    });
}
</script>

<style scoped>
/* Membatasi materi agar tidak terlalu panjang di HP */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;  
    overflow: hidden;
}

/* Efek klik pada kartu */
.surface-card:active {
    transform: scale(0.98);
}
</style>